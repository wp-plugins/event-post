<?php

/** Articles de catgorie **/
class eventpost_widget extends WP_Widget {
   function eventpost_widget() {
  	  parent::WP_Widget(false, __( 'Events', 'eventpost' ),array('description'=>__( 'List of future events posts', 'eventpost' )));
   }
   function widget($args, $instance) {
       extract( $args );
	/* Number of posts to display */
	$numberposts = isset($instance['numberposts']) && !empty($instance['numberposts'])?esc_attr($instance['numberposts']):'';
        /* The Widget Title Itself */
	$widgettitle = isset($instance['widgettitle']) && !empty($instance['widgettitle'])?esc_attr($instance['widgettitle']):'';
        /* Filter by category */
	$cat = isset($instance['cat']) && !empty($instance['cat'])?esc_attr($instance['cat']):'';
	/* Filter by tag */
	$tag = isset($instance['tag']) && !empty($instance['tag'])?esc_attr($instance['tag']):'';
        /*Search for future */
        $future=isset($instance['future']) && is_numeric($instance['future'])?esc_attr($instance['future']):0;
        /*Search for past */
        $past=isset($instance['past']) && is_numeric($instance['past'])?esc_attr($instance['past']):0;
        /*Display thumbnails */
        $thumbnail=isset($instance['thumbnail']) && is_numeric($instance['thumbnail'])?esc_attr($instance['thumbnail']):0;
        /*Display excerpt */
        $excerpt=isset($instance['excerpt']) && is_numeric($instance['excerpt'])?esc_attr($instance['excerpt']):0;
        /*Display excerpt */
        $feed=isset($instance['feed']) && is_numeric($instance['feed'])?esc_attr($instance['feed']):0;
        /*Date sorting */
        $order=isset($instance['order']) && !empty($instance['order'])?esc_attr($instance['order']):'ASC';

        global $EventPost;
	$events = $EventPost->get_events(
	    array(
		'nb'=>$numberposts,
		'future'=>$future,
		'past'=>$past,
		'geo'=>0,
		'cat'=>$cat,
		'tag'=>$tag,
		'order'=>$order
	    )
	);

	if(sizeof($events)>0){
	    echo $args['before_widget'];
	    if(!empty($widgettitle)){
		echo $args['before_title'];
		echo $widgettitle;
		if(!empty($cat) && $feed){
		    echo' <a href="'.admin_url('admin-ajax.php').'?action=EventPostFeed&cat='.$cat.'"><span class="dashicons dashicons-rss"></span></a>';
		}
		echo $args['after_title'];
	    }
	    $atts=array(
		'events'=>$events,
		'class'=>'eventpost_widget',
		'thumbnail'=>$thumbnail,
		'excerpt'=>$excerpt,
		'order'=>$order
	    );
	    echo $EventPost->list_events($atts);
	    echo $args['after_widget'];
	}
   }

   function update($new_instance, $old_instance) {
       return $new_instance;
   }

   function form($instance) {

	/* Number of posts to display */
	$numberposts = isset($instance['numberposts']) && !empty($instance['numberposts'])?esc_attr($instance['numberposts']):'';
        /* The Widget Title Itself */
	$widgettitle = isset($instance['widgettitle']) && !empty($instance['widgettitle'])?esc_attr($instance['widgettitle']):'';
        /* Filter by category */
	$cat = isset($instance['cat']) && !empty($instance['cat'])?esc_attr($instance['cat']):'';
	/* Filter by tag */
	$tag = isset($instance['tag']) && !empty($instance['tag'])?esc_attr($instance['tag']):'';
        /*Search for future */
        $future=isset($instance['future']) && is_numeric($instance['future'])?esc_attr($instance['future']):0;
        /*Search for past */
        $past=isset($instance['past']) && is_numeric($instance['past'])?esc_attr($instance['past']):0;
        /*Display thumbnails */
        $thumbnail=isset($instance['thumbnail']) && is_numeric($instance['thumbnail'])?esc_attr($instance['thumbnail']):0;
        /*Display excerpt */
        $excerpt=isset($instance['excerpt']) && is_numeric($instance['excerpt'])?esc_attr($instance['excerpt']):0;
        /*Display excerpt */
        $feed=isset($instance['feed']) && is_numeric($instance['feed'])?esc_attr($instance['feed']):0;
        /*Date sorting */
        $order=isset($instance['order']) && !empty($instance['order'])?esc_attr($instance['order']):'ASC';
       ?>
       <input type="hidden" id="<?php echo $this->get_field_id('widgettitle'); ?>-title" value="<?php echo $widgettitle; ?>">
       <p>
       <label for="<?php echo $this->get_field_id('widgettitle'); ?>"><?php _e('Title','eventpost'); ?>
       <input class="widefat" id="<?php echo $this->get_field_id('widgettitle'); ?>" name="<?php echo $this->get_field_name('widgettitle'); ?>" type="text" value="<?php echo $widgettitle; ?>" />
       </label>
       </p>

       <p style="margin-top:10px;">
       <label for="<?php echo $this->get_field_id('numberposts'); ?>"><?php _e('Number of posts','eventpost'); ?>
       <input id="<?php echo $this->get_field_id('numberposts'); ?>" name="<?php echo $this->get_field_name('numberposts'); ?>" type="number" value="<?php echo $numberposts; ?>" />
       </label> <?php _e('(-1 is no limit)','eventpost'); ?>
       </p>


       <p style="margin-top:10px;">
       <label for="<?php echo $this->get_field_id('future'); ?>">
       <input id="<?php echo $this->get_field_id('future'); ?>" name="<?php echo $this->get_field_name('future'); ?>" type="checkbox" value="1" <?=($future==true?'checked':'')?> />
       <?php _e('Display future events','eventpost'); ?>
       </label>
       </p>
       <p style="margin-top:10px;">
       <label for="<?php echo $this->get_field_id('past'); ?>">
       <input id="<?php echo $this->get_field_id('past'); ?>" name="<?php echo $this->get_field_name('past'); ?>" type="checkbox" value="1" <?=($past==true?'checked':'')?> />
       <?php _e('Display past events','eventpost'); ?>
       </label>
       </p>

       <p>
       	<label for="<?php echo $this->get_field_id('cat'); ?>">
            <span class="dashicons dashicons-category"></span>
                <?php _e('Only in :','eventpost'); ?>
       	<select  class="widefat" id="<?php echo $this->get_field_id('cat'); ?>" name="<?php echo $this->get_field_name('cat'); ?>">
       		<option value=''><?php _e('All categories','eventpost') ?></option>
       <?php
	   	$cats = get_categories();
		foreach($cats as $_cat){ ?>
       	<option value="<?=$_cat->slug?>" <?php if($_cat->slug==$cat){ echo'selected';} ?>><?=$_cat->cat_name?></option>
       <?php  }  ?>
       </select>
       </label>
       </p>

       <p style="margin-top:10px;">
       <label for="<?php echo $this->get_field_id('feed'); ?>">
       <input id="<?php echo $this->get_field_id('feed'); ?>" name="<?php echo $this->get_field_name('feed'); ?>" type="checkbox" value="1" <?=($feed==true?'checked':'')?> />
       <?php _e('Show category ICS link','eventpost'); ?>
       </label>
       </p>
       <hr>

       <p>
       	<label for="<?php echo $this->get_field_id('tag'); ?>">
            <span class="dashicons dashicons-tag"></span>
            <?php _e('Only in :','eventpost'); ?>
       	<select  class="widefat" id="<?php echo $this->get_field_id('tag'); ?>" name="<?php echo $this->get_field_name('tag'); ?>">
       		<option value=''><?php _e('All tags','eventpost') ?></option>
       <?php
	   	$tags = get_tags();
		foreach($tags as $_tag){?>
       	<option value="<?=$_tag->slug?>" <?php if($_tag->slug==$tag){ echo'selected';} ?>><?=$_tag->name?></option>
       <?php  }  ?>
       </select>
       </label>
       </p>

       <hr>
       <p style="margin-top:10px;">
       <label for="<?php echo $this->get_field_id('thumbnail'); ?>">
       <input id="<?php echo $this->get_field_id('thumbnail'); ?>" name="<?php echo $this->get_field_name('thumbnail'); ?>" type="checkbox" value="1" <?=($thumbnail==true?'checked':'')?> />
       <?php _e('Show thumbnails','eventpost'); ?>
       </label>
       </p>
       <p style="margin-top:10px;">
       <label for="<?php echo $this->get_field_id('excerpt'); ?>">
       <input id="<?php echo $this->get_field_id('excerpt'); ?>" name="<?php echo $this->get_field_name('excerpt'); ?>" type="checkbox" value="1" <?=($excerpt==true?'checked':'')?> />
       <?php _e('Show excerpt','eventpost'); ?>
       </label>
       </p>

       <p>
       	<label for="<?php echo $this->get_field_id('order'); ?>">
            <?php _e('Order :','eventpost'); ?>
       	<select  class="widefat" id="<?php echo $this->get_field_id('order'); ?>" name="<?php echo $this->get_field_name('order'); ?>">
       		<option value='DESC' <?php if('DESC'===$order){ echo'selected';} ?>><?php _e('Reverse chronological','eventpost') ?></option>
                <option value='ASC' <?php if('ASC'===$order){ echo'selected';} ?>><?php _e('Chronological','eventpost') ?></option>
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