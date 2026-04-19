<?php
/*
Plugin Name:  Ed's Social Share
Plugin URI:   https://nextlevelwebdevelopers.com
Description:  This plugin shares your page to other social media pages
Version:      1.0
Author:       Edward Fong
Author URI:   https://profiles.wordpress.org/waianaeboy702/
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  eds-social-share
Domain Path:  /languages
*/

// Register shortcodes on init
add_action('init', 'essp_register_shortcuts');

// Register Custom Styles
add_action('wp_head', 'essp_custom_styles');

//Register custom admin menus
add_action( 'admin_menu', 'essp_admin_menus' ); 	

// Register Field Settings 
add_action("admin_init", "essp_social_share_settings");

// STYLES
// Font Awesome Scripts
function essp_social_share_scripts() {
   wp_enqueue_style( 'font-awesome5', get_template_directory_uri() . '/css/all.min.css',__FILE__);
}
add_action('wp_head', 'essp_social_share_scripts');

// Custom Scripts
function essp_custom_styles() {
    ?>
        <style>
                .wrapper .icon{
                  position: relative;
                  background-color: #ffffff;
                  border-radius: 50%;
                  margin: 10px;
                  width: 50px;
                  height: 50px;
                  line-height: 50px;
                  font-size: 22px;
                  display: inline-block;
                  align-items: center;
                  box-shadow: 0 10px 10px rgba(0, 0, 0, 0.1);
                  cursor: pointer;
                  transition: all 0.2s cubic-bezier(0.68, -0.55, 0.265, 1.55);
				  
				  padding-top: 1px;
                  color: #333;
                  text-decoration: none;
                }
                .wrapper .tooltip {
                  position: absolute;
                  top: 0;
                  line-height: 1.5;
                  font-size: 14px;
                  background-color: #ffffff;
                  color: #ffffff;
                  padding: 5px 8px;
                  border-radius: 5px;
                  box-shadow: 0 10px 10px rgba(0, 0, 0, 0.1);
                  opacity: 0;
                  pointer-events: none;
                  transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265,
1.55);
                }
                .wrapper .tooltip::before {
                  position: absolute;
                  content: "";
                  height: 8px;
                  width: 8px;
                  background-color: #ffffff;
                  bottom: -3px;
                  left: 50%;
                  transform: translate(-50%) rotate(45deg);
                  transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265,
1.55);
                }
                .wrapper .icon:hover .tooltip {
                  top: -45px;
                  opacity: 1;
                  visibility: visible;
                  pointer-events: auto;
                }
                .wrapper .icon:hover span,
                .wrapper .icon:hover .tooltip {
                  text-shadow: 0px -1px 0px rgba(0, 0, 0, 0.1);
                }
                .wrapper .facebook:hover,
                .wrapper .facebook:hover .tooltip,
                .wrapper .facebook:hover .tooltip::before {
                  background-color: #3B5998;
                  color: #ffffff;
                }
                .wrapper .twitter:hover,
                .wrapper .twitter:hover .tooltip,
                .wrapper .twitter:hover .tooltip::before {
                  background-color: #1D9BF0;
                  color: #ffffff;
                }
                .wrapper .instagram:hover,
                .wrapper .instagram:hover .tooltip,
                .wrapper .instagram:hover .tooltip::before {
                  background-color: #e1306c;
                  color: #ffffff;
                }
                .wrapper .github:hover,
                .wrapper .github:hover .tooltip,
                .wrapper .github:hover .tooltip::before {
                  background-color: #333333;
                  color: #ffffff;
                }
				.wrapper .linkedin:hover,
                .wrapper .linkedin:hover .tooltip,
                .wrapper .linkedin:hover .tooltip::before {
                  background-color: #0A66C2;
                  color: #ffffff;
                }
                .wrapper .youtube:hover,
                .wrapper .youtube:hover .tooltip,
                .wrapper .youtube:hover .tooltip::before {
                  background-color: #FF0000;
                  color: #ffffff;
                }
				.wrapper .pinterest:hover,
                .wrapper .pinterest:hover .tooltip,
                .wrapper .pinterest:hover .tooltip::before {
                  background-color: #E60023;
                  color: #ffffff;
                }

                .wrapper .mail:hover,
                .wrapper .mail:hover .tooltip,
                .wrapper .mail:hover .tooltip::before {
                  background-color: #9C36B5;
                  color: #ffffff;
                }

                .wrapper .print:hover,
                .wrapper .print:hover .tooltip,
                .wrapper .print:hover .tooltip::before {
                  background-color: #FAB005;
                  color: #ffffff;
                }
			
			
			
        </style>
    <?php
}


// Share Shortcode Function
function essp_social_share($attr, $content) {
	
global $wp;
$current_url = home_url(add_query_arg( array(), $wp->request));
$sb_title = str_replace( ' ', '%20', get_the_title());

	

// shortcode attributes
$attr = shortcode_atts(
      
  array(
      'color'   =>  '',
	  'text'	=>	'Share Us',
	  'twitter'	=>	'westmesanews',
  ),
  $attr,
  'social_share'
);
// Header Text for share
$content .= '<div style="text-align:center" class="wrapper">
<h1 style="text-align:center; color:'. $attr['color'] .'; margin-bottom:1px;">'. $attr['text'] .'</h1>';

	if(get_option("social-share-facebook") == 1){
		$content .= '<a href="https://www.facebook.com/sharer/sharer.php?u='. $current_url .'" class="icon facebook">
    		<div class="tooltip">Facebook</div>
    		<span><i class="fab fa-facebook-f"></i></span>
  			</a>';	
	}
  	if(get_option("social-share-twitter") == 1){
		$content .= '<a href="http://twitter.com/intent/tweet?text='.$sb_title.'&amp;url='. $current_url .'&amp;via='. $attr['twitter'] .'" class="icon twitter">
     		<div class="tooltip">Twitter</div>
     		<span><i class="fab fa-twitter"></i></span>
   			</a>';	
	}
    if(get_option("social-share-instagram") == 1) {
	  $content .= '<a href="https://instagram.com" class="icon instagram">
    	<div class="tooltip">Instagram</div>
    	<span><i class="fab fa-instagram"></i></span>
  		</a>';
    }
  
	if(get_option("social-share-github") == 1) {
		$content .= '<a href="https://github.com" class="icon github">
    		<div class="tooltip">Github</div>
    		<span><i class="fab fa-github"></i></span>
  			</a>';
	}
	
    if(get_option("social-share-youtube") == 1) {
	  $content .= '<a href="https://youtube.com" class="icon youtube">
    	<div class="tooltip">Youtube</div>
    	<span><i class="fab fa-youtube"></i></span>
  		</a>';
    }
  
	if(get_option("social-share-linkedin") == 1) {
		$content .=	'<a href="https://www.linkedin.com/shareArticle?mini=true&url='. $current_url .'&amp;title='.$sb_title.'" class="icon linkedin">
    		<div class="tooltip">LinkedIn</div>
    		<span><i class="fab fa-linkedin"></i></span>
  			</a>';
	}
  
	if(get_option("social-share-pinterest") == 1) {
		$content .= '<a href="https://pinterest.com/pin/create/button/?url='. $current_url .'&amp;description='. $sb_title .'" class="icon pinterest">
    	<div class="tooltip">Pinterest</div>
    	<span><i class="fab fa-pinterest"></i></span>
  		</a>';
	}
    
    if(get_option("social-share-mail") == 1) {
		$content .= '<a href="mailto:?subject=You%20Have%20To%20Read%20This:%20'. $sb_title .'&body='. $current_url .'" class="icon mail">
    	<div class="tooltip">Mail</div>
    	<span><i class="fa fa-envelope"></i></span>
  		</a>';
	}
  
    if(get_option("social-share-print") == 1) {
		$content .= '<a href="javascript:;" onclick="window.print()" class="icon print">
    	<div class="tooltip">Print</div>
    	<span><i class="fa fa-print"></i></span>
  		</a>';
	}
	$content .= '</div>';

// Return the content
return $content;

}

// Add shortcode to filter
function essp_register_shortcuts(){
  // Hook our function to WordPress the_content filter
  add_shortcode('social_share', 'essp_social_share'); 
}

//Admin Menus
function essp_admin_menus(){
	
	/* main menu */
		$top_menu_item = 'ess_dashboard_admin_page';
		add_menu_page( '', 'Ed\'s Social Share', 'manage_options', 'ess_dashboard_admin_page', 'ess_dashboard_admin_page', 'dashicons-share' );
		
	/* submenu items */
		// dashboards
		add_submenu_page( $top_menu_item, '', 'Dashboard', 'manage_options', $top_menu_item, $top_menu_item );	
		// plugin options 
		add_submenu_page( $top_menu_item, '', 'Plugin Options', 'manage_options', 'essp_options_admin_page', 'essp_options_admin_page' );
}




// Admin Dashboard page
function ess_dashboard_admin_page() { 
 
	$output =
	'
	 <div class="container">
        <div class="card" style="max-width: 100%;">
            <img src="'. esc_url( plugins_url('/assets/banner-772x250.png',__FILE__) ) .'" class="card-img-top img-fluid" alt="scene">
            <div class="card-header bg-secondary ">
                <h2 class="text-white">Ed\'s Social Share Plugin</h2>
            </div>

            <div class="mt-4 card-body">
            <h5 class="mb-2 card-header">The Ultimate Social Share Plugin. Use shortcode to place share icons anywhere on your site.</h6>
                <div class="p-2 mb-2 bg-primary card-title"><h3 class="text-white">How To Use:</h3></div>

                <p>Just plugin in <strong>[social_share]</strong> anywhere you would like on your site and the share bar will appear where it\'s inserted.</p>
				<p>Note: Instagram, Github, and YouTube do not currently have options to share WordPress pages at the moment. The links for these will take you to their site only without sharing the WordPress Post or page</p>
				
				<h3>Options:</h3>
                <strong>Additional options include: </strong>
				<ol>
					<li>Changing the Share Header Text (Default is Share Us)</li>
					<li>Change the Color of the Share Text Header (Default color is black). If  you\'re adding the share shortcut to a black background you\'ll want to change the color to white.</li>
					<li>Changing the @tag for Twitter shares</li>
				</ol>
                <p>To change the text of the Header Text use the <em>text</em> attribute like <strong>[social_share text="Share This Page"]</strong>.</p>
				<p>To change the color of the share Text Header, use the <em>color</em> attribute like, <strong>[social_share color="white"]</strong>.</p>
				<p>To change the twitter @tag, you will use the <em>twitter</em> attribute like, <strong>[social_share twitter="myShare"]</strong>.</p>
				<p>Additionally, you can do all 3 or a combination of them like, <strong>[social_share text="Share Me" twitter="sharedSite" color="green"]</strong>.</p>
            </div>
        </div>
    </div>';
				
				_e( $output );

}

// Option Fields
function essp_social_share_settings()
{
    add_settings_section("social_share_config_section", "", null, "social-share");
 
    add_settings_field("social-share-facebook", "Facebook:", "essp_facebook_checkbox_checkbox", "social-share", "social_share_config_section");
    add_settings_field("social-share-twitter", "Twitter:", "essp_twitter_checkbox", "social-share", "social_share_config_section");
    add_settings_field("social-share-instagram", "Instagram:", "essp_instagram_checkbox", "social-share", "social_share_config_section");
	  add_settings_field("social-share-github", "GitHub:", "essp_github_checkbox", "social-share", "social_share_config_section");
	  add_settings_field("social-share-youtube", "YouTube:", "essp_youtube_checkbox", "social-share", "social_share_config_section");
    add_settings_field("social-share-linkedin", "LinkedIn:", "essp_linkedin_checkbox", "social-share", "social_share_config_section");
    add_settings_field("social-share-pinterest", "Pinterest:", "essp_pinterest_checkbox", "social-share", "social_share_config_section");
    add_settings_field("social-share-mail", "Mail:", "essp_mail_checkbox", "social-share", "social_share_config_section");
    add_settings_field("social-share-print", "Print:", "essp_print_checkbox", "social-share", "social_share_config_section");
 
    register_setting("social_share_config_section", "social-share-facebook");
    register_setting("social_share_config_section", "social-share-twitter");
	  register_setting("social_share_config_section", "social-share-instagram");
	  register_setting("social_share_config_section", "social-share-github");
	  register_setting("social_share_config_section", "social-share-youtube");
    register_setting("social_share_config_section", "social-share-linkedin");
    register_setting("social_share_config_section", "social-share-pinterest");
    register_setting("social_share_config_section", "social-share-mail");
    register_setting("social_share_config_section", "social-share-print");
}
 

// Social Share CheckBoxes - Enable/Disable in Admin Options Page

//FACEBOOK Checkbox
function essp_facebook_checkbox_checkbox()
{  
   ?>
        <input type="checkbox" name="social-share-facebook" value="1" <?php checked(1, get_option('social-share-facebook'), true); ?> /> 
   <?php
}
//TWITTER 
function essp_twitter_checkbox()
{  
   ?>
        <input type="checkbox" name="social-share-twitter" value="1" <?php checked(1, get_option('social-share-twitter'), true); ?> /> 
   <?php
}

// Instagram Checkbox
function essp_instagram_checkbox()
{  
   ?>
        <input type="checkbox" name="social-share-instagram" value="1" <?php checked(1, get_option('social-share-instagram'), true); ?> /> 
   <?php
}
// GitHub Checkbox
function essp_github_checkbox()
{  
   ?>
        <input type="checkbox" name="social-share-github" value="1" <?php checked(1, get_option('social-share-github'), true); ?> /> 
   <?php
}
// YouTube Checkbox
function essp_youtube_checkbox()
{  
   ?>
        <input type="checkbox" name="social-share-youtube" value="1" <?php checked(1, get_option('social-share-youtube'), true); ?> /> 
   <?php
}
//LinkedIn Checkbox
function essp_linkedin_checkbox()
{  
   ?>
        <input type="checkbox" name="social-share-linkedin" value="1" <?php checked(1, get_option('social-share-linkedin'), true); ?> /> 
   <?php
}

//Pinterest Checkbox
function essp_pinterest_checkbox()
{  
   ?>
        <input type="checkbox" name="social-share-pinterest" value="1" <?php checked(1, get_option('social-share-pinterest'), true); ?> /> 
   <?php
}
//Mail Checkbox
function essp_mail_checkbox()
{  
   ?>
        <input type="checkbox" name="social-share-mail" value="1" <?php checked(1, get_option('social-share-mail'), true); ?> /> 
   <?php
}

//Print Checkbox
function essp_print_checkbox()
{  
   ?>
        <input type="checkbox" name="social-share-print" value="1" <?php checked(1, get_option('social-share-print'), true); ?> /> 
   <?php
}

// SECTION 8.2 - Admin Options Page
function essp_options_admin_page() {
	?>
      <div class="wrap">
         <h1>Social Sharing Options</h1>
         <form method="post" action="options.php">
            <?php
               settings_fields("social_share_config_section");
 
               do_settings_sections("social-share");
                
               submit_button(); 
            ?>
         </form>
      </div>
   <?php
} 	