<?php

/** Articles de catgorie **/
class eventpostmap_widget extends WP_Widget {
   function eventpostmap_widget() {
  	  parent::WP_Widget(false, __( 'Events map', 'eventpost' ),array('description'=>__( 'Map of events posts', 'eventpost' )));
   }
   function widget($args, $instance) {
       global $EventPost;
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
	/*Background tile */
        $tile=isset($instance['tile']) && !empty($instance['tile'])?esc_attr($instance['tile']):$EventPost->settings['tile'];
	
		global $EventPost;
		$events = $EventPost->get_events(
			array(
				'nb'=>$numberposts,
				'future'=>$future,
				'past'=>$past,
				'geo'=>1,
				'cat'=>$cat,
				'tag'=>$tag
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
                'class'=>'eventpost_widget',
                'thumbnail'=>$thumbnail,
                'excerpt'=>$excerpt,
                'tile'=>$tile
            );
			echo $EventPost->list_events($atts,'event_geolist');
			echo $args['after_widget'];		
		}		    
   }
   
   function update($new_instance, $old_instance) {
       return $new_instance;
   }

   function form($instance) {
 	global $EventPost;    
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
        /*Background tile */
        $tile=isset($instance['tile']) && !empty($instance['tile'])?esc_attr($instance['tile']):$EventPost->settings['tile'];

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
       
       <p style="margin-top:10px;">
        <label for="<?php echo $this->get_field_id('tile'); ?>">
            <?php _e('Map background', 'eventpost'); ?>
                        <select id="<?php echo $this->get_field_id('tile'); ?>"  name="<?php echo $this->get_field_name('tile'); ?>">
                                    <?php
                                    foreach ($EventPost->maps as $id => $map):
                                        ?>
                                <option value="<?php
                                            if ($EventPost->settings['tile'] != $map['id']) {
                                                echo $map['id'];
                                            }
                                            ?>" <?php
                    if ($tile == $map['id']) {
                        echo'selected';
                    }
                    ?>>
            <?php echo $map['name']; ?><?php
            if ($EventPost->settings['tile'] == $map['id']) {
                echo' (default)';
            }
            ?>
                                </option>
        <?php endforeach; ?>
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