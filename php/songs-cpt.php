<?php
register_post_type( 'songs',
     	array(
	     'labels' => array(
	     'name' => __( 'Songs' ),
	     'singular_name' => __( 'Song' ),
	     'add_new' => __( 'Add New Song' ),
	     'add_new_item' => __( 'Add New Song' ),	 
	     'edit' => __( 'Edit' ),
	     'edit_item' => __( 'Edit Song' ),
	     'new_item' => __( 'New Song' ),
	     'view' => __( 'View Song' ),
	     'view_item' => __( 'View Song' ),
	     'search_items' => __( 'Search' ),
	     'not_found' => __( 'No songs found' ),
	     'not_found_in_trash' => __( 'No songs found in Trash' ),
		   //'parent' => __( 'Parent Song' ),
		 'menu_name'  => __('Manage Songs')
	        ),
 
	     'public' => true,
	     'show_ui' => true,
	     'publicly_queryable' => true,
	     'exclude_from_search' => false,
		  'menu_position' => 94,
		  //'register_meta_box_cb' =>  array(&$this,'add_songs_metaboxes'),
		  'menu_icon' => ZC_MS_URL . '/images/headphones-icon-sm.png',
	     'hierarchical' => false,
	     'query_var' => true,
			   //  'rewrite' => array( 'slug' => 'songs', 'with_front' => false ),
		  //'taxonomies' => array( 'post_tag', 'category'),
	     'can_export' => true,
		 'supports' => array('title','thumbnail')//,'custom-fields')
	)
);
?>
