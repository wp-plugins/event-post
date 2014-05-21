<?php
/*
Plugin Name: Event Post multisite support
Plugin URI: http://ecolosites.eelv.fr/articles-evenement-eventpost/
Description: Extends Event post to multisite networks
Version: 2.7.0
Author: bastho, ecolosites // EÉLV
Author URI: http://ecolosites.eelv.fr/
License: GPLv2
Text Domain: eventpost
Domain Path: /languages/
Tags: Post,posts,event,date,geolocalization,gps,widget,map,openstreetmap, EELV, multisite,network
*/

if(is_multisite()){
   $EventPostMU=new EventPostMU(); 
}

class EventPostMU{
    function EventPostMU(){
        add_filter('eventpost_params',array(&$this,'params'),1,2);
        add_filter('eventpost_get',array(&$this,'get'),1,3);
    }
    function no_use(){
        __('Extends Event post to multisite networks','eventpost');
        __('Event Post multisite support','eventpost');
    }
    function params($param,$context){
        $param['blogs']='';
        return $param;
    }
	/*
	 * function get
	 * @filter eventpost_multisite_get
	 * @filter eventpost_multisite_blogids
	 * @return array of events
	 * 
	 */
    function get($empty,$arg,$requete){
    	$is_result=apply_filters('eventpost_multisite_get',$empty,$arg,$requete);
		if($is_result!=$empty)
			return $is_result;
		
        if(!is_array($arg) || !isset($arg['blogs']) || ''==$arg['blogs'])        
            return $empty;
        
        $blog_ids=array();
        if($arg['blogs']=='all'){
            $blogs=wp_get_sites();
            foreach ($blogs as $blog) {
               $blog_ids[]=$blog['blog_id']; 
            }
        }
        elseif(!empty($arg['blogs'])){
            $blog_ids=apply_filters('eventpost_multisite_blogids',explode(',',$arg['blogs']));
        }
        else{
            return $empty;
        }        
        
        global $EventPost;
        
        $all_events=array();
        foreach ($blog_ids as $blog_id) {
            switch_to_blog($blog_id);
            $query = new WP_Query($requete);
            global $wpdb;
            $events =  $wpdb->get_col($query->request);
            foreach($events as $k=>$event){                 
              $all_events[]=$EventPost->retreive($event);
            }
            restore_current_blog();
        }
        return $all_events;
        
    }
}


