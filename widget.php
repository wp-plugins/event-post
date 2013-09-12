<?php

/** Articles de catgorie **/
class eventpost_widget extends WP_Widget {
   function eventpost_widget() {
  	  parent::WP_Widget(false, __( 'Events', 'eventpost' ),array('description'=>__( 'List of future events posts', 'eventpost' )));
   }
   function widget($args, $instance) {
       extract( $args );
	   if(isset($instance['eventpost_numberposts']) && !empty($instance['eventpost_numberposts'])){ $eventpost_numberposts = $instance['eventpost_numberposts'];}else {$eventpost_numberposts = 3;}
       if(isset($instance['eventpost_widgettitle']) && !empty($instance['eventpost_widgettitle'])){$eventpost_widgettitle = $instance['eventpost_widgettitle'];}else {$eventpost_widgettitle = "";}
	   if(isset($instance['eventpost_cat']) && !empty($instance['eventpost_cat'])){$eventpost_cat = $instance['eventpost_cat'];}else {$eventpost_cat = "";}
		
		
		$events = EventPost::get_events(
			array(
				'nb'=>$eventpost_numberposts,
				'future'=>1,
				'past'=>0,
				'geo'=>0,
				'cat'=>$eventpost_cat
			)
		);
		
		if(sizeof($events)>0){
			echo $args['before_widget'];
			if(!empty($eventpost_widgettitle)){
				echo $args['before_title'];
				echo $eventpost_widgettitle;
				echo $args['after_title'];
			}			
			echo EventPost::list_events(array('events'=>$events));
			echo $args['after_widget'];		
		}		    
   }
   
   function update($new_instance, $old_instance) {
       return $new_instance;
   }

   function form($instance) {
 	    
	  /* Number of posts to display */
	  	if(isset($instance['eventpost_numberposts']) && !empty($instance['eventpost_numberposts'])){
	   	$eventpost_numberposts = esc_attr($instance['eventpost_numberposts']);}else {$eventpost_numberposts='';}

    
     /* The Widget Title Itself */
		if(isset($instance['eventpost_widgettitle']) && !empty($instance['eventpost_widgettitle'])){
		$eventpost_widgettitle = esc_attr($instance['eventpost_widgettitle']);}else { $eventpost_widgettitle='';}

	/* Search in a category */
		if(isset($instance['eventpost_cat']) && !empty($instance['eventpost_cat'])){
		$eventpost_cat = esc_attr($instance['eventpost_cat']);}else { $eventpost_cat='';}


      
       ?>
       <input type="hidden" id="<?php echo $this->get_field_id('eventpost_widgettitle'); ?>-title" value="<?php echo $eventpost_widgettitle; ?>">
       <p>
       <label for="<?php echo $this->get_field_id('eventpost_widgettitle'); ?>"><?php _e('Title','eventpost'); ?>
       <input class="widefat" id="<?php echo $this->get_field_id('eventpost_widgettitle'); ?>" name="<?php echo $this->get_field_name('eventpost_widgettitle'); ?>" type="text" value="<?php echo $eventpost_widgettitle; ?>" />
       </label>
       </p>       
     
       <p style="margin-top:10px;">
       <label for="<?php echo $this->get_field_id('eventpost_numberposts'); ?>"><?php _e('Number of posts','eventpost'); ?>
       <input class="widefat" id="<?php echo $this->get_field_id('eventpost_numberposts'); ?>" name="<?php echo $this->get_field_name('eventpost_numberposts'); ?>" type="number" value="<?php echo $eventpost_numberposts; ?>" />
       </label>
       </p>
       
       <p>
       	<label for="<?php echo $this->get_field_id('eventpost_cat'); ?>"><?php _e('Only in :','eventpost'); ?>
       	<select  id="<?php echo $this->get_field_id('eventpost_cat'); ?>" name="<?php echo $this->get_field_name('eventpost_cat'); ?>">
       		<option value=''><?php _e('All','eventpost') ?></option>
       <?php 
	   	$cats = get_categories();
		foreach($cats as $cat){ ?>
       	<option value="<?=$cat->slug?>" <?php if($cat->slug==$eventpost_cat){ echo'selected';} ?>><?=$cat->cat_name?></option>
       <?php  }  ?>
       </select>
       </label>
       </p>
       <?php
   }

}

function register_eventpost_widget(){
	register_widget('eventpost_widget');	
}

add_action('widgets_init', 'register_eventpost_widget');