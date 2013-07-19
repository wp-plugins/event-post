<?php
/*
Plugin Name: Event Post
Plugin URI: http://ecolosites.eelv.fr/articles-evenement-eventpost/
Description: Add calendar and/or geolocation metadata on posts
Version: 2.2.4
Author: bastho, n4thaniel, ecolosites // EÉLV
Author URI: http://ecolosites.eelv.fr/
License: CC BY-NC
Text Domain: eventpost
Domain Path: /languages/
Tags: Post,posts,event,date,geolocalization,gps,widget,map,openstreetmap, EELV
*/

load_plugin_textdomain( 'eventpost', false, 'event-post/languages' );	
	
add_filter('the_content',array('EventPost', 'display_single'));
add_action('the_event',array('EventPost', 'print_single'));
add_action( 'save_post', array( 'EventPost', 'save_postdata' ) );
add_action( 'admin_enqueue_scripts', array( 'EventPost', 'admin_head') );
add_action( 'admin_print_scripts', array( 'EventPost', 'admin_scripts') );
add_action( 'wp_head', array( 'EventPost', 'single_header') );
add_action('wp_enqueue_scripts', array( 'EventPost', 'load_scripts'));
add_shortcode('events_list',array( 'EventPost', 'shortcode_list'));
add_shortcode('events_map',array( 'EventPost', 'shortcode_map'));
add_action('admin_menu', array( 'EventPost', 'manage_options'));
add_action('wp_ajax_EventPostGetLatLong', array( 'EventPost', 'EventPostGetLatLong'));
add_action('wp_ajax_EventPostHumanDate', array( 'EventPost', 'EventPostHumanDate'));

add_action( 'add_meta_boxes', array('EventPost','add_custom_box') );
add_filter('manage_posts_columns', array( 'EventPost', 'columns_head'),2);  
add_action('manage_posts_custom_column', array( 'EventPost', 'columns_content'), 10, 2); 


include_once (plugin_dir_path(__FILE__).'widget.php');
	
class EventPost{
		
	const META_START ='event_begin';
	const META_END ='event_end';
	const META_COLOR='event_color';
	// http://codex.wordpress.org/Geodata
	const META_ADD = 'geo_address';
	const META_LAT = 'geo_latitude';
	const META_LONG = 'geo_longitude';
	static $list_id=0;
	
	
	function no_use(){
		__('Add calendar and/or geolocation metadata on posts','eventpost');
		__('Event Post','eventpost');
	}
	function get_settings(){
	  	$ep_settings = get_option( 'ep_settings' ,array());
		if(!isset($ep_settings['dateformat']) || empty($ep_settings['dateformat'])){
			$ep_settings['dateformat']=get_option('date_format');
		}
		if(!isset($ep_settings['tile']) || empty($ep_settings['tile'])){
			$maps = self::get_maps();
			$ep_settings['tile']=$maps[0]['id'];
		}
		return $ep_settings;
	}
	function get_maps(){
		$maps=array();			
       if(is_file(plugin_dir_path(__FILE__).'maps.csv')){
	   	$map_f = fopen(plugin_dir_path(__FILE__).'maps.csv','r');
		$map_s = explode("\n",fread($map_f,filesize(plugin_dir_path(__FILE__).'maps.csv')));
		foreach($map_s as $map){
			$map=explode(';',$map);
			if(sizeof($map>=5)){
				$maps[$map[1]]=array(
					'name'=>$map[0],
					'id'=>$map[1],
					'urls'=>array($map[2],$map[3],$map[4]),
				);
			}
		}
		
	   }	
	   return $maps;   
    }
	function get_colors(){
		$colors=array();
		$markpath = plugin_dir_path(__FILE__).'markers';
		if(is_dir($markpath)){
			$files = scandir($markpath);	
			foreach($files as $file){
				if(substr($file,-4)=='.png'){
					$colors[substr($file,0,-4)]=plugins_url('/markers/'.$file, __FILE__);
				}
			}		
		}
		return $colors;
	}
	function get_marker($color){
		$markpath = plugin_dir_path(__FILE__).'markers/';
		if(is_file($markpath.$color.'.png')){
			return 	plugins_url('/markers/'.$color.'.png', __FILE__);
		}
		return 	plugins_url('/markers/ffffff.png', __FILE__);
	}
	function load_scripts(){
		//CSS
		wp_register_style(
	        'eventpost',
	        plugins_url('/css/eventpost.css', __FILE__),
	        false,
	        1.0
	    );
		wp_enqueue_style('eventpost', plugins_url('/css/eventpost.css', __FILE__), false, null);
		
		// JS
		wp_enqueue_script('jquery',false,false,false,true);
		wp_enqueue_script('OpenLayers', plugins_url('/js/OpenLayers.js', __FILE__),false,false,true);
		wp_enqueue_script('eventpost', plugins_url('/js/eventpost.js', __FILE__), false,false,true);
		wp_localize_script('eventpost', 'eventpost_params', array(
			'imgpath' => plugins_url('/img/', __FILE__),
			'maptiles' => self::get_maps()
		));
	}
	function admin_head() {
		wp_enqueue_style('jquery-ui',plugins_url('/css/jquery-ui.css', __FILE__), false, null);
		wp_enqueue_style('eventpostadmin', plugins_url('/css/eventpostadmin.css', __FILE__), false, null);
	}
	function admin_scripts() {
		wp_enqueue_script('jquery-ui-datepicker');
		wp_enqueue_script('datetimepicker', plugins_url('/js/datetimepicker.js', __FILE__),false,false,true);
		wp_enqueue_script('osmadmin', plugins_url('/js/osm-admin.js', __FILE__),false,false,true);
		wp_localize_script('osmadmin', 'eventpost', array(
			'imgpath' => plugins_url('/img/', __FILE__),
			'META_START'=>self::META_START,
			'META_END'=>self::META_END,
			'META_ADD'=>self::META_ADD,
			'META_LAT'=>self::META_LAT,
			'META_LONG'=>self::META_LONG,
		));
	}
	function single_header(){
		if(is_single()){
			$post_id=get_the_ID();
			$address = get_post_meta($post_id, self::META_ADD, true);
			$lat = get_post_meta($post_id, self::META_LAT, true);
			$long = get_post_meta($post_id, self::META_LONG, true);
			if($address!='' || ($lat!='' && $long!='')){ ?>
	<meta name="geo.placename" content="<?php echo $address ?>" />
	<meta name="geo.position" content="<?php echo $lat ?>;<?php echo $long ?>" />
	<meta name="ICBM" content="<?php echo $lat ?>;<?php echo $long ?>" />								
			<?php }
			$start = get_post_meta($post_id, self::META_START, true);
			$end = get_post_meta($post_id, self::META_END, true);
			if($lat!='' && $end!=''){ ?>
	<meta name="datetime-coverage-start" content="<?php echo date('c',strtotime($start)) ?>" />
	<meta name="datetime-coverage-end" content="<?php echo date('c',strtotime($end)) ?>" />	
			<?php }
		}
	}
	
	function parsedate($date,$sep=''){
		if(!empty($date)){
			return substr($date,0,10).$sep.substr($date,11,8);
		}		
		else{
			return '';
		} 
	}
	function human_date($date,$format='l j F Y'){
		if(date('d/m/Y',$date)==date('d/m/Y')){
			return __('today','eventpost');
		}
		elseif(date('d/m/Y',$date)==date('d/m/Y', strtotime('+1 day'))){
			return __('tomorrow','eventpost');
		}
		elseif(date('d/m/Y',$date)==date('d/m/Y', strtotime('-1 day'))){
			return __('yesterday','eventpost');
		}
		return date_i18n($format ,$date);
	}
	function print_date($post_id=null,$links=true){
		$dates='';
		if($post_id==null) $post_id=get_the_ID();
		if(is_numeric($post_id)){
			$post = get_post($post_id);
			$start_date = get_post_meta($post_id, self::META_START, true);
			$end_date = get_post_meta($post_id, self::META_END, true);
			if($start_date!='' && $end_date!=''){
				
				$gmt_offset   = get_option('gmt_offset ');
				$timezone_string  = get_option('timezone_string');
				$codegmt=0;
				if($gmt_offset!=0 && substr($gmt_offset,0,1)!='-' && substr($gmt_offset,0,1)!='+'){
					$codegmt=$gmt_offset*-1;
					$gmt_offset='+'.$gmt_offset;
				}
				$ep_settings=self::get_settings(); 
				//Display dates
				
				$dd=strtotime($start_date);
				$df=strtotime($end_date);
				$dates.='<div class="event_date" data-start="'.self::human_date($dd).'" data-end="'.self::human_date($df).'">';
				
				if(date('d/m/Y',$dd)==date('d/m/Y',$df)){
					$dates.= '<span class="date">'.self::human_date($df)."</span>";
					if(date('H:i',$dd) != date('H:i',$df) && date('H:i',$dd)!='00:00' && date('H:i',$df)!='00:00'){
						$dates.='<span class="linking_word">, '.__('from:','eventpost').'</span> 
						<time class="time" itemprop="dtstart" datetime="'.date('c',$dd).'">'.date('H:i',$dd).'</time> 
						<span class="linking_word">'.__('to:','eventpost').'</span> 
						<time class="time" itemprop="dtend" datetime="'.date('c',$df).'">'.date('H:i',$df).'</time>';	
					}
					elseif( date('H:i',$dd)!='00:00'){
						$dates.='<span class="linking_word">,'.__('at:','eventpost').'</span>
						<time class="time" itemprop="dtstart" datetime="'.date('c',$dd).'">'.date('H:i',$dd).'</time>';	
					}
				  }
				  else{
					$dates.= '
					<span class="linking_word">'.__('from:','eventpost').'</span> 
					<time class="date" itemprop="dtstart" datetime="'.date('c',$dd).'">'.self::human_date($dd,$ep_settings['dateformat']).'</time> 
					<span class="linking_word">'.__('to:','eventpost').'</span> 
					<time class="date" itemprop="dtend" datetime="'.date('c',$df).'">'.self::human_date($df,$ep_settings['dateformat']).'</time>';
				  }	
				  
				  if($links==true && $df > time()){
					  // Export event
					  $title=urlencode($post->post_title);
					  $address = urlencode(get_post_meta($post_id,'geo_address',true));
					  $url = urlencode($post->guid);
					  
					  $mt = strtotime($codegmt.' Hours',$dd);
					  $d_s = date("Ymd",$mt).'T'.date("His",$mt);
					  $mte = strtotime($codegmt.' Hours',$df);
					  $d_e = date("Ymd",$mte).'T'.date("His",$mte);
					  $uid = $post_id.'-'.get_current_blog_id();
					  
					  // format de date ICS
					  $ics_url = plugins_url('export/ics.php',__FILE__).'?t='.$title.'&amp;u='.$uid.'&amp;sd='.$d_s.'&amp;ed='.$d_e.'&amp;a='.$address.'&amp;d='.$url.'&amp;tz=%3BTZID%3D'.urlencode($timezone_string);
					  
					  // format de date Google cal				  
					  $google_url='https://www.google.com/calendar/event?action=TEMPLATE&amp;text='.$title.'&amp;dates='.$d_s.'Z/'.$d_e.'Z&amp;details='.$url.'&amp;location='.$address.'&amp;trp=false&amp;sprop=&amp;sprop=name';
					  
					  // format de date VCS
					  $vcs_url = plugins_url('export/vcs.php',__FILE__).'?t='.$title.'&amp;u='.$uid.'&amp;sd='.$d_s.'&amp;ed='.$d_e.'&amp;a='.$address.'&amp;d='.$url.'&amp;tz=%3BTZID%3D'.urlencode($timezone_string);
		  
					  $dates.='
					  <a href="'.$ics_url.'" class="event_link ics" target="_blank" title="'.__('Download ICS file','eventpost').'">ical</a>
					  <a href="'.$google_url.'" class="event_link gcal" target="_blank" title="'.__('Add to Google calendar','eventpost').'">Google</a>
					  <a href="'.$vcs_url.'" class="event_link vcs" target="_blank" title="'.__('Add to Outlook','eventpost').'">outlook</a>
				  
					  ';
				  }
				$dates.='</div>';
			}
		}
		return $dates;
	}
	function print_location($post_id=null){
		$location='';
		if($post_id==null) $post_id=get_the_ID();
		$address = get_post_meta($post_id, self::META_ADD, true);
		$lat = get_post_meta($post_id, self::META_LAT, true);
		$long = get_post_meta($post_id, self::META_LONG, true);
		$color = get_post_meta($post_id, self::META_COLOR, true);

		if($address!='' || ($lat!='' && $long!='')){
			$location.='<address';
			if($lat!='' && $long!=''){
				$location.=' data-id="'.$post_id.'" data-latitude="'.$lat.'" data-longitude="'.$long.'" data-marker="'.self::get_marker($color).'" ';
			}
			$location.=' itemprop="adr"><span>'.$address.'</span>';
			if(is_single() && $lat!='' && $long!=''){
				$location.='<a class="event_link gps" href="http://www.openstreetmap.org/?lat='.$lat.='&amp;lon='.$long.='&amp;zoom=13" target="_blank"  itemprop="geo">'.__('Map','eventpost').'</a>';
			}
			$location.='</address>';
		}		
		
		return $location;
	}
	function print_categories($post_id=null){
		if($post_id==null) $post_id=get_the_ID();
		$cats='';
		$categories = get_the_category($post_id);
		if($categories){
			$cats.='<span class="event_category"';			
			$color = get_post_meta($post_id, self::META_COLOR, true);
			if($color!=''){
				$cats.=' style="color:#'.$color.'"';
			}
			$cats.='>';
			foreach($categories as $category) {
				$cats .= $category->name.' ';
			}
			$cats.='</span>';
		}			
		return $cats;
	}
	
	// Generate, return or output date event datas
	function get_single($post_id=null,$class=''){
		$datas_date = self::print_date($post_id);
		$datas_cat = self::print_categories($post_id);
		$datas_loc = self::print_location($post_id);
		if($datas_date!='' && $datas_loc!=''){
			return '<div class="event_data '.$class.'" itemscope itemtype="http://microformats.org/profile/hcard">'.$datas_date.$datas_cat.$datas_loc.'</div>';
		}
		return '';
	}
	function get_singledate($post_id=null,$class=''){
		return '<div class="event_data event_date '.$class.'" itemscope itemtype="http://microformats.org/profile/hcard">'.self::print_date($post_id).'</div>';
	}
	
	function get_singlecat($post_id=null,$class=''){
		return '<div class="event_data event_category '.$class.'" itemscope itemtype="http://microformats.org/profile/hcard">'.self::print_categories($post_id).'</div>';
	}
	function get_singleloc($post_id=null,$class=''){
		return '<div class="event_data event_location '.$class.'" itemscope itemtype="http://microformats.org/profile/hcard">'.self::print_location($post_id).'</div>';
	}
	function display_single($content){
		if(is_single()){
			if(!isset($post_id)) $post_id=get_the_ID();
			$datas = self::get_single($post_id,'event_single');
			if($datas!=''){
				return $content.$datas;
			}
			
		}
		return $content;
	}
	function print_single($post_id=null){
		echo self::get_single($post_id);
	}
	// Shortcode to display a list of events
	function shortcode_list($atts){
		$atts=shortcode_atts(array(
		      'nb'=>0,
		      'type'=>'div',
		      'future' => true,
		      'past' => false,
		      'geo' => 0,
		      'width'=>'100%',
		      'height'=>'auto',
		      'title'=>'',
		      'before_title'=>'<h3>',
		      'after_title'=>'</h3>',
		      'cat'=>''
	     ), $atts);
		 return EventPost::list_events($atts);
	}
	// Shortcode to display a map of events
	function shortcode_map($atts){
		$ep_settings = self::get_settings();
		$atts=shortcode_atts(array(
		      'nb'=>0,
		      'future' => true,
		      'past' => false,
		      'width'=>'100%',
		      'height'=>'400px',
		      'tile'=>$ep_settings['tile'],
		      'title'=>'',
		      'before_title'=>'<h3>',
		      'after_title'=>'</h3>',
		      'cat'=>''
	     ), $atts);
		 $atts['geo']=1;
		 $atts['type']='div';
	     return EventPost::list_events($atts,'event_geolist');//$nb,'div',$future,$past,1,'event_geolist');
	}
	// Return an HTML list of events
	function list_events($atts,$id='event_list'){//$nb=0,$type='div',$future=1,$past=0,$geo=0,$id='event_list'){
		$ep_settings = self::get_settings();
		extract(shortcode_atts(array(
		      'nb'=>0,
		      'type'=>'div',
		      'future' => true,
		      'past' => false,
		      'geo' => 0,
		      'width'=>'100%',
		      'height'=>'auto',
		      'tile'=>$ep_settings['tile'],
		      'title'=>'',
		      'before_title'=>'<h3>',
		      'after_title'=>'</h3>',
		      'cat'=>'',
		      'events'=>''
	     ), $atts));
		if(!is_array($events)){		
			$events = self::get_events($nb,$future,$past,$geo,$cat);
		}
		$ret='';
		self::$list_id++;
		if(sizeof($events)>0){
			if(!empty($title)){
				$ret.= html_entity_decode($before_title).$title.html_entity_decode($after_title);
			}	
			$child=($type=='ol' || $type=='ul') ? 'li' : 'div';
			$ret.='<'.$type.' class="event_loop '.$id.'" id="'.$id.self::$list_id.'" style="width:'.$width.';height:'.$height.'" '.($id=='event_geolist' ? 'data-tile="'.$tile.'"' : '').'>';
			foreach($events as $item_id){ $post=get_post($item_id);
				$class=(strtotime(get_post_meta($item_id, self::META_END, true))>=time()) ? 'event_future' : 'event_past';
		 		$ret.='<'.$child.' class="event_item '.$class.'">
		 			<a href="'.get_permalink($item_id).'"><h5>'.$post->post_title.'</h5></a>		 			
		 			'.self::get_singledate($item_id).'
		 			'.self::get_singlecat($item_id).'
		 			'.self::get_singleloc($item_id).'
		 			</'.$child.'>';
			} 
			$ret.='</'.$type.'>';		
		}
		return $ret;
	}

	// Returns an array of post_ids wich are events
	function get_events($nb=5,$future=1,$past=0,$geo=0,$cat=''){
		wp_reset_query();
		
		$arg=array(
	   		'post_type'=>'post',
			'posts_per_page'=>$nb,
			'meta_key'=>EventPost::META_START,
			'orderby'=>'meta_value',
			'order'=>'ASC'
		);
		
		// CAT
		if($cat!=''){
			$arg['category_name'] = $cat;
		}
		// DATES
		$meta_query=array(
			  array(
		           'key' => EventPost::META_END,
		           'value' => '',
		           'compare' => '!='
		       ),
		      array(
		           'key' => EventPost::META_START,
		           'value' => '',
		           'compare' => '!='
		       )
		 );		
		if($future==0 && $past==0){
			  $meta_query=array();
			  $arg['meta_key']=null;
			  $arg['orderby']=null;
			  $arg['order']=null;
		}
		elseif($future==1 && $past==0){
			  $meta_query[]=array(
		           'key' => EventPost::META_END,
		           'value' => date('Y-m-d H:i:s'),
		           'compare' => '>=',
		           'type'=>'DATETIME'
		       );
		}
		elseif($future==0 && $past==1){
			  $meta_query[]=array(
		           'key' => EventPost::META_END,
		           'value' => date('Y-m-d H:i:s'),
		           'compare' => '<=',
		           'type'=>'DATETIME'
		       );
		}
		// GEO
		if($geo==1){
			$meta_query[]=array(
	           'key' => EventPost::META_LAT,
	           'value' => '',
	           'compare' => '!='
	       );
		   $meta_query[]=array(
	           'key' => EventPost::META_LONG,
	           'value' => '',
	           'compare' => '!='
	       );
		   $arg['meta_key']=EventPost::META_LAT;
		   $arg['orderby']='meta_value';
		   $arg['order']='DESC';
		}
		
	   
		$arg['meta_query']=$meta_query;
		$query = new WP_Query($arg);
		global $wpdb;
		$events =  $wpdb->get_col($query->request);	
			
		wp_reset_query();
		return $events;
	}

/** ADMIN ISSUES **/
	
	function add_custom_box() {
	    add_meta_box('event_post', __( 'Event datas', 'eventpost' ), array('EventPost','inner_custom_box'),'', 'side', 'core');
	}
	function inner_custom_box() {

		$post_id=get_the_ID();
		$start_date = get_post_meta($post_id, self::META_START, true);
		$end_date = get_post_meta($post_id, self::META_END, true);
		
		$start_date_to_print=self::parsedate($start_date,'T');
		$end_date_to_print=self::parsedate($end_date,'T');	
		
		$eventcolor = get_post_meta($post_id,self::META_COLOR,true);
		
		$language = get_bloginfo('language');
		if(strpos($language,'-')>-1){
			$language=strtolower(substr($language,0,2));
		}
		?>
		<b><?php _e( 'Date:', 'eventpost' ) ?></b>
		<div class="misc-pub-section">
			<label for="<?php echo self::META_START; ?>">
				<?php _e( 'Begin:', 'eventpost' ) ?>
				<span class="human_date"></span>
				<input id="<?php echo self::META_START; ?>" type="datetime-local" data-language="<?php echo $language; ?>" value ="<?php echo $start_date_to_print ?>" name="<?php echo self::META_START; ?>" id="<?php echo self::META_START; ?>"/>
			</label>  
		</div>
		<div class="misc-pub-section">
			<label for="<?php echo self::META_END; ?>">
				<?php _e( 'End:', 'eventpost' ) ?>
				<span class="human_date"></span>
				<input id="<?php echo self::META_END; ?>" type="datetime-local" data-language="<?php echo $language; ?>"  value ="<?php echo $end_date_to_print ?>" name="<?php echo self::META_END; ?>" id="<?php echo self::META_END; ?>"/>        
			</label> 
		</div>
		<?php $colors = self::get_colors(); if(sizeof($colors)>0): ?>
		<div class="misc-pub-section">
			<?php _e( 'Color:', 'eventpost' ); ?>
			<p>
			<?php foreach ($colors as $color=>$file): ?>	
				<label style="background:#<?php echo $color ?>" for="<?php echo self::META_COLOR; ?><?php echo $color ?>">		
				<input type="radio" value ="<?php echo $color ?>" name="<?php echo self::META_COLOR; ?>" id="<?php echo self::META_COLOR; ?><?php echo $color ?>" <?php if($eventcolor==$color){ echo 'checked';} ?>/>
				</label>	
			<?php endforeach; ?> 
			</p>        
		</div>
		<?php endif; ?>
		
		
		<b><?php _e( 'Location:', 'eventpost' ) ?></b>
		<div class="misc-pub-section">
			<label for="<?php echo self::META_ADD; ?>">
				<?php _e( 'Address:', 'eventpost' ) ?>
				<textarea name="<?php echo self::META_ADD; ?>" id="<?php echo self::META_ADD; ?>"><?php echo get_post_meta($post_id, self::META_ADD, true) ?></textarea>
			</label> 
			<a id="event_address_search">?</a>
			<div id="eventaddress_result"></div> 
		</div>
		<div class="misc-pub-section">
			<label for="<?php echo self::META_LAT; ?>">
				<?php _e( 'Latitude:', 'eventpost' ) ?>
				<input type="text" value ="<?php echo get_post_meta($post_id, self::META_LAT, true) ?>" name="<?php echo self::META_LAT; ?>" id="<?php echo self::META_LAT; ?>"/>
			</label>  
		</div>
		<div class="misc-pub-section">
			<label for="<?php echo self::META_LONG; ?>">
				<?php _e( 'Longitude:', 'eventpost' ) ?>
				<input type="text" value ="<?php echo get_post_meta($post_id, self::META_LONG, true) ?>" name="<?php echo self::META_LONG; ?>" id="<?php echo self::META_LONG; ?>"/>
			</label>  
		</div>
  <?php
  		wp_nonce_field( plugin_basename( __FILE__ ), 'agenda_noncename' );
	}
	
	/* When the post is saved, saves our custom data */
	function save_postdata( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
	     return;
	
	  if ( isset($_POST['agenda_noncename']) && !wp_verify_nonce( $_POST['agenda_noncename'], plugin_basename( __FILE__ ) ) )
	   return ;
	  
	// Clean color or no color
	  if(isset($_POST[self::META_COLOR]) && !empty($_POST[self::META_COLOR])){
			update_post_meta($post_id,self::META_COLOR,$_POST[self::META_COLOR]);
	  }	
	// Clean date or no date
		if(isset($_POST[self::META_START]) && isset($_POST[self::META_END])){
			if(!empty($_POST[self::META_START]) && !empty($_POST[self::META_END])){
				update_post_meta($post_id,self::META_START,$_POST[self::META_START]);
				update_post_meta($post_id,self::META_END,$_POST[self::META_END]);
			}
			else{
				delete_post_meta($post_id,self::META_START);
				delete_post_meta($post_id,self::META_END);
			}
		}
		
	// Clean location or no location
		if(isset($_POST[self::META_LAT]) && isset($_POST[self::META_LONG])){		
			if(!empty($_POST[self::META_LAT]) && !empty($_POST[self::META_LONG])){
				update_post_meta($post_id,self::META_ADD,$_POST[self::META_ADD]);
				update_post_meta($post_id,self::META_LAT,$_POST[self::META_LAT]);
				update_post_meta($post_id,self::META_LONG,$_POST[self::META_LONG]);
			}
			else{
				delete_post_meta($post_id,self::META_ADD);
				delete_post_meta($post_id,self::META_LAT);
				delete_post_meta($post_id,self::META_LONG);
			}
		}
	}

	/** AJAX Get lat long from address */
	function EventPostHumanDate(){
		if(isset($_REQUEST['date']) && !empty($_REQUEST['date'])){
			$date = strtotime($_REQUEST['date']);
			echo self::human_date($date).date(' H:i',$date);
			exit();
		}
	}
	function EventPostGetLatLong(){
		if(isset($_REQUEST['q']) && !empty($_REQUEST['q'])){
			// verifier le cache
			$q = $_REQUEST['q'];
			header('Content-Type: application/json');
			$transient_name = 'eventpost_osquery_'.$q;
			$val = get_transient($transient_name);
			if (false === $val || empty($val) ) {
				$language = get_bloginfo('language');
				if(strpos($language,'-')>-1){
					$language=strtolower(substr($language,0,2));
				}
				$val  = file_get_contents('http://nominatim.openstreetmap.org/search?q='.urlencode($q).'&format=json&accept-language='.$language);
				set_transient($transient_name,$val,30*DAY_IN_SECONDS);
			}
			echo $val;
			exit();
		}
	}
	
	// ADD COLUMNS
  function columns_head($defaults) {  
    $defaults['event'] = __('Event','eventpost'); 
    $defaults['location'] = __('Location','eventpost'); 
    return $defaults;  
  }  
  // COLUMN CONTENT  (ARCHIVES) 
  function columns_content($column_name, $post_id) {  
    if ($column_name == 'location') {  
      $lat = get_post_meta($post_id, self::META_LAT, true);
      $lon = get_post_meta($post_id, self::META_LONG, true);
	  
      if(!empty($lat) && !empty($lon)){
	  	$color=get_post_meta($post_id,self::META_COLOR,true);
      	if($color=='') $color='777777';
		echo'<a href="http://www.openstreetmap.org/?lat='.$lat.='&amp;lon='.$lon.='&amp;zoom=13" target="_blank"><img src="'.plugins_url('/markers/', __FILE__).$color.'.png" alt="'.get_post_meta($post_id,self::META_ADD,true).'"/></a>';
      }
    }
    if ($column_name == 'event') {  
       echo self::print_date($post_id,false);
    }  
  }
  
  
  /** ADMIN PAGES **/
  function manage_options(){
  	add_submenu_page('options-general.php', __('Event settings', 'eventpost' ), __('Event settings', 'eventpost' ), 'manage_options', 'event-settings', array( 'EventPost', 'manage_settings')); 
  }
  
  function manage_settings(){
  	if( isset($_POST[ 'ep_settings' ])) {
        update_option( 'ep_settings', $_POST[ 'ep_settings' ]);
		?>
		<div class="updated"><p><strong><?php _e('Event settings saved !','eventpost')?></strong></p></div>
		<?php
    }
	$ep_settings=self::get_settings(); 
  	?>
  	<div class="wrap">
  	<div class="icon32" id="icon-options-general"><br></div>
  	<h2><?php _e('Event settings', 'eventpost' ); ?></h2>
  	<form name="form1" method="post" action="#">
	  	<h3><?php _e('Event settings', 'eventpost' ); ?></h3>
	  	<p>
			<label for="ep_dateformat">
				<?php _e('Date format','eventpost')?>
				<input type="text" name="ep_settings[dateformat]" id="ep_dateformat" value="<?php echo $ep_settings['dateformat'];  ?>" />
			</label>
		</p>
	  	<h3><?php _e('Map settings', 'eventpost' ); ?></h3>  	
  		<p>
			<label for="ep_tile">
				<?php _e('Map background','eventpost')?>
				<select name="ep_settings[tile]" id="ep_tile">
					<?php $maps = self::get_maps(); foreach($maps as $id=>$map): ?>
				    <option value="<?php echo $map['id']; ?>" <?php if($ep_settings['tile']==$map['id']){ echo'selected';} ?>><?php echo $map['name']; ?></option>
				    <?php endforeach; ?>
				</select>
			</label>
		</p>
		<p class="submit">
			<input type="submit" value="<?php _e('Apply settings', 'eventpost' ); ?>" class="button button-primary" id="submit" name="submit">			
		</p>
  	</form>
  	</div>
  	<?php
  }

}
