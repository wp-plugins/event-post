<?php
/*
  Plugin Name: Event Post
  Plugin URI: http://ecolosites.eelv.fr/articles-evenement-eventpost/
  Description: Add calendar and/or geolocation metadata on posts
  Version: 3.4.2
  Author: bastho
  Contributors: n4thaniel, ecolosites
  Author URI: http://ecolosites.eelv.fr/
  License: GPLv2
  Text Domain: eventpost
  Domain Path: /languages/
  Tags: Post,posts,event,date,geolocalization,gps,widget,map,openstreetmap, EELV
 */


$EventPost = new EventPost();

class EventPost {

    const META_START = 'event_begin';
    const META_END = 'event_end';
    const META_COLOR = 'event_color';
    // http://codex.wordpress.org/Geodata
    const META_ADD = 'geo_address';
    const META_LAT = 'geo_latitude';
    const META_LONG = 'geo_longitude';

    public $list_id;
    public $NomDuMois;
    public $Week;
    public $settings;
    public $dateformat;

    function EventPost() {
        load_plugin_textdomain('eventpost', false, 'event-post/languages');

        add_action('save_post', array(&$this, 'save_postdata'));
        add_action('admin_menu', array(&$this, 'manage_options'));

        // Scripts
        add_action('admin_enqueue_scripts', array(&$this, 'admin_head'));
        add_action('admin_print_scripts', array(&$this, 'admin_scripts'));
        add_action('wp_enqueue_scripts', array(&$this, 'load_styles'));

        // Single
        add_filter('the_content', array(&$this, 'display_single'), 9999);
        add_action('the_event', array(&$this, 'print_single'));
        add_action('wp_head', array(&$this, 'single_header'));

        // Ajax
        add_action('wp_ajax_EventPostGetLatLong', array(&$this, 'GetLatLong'));
        add_action('wp_ajax_EventPostHumanDate', array(&$this, 'HumanDate'));
        add_action('wp_ajax_EventPostCalendar', array(&$this, 'ajaxcal'));
        add_action('wp_ajax_nopriv_EventPostCalendar', array(&$this, 'ajaxcal'));
        add_action('wp_ajax_EventPostCalendarDate', array(&$this, 'ajaxdate'));
        add_action('wp_ajax_nopriv_EventPostCalendarDate', array(&$this, 'ajaxdate'));

	// Calendar publishing
        add_action('wp_ajax_EventPostFeed', array(&$this, 'feed'));
        add_action('wp_ajax_nopriv_EventPostFeed', array(&$this, 'feed'));

        // Edit
        add_action('add_meta_boxes', array(&$this, 'add_custom_box'));
        add_filter('manage_post_posts_columns', array(&$this, 'columns_head'), 2);
        add_action('manage_post_posts_custom_column', array(&$this, 'columns_content'), 10, 2);

        //Shortcodes
        add_shortcode('events_list', array(&$this, 'shortcode_list'));
        add_shortcode('events_map', array(&$this, 'shortcode_map'));
        add_shortcode('events_cal', array(&$this, 'shortcode_cal'));
	//
	add_filter('eventpost_list_shema',array(&$this, 'custom_shema'),10,1);

	// Admin
	add_action('admin_post_EventPostSaveSettings', array(&$this, 'save_settings'));

        include_once (plugin_dir_path(__FILE__) . 'widget.php');
        include_once (plugin_dir_path(__FILE__) . 'widget.cal.php');
        include_once (plugin_dir_path(__FILE__) . 'widget.map.php');
        include_once (plugin_dir_path(__FILE__) . 'multisite.php');


        $this->META_START = 'event_begin';
        $this->META_END = 'event_end';
        $this->META_COLOR = 'event_color';
        // http://codex.wordpress.org/Geodata
        $this->META_ADD = 'geo_address';
        $this->META_LAT = 'geo_latitude';
        $this->META_LONG = 'geo_longitude';
        $this->list_id = 0;
        $this->NomDuMois = array('', __('Jan', 'eventpost'), __('Feb', 'eventpost'), __('Mar', 'eventpost'), __('Apr', 'eventpost'), __('May', 'eventpost'), __('Jun', 'eventpost'), __('Jul', 'eventpost'), __('Aug', 'eventpost'), __('Sept', 'eventpost'), __('Oct', 'eventpost'), __('Nov', 'eventpost'), __('Dec', 'eventpost'));
        $this->Week = array(__('Sunday', 'eventpost'), __('Monday', 'eventpost'), __('Tuesday', 'eventpost'), __('Wednesday', 'eventpost'), __('Thursday', 'eventpost'), __('Friday', 'eventpost'), __('Saturday', 'eventpost'));

        $this->maps = $this->get_maps();
	$this->settings = $this->get_settings();
        if (!empty($this->settings['markpath']) && !empty($this->settings['markurl'])) {
            $this->markpath = ABSPATH.'/'.$this->settings['markpath'];
            $this->markurl = $this->settings['markurl'];
        } else {
            $this->markpath = plugin_dir_path(__FILE__) . 'markers/';
            $this->markurl = plugins_url('/markers/', __FILE__);
        }


        $this->dateformat = str_replace(array('yy', 'mm', 'dd'), array('Y', 'm', 'd'), __('yy-mm-dd', 'eventpost'));

        $this->default_list_shema = array(
            'container' => '
		      <%type% class="event_loop %id% %class%" id="%listid%" style="%style%" %attributes%>
		      	%list%
		      </%type%>',
            'item' => '
		      <%child% class="event_item %class%" data-color="%color%">
			      	<a href="%event_link%">
			      		%event_thumbnail%
			      		<h5>%event_title%</h5>
			      	</a>
		      		%event_date%
		      		%event_cat%
		      		%event_location%
		      		%event_excerpt%
		      </%child%>'
        );
	$this->list_shema = apply_filters('eventpost_list_shema',$this->default_list_shema);

    }

    //Usefull hexadecimal to decimal converter
    function hex2dec($color = '000000') {
        $tbl_color = array();
        if (!ereg('#', $color))
            $color = '#' . $color;
        $tbl_color['R'] = hexdec(substr($color, 1, 2));
        $tbl_color['G'] = hexdec(substr($color, 3, 2));
        $tbl_color['B'] = hexdec(substr($color, 5, 2));
        return $tbl_color;
    }

    function no_use() {
        __('Add calendar and/or geolocation metadata on posts', 'eventpost');
        __('Event Post', 'eventpost');
    }

    function get_settings() {
        $ep_settings = get_option('ep_settings');
        $reg_settings=false;
	if(!is_array($ep_settings)){
	    $ep_settings = array();
	}
        if (!isset($ep_settings['dateformat']) || empty($ep_settings['dateformat'])) {
            $ep_settings['dateformat'] = get_option('date_format');
            $reg_settings=true;
        }
        if (!isset($ep_settings['tile']) || empty($ep_settings['tile']) || !isset($this->maps[$ep_settings['tile']])) {
            $maps = array_keys($this->maps);
            $ep_settings['tile'] = $this->maps[$maps[0]]['id'];
            $reg_settings=true;
        }
        if (!isset($ep_settings['cache']) || !is_numeric($ep_settings['cache'])) {
            $ep_settings['cache'] = 0;
            $reg_settings=true;
        }
        if (!isset($ep_settings['export']) || empty($ep_settings['export'])) {
            $ep_settings['export'] = 'both';
            $reg_settings=true;
        }
        if (!isset($ep_settings['emptylink'])) {
            $ep_settings['emptylink'] = 1;
            $reg_settings=true;
        }
        if (!isset($ep_settings['markpath'])) {
            $ep_settings['markpath'] = '';
            $reg_settings=true;
        }
        if (!isset($ep_settings['singlepos']) || empty($ep_settings['singlepos'])) {
            $ep_settings['singlepos'] = 'after';
            $reg_settings=true;
        }
	if (!isset($ep_settings['container_shema']) ) {
            $ep_settings['container_shema'] = '';
            $reg_settings=true;
        }

	if (!isset($ep_settings['item_shema']) ) {
            $ep_settings['item_shema'] = '';
            $reg_settings=true;
        }

        //Save settings  not changed
        if($reg_settings===true){
           update_option('ep_settings', $ep_settings);
        }
        return $ep_settings;
    }
    function custom_shema($shema){
	if(!empty($this->settings['container_shema'])){
	    $shema['container']=$this->settings['container_shema'];
	}
	if(!empty($this->settings['item_shema'])){
	    $shema['item']=$this->settings['item_shema'];
	}
	return $shema;
    }
    function get_maps() {
        $maps = array();
        if (is_file(plugin_dir_path(__FILE__) . 'maps.csv')) {
            $map_f = fopen(plugin_dir_path(__FILE__) . 'maps.csv', 'r');
            $map_s = explode("\n", fread($map_f, filesize(plugin_dir_path(__FILE__) . 'maps.csv')));
            foreach ($map_s as $map) {
                $map = explode(';', $map);
                if (sizeof($map >= 5)) {
                    $maps[$map[1]] = array(
                        'name' => $map[0],
                        'id' => $map[1],
                        'urls' => array($map[2], $map[3], $map[4]),
                    );
                }
            }
        }
        return $maps;
    }

    function get_colors() {
        $colors = array();
        if (is_dir($this->markpath)) {
            $files = scandir($this->markpath);
            foreach ($files as $file) {
                if (substr($file, -4) == '.png') {
                    $colors[substr($file, 0, -4)] = $this->markurl . $file;
                }
            }
        }
        return $colors;
    }

    function get_marker($color) {
        if (is_file($this->markpath . $color . '.png')) {
            return $this->markurl . $color . '.png';
        }
        return plugins_url('/markers/ffffff.png', __FILE__);
    }

    function load_styles() {
        //CSS
        wp_register_style(
                'eventpost', plugins_url('/css/eventpost.min.css', __FILE__), false, 1.0
        );
        wp_enqueue_style('eventpost', plugins_url('/css/eventpost.min.css', __FILE__), false, null);
        wp_enqueue_style('openlayers', plugins_url('/css/openlayers.css', __FILE__));
        wp_enqueue_style('dashicons-css', includes_url('/css/dashicons.min.css'));
    }
    function load_scripts() {
        // JS
        wp_enqueue_script('jquery', false, false, false, true);
        wp_enqueue_script('eventpost', plugins_url('/js/eventpost.min.js', __FILE__), false, false, true);
        wp_localize_script('eventpost', 'eventpost_params', array(
            'imgpath' => plugins_url('/img/', __FILE__),
            'maptiles' => $this->maps,
            'defaulttile' => $this->settings['tile'],
            'ajaxurl' => get_bloginfo('url') . '/wp-admin/admin-ajax.php'
        ));
    }
    function load_map_scripts() {
        // JS
	$this->load_scripts();
        wp_enqueue_script('openlayers', plugins_url('/js/OpenLayers.js', __FILE__), false, false, true);
    }

    function admin_head() {
        wp_enqueue_style('jquery-ui', plugins_url('/css/jquery-ui.css', __FILE__), false, null);
        wp_enqueue_style('eventpostadmin', plugins_url('/css/eventpostadmin.css', __FILE__), false, null);
    }

    function admin_scripts() {
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_script('eventpost-admin', plugins_url('/js/osm-admin.min.js', __FILE__), false, false, true);

        wp_localize_script('eventpost-admin', 'eventpost', array(
            'imgpath' => plugins_url('/img/', __FILE__),
            'date_choose' => __('Choose', 'eventpost'),
            'date_format' => __('yy-mm-dd', 'eventpost'),
            'META_START' => $this->META_START,
            'META_END' => $this->META_END,
            'META_ADD' => $this->META_ADD,
            'META_LAT' => $this->META_LAT,
            'META_LONG' => $this->META_LONG,
        ));

        $lang = substr(get_bloginfo('language'), 0, 2);
        if (is_file(plugin_dir_path(__FILE__) . 'js/jquery.ui.datepicker-' . $lang . '.js')) {
            wp_enqueue_script('jquery-ui-datepicker-' . $lang, plugins_url('/js/jquery.ui.datepicker-' . $lang . '.js', __FILE__), false, false, true);
        }
    }

    function single_header() {
        if (is_single()) {
            $post_id = get_the_ID();
            $address = get_post_meta($post_id, $this->META_ADD, true);
            $lat = get_post_meta($post_id, $this->META_LAT, true);
            $long = get_post_meta($post_id, $this->META_LONG, true);
            if ($address != '' || ($lat != '' && $long != '')) {
                ?>
                <meta name="geo.placename" content="<?php echo $address ?>" />
                <meta name="geo.position" content="<?php echo $lat ?>;<?php echo $long ?>" />
                <meta name="ICBM" content="<?php echo $lat ?>;<?php echo $long ?>" />
                <?php
            }
            $start = get_post_meta($post_id, $this->META_START, true);
            $end = get_post_meta($post_id, $this->META_END, true);
            if ($lat != '' && $end != '') {
                ?>
                <meta name="datetime-coverage-start" content="<?php echo date('c', strtotime($start)) ?>" />
                <meta name="datetime-coverage-end" content="<?php echo date('c', strtotime($end)) ?>" />
                <?php
            }
        }
    }

    function dateisvalid($str) {
        return is_string($str) && trim(str_replace(array(':', '0'), '', $str)) != '';
    }

    function parsedate($date, $sep = '') {
        if (!empty($date)) {
            return substr($date, 0, 10) . $sep . substr($date, 11, 8);
        } else {
            return '';
        }
    }

    function human_date($date, $format = 'l j F Y') {
        if (is_numeric($date) && date('d/m/Y', $date) == date('d/m/Y')) {
            return __('today', 'eventpost');
        } elseif (is_numeric($date) && date('d/m/Y', $date) == date('d/m/Y', strtotime('+1 day'))) {
            return __('tomorrow', 'eventpost');
        } elseif (is_numeric($date) && date('d/m/Y', $date) == date('d/m/Y', strtotime('-1 day'))) {
            return __('yesterday', 'eventpost');
        }
        return date_i18n($format, $date);
    }

    function print_date($post = null, $links = 'deprecated') {
        $dates = '';
        if ($post == null)
            $post = get_post();
        elseif (is_numeric($post)) {
            $post = get_post($post);
        }
        if (!isset($post->start)) {
            $post = $this->retreive($post);
        }
        $start_date = $post->start;
        $end_date = $post->end;
        if ($start_date != '' && $end_date != '' && $this->dateisvalid($start_date) && $this->dateisvalid($end_date)) {

            $gmt_offset = get_option('gmt_offset ');
            $timezone_string = get_option('timezone_string');
            $codegmt = 0;
            if ($gmt_offset != 0 && substr($gmt_offset, 0, 1) != '-' && substr($gmt_offset, 0, 1) != '+') {
                $codegmt = $gmt_offset * -1;
                $gmt_offset = '+' . $gmt_offset;
            }
            $ep_settings = $this->settings;
            //Display dates

            $dd = strtotime($start_date);
            $df = strtotime($end_date);
            $dates.='<div class="event_date" data-start="' . $this->human_date($dd) . '" data-end="' . $this->human_date($df) . '">';

            if (date('d/m/Y', $dd) == date('d/m/Y', $df)) {
                $dates.= '<span class="date">' . $this->human_date($df) . "</span>";
                if (date('H:i', $dd) != date('H:i', $df) && date('H:i', $dd) != '00:00' && date('H:i', $df) != '00:00') {
                    $dates.='<span class="linking_word">, ' . __('from:', 'eventpost') . '</span>
					<time class="time" itemprop="dtstart" datetime="' . date('c', $dd) . '">' . date('H:i', $dd) . '</time>
					<span class="linking_word">' . __('to:', 'eventpost') . '</span>
					<time class="time" itemprop="dtend" datetime="' . date('c', $df) . '">' . date('H:i', $df) . '</time>';
                } elseif (date('H:i', $dd) != '00:00') {
                    $dates.='<span class="linking_word">,' . __('at:', 'eventpost') . '</span>
					<time class="time" itemprop="dtstart" datetime="' . date('c', $dd) . '">' . date('H:i', $dd) . '</time>';
                }
            } else {
                $dates.= '
				<span class="linking_word">' . __('from:', 'eventpost') . '</span>
				<time class="date" itemprop="dtstart" datetime="' . date('c', $dd) . '">' . $this->human_date($dd, $ep_settings['dateformat']) . '</time>
				<span class="linking_word">' . __('to:', 'eventpost') . '</span>
				<time class="date" itemprop="dtend" datetime="' . date('c', $df) . '">' . $this->human_date($df, $ep_settings['dateformat']) . '</time>';
            }

            if ($df > time() && (
                    $this->settings['export'] == 'both' ||
                    ($this->settings['export'] == 'single' && is_single() ) ||
                    ($this->settings['export'] == 'list' && !is_single() )
                    )) {
                // Export event
                $title = urlencode($post->post_title);
                $address = urlencode($post->address);
                $url = urlencode($post->guid);

                $mt = strtotime($codegmt . ' Hours', $dd);
                $d_s = date("Ymd", $mt) . 'T' . date("His", $mt);
                $mte = strtotime($codegmt . ' Hours', $df);
                $d_e = date("Ymd", $mte) . 'T' . date("His", $mte);
                $uid = $post->ID . '-' . $post->blog_id;

                // format de date ICS
                $ics_url = plugins_url('export/ics.php', __FILE__) . '?t=' . $title . '&amp;u=' . $uid . '&amp;sd=' . $d_s . '&amp;ed=' . $d_e . '&amp;a=' . $address . '&amp;d=' . $url . '&amp;tz=%3BTZID%3D' . urlencode($timezone_string);

                // format de date Google cal
                $google_url = 'https://www.google.com/calendar/event?action=TEMPLATE&amp;text=' . $title . '&amp;dates=' . $d_s . 'Z/' . $d_e . 'Z&amp;details=' . $url . '&amp;location=' . $address . '&amp;trp=false&amp;sprop=&amp;sprop=name';

                // format de date VCS
                $vcs_url = plugins_url('export/vcs.php', __FILE__) . '?t=' . $title . '&amp;u=' . $uid . '&amp;sd=' . $d_s . '&amp;ed=' . $d_e . '&amp;a=' . $address . '&amp;d=' . $url . '&amp;tz=%3BTZID%3D' . urlencode($timezone_string);

                $dates.='
				  <a href="' . $ics_url . '" class="event_link ics" target="_blank" title="' . __('Download ICS file', 'eventpost') . '">ical</a>
				  <a href="' . $google_url . '" class="event_link gcal" target="_blank" title="' . __('Add to Google calendar', 'eventpost') . '">Google</a>
				  <a href="' . $vcs_url . '" class="event_link vcs" target="_blank" title="' . __('Add to Outlook', 'eventpost') . '">outlook</a>

				  ';
            }
            $dates.='</div>';
        }
        return apply_filters('eventpost_printdate', $dates);
    }

    function print_location($post = null) {
        $location = '';
        if ($post == null)
            $post = get_post();
        elseif (is_numeric($post)) {
            $post = get_post($post);
        }
        if (!isset($post->start)) {
            $post = $this->retreive($post);
        }
        $address = $post->address;
        $lat = $post->lat;
        $long = $post->long;
        $color = $post->color;

        if ($address != '' || ($lat != '' && $long != '')) {
            $location.='<address';
            if ($lat != '' && $long != '') {
                $location.=' data-id="' . $post->ID . '" data-latitude="' . $lat . '" data-longitude="' . $long . '" data-marker="' . $this->get_marker($color) . '" ';
            }
            $location.=' itemprop="adr"><span>' . $address . '</span>';
            if (is_single() && $lat != '' && $long != '') {
                $location.='<a class="event_link gps" href="https://www.openstreetmap.org/?lat=' . $lat.='&amp;lon=' . $long.='&amp;zoom=13" target="_blank"  itemprop="geo">' . __('Map', 'eventpost') . '</a>';
            }
            $location.='</address>';
        }

        return apply_filters('eventpost_printlocation', $location);
    }

    function print_categories($post = null) {
        if ($post == null)
            $post = get_post();
        elseif (is_numeric($post)) {
            $post = get_post($post);
        }
        if (!isset($post->start)) {
            $post = $this->retreive($post);
        }
        $cats = '';
        $categories = $post->categories;
        if ($categories) {
            $cats.='<span class="event_category"';
            $color = $post->color;
            if ($color != '') {
                $cats.=' style="color:#' . $color . '"';
            }
            $cats.='>';
            foreach ($categories as $category) {
                $cats .= $category->name . ' ';
            }
            $cats.='</span>';
        }
        return $cats;
    }

    // Generate, return or output date event datas
    function get_single($post = null, $class = '') {
        if ($post == null) {
            $post = $this->retreive();
        }
        $datas_date = $this->print_date($post);
        $datas_cat = $this->print_categories($post);
        $datas_loc = $this->print_location($post);
        if ($datas_date != '' || $datas_loc != '') {
            $rgb = $this->hex2dec($post->color);
            return '<div class="event_data ' . $class . '" style="border-left-color:#' . $post->color . ';background:rgba(' . $rgb['R'] . ',' . $rgb['G'] . ',' . $rgb['B'] . ',0.1)" itemscope itemtype="http://microformats.org/profile/hcard">' . $datas_date . $datas_cat . $datas_loc . '</div>';
        }
        return '';
    }

    function get_singledate($post = null, $class = '') {
        return '<div class="event_data event_date ' . $class . '" itemscope itemtype="http://microformats.org/profile/hcard">' . $this->print_date($post) . '</div>';
    }

    function get_singlecat($post = null, $class = '') {
        return '<div class="event_data event_category ' . $class . '" itemscope itemtype="http://microformats.org/profile/hcard">' . $this->print_categories($post) . '</div>';
    }

    function get_singleloc($post = null, $class = '') {
        return '<div class="event_data event_location ' . $class . '" itemscope itemtype="http://microformats.org/profile/hcard">' . $this->print_location($post) . '</div>';
    }

    function display_single($content) {
        if (is_page() || !is_single() || is_home())
            return $content;
        $post = get_queried_object();
        //Prevent from filters applying "the_content" on another thing than the current post content
        remove_filter('the_content', array(&$this, 'display_single'), 9999);
        $current_content = apply_filters('the_content', $post->post_content);
        if ($current_content == $content) {
            $post = $this->retreive();
            if($this->settings['singlepos']=='before'){
                $content=$this->get_single($post, 'event_single').$content;
            }
            else{
                $content.=$this->get_single($post, 'event_single');
            }
	    $this->load_map_scripts();
        }
        add_filter('the_content', array(&$this, 'display_single'), 9999);
        return $content;
    }

    function print_single($post = null) {
        echo $this->get_single($post);
    }

    // Shortcode to display a list of events
    // uses filter : eventpost_params
    function shortcode_list($atts) {
        $atts = shortcode_atts(apply_filters('eventpost_params', array(
            'nb' => 0,
            'type' => 'div',
            'future' => true,
            'past' => false,
            'geo' => 0,
            'width' => '',
            'height' => 'auto',
            'title' => '',
            'before_title' => '<h3>',
            'after_title' => '</h3>',
            'cat' => '',
            'tag' => '',
            'thumbnail' => '',
            'thumbnail_size' => '',
            'excerpt' => '',
            'style' => '',
            'orderby' => 'meta_value',
            'order' => 'ASC',
            'container_schema' => $this->list_shema['container'],
            'item_schema' => $this->list_shema['item'],
                        ), 'shortcode_list'), $atts);

        if ($atts['container_schema'] != $this->list_shema['container'])
            $atts['container_schema'] = html_entity_decode($atts['container_schema']);
        if ($atts['item_schema'] != $this->list_shema['item'])
            $atts['item_schema'] = html_entity_decode($atts['item_schema']);
        return $this->list_events($atts);
    }

    // Shortcode to display a map of events
    // uses filter : eventpost_params
    function shortcode_map($atts) {
        $ep_settings = $this->settings;
        $atts = shortcode_atts(apply_filters('eventpost_params', array(
            'nb' => 0,
            'future' => true,
            'past' => false,
            'width' => '',
            'height' => '',
            'tile' => $ep_settings['tile'],
            'title' => '',
            'before_title' => '<h3>',
            'after_title' => '</h3>',
            'cat' => '',
            'tag' => '',
            'style' => '',
            'thumbnail' => '',
            'excerpt' => '',
            'orderby' => 'meta_value',
            'order' => 'ASC'
                        ), 'shortcode_map'), $atts);
        $atts['geo'] = 1;
        $atts['type'] = 'div';
        return $this->list_events($atts, 'event_geolist'); //$nb,'div',$future,$past,1,'event_geolist');
    }

    // Shortcode to display a calendar of events
    // uses filter : eventpost_params
    function shortcode_cal($atts) {
	$this->load_scripts();
        $ep_settings = $this->settings;
        $atts = shortcode_atts(apply_filters('eventpost_params', array(
            'date' => date('Y-n'),
            'cat' => '',
            'mondayfirst' => 0, //1 : weeks starts on monday
            'datepicker' => 1
                        ), 'shortcode_cal'), $atts);
        extract($atts);
        return '<div class="eventpost_calendar" data-cat="' . $cat . '" data-date="' . $date . '" data-mf="' . $mondayfirst . '" data-dp="' . $datepicker . '">' . $this->calendar($atts) . '</div>';
    }

    // Return an HTML list of events
    // uses filter : eventpost_params
    function list_events($atts, $id = 'event_list') {
	$ep_settings = $this->settings;
        $atts = shortcode_atts(apply_filters('eventpost_params', array(
            'nb' => 0,
            'type' => 'div',
            'future' => true,
            'past' => false,
            'geo' => 0,
            'width' => '',
            'height' => '',
            'tile' => $ep_settings['tile'],
            'title' => '',
            'before_title' => '<h3>',
            'after_title' => '</h3>',
            'cat' => '',
            'tag' => '',
            'events' => '',
            'style' => '',
            'thumbnail' => '',
            'thumbnail_size' => '',
            'excerpt' => '',
            'orderby' => 'meta_value',
            'order' => 'ASC',
            'class' => '',
            'container_schema' => $this->list_shema['container'],
            'item_schema' => $this->list_shema['item'],
                        ), 'list_events'), $atts);

        extract($atts);
        if (!is_array($events)) {
            $events = $this->get_events($atts);
        }
        $ret = '';
        $this->list_id++;
        if (sizeof($events) > 0) {
	    if($id=='event_geolist'){
		$this->load_map_scripts();
	    }
            if (!empty($title)) {
                $ret.= html_entity_decode($before_title) . $title . html_entity_decode($after_title);
            }

            $child = ($type == 'ol' || $type == 'ul') ? 'li' : 'div';

            $list = '';

            foreach ($events as $post) { //$post=get_post($item_id);
                $item_id = $post->ID;
                $class_item = ($post->time_end >= time()) ? 'event_future' : 'event_past';
                if ($ep_settings['emptylink'] == 0 && empty($post->post_content)) {
                    $post->permalink = '#' . $id . $this->list_id;
                }
                $list.=str_replace(
                        array(
                    '%child%',
                    '%class%',
                    '%color%',
                    '%event_link%',
                    '%event_thumbnail%',
                    '%event_title%',
                    '%event_date%',
                    '%event_cat%',
                    '%event_location%',
                    '%event_excerpt%'
                        ), array(
                    $child,
                    $class_item,
                    $post->color,
                    $post->permalink,
                    $thumbnail == true ? '<span class="event_thumbnail_wrap">' . get_the_post_thumbnail($post->ID, !empty($thumbnail_size) ? $thumbnail_size : 'thumbnail', array('class' => 'attachment-thumbnail wp-post-image event_thumbnail')) . '</span>' : '',
                    $post->post_title,
                    $this->get_singledate($post),
                    $this->get_singlecat($post),
                    $this->get_singleloc($post),
                    $excerpt == true && $post->post_excerpt!='' ? '<span class="event_exerpt">'.$post->post_excerpt.'</span>' : '',
                        ), $item_schema
                );
            }
            $ret.=str_replace(
                    array(
                '%type%',
                '%id%',
                '%class%',
                '%listid%',
                '%style%',
                '%attributes%',
                '%list%'
                    ), array(
                $type,
                $id,
                $class,
                $id . $this->list_id,
                (!empty($width) ? 'width:' . $width . ';' : '') . (!empty($height) ? 'height:' . $height . ';' : '') . $style,
                $id == 'event_geolist' ? 'data-tile="' . $tile . '" data-width="' . $width . '" data-height="' . $height . '"' : '',
                $list
                    ), $container_schema
            );
        }
        return $ret;
    }

    /* get_events
     * @param: $attr (array)
     * @filter:  eventpost_params
     * @return: array of post_ids wich are events
     */

    function get_events($atts) {
        $requete = (shortcode_atts(apply_filters('eventpost_params', array(
                    'nb' => 5,
                    'future' => true,
                    'past' => false,
                    'geo' => 0,
                    'cat' => '',
                    'tag' => '',
                    'date' => '',
                    'orderby' => 'meta_value',
                    'order' => 'ASC'
                                ), 'get_events'), $atts));
        extract($requete);


        wp_reset_query();

        $arg = array(
            'post_type' => 'post',
            'posts_per_page' => $nb,
            'meta_key' => $this->META_START,
            'orderby' => $orderby,
            'order' => $order
        );

        // CAT
        if ($cat != '') {
            if (preg_match('/[a-zA-Z]/i', $cat)) {
                $arg['category_name'] = $cat;
            } else {
                $arg['cat'] = $cat;
            }
        }
        // TAG
        if ($tag != '') {
            $arg['tag'] = $tag;
        }
        // DATES
        $meta_query = array(
            array(
                'key' => $this->META_END,
                'value' => '',
                'compare' => '!='
            ),
            array(
                'key' => $this->META_END,
                'value' => '0:0:00 0:',
                'compare' => '!='
            ),
            array(
                'key' => $this->META_START,
                'value' => '',
                'compare' => '!='
            ),
            array(
                'key' => $this->META_START,
                'value' => '0:0:00 0:',
                'compare' => '!='
            )
        );
        if ($future == 0 && $past == 0) {
            $meta_query = array();
            $arg['meta_key'] = null;
            $arg['orderby'] = null;
            $arg['order'] = null;
        } elseif ($future == 1 && $past == 0) {
            $meta_query[] = array(
                'key' => $this->META_END,
                'value' => date('Y-m-d H:i:s'),
                'compare' => '>=',
                    //'type'=>'DATETIME'
            );
        } elseif ($future == 0 && $past == 1) {
            $meta_query[] = array(
                'key' => $this->META_END,
                'value' => date('Y-m-d H:i:s'),
                'compare' => '<=',
                    //'type'=>'DATETIME'
            );
        }
        if ($date != '') {
            $date = date('Y-m-d', $date);

            $meta_query = array(
                array(
                    'key' => $this->META_END,
                    'value' => $date . ' 00:00:00',
                    'compare' => '>=',
                    'type' => 'DATETIME'
                ),
                array(
                    'key' => $this->META_START,
                    'value' => $date . ' 23:59:59',
                    'compare' => '<=',
                    'type' => 'DATETIME'
                )
            );
        }
        // GEO
        if ($geo == 1) {
            $meta_query[] = array(
                'key' => $this->META_LAT,
                'value' => '',
                'compare' => '!='
            );
            $meta_query[] = array(
                'key' => $this->META_LONG,
                'value' => '',
                'compare' => '!='
            );
            $arg['meta_key'] = $this->META_LAT;
            $arg['orderby'] = 'meta_value';
            $arg['order'] = 'DESC';
        }

        $arg['meta_query'] = $meta_query;

        $query_md5 = 'eventpost_' . md5(var_export($requete, true));
        // Check if cache is activated
        if ($this->settings['cache'] == 1 && false !== ( $cached_events = get_transient($query_md5) )) {
            return is_array($cached_events) ? $cached_events : array();
        }


        $events = apply_filters('eventpost_get', '', $requete, $arg);
        if ('' === $events) {

            global $wpdb;
            $query = new WP_Query($arg);
            $events = $wpdb->get_col($query->request);
            foreach ($events as $k => $event) {
                $events[$k] = $this->retreive($event);
            }
        }
        if ($this->settings['cache'] == 1)
            set_transient($query_md5, $events, 5 * MINUTE_IN_SECONDS);

        return $events;
    }

    function retreive($event = null) {
        $ob = get_post($event);
        $ob->start = get_post_meta($ob->ID, $this->META_START, true);
        $ob->end = get_post_meta($ob->ID, $this->META_END, true);

        if (!$this->dateisvalid($ob->start))
            $ob->start = '';
        if (!$this->dateisvalid($ob->end))
            $ob->end = '';

        $ob->time_start = !empty($ob->start) ? strtotime($ob->start) : '';
        $ob->time_end = !empty($ob->end) ? strtotime($ob->end) : '';

        $ob->address = get_post_meta($ob->ID, $this->META_ADD, true);
        $ob->lat = get_post_meta($ob->ID, $this->META_LAT, true);
        $ob->long = get_post_meta($ob->ID, $this->META_LONG, true);
        $ob->color = get_post_meta($ob->ID, $this->META_COLOR, true);
        $ob->categories = get_the_category($ob->ID);
        $ob->permalink = get_permalink($ob->ID);
        $ob->blog_id = get_current_blog_id();
        if ($ob->color == '')
            $ob->color = '000000';
        return apply_filters('eventpost_retreive', $ob);
    }

    /** ADMIN ISSUES * */
    function add_custom_box() {
        add_meta_box('event_post', __('Event informations', 'eventpost'), array(&$this, 'inner_custom_box'), 'post', 'side', 'core');
        add_meta_box('event_post_sc_edit', __('Events Shortcode editor', 'eventpost'), array(&$this, 'inner_custom_box_edit'), 'page');
    }

    function inner_custom_box() {
        $post_id = get_the_ID();
        $event = $this->retreive($post_id);
        $start_date = $event->start;
        $end_date = $event->end;



        $start_date_date = !empty($start_date) ? substr($start_date, 0, 10) : '';
        $start_date_hour = !empty($start_date) ? abs(substr($start_date, 11, 2)) : '';
        $start_date_minutes = !empty($start_date) ? abs(substr($start_date, 14, 2)) : '';

        $end_date_date = !empty($end_date) ? substr($end_date, 0, 10) : '';
        $end_date_hour = !empty($end_date) ? abs(substr($end_date, 11, 2)) : '';
        $end_date_minutes = !empty($end_date) ? abs(substr($end_date, 14, 2)) : '';
        //$end_date_to_print=$this->parsedate($end_date,'T');



        $eventcolor = $event->color;

        $language = get_bloginfo('language');
        if (strpos($language, '-') > -1) {
            $language = strtolower(substr($language, 0, 2));
        }
        ?>
        <b><?php _e('Date:', 'eventpost') ?></b>
        <div class="misc-pub-section">
            <label for="<?php echo $this->META_START; ?>_date">
                <p><?php _e('Begin:', 'eventpost') ?>
                    <span id="<?php echo $this->META_START; ?>_date_human" class="human_date">
                        <?php
                        if ($event->time_start != '') {
                            echo $this->human_date($event->time_start) . date(' H:i', $event->time_start);
                        }
                        ?>
                    </span>
                </p>
                <input type="text" class="input-date" data-language="<?php echo $language; ?>" value ="<?php echo $start_date_date ?>" name="<?php echo $this->META_START; ?>[date]" id="<?php echo $this->META_START; ?>_date"/>
            </label>

            <p><label for="<?php echo $this->META_START; ?>_hour">
                    <select name="<?php echo $this->META_START; ?>[hour]" id="<?php echo $this->META_START; ?>_hour" class="select-date">
                        <?php for ($h = 0; $h < 24; $h++): ?>
                            <option value="<?= $h ?>" <?= ($start_date_hour == $h ? 'selected' : '') ?>><?= $h ?></option>
                        <?php endfor; ?>
                    </select>:
                </label>
                <label for="<?php echo $this->META_START; ?>_minute">
                    <select name="<?php echo $this->META_START; ?>[minute]" id="<?php echo $this->META_START; ?>_minute" class="select-date">
                        <?php for ($m = 0; $m < 60; $m+=15): ?>
                            <option value="<?= $m ?>" <?= ($start_date_minutes == $m ? 'selected' : '') ?>><?= $m ?></option>
                        <?php endfor; ?>
                    </select>
                </label>
            </p>
        </div>
        <div class="misc-pub-section">
            <label for="<?php echo $this->META_END; ?>_date">
                <p><?php _e('End:', 'eventpost') ?>
                    <span id="<?php echo $this->META_END; ?>_date_human" class="human_date">
                        <?php
                        if ($event->time_start != '') {
                            echo $this->human_date($event->time_end) . date(' H:i', $event->time_end);
                        }
                        ?>
                    </span>
                </p>
                <input type="text" class="input-date" data-language="<?php echo $language; ?>"  value ="<?php echo $end_date_date ?>" name="<?php echo $this->META_END; ?>[date]" id="<?php echo $this->META_END; ?>_date"/>
            </label>
            <p><label for="<?php echo $this->META_END; ?>_hour">
                    <select name="<?php echo $this->META_END; ?>[hour]" id="<?php echo $this->META_END; ?>_hour" class="select-date">
                        <?php for ($h = 0; $h < 24; $h++): ?>
                            <option value="<?= $h ?>" <?= ($end_date_hour == $h ? 'selected' : '') ?>><?= $h ?></option>
                        <?php endfor; ?>
                    </select>:
                </label>
                <label for="<?php echo $this->META_END; ?>_minute">
                    <select name="<?php echo $this->META_END; ?>[minute]" id="<?php echo $this->META_END; ?>_minute" class="select-date">
                        <?php for ($m = 0; $m < 60; $m+=15): ?>
                            <option value="<?= $m ?>" <?= ($end_date_minutes == $m ? 'selected' : '') ?>><?= $m ?></option>
                        <?php endfor; ?>
                    </select>
                </label>
            </p>
        </div>
        <?php $colors = $this->get_colors();
        if (sizeof($colors) > 0):
            ?>
            <div class="misc-pub-section">
                    <?php _e('Color:', 'eventpost'); ?>
                <p>
            <?php foreach ($colors as $color => $file): ?>
                        <label style="background:#<?php echo $color ?>" for="<?php echo $this->META_COLOR; ?><?php echo $color ?>">
                            <img src="<?=$this->markurl.$color.'.png' ?>"><input type="radio" value ="<?php echo $color ?>" name="<?php echo $this->META_COLOR; ?>" id="<?php echo $this->META_COLOR; ?><?php echo $color ?>" <?php
                                   if ($eventcolor == $color) {
                                       echo 'checked';
                                   }
                                   ?>/>
                        </label>
            <?php endforeach; ?>
                </p>
            </div>
        <?php endif; ?>


        <b><?php _e('Location:', 'eventpost') ?></b>
        <div class="misc-pub-section">
            <p><a href="#event_address_search" id="event_address_search">
                    <span class="dashicons dashicons-search"></span>
        <?php _e('Look for event\'s GPS coordinates:', 'eventpost') ?>
                </a>
                <span id="eventaddress_result"></span>
            </p>
            <label for="<?php echo $this->META_ADD; ?>">
        <?php _e('Event address, as it will be displayed:', 'eventpost') ?>
                <textarea name="<?php echo $this->META_ADD; ?>" id="<?php echo $this->META_ADD; ?>"><?php echo $event->address ?></textarea>
            </label>

        </div>
        <div class="misc-pub-section">
            <label for="<?php echo $this->META_LAT; ?>">
        <?php _e('Latitude:', 'eventpost') ?>
                <input type="text" value ="<?php echo $event->lat ?>" name="<?php echo $this->META_LAT; ?>" id="<?php echo $this->META_LAT; ?>"/>
            </label>
        </div>
        <div class="misc-pub-section">
            <label for="<?php echo $this->META_LONG; ?>">
        <?php _e('Longitude:', 'eventpost') ?>
                <input type="text" value ="<?php echo $event->long ?>" name="<?php echo $this->META_LONG; ?>" id="<?php echo $this->META_LONG; ?>"/>
            </label>
        </div>
        <?php
        wp_nonce_field(plugin_basename(__FILE__), 'agenda_noncename');
    }

    function inner_custom_box_edit() {
        $ep_settings = $this->settings;
        ?>
        <?php do_action('before_eventpost_generator'); ?>
        <div class="all">
            <p>
                <label for="ep_sce_type"><?php _e('Type :', 'eventpost'); ?>
                    <select  id="ep_sce_type">
                        <option value='list'><?php _e('List', 'eventpost') ?></option>
                        <option value='map'><?php _e('Map', 'eventpost') ?></option>
                    </select>
                </label>
            </p>

            <p>
                <label for="ep_sce_nb"><?php _e('Number of posts', 'eventpost'); ?>
                    <input id="ep_sce_nb" type="number" value="5" data-att="nb"/>
                    <a class="button" id="ep_sce_nball"><?php _e('All', 'eventpost'); ?></a>
                </label>
            </p>

            <p>
                <label for="ep_sce_cat"><?php _e('Only in :', 'eventpost'); ?>
                    <select  id="ep_sce_cat" data-att="cat">
                        <option value=''><?php _e('All', 'eventpost') ?></option>
                        <?php
                        $cats = get_categories();
                        foreach ($cats as $cat) {
                            ?>
                            <option value="<?= $cat->slug ?>" <?php
                        if ($cat->slug == $eventpost_cat) {
                            echo'selected';
                        }
                        ?>><?= $cat->cat_name ?></option>
        <?php } ?>
                    </select>
                </label>
            </p>

            <p>
                <label for="ep_sce_future"><?php _e('Future events:', 'eventpost'); ?>
                    <select  id="ep_sce_future" data-att="future">
                        <option value='1'><?php _e('Yes', 'eventpost') ?></option>
                        <option value='0'><?php _e('No', 'eventpost') ?></option>
                    </select>
                </label>
                <label for="ep_sce_past"><?php _e('Past events:', 'eventpost'); ?>
                    <select  id="ep_sce_past" data-att="past">
                        <option value='0'><?php _e('No', 'eventpost') ?></option>
                        <option value='1'><?php _e('Yes', 'eventpost') ?></option>
                    </select>
                </label>
            </p>

            <div id="ep_sce_listonly" class="list">
                <p>
                    <label for="ep_sce_geo"><?php _e('Only geotagged events:', 'eventpost'); ?>
                        <select  id="ep_sce_geo" data-att="geo">
                            <option value='0'><?php _e('No', 'eventpost') ?></option>
                            <option value='1'><?php _e('Yes', 'eventpost') ?></option>
                        </select>
                    </label>
                </p>
            </div>
            <div id="ep_sce_maponly" class="map">
                <p>
                    <label for="ep_sce_tile"><?php _e('Map background', 'eventpost'); ?>
                        <select id="ep_sce_tile" data-att="tile">
                                    <?php
                                    foreach ($this->maps as $id => $map):
                                        ?>
                                <option value="<?php
                                            if ($ep_settings['tile'] != $map['id']) {
                                                echo $map['id'];
                                            }
                                            ?>" <?php
                    if ($ep_settings['tile'] == $map['id']) {
                        echo'selected';
                    }
                    ?>>
            <?php echo $map['name']; ?><?php
            if ($ep_settings['tile'] == $map['id']) {
                echo' (default)';
            }
            ?>
                                </option>
        <?php endforeach; ?>
                        </select>
                    </label>
                </p>
                <p>
                    <label for="ep_sce_width"><?php _e('Width', 'eventpost'); ?>
                        <input id="ep_sce_width" type="text" value="500px" data-att="width"/>
                    </label>
                </p>
                <p>
                    <label for="ep_sce_height"><?php _e('Height', 'eventpost'); ?>
                        <input id="ep_sce_height" type="text" value="500px" data-att="height"/>
                    </label>
                </p>
            </div>
        <?php do_action('after_eventpost_generator'); ?>
            <div id="ep_sce_shortcode">[event_list]</div>
            <a class="button" id="ep_sce_submit"><?php _e('Insert shortcode', 'eventpost'); ?></a>
        </div>
        <script>
            var IEbof = false;
        </script>
        <!--[if lt IE 9]>
        <script>IEbof=true;</script>
        <![endif]-->
        <?php
    }

    /* When the post is saved, saves our custom data */

    function save_postdata($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return;

        if (isset($_POST['agenda_noncename']) && !wp_verify_nonce($_POST['agenda_noncename'], plugin_basename(__FILE__)))
            return;

        // Clean color or no color
        if (isset($_POST[$this->META_COLOR]) && !empty($_POST[$this->META_COLOR])) {
            update_post_meta($post_id, $this->META_COLOR, $_POST[$this->META_COLOR]);
        }
        // Clean date or no date
        if (isset($_POST[$this->META_START]) && isset($_POST[$this->META_END])) {
            if (is_array($_POST[$this->META_START]) && is_array($_POST[$this->META_END]) && $_POST[$this->META_START]['date'] != '' && $_POST[$this->META_END]['date'] != '') {
                update_post_meta($post_id, $this->META_START, $_POST[$this->META_START]['date'] . ' ' . $_POST[$this->META_START]['hour'] . ':' . $_POST[$this->META_START]['minute'] . ':00');
                update_post_meta($post_id, $this->META_END, $_POST[$this->META_END]['date'] . ' ' . $_POST[$this->META_END]['hour'] . ':' . $_POST[$this->META_END]['minute'] . ':00');
            } else {
                delete_post_meta($post_id, $this->META_START);
                delete_post_meta($post_id, $this->META_END);
            }
        }

        // Clean location or no location
        if (isset($_POST[$this->META_LAT]) && isset($_POST[$this->META_LONG])) {
            if (!empty($_POST[$this->META_LAT]) && !empty($_POST[$this->META_LONG])) {
                update_post_meta($post_id, $this->META_ADD, $_POST[$this->META_ADD]);
                update_post_meta($post_id, $this->META_LAT, $_POST[$this->META_LAT]);
                update_post_meta($post_id, $this->META_LONG, $_POST[$this->META_LONG]);
            } else {
                delete_post_meta($post_id, $this->META_ADD);
                delete_post_meta($post_id, $this->META_LAT);
                delete_post_meta($post_id, $this->META_LONG);
            }
        }
    }

    function display_caldate($date, $cat = '', $display = false) {
        $events = $this->get_events(array('nb' => -1, 'date' => $date, 'cat' => $cat, 'retreive' => true));
        $nb = count($events);
        if ($display) {
            if ($nb > 0) {
                $ret.='<ul>';
                foreach ($events as $event) {
		    if ($this->settings['emptylink'] == 0 && empty($event->post_content)) {
			$event->guid = '#';
		    }
                    $ret.='<li>';
                    $ret.='<a href="' . $event->guid . '">';
                    $ret.='<h4>' . $event->post_title . '</h4>';
                    $ret.=$this->get_single($event);
                    $ret.='</a></li>';
                }
                $ret.='</ul>';
                return $ret;
            }
            return'';
        } else {
            return $nb > 0 ? '<a data-date="' . date('Y-m-d', $date) . '" class="eventpost_cal_link">' . date('j', $date) . '</a>' : date('j', $date);
        }
    }

    // uses filter : eventpost_params
    function calendar($atts) {
        extract(shortcode_atts(apply_filters('eventpost_params', array(
            'date' => date('Y-n'),
            'cat' => '',
            'mondayfirst' => 0, //1 : weeks starts on monday
            'datepicker' => 1
                                ), 'calendar'), $atts));

        $annee = substr($date, 0, 4);
        $mois = substr($date, 5);

        $time = mktime(0, 0, 0, $mois, 1, $annee);


        $JourMax = date("t", $time);
        $NoJour = -date("w", $time);
        if ($mondayfirst == 0) {
            $NoJour +=1;
        } else {
            $NoJour +=2;
            $this->Week[] = array_shift($this->Week);
        }
        if ($NoJour > 0 && $mondayfirst == 1) {
            $NoJour -=7;
        }
        $ret = '<table>';
        if ($datepicker == 1) {
            $ret.='<thead><tr><td colspan="7">';
            $ret.='<a data-date="' . date('Y-n', strtotime('-1 Year', $time)) . '" class="eventpost_cal_bt">&lt;&lt;</a> ';
            $ret.=$annee;
            $ret.='<a data-date="' . date('Y-n', strtotime('+1 Year', $time)) . '" class="eventpost_cal_bt">&gt;&gt;</a> ';
            $ret.='<a data-date="' . date('Y-n', strtotime('-1 Month', $time)) . '" class="eventpost_cal_bt">&lt;</a> ';
            $ret.=$this->NomDuMois[$mois];
            $ret.='<a data-date="' . date('Y-n', strtotime('+1 Month', $time)) . '" class="eventpost_cal_bt">&gt;</a> ';
            $ret.='<a data-date="' . date('Y-n') . '" class="eventpost_cal_bt">' . __('Today', 'eventpost') . '</a> </td></tr></thead>';
        }
        $ret.='<tbody>';
        $ret.='<tr class="event_post_cal_days">';
        for ($w = 0; $w < 7; $w++) {
            $ret.='<td>' . strtoupper(substr($this->Week[$w], 0, 1)) . '</td>';
        }
        $ret.='</tr>';


        $sqldate = date('Y-m', $time);
        $cejour = date('Y-m-d');
        for ($semaine = 0; $semaine <= 5; $semaine++) {   // 6 semaines par mois
            $ret.='<tr>';
            for ($journee = 0; $journee <= 6; $journee++) { // 7 jours par semaine
                if ($NoJour > 0 && $NoJour <= $JourMax) { // si le jour est valide a afficher
                    $td = '<td class="event_post_day">';
                    if ($sqldate . '-' . $NoJour == $cejour) {
                        $td = '<td class="event_post_day_now">';
                    }
                    $ret.=$td;

                    $ret.= $this->display_caldate(mktime(0, 0, 0, $mois, $NoJour, $annee), $cat);
                    $ret.='</td>';
                } else {
                    $ret.='<td></td>';
                }
                $NoJour ++;
            }
            $ret.='</tr>';
        }
        $ret.='</tbody></table>';

        return $ret;
    }

    function ajaxcal() {
        echo $this->calendar(array(
            'date' => $_REQUEST['date'],
            'cat' => $_REQUEST['cat'],
            'mondayfirst' => $_REQUEST['mf'],
            'datepicker' => $_REQUEST['dp']
        ));
        exit();
    }

    function ajaxdate() {
        echo $this->display_caldate(strtotime($_REQUEST['date']), $_REQUEST['cat'], true);
        exit();
    }

    function HumanDate() {
        if (isset($_REQUEST['date']) && !empty($_REQUEST['date'])) {
            $date = strtotime($_REQUEST['date']);
            echo $this->human_date($date) . date(' H:i', $date);
            exit();
        }
    }

    /** AJAX Get lat long from address */
    function GetLatLong() {
        if (isset($_REQUEST['q']) && !empty($_REQUEST['q'])) {
            // verifier le cache
            $q = $_REQUEST['q'];
            header('Content-Type: application/json');
            $transient_name = 'eventpost_osquery_' . $q;
            $val = get_transient($transient_name);
            if (false === $val || empty($val)) {
                $language = get_bloginfo('language');
                if (strpos($language, '-') > -1) {
                    $language = strtolower(substr($language, 0, 2));
                }
                $val = file_get_contents('http://nominatim.openstreetmap.org/search?q=' . urlencode($q) . '&format=json&accept-language=' . $language);
                set_transient($transient_name, $val, 30 * DAY_IN_SECONDS);
            }
            echo $val;
            exit();
        }
    }

    // ADD COLUMNS
    function columns_head($defaults) {
        $defaults['event'] = __('Event', 'eventpost');
        $defaults['location'] = __('Location', 'eventpost');
        return $defaults;
    }

    // COLUMN CONTENT  (ARCHIVES)
    function columns_content($column_name, $post_id) {
        if ($column_name == 'location') {
            $lat = get_post_meta($post_id, $this->META_LAT, true);
            $lon = get_post_meta($post_id, $this->META_LONG, true);

            if (!empty($lat) && !empty($lon)) {
                $color = get_post_meta($post_id, $this->META_COLOR, true);
                if ($color == '')
                    $color = '777777';
                echo'<a href="https://www.openstreetmap.org/?lat=' . $lat.='&amp;lon=' . $lon.='&amp;zoom=13" target="_blank"><img src="' . plugins_url('/markers/', __FILE__) . $color . '.png" alt="' . get_post_meta($post_id, $this->META_ADD, true) . '"/></a>';
            }
        }
        if ($column_name == 'event') {
            echo $this->print_date($post_id, false);
        }
    }

    /** ADMIN PAGES * */
    function save_settings(){
	if (!current_user_can('manage_options')){
	    return;
	}
	if (!wp_verify_nonce(\filter_input(INPUT_POST,'ep_nonce_settings',FILTER_SANITIZE_STRING), 'ep_nonce_settings')) {
	    wp_die(__('Security error', 'eventpost'));
	}

	$valid_post = array(
	    'ep_settings'=>array(
		'filter' => FILTER_SANITIZE_STRING,
		'flags'  => FILTER_REQUIRE_ARRAY
	    )
	);

	foreach ($this->settings as $item_name=>$item_value){
	    $valid_post['ep_settings'][$item_name] = FILTER_SANITIZE_STRING;
	}

	if (false !== $settings = \filter_input_array(INPUT_POST,$valid_post)) {
	    $settings['ep_settings']['container_shema']=stripslashes($_POST['ep_settings']['container_shema']);
	    $settings['ep_settings']['item_shema']=  stripslashes($_POST['ep_settings']['item_shema']);
	    update_option('ep_settings', $settings['ep_settings']);
	}
	wp_redirect('options-general.php?page=event-settings&confirm=options_saved');
	exit;
    }
    function manage_options() {
        add_submenu_page('options-general.php', __('Event settings', 'eventpost'), __('Event settings', 'eventpost'), 'manage_options', 'event-settings', array(&$this, 'manage_settings'));
    }

    function manage_settings() {
        if ('options_saved'===\filter_input(INPUT_GET,'confirm',FILTER_SANITIZE_STRING)) {
            ?>
            <div class="updated"><p><strong><?php _e('Event settings saved !', 'eventpost') ?></strong></p></div>
            <?php
        }
        $ep_settings = $this->settings;
        ?>
        <div class="wrap">
            <div class="icon32" id="icon-options-general"><br></div>
            <h2><?php _e('Event settings', 'eventpost'); ?></h2>
            <form name="form1" method="post" action="admin-post.php">
		<input type="hidden" name="action" value="EventPostSaveSettings">
		<?php wp_nonce_field('ep_nonce_settings','ep_nonce_settings') ?>
                <table class="form-table" id="eventpost-settings-table">
                    <tbody>
                        <tr><td colspan="2">
                                <h3><?php _e('Event settings', 'eventpost'); ?></h3>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="ep_dateformat">
                                    <?php _e('Date format', 'eventpost') ?>
                                </label></th>
                            <td><input type="text" name="ep_settings[dateformat]" id="ep_dateformat" value="<?php echo $ep_settings['dateformat']; ?>"  class="widefat">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="ep_dateexport">
                                    <?php _e('Show export buttons on:', 'eventpost') ?>

                                </label></th>
                            <td><select name="ep_settings[export]" id="ep_dateexport" class="widefat">
                                    <option value="list" <?php
                                    if ($ep_settings['export'] == 'list') {
                                        echo'selected';
                                    }
                                    ?>><?php _e('List only', 'eventpost') ?></option>
                                    <option value="single" <?php
                                    if ($ep_settings['export'] == 'single') {
                                        echo'selected';
                                    }
                                    ?>><?php _e('Single only', 'eventpost') ?></option>
                                    <option value="both" <?php
                                    if ($ep_settings['export'] == 'both') {
                                        echo'selected';
                                    }
                                    ?>><?php _e('Both', 'eventpost') ?></option>
                                    <option value="none" <?php
                                    if ($ep_settings['export'] == 'none') {
                                        echo'selected';
                                    }
                                    ?>><?php _e('None', 'eventpost') ?></option>
                                </select></td>
                        </tr>
                        <tr><td colspan="2">
                                <h3><?php _e('List settings', 'eventpost'); ?></h3>
                            </td>
                        </tr>
			<tr>
                            <th><label for="ep_container_shema">
                                    <?php _e('Container shema', 'eventpost') ?>
                                </label></th>
				<td><textarea class="widefat" name="ep_settings[container_shema]" id="ep_container_shema"><?php echo $ep_settings['container_shema']; ?></textarea>
				    <p><?php _e('default:','eventpost') ?></p>
					<code><?php echo htmlentities($this->default_list_shema['container']) ?></code>
                            </td>
                        </tr>
			<tr>
                            <th><label for="ep_item_shema">
                                    <?php _e('Item shema', 'eventpost') ?>
                                </label></th>
				<td><textarea class="widefat" name="ep_settings[item_shema]" id="ep_item_shema"><?php echo $ep_settings['item_shema']; ?></textarea>
				    <p><?php _e('default:','eventpost') ?></p>
					<code><?php echo htmlentities($this->default_list_shema['item']) ?></code>
                            </td>
                        </tr>
			<tr><td colspan="2">
                                <h3><?php _e('Map settings', 'eventpost'); ?></h3>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="ep_tile">
                                    <?php _e('Map background', 'eventpost') ?>

                                </label></th>
                            <td><select name="ep_settings[tile]" id="ep_tile" class="widefat">
                                    <?php
                                    foreach ($this->maps as $id => $map):
                                        ?>
                                        <option value="<?php echo $map['id']; ?>" <?php
                                        if ($ep_settings['tile'] == $map['id']) {
                                            echo'selected';
                                        }
                                        ?>><?php echo $map['name']; ?></option>
        <?php endforeach; ?>
                                </select></td>
                        </tr>
                        <tr>
                            <th>
        <?php _e('Makers custom directory (leave empty for default settings)', 'eventpost') ?>
                                </label></th>
                            <td>
                                <label for="ep_markpath">
                                    <?php _e('Root path:', 'eventpost') ?><br>
                                    ABSPATH/<input name="ep_settings[markpath]" id="ep_markpath" value="<?php echo $this->settings['markpath'] ?>" class="widefat">
                                </label><br>
                                <label for="ep_markurl">
                                    <?php _e('URL:', 'eventpost') ?><br>
                                    <input name="ep_settings[markurl]" id="ep_markurl" value="<?php echo $this->settings['markurl'] ?>" class="widefat">
                                </label>
                            </td>
                        </tr>
                        <tr><td colspan="2">
                                <h3><?php _e('Global settings', 'eventpost'); ?></h3>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="ep_emptylink">
        <?php _e('Print link for empty posts', 'eventpost') ?>

                                </label></th>
                            <td><select name="ep_settings[emptylink]" id="ep_emptylink" class="widefat">
                                    <option value="1" <?php
                                    if ($ep_settings['emptylink'] == 1) {
                                        echo'selected';
                                    }
                                    ?>><?php _e('Link all posts', 'eventpost'); ?></option>
                                    <option value="0" <?php
                                    if ($ep_settings['emptylink'] == 0) {
                                        echo'selected';
                                    }
                                    ?>><?php _e('Do not link posts with empty content', 'eventpost'); ?></option>
                                </select></td>
                        </tr>
                        <tr>
                            <th><label for="ep_singlepos">
        <?php _e('Event bar position for single posts', 'eventpost') ?>

                                </label></th>
                            <td><select name="ep_settings[singlepos]" id="ep_singlepos" class="widefat">
                                    <option value="before" <?php
                                    if ($ep_settings['singlepos'] == 'before') {
                                        echo'selected';
                                    }
                                    ?>><?php _e('Before the content', 'eventpost'); ?></option>
                                    <option value="after" <?php
                                    if ($ep_settings['singlepos'] == 'after') {
                                        echo'selected';
                                    }
                                    ?>><?php _e('After the content', 'eventpost'); ?></option>
                                </select></td>
                        </tr>


                        <tr><td colspan="2">
                                <h3><?php _e('Performances settings', 'eventpost'); ?></h3>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <label for="ep_cache">
                                    <input type="checkbox" name="ep_settings[cache]" id="ep_cache" <?php if($ep_settings['cache']=='1'){ echo'checked';} ?> value="1">
                                    <?php _e('Cache results','eventpost')?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2"><p class="submit"><input type="submit" value="<?php _e('Apply settings', 'eventpost'); ?>" class="button button-primary" id="submit" name="submit">			</p></td>
                        </tr>



                    </tbody>
                </table>
            </form>
        </div>
        <?php
    }

    /*
     * feed
     * generate ICS or VCS files from a category
     */
    function ics_date($timestamp){
	return date("Ymd",$timestamp).'T'.date("His",$timestamp).'Z';
    }
    function feed(){
	if(false !== $cat=\filter_input(INPUT_GET, 'cat',FILTER_SANITIZE_STRING)){
	    $format = \filter_input(INPUT_GET, 'format',FILTER_SANITIZE_STRING)?:'ics';
	    $timezone_string = get_option('timezone_string');
	    date_default_timezone_set($timezone_string);

	    header("content-type:text/calendar");
	    header("Pragma: public");
	    header("Expires: 0");
	    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	    header("Cache-Control: public");
	    header("Content-Disposition: attachment; filename=". str_replace('+','-',urlencode(get_option('blogname').'-'.$cat)).".ics;" );
	    echo"BEGIN:VCALENDAR\r\n"
	    . "PRODID:EventPost\r\n"
	    . "VERSION:2.0\r\n";
	    /*
	    . "BEGIN:VTIMEZONE\r\n"
	    . "TZID:$timezone_string\r\n"
	    . "X-LIC-LOCATION:$timezone_string\r\n"
	    . "END:VTIMEZONE";
	     *
	     */
	    $events=$this->get_events(array('cat'=>$cat,'nb'=>-1));
	    //print_r($events);
	    foreach ($events as $event) {
		echo"BEGIN:VEVENT\r\n"
			. "CREATED:".$this->ics_date(strtotime($event->post_date))."\r\n"
			. "LAST-MODIFIED:".$this->ics_date(strtotime($event->post_modified))."\r\n"
			. "SUMMARY:".$event->post_title."\r\n"
			. "UID:".md5(site_url()."_eventpost_".$event->ID)."\r\n"
			. "LOCATION:".str_replace(',','\,',$event->address)."\r\n"
			. "DTSTART;TZID=$timezone_string:".$this->ics_date($event->time_start)."\r\n"
			. "DTEND;TZID=$timezone_string:".$this->ics_date($event->time_end)."\r\n"
			. "DESCRIPTION:".$event->guid."\r\n"
			. "END:VEVENT\r\n";
	    }
	    echo"END:VCALENDAR\r\n";
	    exit;
	}
    }

}
