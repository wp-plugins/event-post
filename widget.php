<?php

/** Articles de catgorie **/
class eventpost_widget extends WP_Widget {
    var $defaults;
   function __construct() {
  	parent::__construct(false, __( 'Events', 'eventpost' ),array('description'=>__( 'List of future events posts', 'eventpost' )));
        $this->defaults = array(
            'numberposts' => '',
            'widgettitle'  => '',
            'cat'   => '',
            'tag' => '',
            'future' => 0,
            'past' => 0,
            'thumbnail' => 0,
            'thumbnail_size' => '',
            'excerpt' => 0,
            'feed' => 0,
            'order' => 'ASC',
            'excerpt' => '',
            'excerpt' => '',
            'excerpt' => '',
        );

   }
   function eventpost_widget(){
       $this->__construct();
   }
   function widget($args, $local_instance) {
        extract( $args );
	$instance = wp_parse_args( (array) $local_instance, $this->defaults );

        global $EventPost;
	$events = $EventPost->get_events(
	    array(
		'nb'=>$instance['numberposts'],
		'future'=>$instance['future'],
		'past'=>$instance['past'],
		'geo'=>0,
		'cat'=>$instance['cat'],
		'tag'=>$instance['tag'],
		'order'=>$instance['order']
	    )
	);
	if(count($events)==0){
            return;
        }

        echo $args['before_widget'];
        if(!empty($instance['widgettitle'])){
            echo $args['before_title'];
            echo $instance['widgettitle'];
            if(!empty($instance['cat']) && $instance['feed']){
                echo' <a href="'.admin_url('admin-ajax.php').'?action=EventPostFeed&cat='.$instance['cat'].'"><span class="dashicons dashicons-rss"></span></a>';
            }
            echo $args['after_title'];
        }
        $atts=array(
            'events'=>$events,
            'class'=>'eventpost_widget',
            'thumbnail'=>$instance['thumbnail'],
            'thumbnail_size'=>$instance['thumbnail_size'],
            'excerpt'=>$instance['excerpt'],
            'order'=>$instance['order']
        );
        echo $EventPost->list_events($atts, 'event_list', 'widget');
        echo $args['after_widget'];
   }

   function update($new_instance, $old_instance) {
       return $new_instance;
   }

   function form($local_instance) {
        global $EventPost;
	$instance = wp_parse_args( (array) $local_instance, $this->defaults );

        $cats = get_categories();
        $tags = get_tags();
        $thumbnail_sizes = $EventPost->get_thumbnail_sizes();
       ?>
       <input type="hidden" id="<?php echo $this->get_field_id('widgettitle'); ?>-title" value="<?php echo $instance['widgettitle']; ?>">
       <p>
       <label for="<?php echo $this->get_field_id('widgettitle'); ?>"><?php _e('Title','eventpost'); ?>
       <input class="widefat" id="<?php echo $this->get_field_id('widgettitle'); ?>" name="<?php echo $this->get_field_name('widgettitle'); ?>" type="text" value="<?php echo $instance['widgettitle']; ?>" />
       </label>
       </p>

       <p style="margin-top:10px;">
       <label for="<?php echo $this->get_field_id('numberposts'); ?>"><?php _e('Number of posts','eventpost'); ?>
       <input id="<?php echo $this->get_field_id('numberposts'); ?>" name="<?php echo $this->get_field_name('numberposts'); ?>" type="number" value="<?php echo $instance['numberposts']; ?>" />
       </label> <?php _e('(-1 is no limit)','eventpost'); ?>
       </p>


       <p style="margin-top:10px;">
       <label for="<?php echo $this->get_field_id('future'); ?>">
       <input id="<?php echo $this->get_field_id('future'); ?>" name="<?php echo $this->get_field_name('future'); ?>" type="checkbox" value="1" <?php checked($instance['future'], true, true); ?> />
       <?php _e('Display future events','eventpost'); ?>
       </label>
       </p>
       <p style="margin-top:10px;">
       <label for="<?php echo $this->get_field_id('past'); ?>">
       <input id="<?php echo $this->get_field_id('past'); ?>" name="<?php echo $this->get_field_name('past'); ?>" type="checkbox" value="1" <?php checked($instance['past'], true, true); ?> />
       <?php _e('Display past events','eventpost'); ?>
       </label>
       </p>

       <p>
       	<label for="<?php echo $this->get_field_id('cat'); ?>">
            <span class="dashicons dashicons-category"></span>
                <?php _e('Only in :','eventpost'); ?>
       	<select  class="widefat" id="<?php echo $this->get_field_id('cat'); ?>" name="<?php echo $this->get_field_name('cat'); ?>">
       		<option value=''><?php _e('All categories','eventpost') ?></option>
       <?php foreach($cats as $_cat){ ?>
       	<option value="<?php echo $_cat->slug; ?>" <?php selected($_cat->slug, $instance['cat'], true); ?>><?php echo $_cat->cat_name; ?></option>
       <?php  }  ?>
       </select>
       </label>
       </p>

       <p style="margin-top:10px;">
       <label for="<?php echo $this->get_field_id('feed'); ?>">
       <input id="<?php echo $this->get_field_id('feed'); ?>" name="<?php echo $this->get_field_name('feed'); ?>" type="checkbox" value="1" <?php checked($instance['feed'], true, true); ?> />
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
       <?php foreach($tags as $_tag){?>
       	<option value="<?php echo $_tag->slug; ?>" <?php selected($_tag->slug, $instance['tag'], true); ?>><?php echo $_tag->name; ?></option>
       <?php  }  ?>
       </select>
       </label>
       </p>

       <hr>
       <p style="margin-top:10px;">
       <label for="<?php echo $this->get_field_id('thumbnail'); ?>">
       <input id="<?php echo $this->get_field_id('thumbnail'); ?>" name="<?php echo $this->get_field_name('thumbnail'); ?>" type="checkbox" value="1" <?php checked($instance['thumbnail'], true, true); ?> />
       <?php _e('Show thumbnails','eventpost'); ?>
       </label>
       </p>
       <p>
       	<label for="<?php echo $this->get_field_id('thumbnail_size'); ?>">
            <?php _e('Thumbnail size:','eventpost'); ?>
       	<select  class="widefat" id="<?php echo $this->get_field_id('thumbnail_size'); ?>" name="<?php echo $this->get_field_name('thumbnail_size'); ?>">
       		<option value=''></option>
       <?php foreach($thumbnail_sizes as $size){?>
       	<option value="<?php echo $size; ?>" <?php selected($size, $instance['thumbnail_size'], true); ?>><?php echo $size; ?></option>
       <?php  }  ?>
       </select>
       </label>
       </p>


       <p style="margin-top:10px;">
       <label for="<?php echo $this->get_field_id('excerpt'); ?>">
       <input id="<?php echo $this->get_field_id('excerpt'); ?>" name="<?php echo $this->get_field_name('excerpt'); ?>" type="checkbox" value="1" <?php checked($instance['excerpt'], true, true); ?> />
       <?php _e('Show excerpt','eventpost'); ?>
       </label>
       </p>

       <p>
       	<label for="<?php echo $this->get_field_id('order'); ?>">
            <?php _e('Order :','eventpost'); ?>
       	<select  class="widefat" id="<?php echo $this->get_field_id('order'); ?>" name="<?php echo $this->get_field_name('order'); ?>">
       		<option value='DESC' <?php selected('DESC', $instance['order'], true); ?>><?php _e('Reverse chronological','eventpost') ?></option>
                <option value='ASC' <?php selected('ASC', $instance['order'], true); ?>><?php _e('Chronological','eventpost') ?></option>
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