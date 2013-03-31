<?php

/** Articles de catgorie **/
class eventpost_widget extends WP_Widget {
   function eventpost_widget() {
  	  parent::WP_Widget(false, __( 'Events', 'eventpost' ));
   }
   function widget($args, $instance) {
       extract( $args );
	   if(isset($instance['eventpost_numberposts']) && !empty($instance['eventpost_numberposts'])){ $eventpost_numberposts = $instance['eventpost_numberposts'];}else {$eventpost_numberposts = 3;}
       if(isset($instance['eventpost_widgettitle']) && !empty($instance['eventpost_widgettitle'])){$eventpost_widgettitle = $instance['eventpost_widgettitle'];}else {$eventpost_widgettitle = "";}
		
		
		$events = EventPost::get_events($eventpost_numberposts);
		
		if(sizeof($events)>0){
			echo $args['before_widget'];
			echo $args['before_title'];
			echo $eventpost_widgettitle;
			echo $args['after_title'];
			echo EventPost::list_events($events);
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


      
       ?>
       <input type="hidden" id="<?php echo $this->get_field_id('eventpost_widgettitle'); ?>-title" value="<?php echo $eventpost_widgettitle; ?>">
       <p>
       <label for="<?php echo $this->get_field_id('eventpost_widgettitle'); ?>"><?php _e('Title','ecolosites'); ?>
       <input class="widefat" id="<?php echo $this->get_field_id('eventpost_widgettitle'); ?>" name="<?php echo $this->get_field_name('eventpost_widgettitle'); ?>" type="text" value="<?php echo $eventpost_widgettitle; ?>" />
       </label>
       </p>       
     
       <p style="margin-top:10px;">
       <label for="<?php echo $this->get_field_id('eventpost_numberposts'); ?>"><?php _e('Number of posts','ecolosites'); ?>
       <input class="widefat" id="<?php echo $this->get_field_id('eventpost_numberposts'); ?>" name="<?php echo $this->get_field_name('eventpost_numberposts'); ?>" type="number" value="<?php echo $eventpost_numberposts; ?>" />
       </label>
       </p>
       <?php
   }

}

function register_eventpost_widget(){
	register_widget('eventpost_widget');	
}

add_action('widgets_init', 'register_eventpost_widget');