<?php
/*
Event Post multisite support
Extends Event post to multisite networks
*/

if(is_multisite()){
   $EventPostMU=new EventPostMU();
}

class EventPostMU{
    function EventPostMU(){
        add_filter('eventpost_params',array(&$this,'params'),1,2);
        add_filter('eventpost_get',array(&$this,'get'),1,3);
        add_filter('eventpost_shortcodeui_list',array(&$this,'shortcode_ui'),1,1);
        add_filter('eventpost_shortcodeui_map',array(&$this,'shortcode_ui'),1,1);
    }
    function no_use(){
        __('Extends Event post to multisite networks','eventpost');
        __('Event Post multisite support','eventpost');
    }
    function params($param,$context){
        $param['blogs']='';
        return $param;
    }
    function shortcode_ui($param){
	$param['attrs'][]=array(
                    'label'       => __('Blogs','eventpost'),
                    'attr'        => 'blogs',
                    'type'        => 'text',
		    'description' => __('Blog\'s id, separated by comma. "all" for all blogs','eventpost')
                );
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
        //print_r($arg);
        $blog_ids=array();
        if($arg['blogs']=='all'){
            $blogs=wp_get_sites(array('limit'=>0));
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


        global $EventPost,$wpdb;

        $all_events=array();
        foreach ($blog_ids as $blog_id) {
            switch_to_blog($blog_id);
            $query = new WP_Query($requete);
            $events =  $wpdb->get_col($query->request);
            foreach($events as $k=>$post){
		$event = $EventPost->retreive($post);
                $all_events[($arg['orderby']!='meta_value' && isset($event->$arg['orderby'])?$event->$arg['orderby']:$event->time_start).'-'.$blog_id.'-'.$event->ID]=$event;
            }
            restore_current_blog();
        }
	if($arg['order']!=''){
	    $sort = $arg['order']=='DESC'?'krsort':'ksort';
	    $sort($all_events);
	}
        return $all_events;

    }
}


