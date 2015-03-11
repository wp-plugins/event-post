<?php

/** Articles de catgorie **/
class eventpostcal_widget extends WP_Widget {
   function eventpostcal_widget() {
  	  parent::WP_Widget(false, __( 'Events calendar', 'eventpost' ),array('description'=>__( 'Calendar presentation of events posts', 'eventpost' )));
   }
   function widget($args, $instance) {
       extract( $args );
	$title = isset($instance['title']) && !empty($instance['title'])?esc_attr($instance['title']):'';
	$cat = isset($instance['cat']) && !empty($instance['cat'])?esc_attr($instance['cat']):'';
	$date = isset($instance['date']) && !empty($instance['date'])?date('Y-n',strtotime(esc_attr($instance['date']))):date('Y-n');
	$mf = isset($instance['mf']) && is_numeric($instance['mf'])?esc_attr($instance['mf']):0;
	$choose = isset($instance['choose']) && is_numeric($instance['choose'])?esc_attr($instance['choose']):1;

	global $EventPost;
	$events = $EventPost->get_events(
	    array(
		    'nb'=>-1,
		    'future'=>1,
		    'past'=>1,
		    'geo'=>0,
		    'cat'=>$cat
	    )
	);
	if(sizeof($events)>0){
	    $EventPost->load_scripts();
	    echo $args['before_widget'];
	    if(!empty($title)){
		    echo $args['before_title'];
		    echo $title;
		    echo $args['after_title'];
	    }
	    echo '<div class="eventpost_calendar" data-cat="'.$cat.'" data-date="'.$date.'" data-mf="'.$mf.'" data-dp="'.$choose.'"></div>';
	    echo $args['after_widget'];
	}
   }

   function update($new_instance, $old_instance) {
       return $new_instance;
   }

   function form($instance) {

     /* The Widget Title Itself */
		$title = isset($instance['title']) && !empty($instance['title'])?esc_attr($instance['title']):'';

	/* Search in a category */
		$cate = isset($instance['cat']) && !empty($instance['cat'])?esc_attr($instance['cat']):'';


	/* Date */
		$date = isset($instance['date']) && !empty($instance['date'])?esc_attr($instance['date']):'';


	/* Monday first */
		$mf = isset($instance['mf']) && is_numeric($instance['mf'])?esc_attr($instance['mf']):0;


	/* Date picker */
		$choose = isset($instance['choose']) && is_numeric($instance['choose'])?esc_attr($instance['choose']):1;



       ?>
       <input type="hidden" id="<?php echo $this->get_field_id('title'); ?>-title" value="<?php echo $title; ?>">
       <p>
       <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title','eventpost'); ?>
       <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
       </label>
       </p>

       <p>
       	<label for="<?php echo $this->get_field_id('cat'); ?>"><?php _e('Only in :','eventpost'); ?>
       	<select  id="<?php echo $this->get_field_id('cat'); ?>" name="<?php echo $this->get_field_name('cat'); ?>">
       		<option value=''><?php _e('All','eventpost') ?></option>
       <?php
	   	$cats = get_categories();
		foreach($cats as $cat){ ?>
       	<option value="<?=$cat->slug?>" <?php if($cat->slug==$cate){ echo'selected';} ?>><?=$cat->cat_name?></option>
       <?php  }  ?>
       </select>
       </label>
       </p>

        <p>
       <label for="<?php echo $this->get_field_id('date'); ?>"><?php _e('Date','eventpost'); ?>
       <select id="<?php echo $this->get_field_id('date'); ?>"  name="<?php echo $this->get_field_name('date'); ?>">
       	<option value='' <?=$date==''?'selected':''?>><?php _e('Current','eventpost'); ?></option>
       	<option value='-1 Month' <?=$date=='-1 Month'?'selected':''?>><?php _e('-1 Month','eventpost'); ?></option>
       	<option value='-2 Months' <?=$date=='-2 Months'?'selected':''?>><?php _e('-2 Months','eventpost'); ?></option>
       	<option value='+1 Month' <?=$date=='+1 Month'?'selected':''?>><?php _e('+1 Month','eventpost'); ?></option>
       	<option value='+2 Months' <?=$date=='+2 Months'?'selected':''?>><?php _e('+2 Months','eventpost'); ?></option>
       </select>
       </label>
       </p>

       <p>
       <label for="<?php echo $this->get_field_id('mf'); ?>"><?php _e('Weeks start on','eventpost'); ?>
       <select id="<?php echo $this->get_field_id('mf'); ?>" name="<?php echo $this->get_field_name('mf'); ?>">
       	<option value="0" <?=$mf==0?'selected':''?>><?php _e('Sunday','eventpost'); ?></option>
       	<option value="1" <?=$mf==1?'selected':''?>><?php _e('Monday','eventpost'); ?></option>
       </select>
       </label>
       </p>

       <p>
       <label for="<?php echo $this->get_field_id('choose'); ?>"><?php _e('Date picker','eventpost'); ?>
       <select id="<?php echo $this->get_field_id('choose'); ?>" name="<?php echo $this->get_field_name('choose'); ?>">
       	<option value="0" <?=$mf==0?'selected':''?>><?php _e('No','eventpost'); ?></option>
       	<option value="1" <?=$mf==1?'selected':''?>><?php _e('Yes','eventpost'); ?></option>
       </select>
       </label>
       </p>
       <?php
   }

}

function register_eventpostcal_widget(){
	register_widget('eventpostcal_widget');
}

add_action('widgets_init', 'register_eventpostcal_widget');