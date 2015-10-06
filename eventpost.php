<?php
/*
  Plugin Name: Event Post
  Plugin URI: http://ecolosites.eelv.fr/articles-evenement-eventpost/
  Description: Add calendar and/or geolocation metadata on posts
  Version: 3.9.0
  Author: bastho
  Contributors: n4thaniel, ecolosites
  Author URI: http://ecolosites.eelv.fr/
  License: GPLv2
  Text Domain: eventpost
  Domain Path: /languages/
  Tags: Post,posts,event,date,geolocalization,gps,widget,map,openstreetmap,EELV,calendar,agenda
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

    public $version = '3.8.0';

    public $map_interactions;

    public function __construct() {
        load_plugin_textdomain('eventpost', false, 'event-post/languages');

        add_action('save_post', array(&$this, 'save_postdata'));
        add_action('admin_menu', array(&$this, 'manage_options'));
	add_filter('dashboard_glance_items', array(&$this, 'dashboard_right_now'));

        // Scripts
        add_action('admin_enqueue_scripts', array(&$this, 'admin_head'));
        add_action('admin_print_scripts', array(&$this, 'admin_scripts'));
        add_action('wp_enqueue_scripts', array(&$this, 'load_styles'));

        // Single
        add_filter('the_content', array(&$this, 'display_single'), 9999);
        add_filter('the_title', array(&$this, 'the_title'), 9999);
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

        //Shortcodes
	add_action('init', array(&$this,'init'));
        add_shortcode('events_list', array(&$this, 'shortcode_list'));
        add_shortcode('events_map', array(&$this, 'shortcode_map'));
        add_shortcode('events_cal', array(&$this, 'shortcode_cal'));
        add_shortcode('event_details', array(&$this, 'shortcode_single'));
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


        // Edit
        add_action('add_meta_boxes', array(&$this, 'add_custom_box'));
        foreach($this->settings['posttypes'] as $posttype){
            add_filter('manage_'.$posttype.'_posts_columns', array(&$this, 'columns_head'), 2);
            add_action('manage_'.$posttype.'_posts_custom_column', array(&$this, 'columns_content'), 10, 2);
        }


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

        $this->map_interactions=array(
            'DragRotate'=>__('Drag Rotate', 'eventpost'),
            'DoubleClickZoom'=>__('Double Click Zoom', 'eventpost'),
            'DragPan'=>__('Drag Pan', 'eventpost'),
            'PinchRotate'=>__('Pinch Rotate', 'eventpost'),
            'PinchZoom'=>__('Pinch Zoom', 'eventpost'),
            'KeyboardPan'=>__('Keyboard Pan', 'eventpost'),
            'KeyboardZoom'=>__('Keyboard Zoom', 'eventpost'),
            'MouseWheelZoom'=>__('Mouse Wheel Zoom', 'eventpost'),
            'DragZoom'=>__('Drag Zoom', 'eventpost'),
        );

    }

    /**
     * PHP4 constructor
     */
    public function EventPost(){
        $this->__construct();
    }

    /**
     * Call functions when WP is ready
     */
    public function init(){
	$this->shortcode_ui();
    }

    /**
     * @desc Usefull hexadecimal to decimal converter
     * @param string $color
     * @return array $color($R, $G, $B)
     */
    public function hex2dec($color = '000000') {
        $tbl_color = array();
        if (!strstr('#', $color)){
            $color = '#' . $color;
	}
        $tbl_color['R'] = hexdec(substr($color, 1, 2));
        $tbl_color['G'] = hexdec(substr($color, 3, 2));
        $tbl_color['B'] = hexdec(substr($color, 5, 2));
        return $tbl_color;
    }
    /**
     *
     * @global array $_wp_additional_image_sizes
     * @return array
     */
    function get_thumbnail_sizes(){
        global $_wp_additional_image_sizes;
        $sizes = array('thumbnail', 'medium', 'large', 'full');
        foreach($_wp_additional_image_sizes as $size=>$attrs){
            $sizes[]=$size;
        }
        return $sizes;
    }

    /**
     * Just for localisation
     */
    private function no_use() {
        __('Add calendar and/or geolocation metadata on posts', 'eventpost');
        __('Event Post', 'eventpost');
    }

    /**
     * @desc get blog settings, load and saves default settings id needed
     * @action eventpost_getsettings
     * @filter eventpost_getsettings
     * @return array
     */
    public function get_settings() {
        $ep_settings = get_option('ep_settings');
        $reg_settings=false;
	if(!is_array($ep_settings)){
	    $ep_settings = array();
	}
        if (!isset($ep_settings['dateformat']) || empty($ep_settings['dateformat'])) {
            $ep_settings['dateformat'] = get_option('date_format');
            $reg_settings=true;
        }
        if (!isset($ep_settings['timeformat']) || empty($ep_settings['timeformat'])) {
            $ep_settings['timeformat'] = get_option('time_format');
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
	if (!isset($ep_settings['loopicons'])) {
            $ep_settings['loopicons'] = 1;
            $reg_settings=true;
        }
        if (!isset($ep_settings['adminpos']) || empty($ep_settings['adminpos'])) {
            $ep_settings['adminpos'] = 'side';
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

        if(!isset($ep_settings['datepicker'])){
            $ep_settings['datepicker']='dual';
            $reg_settings=true;
        }

        if(!isset($ep_settings['posttypes']) || !is_array($ep_settings['posttypes'])){
            $ep_settings['posttypes']=array('post');
            $reg_settings=true;
        }

        do_action('eventpost_getsettings');

        //Save settings  not changed
        if($reg_settings===true){
           update_option('ep_settings', $ep_settings);
        }
        return apply_filters('eventpost_getsettings', $ep_settings);
    }

    /**
     *
     * @param array $shema
     * @return array
     */
    public function custom_shema($shema){
	if(!empty($this->settings['container_shema'])){
	    $shema['container']=$this->settings['container_shema'];
	}
	if(!empty($this->settings['item_shema'])){
	    $shema['item']=$this->settings['item_shema'];
	}
	return $shema;
    }

    /**
     *
     * @return array
     */
    public function get_maps() {
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

    /**
     *
     * @return array
     */
    public function get_colors() {
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

    /**
     *
     * @param string $color
     * @return sring
     */
    public function get_marker($color) {
        if (is_file($this->markpath . $color . '.png')) {
            return $this->markurl . $color . '.png';
        }
        return plugins_url('/markers/ffffff.png', __FILE__);
    }

    /**
     * Enqueue CSS files
     */
    public function load_styles() {
        //CSS
        wp_register_style('eventpost', plugins_url('/css/eventpost.min.css', __FILE__), false,  $this->version);
        wp_enqueue_style('eventpost', plugins_url('/css/eventpost.min.css', __FILE__), false,  $this->version);
        wp_enqueue_style('openlayers', plugins_url('/css/openlayers.css', __FILE__), false,  $this->version);
        wp_enqueue_style('dashicons-css', includes_url('/css/dashicons.min.css'));
    }

    /**
     * Enqueue JS files
     */
    public function load_scripts() {
        // JS
        wp_enqueue_script('jquery', false, false, false, true);
        wp_enqueue_script('eventpost', plugins_url('/js/eventpost.min.js', __FILE__), false, $this->version, true);
        wp_localize_script('eventpost', 'eventpost_params', array(
            'imgpath' => plugins_url('/img/', __FILE__),
            'maptiles' => $this->maps,
            'defaulttile' => $this->settings['tile'],
            'ajaxurl' => admin_url() . 'admin-ajax.php',
            'map_interactions'=>$this->map_interactions,
        ));
    }
    /**
     * Enqueue JS files for maps
     */
    public function load_map_scripts() {
        // JS
	$this->load_scripts();
        wp_enqueue_script('openlayers', plugins_url('/js/OpenLayers.js', __FILE__), false,  $this->version, true);
    }

    /**
     * Enqueue CSS files in admin
     */
    public function admin_head() {
        wp_enqueue_style('jquery-ui', plugins_url('/css/jquery-ui.css', __FILE__), false,  $this->version);
        wp_enqueue_style('eventpostadmin', plugins_url('/css/eventpostadmin.css', __FILE__), false,  $this->version);
        if($this->settings['datepicker']=='dual' || (isset($_GET['page']) && $_GET['page']=='event-settings')){
            wp_enqueue_style('eventpost-datetimepicker', plugins_url('/css/jquery.datetimepicker.css', __FILE__), false,  $this->version);
        }
    }

    /**
     * Enqueue JS files in admin
     */
    public function admin_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('eventpost-admin', plugins_url('/js/osm-admin.min.js', __FILE__), false,  $this->version, true);
        if($this->settings['datepicker']=='dual' || (isset($_GET['page']) && $_GET['page']=='event-settings')){
            wp_enqueue_script('eventpost-datetimepicker', plugins_url('/js/jquery.datetimepicker.js', __FILE__), false,  $this->version, true);
        }
        if($this->settings['datepicker']=='separate' || (isset($_GET['page']) && $_GET['page']=='event-settings')){
            wp_enqueue_script('jquery-ui-datepicker');
        }
        $language = get_bloginfo('language');
        if (strpos($language, '-') > -1) {
            $language = strtolower(substr($language, 0, 2));
        }
        wp_localize_script('eventpost-admin', 'eventpost', array(
            'imgpath' => plugins_url('/img/', __FILE__),
            'date_choose' => __('Choose', 'eventpost'),
            'date_format' => __('yy-mm-dd', 'eventpost'),
            'more_icons' => __('More icons', 'eventpost'),
	    'pick_a_date'=>__('Pick a date','eventpost'),
            'META_START' => $this->META_START,
            'META_END' => $this->META_END,
            'META_ADD' => $this->META_ADD,
            'META_LAT' => $this->META_LAT,
            'META_LONG' => $this->META_LONG,
            'lang'=>$language,
        ));
    }

    /**
     * @desc Add custom header meta for single events
     */
    public function single_header() {
        if (is_single()) {
	    $event = $this->retreive();
            if ($event->address != '' || ($event->lat != '' && $event->long != '')) {
                ?>
<meta name="geo.placename" content="<?php echo $event->address ?>" />
<meta name="geo.position" content="<?php echo $event->lat ?>;<?php echo $event->long ?>" />
<meta name="ICBM" content="<?php echo $event->lat ?>;<?php echo $event->long ?>" />
                <?php
            }
            if ($event->start != '' && $event->end != '') {
                ?>
<meta name="datetime-coverage-start" content="<?php echo date('c', $event->time_start) ?>" />
<meta name="datetime-coverage-end" content="<?php echo date('c', $event->time_end) ?>" />
                <?php
            }
        }
    }

    /**
     *
     * @param string $str
     * @return boolean
     */
    public function dateisvalid($str) {
        return is_string($str) && trim(str_replace(array(':', '0'), '', $str)) != '';
    }

    /**
     *
     * @param string $date
     * @param string $sep
     * @return string
     */
    public function parsedate($date, $sep = '') {
        if (!empty($date)) {
            return substr($date, 0, 10) . $sep . substr($date, 11, 8);
        } else {
            return '';
        }
    }

    /**
     *
     * @param mixed $date
     * @param string $format
     * @return type
     */
    public function human_date($date, $format = 'l j F Y') {
        if (is_numeric($date) && date('d/m/Y', $date) == date('d/m/Y')) {
            return __('today', 'eventpost');
        } elseif (is_numeric($date) && date('d/m/Y', $date) == date('d/m/Y', strtotime('+1 day'))) {
            return __('tomorrow', 'eventpost');
        } elseif (is_numeric($date) && date('d/m/Y', $date) == date('d/m/Y', strtotime('-1 day'))) {
            return __('yesterday', 'eventpost');
        }
        return date_i18n($format, $date);
    }

    /**
     *
     * @param WP_Post object $post
     * @param mixed $links
     * @return string
     */
    public function print_date($post = null, $links = 'deprecated') {
        $dates = '';
        $event = $this->retreive($post);
        $start_date = $event->start;
        $end_date = $post->end;
        if ($event->start != '' && $event->end != '') {
            $gmt_offset = get_option('gmt_offset ');
            $timezone_string = get_option('timezone_string');
            $codegmt = 0;
            if ($gmt_offset != 0 && substr($gmt_offset, 0, 1) != '-' && substr($gmt_offset, 0, 1) != '+') {
                $codegmt = $gmt_offset * -1;
                $gmt_offset = '+' . $gmt_offset;
            }
            //Display dates
            $dates.='<div class="event_date" data-start="' . $this->human_date($event->time_start) . '" data-end="' . $this->human_date($event->time_end) . '">';
            if (date('d/m/Y', $event->time_start) == date('d/m/Y', $event->time_end)) {
                $dates.= '<time itemprop="dtstart" datetime="' . date('c', $event->time_start) . '">'
                        . '<span class="date date-single">' . $this->human_date($event->time_end, $this->settings['dateformat']) . "</span>";
                if (date('H:i', $event->time_start) != date('H:i', $event->time_end) && date('H:i', $event->time_start) != '00:00' && date('H:i', $event->time_end) != '00:00') {
                    $dates.=' <span class="linking_word linking_word-from">' . __('from', 'eventpost') . '</span>
					<span class="time time-start">' . date($this->settings['timeformat'], $event->time_start) . '</span>
					<span class="linking_word linking_word-t">' . __('to', 'eventpost') . '</span>
					<span class="time time-end">' . date($this->settings['timeformat'], $event->time_end) . '</span>';
                } elseif (date('H:i', $event->time_start) != '00:00') {
                    $dates.=' <span class="linking_word">' . __('at', 'eventpost') . '</span>
					<span class="time time-single" itemprop="dtstart" datetime="' . date('c', $event->time_start) . '">' . date($this->settings['timeformat'], $event->time_start) . '</span>';
                }
                        $dates.='</time>';
            } else {
                $dates.= '
		<span class="linking_word linking_word-from">' . __('from', 'eventpost') . '</span>
		<span class="date date-start" itemprop="dtstart" datetime="' . date('c', $event->time_start) . '">' . $this->human_date($event->time_start, $this->settings['dateformat']) . '</span>
		<span class="linking_word linking_word-to">' . __('to', 'eventpost') . '</span>
		<span class="date date-end" itemprop="dtend" datetime="' . date('c', $event->time_end) . '">' . $this->human_date($event->time_end, $this->settings['dateformat']) . '</span>
		';
            }

            if (!is_admin() && $event->time_end > current_time('timestamp') && (
                    $this->settings['export'] == 'both' ||
                    ($this->settings['export'] == 'single' && is_single() ) ||
                    ($this->settings['export'] == 'list' && !is_single() )
                    )) {
                // Export event
                $title = urlencode($post->post_title);
                $address = urlencode($post->address);
                $url = urlencode($post->guid);

                $mt = strtotime($codegmt . ' Hours', $event->time_start);
                $d_s = date("Ymd", $mt) . 'T' . date("His", $mt);
                $mte = strtotime($codegmt . ' Hours', $event->time_end);
                $d_e = date("Ymd", $mte) . 'T' . date("His", $mte);
                $uid = $post->ID . '-' . $post->blog_id;

                // format de date ICS
                $ics_url = plugins_url('export/ics.php', __FILE__) . '?t=' . $title . '&amp;u=' . $uid . '&amp;sd=' . $d_s . '&amp;ed=' . $d_e . '&amp;a=' . $address . '&amp;d=' . $url . '&amp;tz=%3BTZID%3D' . urlencode($timezone_string);

                // format de date Google cal
                $google_url = 'https://www.google.com/calendar/event?action=TEMPLATE&amp;text=' . $title . '&amp;dates=' . $d_s . 'Z/' . $d_e . 'Z&amp;details=' . $url . '&amp;location=' . $address . '&amp;trp=false&amp;sprop=&amp;sprop=name';

                // format de date VCS
                $vcs_url = plugins_url('export/vcs.php', __FILE__) . '?t=' . $title . '&amp;u=' . $uid . '&amp;sd=' . $d_s . '&amp;ed=' . $d_e . '&amp;a=' . $address . '&amp;d=' . $url . '&amp;tz=%3BTZID%3D' . urlencode($timezone_string);

                $dates.='
                    <span class="eventpost-date-export">
                        <a href="' . $ics_url . '" class="event_link ics" target="_blank" title="' . __('Download ICS file', 'eventpost') . '">ical</a>
                        <a href="' . $google_url . '" class="event_link gcal" target="_blank" title="' . __('Add to Google calendar', 'eventpost') . '">Google</a>
                        <a href="' . $vcs_url . '" class="event_link vcs" target="_blank" title="' . __('Add to Outlook', 'eventpost') . '">outlook</a>
                        <i class=" dashicons-before dashicons-calendar"></i>
                    </span>';
            }
            $dates.='</div>';
        }
        return apply_filters('eventpost_printdate', $dates);
    }

    /**
     *
     * @param WP_Post object $post
     * @return string
     */
    public function print_location($post = null) {
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
                $location.='<a class="event_link gps dashicons-before dashicons-location-alt" href="https://www.openstreetmap.org/?lat=' . $lat.='&amp;lon=' . $long.='&amp;zoom=13" target="_blank"  itemprop="geo">' . __('Map', 'eventpost') . '</a>';
            }
            $location.='</address>';
        }

        return apply_filters('eventpost_printlocation', $location);
    }

    /**
     *
     * @param WP_Post object $post
     * @return string
     */
    public function print_categories($post = null) {
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

    /**
     * @desc Generate, return or output date event datas
     * @param WP_Post object $post
     * @param string $class
     * @return string
     */
    public function get_single($post = null, $class = '') {
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

    /**
     *
     * @param WP_Post object $post
     * @param string $class
     * @return string
     */
    public function get_singledate($post = null, $class = '') {
        return '<div class="event_data event_date ' . $class . '" itemscope itemtype="http://microformats.org/profile/hcard">' . $this->print_date($post) . '</div>';
    }

    /**
     *
     * @param WP_Post object $post
     * @param string $class
     * @return string
     */
    public function get_singlecat($post = null, $class = '') {
        return '<div class="event_data event_category ' . $class . '" itemscope itemtype="http://microformats.org/profile/hcard">' . $this->print_categories($post) . '</div>';
    }

    /**
     *
     * @param WP_Post object $post
     * @param string $class
     * @return string
     */
    public function get_singleloc($post = null, $class = '') {
        return '<div class="event_data event_location ' . $class . '" itemscope itemtype="http://microformats.org/profile/hcard">' . $this->print_location($post) . '</div>';
    }

    /**
     *
     * @param string $content
     * @return string
     */
    public function display_single($content) {
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

    /**
     *
     * @param WP_Post object $post
     * @echoes string
     * @return void
     */
    public function print_single($post = null) {
        echo $this->get_single($post);
    }

    /**
     * the_title
     * @desc alter the post title in order to add icons if needed
     * @param string $title
     * @return string
     */
    public function the_title($title){
	if(!in_the_loop() || !$this->settings['loopicons']){
	    return $title;
	}
	$event = $this->retreive();
	if(!empty($event->start)){
	   $title .= ' <span class="dashicons dashicons-calendar"></span>';
	}
	if(!empty($event->lat) && !empty($event->long)){
	   $title .= ' <span class="dashicons dashicons-location"></span>';
	}
	return $title;
    }

    /**
     * shortcode_single
     * @param array $atts
     * @filter : eventpost_params
     * @return string
     */
    public function shortcode_single($atts){
	extract(shortcode_atts(apply_filters('eventpost_params', array(
            'attribute' => '',
                        ), 'shortcode_single'), $atts));
	$event = $this->retreive();
	switch($attribute){
	    case 'start':
		return $this->human_date($event->time_start);
	    case 'end':
		return $this->human_date($event->time_end);
	    case 'address':
		return $event->address;
	    case 'location':
		return $this->get_singleloc($event);
	    case 'date':
		return $this->get_singledate($event);
	    default:
		return $this->get_single($event);
	}
    }

    /**
     * @desc Shortcode to display a list of events
     * @param array $atts
     * @filter eventpost_params
     * @return string
     */
    public function shortcode_list($atts) {
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
        return $this->list_events($atts, 'event_list', 'shortcode');
    }

    /**
     * @desc Shortcode to display a map of events
     * @param array $atts
     * @filter eventpost_params
     * @return string
     */
    public function shortcode_map($atts) {
        $ep_settings = $this->settings;

        $defaults = array(
            // Display
            'width' => '',
            'height' => '',
            'tile' => $ep_settings['tile'],
            'title' => '',
            'before_title' => '<h3>',
            'after_title' => '</h3>',
            'style' => '',
            'thumbnail' => '',
            'thumbnail_size' => '',
            'excerpt' => '',
            // Filters
            'nb' => 0,
            'future' => true,
            'past' => false,
            'cat' => '',
            'tag' => '',
            'orderby' => 'meta_value',
            'order' => 'ASC',
        );
            // UI options
        foreach($this->map_interactions as $int_key=>$int_name){
            $defaults[$int_key]=true;
        }
            // - UI options
        foreach($this->map_interactions as $int_key=>$int_name){
            $defaults['disable_'.strtolower($int_key)]=false;
        }

        $atts = shortcode_atts(apply_filters('eventpost_params', $defaults, 'shortcode_map'), $atts);
            // UI options
        foreach($this->map_interactions as $int_key=>$int_name){
            if($atts['disable_'.strtolower($int_key)]==true){
                $atts[$int_key]=false;
            }
            unset($atts['disable_'.strtolower($int_key)]);
        }
        $atts['geo'] = 1;
        $atts['type'] = 'div';
        return $this->list_events($atts, 'event_geolist', 'shortcode'); //$nb,'div',$future,$past,1,'event_geolist');
    }

    /**
     * @desc Shortcode to display a calendar of events
     * @param array $atts
     * @filter eventpost_params
     * @return string
     */
    public function shortcode_cal($atts) {
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

    /**
     * @desc Return an HTML list of events
     * @param array $atts
     * @filter eventpost_params
     * @filter eventpost_listevents
     * @filter eventpost_item_scheme_entities
     * @filter eventpost_item_scheme_values
     * @return string
     */
    public function list_events($atts, $id = 'event_list', $context='') {
	$ep_settings = $this->settings;
        $defaults = array(
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
                        );
        // Map UI options
        foreach($this->map_interactions as $int_key=>$int_name){
            $defaults[$int_key]=true;
        }
        $atts = shortcode_atts(apply_filters('eventpost_params', $defaults, 'list_events'), $atts);

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
                $class_item = ($post->time_end >= current_time('timestamp')) ? 'event_future' : 'event_past';
                if ($ep_settings['emptylink'] == 0 && empty($post->post_content)) {
                    $post->permalink = '#' . $id . $this->list_id;
                }
                $list.=str_replace(
                        apply_filters('eventpost_item_scheme_entities', array(
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
                        )), apply_filters('eventpost_item_scheme_values', array(
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
                        )), $item_schema
                );
            }
            $attributes = '';
            if($id == 'event_geolist'){
                $attributes = 'data-tile="'.$tile.'" data-width="'.$width.'" data-height="'.$height.'" data-disabled-interactions="';
                foreach($this->map_interactions as $int_key=>$int_name){
                    $attributes.=$atts[$int_key]==false ? $int_key.', ' : '';
                }
                $attributes.='"';
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
                $attributes,
                $list
                    ), $container_schema
            );
        }
        return apply_filters('eventpost_listevents', $ret, $id.$this->list_id, $atts, $events, $context);
    }

    /**
     * get_events
     * @param array $attr
     * @filter  eventpost_params
     * @return array of post_ids wich are events
     */
    public function get_events($atts) {
        $requete = (shortcode_atts(apply_filters('eventpost_params', array(
                    'nb' => 5,
                    'future' => true,
                    'past' => false,
                    'geo' => 0,
                    'cat' => '',
                    'tag' => '',
                    'date' => '',
                    'orderby' => 'meta_value',
                    'order' => 'ASC',
                    'post_type'=> $this->settings['posttypes']
                    ), 'get_events'), $atts));
        extract($requete);
        wp_reset_query();

        $arg = array(
            'post_type' => $post_type,
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
                'key' => $this->META_END,
                'value' => ':00',
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
                'value' => current_time('mysql'),
                'compare' => '>=',
                    //'type'=>'DATETIME'
            );
        } elseif ($future == 0 && $past == 1) {
            $meta_query[] = array(
                'key' => $this->META_END,
                'value' => current_time('mysql'),
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
            foreach ($events as $k => $post) {
		$event = $this->retreive($post);
                $events[$k] = $event;
            }
        }
        if ($this->settings['cache'] == 1){
            set_transient($query_md5, $events, 5 * MINUTE_IN_SECONDS);
	}
        return $events;
    }

    /**
     *
     * @param object $event
     * @return object
     */
    public function retreive($event = null) {
	if(isset($event->start)){
	    return $event;
	}
        $ob = get_post($event);
        $ob->start = get_post_meta($ob->ID, $this->META_START, true);
        $ob->end = get_post_meta($ob->ID, $this->META_END, true);
        if (!$this->dateisvalid($ob->start)){
            $ob->start = '';
	}
        if (!$this->dateisvalid($ob->end)){
            $ob->end = '';
	}
        $ob->time_start = !empty($ob->start) ? strtotime($ob->start) : '';
        $ob->time_end = !empty($ob->end) ? strtotime($ob->end) : '';
        $ob->address = get_post_meta($ob->ID, $this->META_ADD, true);
        $ob->lat = get_post_meta($ob->ID, $this->META_LAT, true);
        $ob->long = get_post_meta($ob->ID, $this->META_LONG, true);
        $ob->color = get_post_meta($ob->ID, $this->META_COLOR, true);
        $ob->categories = get_the_category($ob->ID);
        $ob->permalink = get_permalink($ob->ID);
        $ob->blog_id = get_current_blog_id();
        if ($ob->color == ''){
            $ob->color = '000000';
	}
        return apply_filters('eventpost_retreive', $ob);
    }

    /** ADMIN ISSUES * */

    /**
     * set_shortcode_ui
     * needs Shortcake (shortcode UI) plugin
     * https://wordpress.org/plugins/shortcode-ui/
     */
    public function shortcode_ui(){
	if(!function_exists('shortcode_ui_register_for_shortcode')){
	    return;
	}
	$shortcodes_list_atts=array(
            'label' => __('Events list','eventpost'),
            'listItemImage' => 'dashicons-calendar',
	    'post_type'=>array('page','post'),
            'attrs' => array(
                0=>array(
                    'label'       => __('Number of posts','eventpost'),
                    'attr'        => 'nb',
                    'type'        => 'number',
		    'description' => __('-1 is for: no limit','eventpost')
                ),
                1=>array(
                    'label'       => __('Categories','eventpost'),
                    'attr'        => 'cat',
                    'type'        => 'text',
                ),
                2=>array(
                    'label'       => __('Tags','eventpost'),
                    'attr'        => 'tag',
                    'type'        => 'text',
                ),
                3=>array(
                    'label' =>  __('Future events:','eventpost'),
                    'attr'  => 'future',
                    'type'  => 'select',
		    'options' => array(
			'1' => __('Yes','eventpost'),
			'0' => __('No','eventpost'),
		    ),
                ),
                4=>array(
                    'label' =>  __('Past events:','eventpost'),
                    'attr'  => 'past',
                    'type'  => 'select',
		    'options' => array(
			'1' => __('Yes','eventpost'),
			'0' => __('No','eventpost'),
		    ),
                ),
                5=>array(
                    'label' =>  __('Only geotagged events:','eventpost'),
                    'attr'  => 'geo',
                    'type'  => 'select',
		    'options' => array(
			'1' => __('Yes','eventpost'),
			'0' => __('No','eventpost'),
		    ),
                ),
                6=>array(
                    'label' =>  __('Thumbnail:','eventpost'),
                    'attr'  => 'thumbnail',
                    'type'  => 'select',
		    'options' => array(
			'1' => __('Yes','eventpost'),
			'0' => __('No','eventpost'),
		   ),
                ),
                7=>array(
                    'label' =>  __('Thumbnail size:','eventpost'),
                    'attr'  => 'thumbnail_size',
                    'type'  => 'select',
		    'options' => array(
			'thumbnail' => __('Thumbnail'),
			'medium' => __('Medium'),
			'large' => __('Large'),
		    ),
                ),
                8=>array(
                    'label' =>  __('Order by:','eventpost'),
                    'attr'  => 'orderby',
                    'type'  => 'select',
		    'options' => array(
			'meta_value' => __('Date'),
			'title' => __('Title'),
		    ),
                ),
                9=>array(
                    'label' =>  __('Order:','eventpost'),
                    'attr'  => 'order',
                    'type'  => 'select',
		    'options' => array(
			'ASC' => __('Asc.'),
			'DESC' => __('Desc.'),
		    ),
                ),
            ),
        );
	/*
	 * Map
	 */
	$shortcodes_map_atts = $shortcodes_list_atts;
	unset($shortcodes_map_atts['attrs'][5]); // Remove geotagged attr
	unset($shortcodes_map_atts['attrs'][8]); // Remove orderby attr
	unset($shortcodes_map_atts['attrs'][9]); // Remove order attr
	array_unshift($shortcodes_map_atts['attrs'],
		array(
                    'label'       => __('Width','eventpost'),
                    'attr'        => 'width',
                    'type'        => 'text',
                ),
		array(
                    'label'       => __('Height','eventpost'),
                    'attr'        => 'height',
                    'type'        => 'text',
                )
	);
	$shortcodes_map_atts['label']=__('Events map','eventpost');
	$shortcodes_map_atts['listItemImage']='dashicons-location-alt';
        foreach($this->map_interactions as $int_key=>$int_name){
            $shortcodes_map_atts['attrs'][]=array(
                'label' => sprintf(__('Disable %s interaction','eventpost'), $int_name),
                'attr'  => 'disable_'.$int_key,
                'type'  => 'checkbox'
            );
        }
	shortcode_ui_register_for_shortcode('events_list', apply_filters('eventpost_shortcodeui_list',$shortcodes_list_atts));
	shortcode_ui_register_for_shortcode('events_map', apply_filters('eventpost_shortcodeui_map',$shortcodes_map_atts));

	/*
	 * Calendar
	 */
	$shortcodes_cal_atts=array(
            'label' => __('Events calendar','eventpost'),
            'listItemImage' => 'dashicons-calendar-alt',
	    'post_type'=>array('page','post'),
            'attrs' => array(
                array(
                    'label'       => __('Default date','eventpost'),
                    'attr'        => 'date',
                    'type'        => 'text',
		    'description' => date('Y-n')
                ),
                array(
                    'label'       => __('Categories','eventpost'),
                    'attr'        => 'cat',
                    'type'        => 'text',
                ),
                array(
                    'label'       => __('Monday first','eventpost'),
                    'attr'        => 'mondayfirst',
                    'type'        => 'checkbox',
                ),
                array(
                    'label'       => __('Date selector','eventpost'),
                    'attr'        => 'datepicker',
                    'type'        => 'checkbox',
                )
	    )
	);
	shortcode_ui_register_for_shortcode('events_cal', apply_filters('eventpost_shortcodeui_cal',$shortcodes_cal_atts));
	/*
	 * Details
	 */
	$shortcodes_details_atts=array(
            'label' => __('Event details','eventpost'),
            'listItemImage' => 'dashicons-clock',
            'attrs' => array(
                array(
                    'label' =>  __('Attribute','eventpost'),
                    'attr'  => 'attribute',
                    'type'  => 'select',
		    'options' => array(
			'' => __('Full details','eventpost'),
			'date' => __('Full date','eventpost'),
			'start' => __('Begin date','eventpost'),
			'end' => __('End date','eventpost'),
			'address' => __('Address text','eventpost'),
			'location' => __('Location','eventpost'),
		    ),
                ),
	    )
	);
	shortcode_ui_register_for_shortcode('event_details', apply_filters('eventpost_shortcodeui_details',$shortcodes_details_atts));

    }

    /**
     * @desc add custom boxes in posts edit page
     */
    public function add_custom_box() {
        foreach($this->settings['posttypes'] as $posttype){
            add_meta_box('event_post_date', __('Event date', 'eventpost'), array(&$this, 'inner_custom_box_date'), $posttype, $this->settings['adminpos'], 'core');
            add_meta_box('event_post_loc', __('Event location', 'eventpost'), array(&$this, 'inner_custom_box_loc'), $posttype, $this->settings['adminpos'], 'core');
        }
        if(!function_exists('shortcode_ui_register_for_shortcode')){
	    add_meta_box('event_post_sc_edit', __('Events Shortcode editor', 'eventpost'), array(&$this, 'inner_custom_box_edit'), 'page');
	}
    }
    /**
     * @desc disokay the date custom box
     */
    public function inner_custom_box_date() {
        wp_nonce_field(plugin_basename(__FILE__), 'eventpost_nonce');
        $post_id = get_the_ID();
        $event = $this->retreive($post_id);
        $start_date = $event->start;
        $end_date = $event->end;
        $eventcolor = $event->color;

        $language = get_bloginfo('language');
        if (strpos($language, '-') > -1) {
            $language = strtolower(substr($language, 0, 2));
        }
        $colors = $this->get_colors();
        if (sizeof($colors) > 0):
            ?>
            <div class="misc-pub-section event-color-section">
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
        <div class="misc-pub-section">
            <label for="<?php echo $this->META_START; ?>_date">
                <?php _e('Begin:', 'eventpost') ?>
                    <span id="<?php echo $this->META_START; ?>_date_human" class="human_date">
                        <?php
                        if ($event->time_start != '') {
                            echo $this->human_date($event->time_start) . date(' H:i', $event->time_start);
                        }
			else{
			    _e('Pick a date','eventpost');
			}
                        ?>
                    </span>
                <input type="<?php echo ($this->settings['datepicker']=='browser'?'datetime':''); ?>" class="eventpost-datepicker-<?php echo $this->settings['datepicker']; ?>" data-lang="<?php echo $language; ?>" value="<?php echo substr($start_date,0,16) ?>" name="<?php echo $this->META_START; ?>" id="<?php echo $this->META_START; ?>_date"/>
            </label>
	    <br>
            <label for="<?php echo $this->META_END; ?>_date">
                <?php _e('End:', 'eventpost') ?>
                    <span id="<?php echo $this->META_END; ?>_date_human" class="human_date">
                        <?php
                        if ($event->time_start != '') {
                            echo $this->human_date($event->time_end) . date(' H:i', $event->time_end);
                        }
			else{
			    _e('Pick a date','eventpost');
			}
                        ?>
                    </span>
                <input type="<?php echo ($this->settings['datepicker']=='browser'?'datetime':''); ?>" class="eventpost-datepicker-<?php echo $this->settings['datepicker']; ?>" data-lang="<?php echo $language; ?>"  value ="<?php echo substr($end_date,0,16) ?>" name="<?php echo $this->META_END; ?>" id="<?php echo $this->META_END; ?>_date"/>
            </label>
        </div>
        <?php
    }
    /**
     * @desc displays the location custom box
     */
    public function inner_custom_box_loc() {
        $post_id = get_the_ID();
        $event = $this->retreive($post_id);
        ?>
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
    }

    /**
     * @desc display custombox containing shortcode wizard
     */
    public function inner_custom_box_edit() {
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

    /**
     * @desc When the post is saved, saves our custom data
     * @param int $post_id
     * @return void
     */
    public function save_postdata($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE){
            return;
	}

        if (!wp_verify_nonce(filter_input(INPUT_POST, 'eventpost_nonce', FILTER_SANITIZE_STRING), plugin_basename(__FILE__))){
            return;
	}

        // Clean color or no color
        if (false !== $color = filter_input(INPUT_POST, $this->META_COLOR, FILTER_SANITIZE_STRING)) {
            update_post_meta($post_id, $this->META_COLOR, $color);
        }
        // Clean date or no date
	if ((false !== $start = filter_input(INPUT_POST, $this->META_START, FILTER_SANITIZE_STRING)) &&
	    (false !== $end = filter_input(INPUT_POST, $this->META_END, FILTER_SANITIZE_STRING)) &&
	    '' != $start &&
	    '' != $end) {
	    update_post_meta($post_id, $this->META_START, substr($start,0,16).':00');
	    update_post_meta($post_id, $this->META_END, substr($end,0,16).':00');
        }
	else {
	    delete_post_meta($post_id, $this->META_START);
	    delete_post_meta($post_id, $this->META_END);
	}

        // Clean location or no location
	if ((false !== $lat = filter_input(INPUT_POST, $this->META_LAT, FILTER_SANITIZE_STRING)) &&
	    (false !== $long = filter_input(INPUT_POST, $this->META_LONG, FILTER_SANITIZE_STRING)) &&
	    '' != $lat &&
	    '' != $long) {
	    update_post_meta($post_id, $this->META_ADD, filter_input(INPUT_POST, $this->META_ADD, FILTER_SANITIZE_STRING));
	    update_post_meta($post_id, $this->META_LAT, $lat);
	    update_post_meta($post_id, $this->META_LONG, $long);
	}
	else {
	    delete_post_meta($post_id, $this->META_ADD);
	    delete_post_meta($post_id, $this->META_LAT);
	    delete_post_meta($post_id, $this->META_LONG);
	}
    }

    /**
     *
     * @param string $date
     * @param string $cat
     * @param boolean $display
     * @return boolean
     */
    public function display_caldate($date, $cat = '', $display = false, $colored=true, $thumbnail='') {
        $events = $this->get_events(array('nb' => -1, 'date' => $date, 'cat' => $cat, 'retreive' => true));
        $nb = count($events);
        if ($display) {
            if ($nb > 0) {
                $ret.='<ul>';
                foreach ($events as $event) {
		    if ($this->settings['emptylink'] == 0 && empty($event->post_content)) {
			$event->guid = '#';
		    }
                    $ret.='<li>'
                            . '<a href="' . $event->guid . '">'
                            . '<h4>' . $event->post_title . '</h4>'
                            .$this->get_single($event)
                            . (!empty($thumbnail) ? '<span class="event_thumbnail_wrap">' . get_the_post_thumbnail($event->ID, $thumbnail) . '</span>' : '')
                            .'</a>'
                            . '</li>';
                }
                $ret.='</ul>';
                return $ret;
            }
            return'';
        } else {
            return $nb > 0 ? '<a data-date="' . date('Y-m-d', $date) . '" class="eventpost_cal_link"'.($colored?' style="background-color:#'.$events[0]->color.'"':'').'>' . date('j', $date) . '</a>' : date('j', $date);
        }
    }


    /**
     * @param array $atts
     * @filter eventpost_params
     * @return string
     */
    public function calendar($atts) {
        extract(shortcode_atts(apply_filters('eventpost_params', array(
            'date' => date('Y-n'),
            'cat' => '',
            'mondayfirst' => 0, //1 : weeks starts on monday
            'datepicker' => 1,
            'colored' => 1,
            'thumbnail'=>'',
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

                    $ret.= $this->display_caldate(mktime(0, 0, 0, $mois, $NoJour, $annee), $cat, false, $colored, $thumbnail);
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

    /**
     * @desc echoes the content of the calendar in ajax context
     */
    public function ajaxcal() {
        echo $this->calendar(array(
            'date' => esc_attr(FILTER_INPUT(INPUT_GET, 'date')),
            'cat' => esc_attr(FILTER_INPUT(INPUT_GET, 'cat')),
            'mondayfirst' => esc_attr(FILTER_INPUT(INPUT_GET, 'mf')),
            'datepicker' => esc_attr(FILTER_INPUT(INPUT_GET, 'dp')),
            'colored' => esc_attr(FILTER_INPUT(INPUT_GET, 'color')),
            'thumbnail' => esc_attr(FILTER_INPUT(INPUT_GET, 'thumbnail')),
        ));
        exit();
    }

    /**
     * @desc echoes the date of the calendar in ajax context
     */
    public function ajaxdate() {
        echo $this->display_caldate(strtotime(esc_attr(FILTER_INPUT(INPUT_GET, 'date'))), esc_attr(FILTER_INPUT(INPUT_GET, 'cat')), true, esc_attr(FILTER_INPUT(INPUT_GET, 'color')), esc_attr(FILTER_INPUT(INPUT_GET, 'thumbnail')));
        exit();
    }

    /**
     * @desc echoes a date in ajax context
     */
    public function HumanDate() {
        if (isset($_REQUEST['date']) && !empty($_REQUEST['date'])) {
            $date = strtotime($_REQUEST['date']);
            echo $this->human_date($date) . date(' H:i', $date);
            exit();
        }
    }

    /**
     * @desc AJAX Get lat long from address
     */
    public function GetLatLong() {
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

    /**
     * @desc alters columns
     * @param array $defaults
     * @return array
     */
    public function columns_head($defaults) {
        $defaults['event'] = __('Event', 'eventpost');
        $defaults['location'] = __('Location', 'eventpost');
        return $defaults;
    }

    /**
     * @desc echoes content of a row in a given column
     * @param string $column_name
     * @param int $post_id
     */
    public function columns_content($column_name, $post_id) {
        if ($column_name == 'location') {
            $lat = get_post_meta($post_id, $this->META_LAT, true);
            $lon = get_post_meta($post_id, $this->META_LONG, true);

            if (!empty($lat) && !empty($lon)) {
                $color = get_post_meta($post_id, $this->META_COLOR, true);
                if ($color == ''){
                    $color = '777777';
		}
                echo'<a href="https://www.openstreetmap.org/?lat=' . $lat.='&amp;lon=' . $lon.='&amp;zoom=13" target="_blank"><img src="' . plugins_url('/markers/', __FILE__) . $color . '.png" alt="' . get_post_meta($post_id, $this->META_ADD, true) . '"/></a>';
            }
        }
        if ($column_name == 'event') {
            echo $this->print_date($post_id, false);
        }
    }

    /** ADMIN PAGES **/

    /**
     * @desc save settings end redirect
     * @return void
     */
    public function save_settings(){
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

        $post_types=(array) $_POST['ep_settings']['posttypes'];
        $posttypes = get_post_types();
        foreach($post_types as $posttype){
            if(!in_array($posttype, $posttypes)){
                unset($post_types[$posttype]);
            }
        }

	if (false !== $settings = \filter_input_array(INPUT_POST,$valid_post)) {
	    $settings['ep_settings']['container_shema']=stripslashes($_POST['ep_settings']['container_shema']);
	    $settings['ep_settings']['item_shema']=  stripslashes($_POST['ep_settings']['item_shema']);
	    $settings['ep_settings']['posttypes']=  array_values($post_types);
	    update_option('ep_settings', $settings['ep_settings']);
	}
	wp_redirect('options-general.php?page=event-settings&confirm=options_saved');
	exit;
    }
    /**
     * @desc adds menu items
     */
    public function manage_options() {
        add_submenu_page('options-general.php', __('Event settings', 'eventpost'), __('Event settings', 'eventpost'), 'manage_options', 'event-settings', array(&$this, 'manage_settings'));
    }
    /**
     * @desc adds items to the native "right now" dashboard widget
     * @param array $elements
     * @return array
     */
    public function dashboard_right_now($elements){
	array_push($elements, '<i class="dashicons dashicons-calendar"></i> <i href="edit.php?post_type=post">'.sprintf(__('%d Events','eventpost'), count($this->get_events(array('future'=>1, 'past'=>1, 'nb'=>-1))))."</i>");
	array_push($elements, '<i class="dashicons dashicons-location"></i> <i href="edit.php?post_type=post">'.sprintf(__('%d Geolocalized events','eventpost'), count($this->get_events(array('future'=>1, 'past'=>1, 'geo'=>1, 'nb'=>-1))))."</i>");
	return $elements;
    }

    /**
     * @desc output content of the setting page
     */
    public function manage_settings() {
        if ('options_saved'===\filter_input(INPUT_GET,'confirm',FILTER_SANITIZE_STRING)) { ?>
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
                            <th><label for="ep_timeformatformat">
                                    <?php _e('Time format', 'eventpost') ?>
                                </label></th>
                            <td><input type="text" name="ep_settings[timeformat]" id="ep_dateformat" value="<?php echo $ep_settings['timeformat']; ?>"  class="widefat">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="ep_dateexport">
                                    <?php _e('Show export buttons on:', 'eventpost') ?>

                                </label></th>
                            <td><select name="ep_settings[export]" id="ep_dateexport" class="widefat">
                                    <option value="list" <?php selected($ep_settings['export'], 'list', true);?>>
					<?php _e('List only', 'eventpost') ?>
				    </option>
                                    <option value="single" <?php selected($ep_settings['export'], 'single', true);?>>
					<?php _e('Single only', 'eventpost') ?>
				    </option>
                                    <option value="both" <?php selected($ep_settings['export'], 'both', true);?>>
					<?php _e('Both', 'eventpost') ?>
				    </option>
                                    <option value="none" <?php selected($ep_settings['export'], 'none', true);?>>
					<?php _e('None', 'eventpost') ?>
				    </option>
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
                                        <option value="<?php echo $map['id']; ?>" <?php selected($ep_settings['tile'], $map['id'], true); ?>>
					    <?php echo $map['name']; ?>
					</option>
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
                                    <option value="1" <?php selected($ep_settings['emptylink'], 1, true);?>>
					<?php _e('Link all posts', 'eventpost'); ?>
				    </option>
                                    <option value="0" <?php selected($ep_settings['emptylink'], 0, true);?>>
					<?php _e('Do not link posts with empty content', 'eventpost'); ?>
				    </option>
                                </select></td>
                        </tr>
                        <tr>
                            <th><label for="ep_singlepos">
        <?php _e('Event bar position for single posts', 'eventpost') ?>

                                </label></th>
                            <td><select name="ep_settings[singlepos]" id="ep_singlepos" class="widefat">
                                    <option value="before" <?php selected($ep_settings['singlepos'], 'before', true); ?>>
					<?php _e('Before the content', 'eventpost'); ?>
				    </option>
                                    <option value="after" <?php selected($ep_settings['singlepos'], 'after', true); ?>>
					<?php _e('After the content', 'eventpost'); ?>
				    </option>
                                </select></td>
                        </tr>
                        <tr>
                            <th><label for="ep_loopicons">
        <?php _e('Add icons for events in the loop', 'eventpost') ?>
                                </label></th>
                            <td><select name="ep_settings[loopicons]" id="ep_loopicons" class="widefat">
                                    <option value="1" <?php selected($ep_settings['loopicons'],'1', true) ?>>
					<?php _e('Yes', 'eventpost'); ?></option>
                                    <option value="0" <?php selected($ep_settings['loopicons'],'0', true) ?>>
				        <?php _e('No', 'eventpost'); ?></option>
                                </select></td>
                        </tr>
			<tr>
                            <th><label for="ep_adminpos">
        <?php _e('Position of event details boxes', 'eventpost') ?>
                                </label></th>
                            <td><select name="ep_settings[adminpos]" id="ep_adminpos" class="widefat">
                                    <option value="side" <?php selected($ep_settings['adminpos'],'side', true) ?>>
					<?php _e('Side', 'eventpost'); ?></option>
                                    <option value="normal" <?php selected($ep_settings['adminpos'],'normal', true) ?>>
				        <?php _e('Under the text', 'eventpost'); ?></option>
                                </select></td>
                        </tr>

			<tr><td colspan="2">
                                <h3><?php _e('Admin UI settings', 'eventpost'); ?></h3>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="ep_posttypes">
        <?php _e('Wich post types can be events ?', 'eventpost') ?>
                                </label></th>
                            <td><?php $posttypes = get_post_types(array(), 'objects'); ?>
                                <?php foreach($posttypes as $posttype): ?>
                                <p><label>
                                    <input type="checkbox" name="ep_settings[posttypes][<?php echo $posttype->name; ?>]" value="<?php echo $posttype->name; ?>" <?php checked(in_array($posttype->name, $ep_settings['posttypes']),true, true) ?>>
					<?php echo $posttype->labels->name; ?></option>
                                </label></p>
                                <?php endforeach; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>
        <?php _e('Datepicker style', 'eventpost') ?>
                                <?php $now = date('Y-m-d H:i:s'); ?>
                                <?php $human_date = $this->human_date(current_time('timestamp')) . date(' H:i'); ?>
                            </th>
                            <td>
                                <p>
                                    <label><input type="radio" name="ep_settings[datepicker]" id="ep_datepicker_dual" value="dual" <?php checked($ep_settings['datepicker'],'dual', true) ?>>
                                        <?php _e('Dual', 'eventpost'); ?></option>
                                    </label>
                                    <span id="eventpost_dual_date_human" class="human_date">
                                         <?php echo $human_date; ?>
                                    </span>
                                    <input type="text" class="eventpost-datepicker-dual" id="eventpost_dual_date" value="<?php echo $now; ?>">
                                </p>
                                <p>
                                    <label><input type="radio" name="ep_settings[datepicker]" id="ep_datepicker_dual" value="separate" <?php checked($ep_settings['datepicker'],'separate', true) ?>>
                                        <?php _e('Separate', 'eventpost'); ?></option>
                                    </label>
                                    <span id="eventpost_separate_date_human" class="human_date">
                                         <?php echo $human_date; ?>
                                    </span>
                                    <input type="text" class="eventpost-datepicker-separate"  id="eventpost_separate_date" value="<?php echo $now; ?>">
                                </p>
                                <p>
                                    <label><input type="radio" name="ep_settings[datepicker]" id="ep_datepicker_dual" value="browser" <?php checked($ep_settings['datepicker'],'browser', true) ?>>
                                        <?php _e('Browser\'s style', 'eventpost'); ?></option>
                                    </label>
                                    <input type="datetime" class="eventpost-datepicker-browser" value="<?php echo $now; ?>">
                                </p>
                            </td>
                        </tr>

                        <tr><td colspan="2">
                                <h3><?php _e('Performances settings', 'eventpost'); ?></h3>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <label for="ep_cache">
                                    <input type="checkbox" name="ep_settings[cache]" id="ep_cache" <?php if($ep_settings['cache']=='1'){ echo'checked';} ?> value="1">
                                    <?php _e('Use cache for results','eventpost')?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2"><p class="submit"><input type="submit" value="<?php _e('Apply settings', 'eventpost'); ?>" class="button button-primary" id="submit" name="submit">			</p></td>
                        </tr>
                        <?php do_action('eventpost_settings_form'); ?>
                    </tbody>
                </table>
            </form>
        </div>
        <?php
        do_action('eventpost_after_settings_form');
    }

    /*
     * feed
     * generate ICS or VCS files from a category
     */

    /**
     *
     * @param timestamp $timestamp
     * @return string
     */
    public function ics_date($timestamp){
	return date("Ymd",$timestamp).'T'.date("His",$timestamp).'Z';
    }

    /**
     * @desc outputs an RSS document
     */
    public function feed(){
	if(false !== $cat=\filter_input(INPUT_GET, 'cat',FILTER_SANITIZE_STRING)){
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