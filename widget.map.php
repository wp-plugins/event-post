<?php

/** Articles de catgorie **/
class eventpostmap_widget extends WP_Widget {
   function eventpostmap_widget() {
  	  parent::WP_Widget(false, __( 'Events map', 'eventpost' ),array('description'=>__( 'Map of events posts', 'eventpost' )));
   }
   function widget($args, $instance) {
       extract( $args );
	   if(isset($instance['numberposts']) && !empty($instance['numberposts'])){ $numberposts = $instance['numberposts'];}else {$numberposts = 3;}
       if(isset($instance['widgettitle']) && !empty($instance['widgettitle'])){$widgettitle = $instance['widgettitle'];}else {$widgettitle = "";}
	   if(isset($instance['cat']) && !empty($instance['cat'])){$cat = $instance['cat'];}else {$cat = "";}
		
		global $EventPost;
		$events = $EventPost->get_events(
			array(
				'nb'=>$numberposts,
				'future'=>1,
				'past'=>1,
				'geo'=>1,
				'cat'=>$cat
			)
		);
		
		if(sizeof($events)>0){
			echo $args['before_widget'];
			if(!empty($widgettitle)){
				echo $args['before_title'];
				echo $widgettitle;
				echo $args['after_title'];
			}			
            $atts=array(
                'events'=>$events,
                'width'=>'',
                'height'=>'',
                'geo'=>1,
                'class'=>'eventpost_widget'
            );
			echo $EventPost->list_events($atts,'event_geolist');
			echo $args['after_widget'];		
		}		    
   }
   
   function update($new_instance, $old_instance) {
       return $new_instance;
   }

   function form($instance) {
 	    
	  /* Number of posts to display */
	  	if(isset($instance['numberposts']) && !empty($instance['numberposts'])){
	   	$numberposts = esc_attr($instance['numberposts']);}else {$numberposts='';}

    
     /* The Widget Title Itself */
		if(isset($instance['widgettitle']) && !empty($instance['widgettitle'])){
		$widgettitle = esc_attr($instance['widgettitle']);}else { $widgettitle='';}

	/* Search in a category */
		if(isset($instance['cat']) && !empty($instance['cat'])){
		$cat = esc_attr($instance['cat']);}else { $cat='';}


      
       ?>
       <input type="hidden" id="<?php echo $this->get_field_id('widgettitle'); ?>-title" value="<?php echo $widgettitle; ?>">
       <p>
       <label for="<?php echo $this->get_field_id('widgettitle'); ?>"><?php _e('Title','eventpost'); ?>
       <input class="widefat" id="<?php echo $this->get_field_id('widgettitle'); ?>" name="<?php echo $this->get_field_name('widgettitle'); ?>" type="text" value="<?php echo $widgettitle; ?>" />
       </label>
       </p>       
     
       <p style="margin-top:10px;">
       <label for="<?php echo $this->get_field_id('numberposts'); ?>"><?php _e('Number of posts','eventpost'); ?>
       <input class="widefat" id="<?php echo $this->get_field_id('numberposts'); ?>" name="<?php echo $this->get_field_name('numberposts'); ?>" type="number" value="<?php echo $numberposts; ?>" />
       </label>
       </p>
       
       <p>
       	<label for="<?php echo $this->get_field_id('cat'); ?>"><?php _e('Only in :','eventpost'); ?>
       	<select  id="<?php echo $this->get_field_id('cat'); ?>" name="<?php echo $this->get_field_name('cat'); ?>">
       		<option value=''><?php _e('All','eventpost') ?></option>
       <?php 
	   	$cats = get_categories();
		foreach($cats as $cat){ ?>
       	<option value="<?=$cat->slug?>" <?php if($cat->slug==$cat){ echo'selected';} ?>><?=$cat->cat_name?></option>
       <?php  }  ?>
       </select>
       </label>
       </p>
       <?php
   }

}

function register_eventpostmap_widget(){
	register_widget('eventpostmap_widget');	
}

add_action('widgets_init', 'register_eventpostmap_widget');