<?php
/*
Plugin Name:  Ed's Ultimate List Builder
Plugin URI:   https://nextlevelwebdevelopers.com
Description:  The Ultimate email list builder for Wordpress. Capture new subscribers. Reward new subscribers with a custom download upon opt-in. Import and export csv lists.
Version:      1.0
Author:       Edward Fong
Author URI:   https://nextlevelwebdevelopers.com
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  eds-ultimate-list-builder

*/

/* !0. TABLE OF CONTENTS */

/*
	
	1. HOOKS
	    1.1 - registers all our customer shortcodes
        1.2 - reigster custom admin column headers
        1.3 - register custom admin column data
	2. SHORTCODES
		2.1 - eulb_register_shortcodes()
        2.2 - eulb_form_shortcode()
	3. FILTERS
		3.1 - eulb_subscriber_column_headers()
        3.2 - eulb_subscriber_column_data()
        3.2.2 - eulb_register_custom_admin_titles()
        3.2.3 - eulb_custom_admin_titles()
        3.3 - eulb_list_column_headers()
        3.4 - eulb_list_column_data()
        
	4. EXTERNAL SCRIPTS
		
	5. ACTIONS
		
	6. HELPERS
		
	7. CUSTOM POST TYPES
	
	8. ADMIN PAGES
	
	9. SETTINGS
	
	10. MISCELLANEOUS 

*/




/* !1. HOOKS */

// 1.1
// hint: registers all our customer shortcodes on init
add_action('init', 'eulb_register_shortcodes');

// 1.2
// hint: register custom admin column headers
add_filter('manage_edit-eulb_subscriber_columns','eulb_subscriber_column_headers');
add_filter('manage_edit-eulb_list_columns', 'eulb_list_column_headers');

// 1.3
// hint: register custom admin column data
add_filter('manage_eulb_subscriber_posts_custom_column','eulb_subscriber_column_data',1,2);

add_action(
    'admin_head-edit.php',
    'eulb_register_custom_admin_titles'
);
add_filter('manage_eulb_list_posts_custom_column', 'eulb_list_column_data',1,2);


/* !2. SHORTCODES */

// 2.1
function eulb_register_shortcodes() {
    
    add_shortcode('eulb_form', 'eulb_form_shortcode');
}
// 2.2
// hint: returns a html string for a email capture form
function eulb_form_shortcode( $args, $content="") {
  
  // get the list id
  $list_id = 0;
  if( isset($args['id']) ) $list_id = (int)$args['id'];
    
  // setup our output variable - the form html
  $output = '
  
  	<div class="eulb">
    	<form id="eulb_form" name="eulb-form" class="eulb-form" method="post" action="/wp-admin/admin-ajax.php?action=eulb_save_subscription">
        	
            <input type="hidden" name="eulb_list" value='. $list_id .'">
            
            <p class="eulb-input-container">
            
            	<label>Your Name</label><br>
                <input type="text" name="eulb_fname" placeholder="First Name">
                <input type="text" name="eulb_lname" placeholder="Last Name">
            </p>
            
            <p class="eulb-input-container">
            
            	<label>Your Email</label><br>
                <input type="email" name="eulb_fname" placeholder="ex.your@email.com">
                
            </p>';
            
            // including content in our html if content is passed into the function
            if( strlen($content)):
                $output .= '<div class="eulb-content">'. wpautop($content) . '</div>';
            endif;
    
            // completing our form html 
            $output .= '<p class="eulb-input-container">
            
            	<input type="submit" name="eulb_submit" value="Sign Me Up!">
            </p>
        </form>
    </div>
  ';
  
  // return our results/html
  return $output;
}


/* !3. FILTERS */

// 3.1
function eulb_subscriber_column_headers( $columns ) {
 	
  // creating custom column header data
  $columns = array(
  		'cb'=>'<input type="checkbox">',
    	'title'=>__('Subscriber Name'),
    	'email'=>__('Email Address'),
  
  );
  
  // returning new columns
  return $columns;
  
}

// 3.2
function eulb_subscriber_column_data( $column, $post_id ) {
  
  // setup our return text
  $output = '';
  
  switch( $column ) {
   
    case 'title':
      // get the custom name data
      $fname = get_field('eulb_fname', $post_id );
      $lname = get_field('eulb_lname', $post_id );
      $output .= $fname .' '.$lname;
      break;
    case 'email':
      // get the custom email data
      $email = get_field('eulb_email', $post_id );
      $output .= $email;
      break;
      
  }
  
  // echo the output
  echo $output;
  
}

// 3.2.2
// hint: registers special custom admin title columns
function eulb_register_custom_admin_titles() {
    add_filter(
        'the_title',
        'eulb_custom_admin_titles',
        99,
        2
    );
}

// 3.2.3
// hint: handls custom admin title "title" column data for post types without titles
function eulb_custom_admin_titles( $title, $post_id ) {
    
    global $post;
    $output = $title;
    
    if ( isset($post->post_type) ):
        switch( $post->post_type ) {
            case 'eulb_subscriber':
                $fname = get_field('eulb_fname', $post_id );
                $lname = get_field('eulb_lname', $post_id );
                $output = $fname .' '.$lname;
            }
        endif;
    
    return $output;
    
}
// 3.3
function eulb_list_column_headers( $columns ) {
 	
  // creating custom column header data
  $columns = array(
  		'cb'=>'<input type="checkbox">',
    	'title'=>__('List Name'),
  );
  
  // returning new columns
  return $columns;
  
}

// 3.4
function eulb_list_column_data( $column, $post_id ) {
  
  // setup our return text
  $output = '';
  
  switch( $column ) {
   
    case 'example':
      // get the custom name data
//      $fname = get_field('eulb_fname', $post_id );
//      $lname = get_field('eulb_lname', $post_id );
//      $output .= $fname .' '.$lname;
      break;
      
  }
  
  // echo the output
  echo $output;
  
}


/* !4. EXTERNAL SCRIPTS */




/* !5. ACTIONS */




/* !6. HELPERS */




/* !7. CUSTOM POST TYPES */




/* !8. ADMIN PAGES */




/* !9. SETTINGS */




/* !10. MISCELLANEOUS */



