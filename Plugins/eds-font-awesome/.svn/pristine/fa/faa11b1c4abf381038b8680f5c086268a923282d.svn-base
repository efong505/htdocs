<?php
/**
 * 
 * Plugin Name: Eds Font Awesome 
 * Plugin URI: https://www.nextlevelwebdevelopers.com/font-awesome-plugin/
 * Description: Ed's Font Awesome Plugin is the ultimate Font Awesome Icon Shortcode plugin. You can place over 1,600 font awesome icons for free anywhere on your WordPres Site simply and easily. 
 * Version: 2.0
 * Author: Edward Fong
 * Author URI: nextlevelwebdevelopers.com
 * License: GPLv2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */


//SECTION 0. TABLE OF CONTENTS 																

/*
    
    1. HOOKS
        1.1- Register all custom shortcodes on init
        1.2 - Register custom menus
        1.3 - Load external files into Wordpress ADMIN
        1.4 - Register activate/deactivate/uninstall functions
    
    2. SHORTCODES
        2.1 Register all custom shortcodes on init	
        2.2 - returns font awesome icon w/style, name, and size	
        2.3 - Returns font awesome icon mask
        2.4 - Font Awesome Icon Mask Circle
        2.5 - Rotate Icon
        
    3. FILTERS
        3.1 - Registers custom plugin admin menus
        
        
    4. EXTERNAL SCRIPTS
        4.1 - Loads external files into public website
        4.2 - Loads external file into WordPress ADMIN
        
        
    5. ACTIONS
        5.1 Checks the version of WordPress
        5.2 Run functions for plugin deactivation
        
    6. HELPERS
        
        
    7. CUSTOM POST TYPES
        
    8. ADMIN PAGES
        8.1 - Dashboard admin page
        8.2 - Admin Options Page
    
        
    
    9. SETTINGS
        

*/																																		//!SECTION End of table of contents

//SECTION 1. HOOKS 
// SECTION 1.1- Register all custom shortcodes on init
add_action('init', 'efap_register_shortcodes');																			//!SECTION end of SECTION 1.1

// SECTION 1.2 - Register custom menus
add_action('admin_menu', 'efa_admin_menus'); 																			// !SECTION end of 1.2

// SECTION 1.3 - Load external files into Wordpress ADMIN
add_action('admin_enqueue_scripts', 'efa_admin_scripts');																// !SECTION end of 1.3

// SECTION 1.4 - Register activate/deactivate/uninstall functions
add_action('admin_notices', 'efa_check_wp_version');
//register_uninstall_hook( __FILE__, 'efa_uninstall_plugin' );                                                                  // !SECTION end of 1.4


//	End of 1. Hooks																																	!SECTION end of 1. HOOKS 

// SECTION 2. SHORTCODES 

// SECTION 2.1 Register all custom shortcodes on init
function efap_register_shortcodes()
{
    add_shortcode('eds_font_awesome', 'eds_font_awesome_shortcode');
    add_shortcode('eds_fa_mask', 'eds_font_awesome_mask_shortcode');
    add_shortcode('eds_fa_mask_circle', 'eds_font_awesome_mask_circle_shortcode');
    add_shortcode('eds_font_awesome_rotate', 'eds_font_awesome_rotate_icon_shortcode');
}																														// !SECTION end of 2.1																															

//SECTION 2.2 - returns font awesome icon w/style, name, and size
function eds_font_awesome_shortcode($atts, $content = '')
{
    // Attributes
    $att = shortcode_atts(
        array(
            'style' => '',
            'name' => '',
            'size' => '',
        ),
        $atts,
        'eds_font_awesome'
    );

    $output = '<i class="' . $att['style'] . ' fa-' . $att['name'] . ' fa-' . $att['size'] .
        '"></i>';
    //$output = '<i class="fas fa-camera fa-3x"></i>';
    //$output = 'This is a test';
    return $output;
}																														//!SECTION END OF 2.2

// SECTION 2.3 - Returns font awesome icon mask
function eds_font_awesome_mask_shortcode($attr, $content = '')
{

    // attributes
    $attr = shortcode_atts(

        array(
            'inner_style' => '',
            'outer_style' => '',
            'inner_icon' => '',
            'outer_icon' => '',
            'position' => '',
            'position_value' => '',
            'grow_shrink' => '',
            'gs_value' => '',
            'size' => '',
            'bg_color' => '',
        ),
        $attr,
        'eds_fa_mask'
    );


    $output = '
        <div class="fa-' . $attr['size'] . '">
            <i class="' . $attr['inner_style'] . ' fa-' . $attr['inner_icon'] . '" data-fa-transform="' . $attr['grow_shrink'] . '-' . $attr['gs_value'] . ' ' . $attr['position'] . '-' . $attr['position_value'] .
        '" data-fa-mask="' . $attr['outer_style'] . ' fa-' . $attr['outer_icon'] . '" style="background:' . $attr['bg_color'] . '"></i>
            
        </div>
        ';
    return $output;
}																													// !SECTION END OF 2.3

// SECTION 2.4 - Font Awesome Icon Mask Circle
function eds_font_awesome_mask_circle_shortcode($attr)
{

    // attributes
    $attr = shortcode_atts(

        array(

            'style' => '',
            'name' => '',
            'position' => '',
            'position_value' => '',
            'grow_shrink' => '',
            'gs_value' => '',
            'size' => '',
            'bg_color' => '',
        ),
        $attr,
        'eds_fa_mask_circle'
    );

    $output = '
		<div class="fa-' . $attr['size'] . '">
			<i class="' . $attr['style'] . ' fa-' . $attr['name'] . '" data-fa-transform="' . $attr['grow_shrink'] . '-' . $attr['gs_value'] . ' ' . $attr['position'] . '-' . $attr['position_value'] . '" data-fa-mask="fas fa-circle" style="background:' . $attr['bg_color'] . '"></i>
		</div>
	
	';

    return $output;

} // !SECTION end of 2.4

// SECTION 2.5 - Rotate Icon
function eds_font_awesome_rotate_icon_shortcode($attr)
{

    // attributes
    $attr = shortcode_atts(
        array(

            'style' => '',
            'name' => '',
            'rotation_amount' => '',
            'size' => '',

        ),
        $attr,
        'eds_font_awesome_rotate'
    );

    $output = '
	
		
		<div class="fa-' . $attr['size'] . '">
			<i class="' . $attr['style'] . ' fa-' . $attr['name'] . ' fa-' . $attr['rotation_amount'] . '"></i>
		</div>
	';

    return $output;

} 																	// !SECTION end of 2.5

/*    $output = '<div class="fa-4x">
<span class="fa-layers fa-fw" style="background:Tan">
<i class="fas fa-envelope"></i>
<span class="fa-layers-counter" style="background:LightBlue">1,419</span>
</span></div>';
*/
// [eds_fa_mask size="4x" inner_style="fas" inner_icon="mask" grow_shrink="shrink" gs_value="3" position="up" position_value="1" outer_style="fas" outer_icon="circle" bg_color="MistyRose"]

//<i class="fas fa-mask" data-fa-transform="shrink-3 up-1" data-fa-mask="fas fa-circle" style="background:MistyRose"></i>
//<i class="fas fa-pencil-alt" data-fa-transform="shrink-6" data-fa-mask="fas fa-square" style="background:Yellow"></i>

// <div class="fa-4x">
//<i class="fas fa-pencil-alt" data-fa-transform="shrink-10 up-.5" data-fa-mask="fas fa-comment" style="background:MistyRose"></i>
//<i class="fab fa-facebook-f" data-fa-transform="shrink-3.5 down-1.6 right-1.25" data-fa-mask="fas fa-circle" style="background:MistyRose"></i>
//<i class="fas fa-headphones" data-fa-transform="shrink-6" data-fa-mask="fas fa-square" style="background:MistyRose"></i>
//<i class="fas fa-mask" data-fa-transform="shrink-3 up-1" data-fa-mask="fas fa-circle" style="background:MistyRose"></i>
//</div>

//  end of 2. SHORTCODES 																											!SECTION

// SECTION 3. FILTERS

// SECTION 3.1 - Registers custom plugin admin menus
function efa_admin_menus()
{

    /* main menu */

    $top_menu_item = 'efa_dashboard_admin_page';

    add_menu_page('', 'Ed\'s Font Awesome', 'manage_options', 'efa_dashboard_admin_page', 'efa_dashboard_admin_page', 'dashicons-smiley');

    /* submenu items */

    // dashboard
    add_submenu_page($top_menu_item, '', 'Dashboard', 'manage_options', $top_menu_item, $top_menu_item);

    // plugin options 
    add_submenu_page($top_menu_item, '', 'Plugin Options', 'manage_options', 'efa_options_admin_page', 'efa_options_admin_page');

}																														// !SECTION END OF 3.1

// END OF 3. FILTERS 																														!SECTION 

// SECTION 4. EXTERNAL SCRIPTS 

// SECTION 4.1 - Loads external files into public website


function efa_custom_setup_kit($kit_url = '')
{
    foreach (['wp_enqueue_scripts', 'admin_enqueue_scripts', 'login_enqueue_scripts'] as $action) {
        add_action(
            $action,
            function () use ($kit_url) {
                wp_enqueue_script('font-awesome-kit', $kit_url, [], null);

            }
        );
    }
}                                                                                                                        // !SECTION END OF 4.1

efa_custom_setup_kit(plugins_url('/font-awesome/js/all.js', __FILE__));

// SECTION 4.2 - Loads external file into WordPress ADMIN
function efa_admin_scripts()
{

    // register scripts with WordPress's internal library
    wp_register_script(
        'eds-font-awesome-js-private',
        plugins_url('/js/private/eds-font-awesome.js', __FILE__),
        array('jquery'),
        '',
        true
    );

    wp_enqueue_script('eds-font-awesome-js-private');


    /* BOOTSTRAP */
    // bootstrap css
    wp_register_style('bootstrap-style-css', plugins_url('/lib/bootstrap/css/bootstrap.min.css', __FILE__));

    // Popper
    wp_register_script(
        'bootstrap-popper',
        plugins_url('/lib/bootstrap/js/popper.min.js', __FILE__),
        array('jquery'),
        '',
        true
    );

    // Bootstrap js
    wp_register_script(
        'bootstrap-cdn',
        plugins_url('/lib/bootstrap/js/bootstrap.min.js', __FILE__),
        array('jquery'),
        '',
        true
    );

    // add to que of scripts that get loaded into every admin page
    wp_enqueue_script('bootstrap-popper');
    wp_enqueue_script('bootstrap-cdn');

    wp_enqueue_style('bootstrap-style-css');

}																														// !SECTION END OF 4.2

// END OF 4. EXTERNAL SCRIPTS  																														!SECTION 

// SECTION 5. ACTIONS

// SECTION 5.1 Checks the version of WordPress
function efa_check_wp_version()
{

    global $pagenow;

    if ($pagenow == 'plugins.php' && is_plugin_active('eds-font-awesome-plugin/eds_font_awesome_plugin.php')):

        // get the wp version
        $wp_version = get_bloginfo('version');

        // tested versions
        // versions we've tested plugin in
        $tested_versions = array(

            '5.6.1',
            '5.6.2',

        );

        // if the current wp version is not in our tested versions
        if (!in_array($wp_version, $tested_versions)):

            // get notice html
            $notice = efa_get_admin_notice('Ed\'s Font Awesome Shortcode Plugin has not been tested with your version of WordPress. It still, however, may work...', 'error');

            // echo the notice html
            echo ($notice);

        endif;

    endif;

}                                                                                                                           // !SECTION end of 5.1

// SECTION 5.2 Run functions for plugin deactivation
function efa_uninstall_plugin()
{

    // remove our custom plugin tables
    efa_remove_plugin_tables();
    // remove custom post types posts and data
    efa_remove_post_data();
    // remove plugin options
    efa_remove_options();

}                                                                                                                           // !SECTION end of 5.2


// end of 6.  Actions                                                                                                                               !SECTION 

// SECTION 6. HELPERS

// SECTION 6.1 efa_get_admin_notice - Returns HTML formatted for WP admin notices
function efa_get_admin_notice($message, $class)
{

    // setup our return variable
    $output = '';

    try {

        // create output html
        $output = '
        
        <div class="' . $class . '">
            <p>' . $message . '</p>
        </div>
        ';
    } catch (Exception $e) {

        // php error
    }

    // return output
    return $output;

}                                                                                                                               // !SECTION END OF 6.1

// END OF 6. HELPERS                                                                                                                                !SECTION 
// SECTION 8. ADMIN PAGES 

// SECTION 8.1 - Dashboard admin page


function efa_dashboard_admin_page()
{

    $output =

        '
			
	 <div class="container">
				
				
        <div class="card" style="max-width: 100%;">
            <img src="' . esc_url(plugins_url('/lib/images/andrey-grinkevich-rIDE73mqi2s-unsplash.jpg', __FILE__)) . '" class="card-img-top img-fluid" alt="scene">
            <div class="card-header bg-secondary ">
                <h2 class="text-white">Ed\'s Font Awesome Plugin</h2>
            </div>
			
			<span>Photo by <a href="https://unsplash.com/@grin?utm_source=unsplash&amp;utm_medium=referral&amp;utm_content=creditCopyText">Andrey Grinkevich</a> on <a href="https://unsplash.com/t/nature?utm_source=unsplash&amp;utm_medium=referral&amp;utm_content=creditCopyText">Unsplash</a></span>
            <div class="mt-4 card-body">
            <h5 class="mb-2 card-header">The Ultimate Font Awesome Plugin. Use shortcode to place Font Awesome icons anywhere on your site.</h6>
                <h5>Options:</h5>
                <ol>
                    <li>Regular Font Awesome Icon</li>
                    <li>Mask Icon - Combine 2 icons into one. Currently you can mask any icon into a circle icon.</li>
                    <li>Rotated Icons - Rotate an icon any degree or flip vertically or horizontally. </li>
                </ol>


                
                <div class="p-2 mb-2 bg-primary card-title"><h3 class="text-white">How To Use</h3></div>

                <p class="card-text">A list of all of the free icons are located here: <a href="https://fontawesome.com/icons?d=gallery&p=2&m=free" target="_blank">Font Awesome Icons</a></p>
                <p class="card-text">Simply add the shortcode to the location that you would like the font awesome icon to be placed as shown in the following examples below:<br /><br /></p>

                
            <!-- 1. Regular Font Awesome  -->
            <h2 class="card-title">1. Regular Font Awesome Shortcode</h2>
            
            <p class="card-text">Will look like this:<br /></p>

            <strong>Example:</strong><br /><br />
            &nbsp;&nbsp;&nbsp;
            <strong>[eds_font_awesome style="fab" name="accusoft" size="9x"]</strong><br />
			<i class="fab fa-accusoft fa-9x"></i><br />
            
            <p class="card-text">[eds_font_awesome] is the shortcode that you\'ll use for the Ed\'s Font Awesome Shortcode</p>
            
            <p class="card-text"><strong>STYLE - </strong>In the example above, the style is the style prefix. Ex. fab or fas. In this example <strong><i>style="fab".</i></strong> <br />
            <strong>NAME - </strong>The name is whatever the icon name you would like to be displayed. In this example,<strong> <i>name="accusoft"</i></strong>. You do not need to add the prefix fa- to the name.<br >
            <strong>SIZE - </strong>The sizes are as follow and can also be viewed on the font awesome site at: <a href="https://fontawesome.com/how-to-use/on-the-web/styling/sizing-icons" target="_blank">Font Awesome Sizing</a>. In this example: <strong><i>size="5x"</i></strong>
            <br />&nbsp;&nbsp;The sizes are: xs, sm, lg, 2x, 3x, 5x, 7x, and 10x. You do not need to add the prefix fa- before the size number. </p>
            
            <!-- 2. Mask Circle Icon  -->
            <h2 class="card-title">2. Mask Circle Icon</h2>
            <p class="card-text">The Mask icon shortcode will show the icon that you select in a circle icon. You can select the color of the background icon color as well.</p>

            <strong>Example:</strong><br /><br />
            &nbsp;&nbsp;&nbsp;
            <strong>[eds_fa_mask_circle size="4x" style="fas" name="mask" grow_shrink="shrink" gs_value="3" position="up" position_value="1" bg_color="LightBlue"]</strong></p>
			<div class="fa-9x">
			 
			  <i class="fas fa-mask" data-fa-transform="shrink-3 up-1" data-fa-mask="fas fa-circle" style="background:LightBlue"></i>
			</div>
            
            <p class="card-text">[eds_fa_mask_circle] is the shortcode that you\'ll use for the Ed\'s Font Awesome Shortcode</p>
            
            <p class="card-text"><strong>STYLE - </strong>In the example above, the style is the style prefix. Ex. fab or fas. In this example <strong><i>style="fas".</i></strong> <br />
            <strong>NAME - </strong>The name is whatever the icon name you would like to be displayed. In this example,<strong> <i>name="mask"</i></strong>. You do not need to add the prefix fa- to the name.<br />

            <strong>GROW_SHRINK</strong> is the option whether or not you want the icon to be larger or smaller than the circle icon. In this example, <strong><i>grow_shrink"shrink"</i></strong><br />

            <strong>GS_VALUE - </strong>This is the value that by how much you would like to grow or shrink the icon. In this example, <strong><i>gs_value="3"</i></strong><br />


            <strong>POSITION_VALUE - </strong>This is the value that select to  move up or down the center icon. In this example, <strong><i>position_value="1"</i></strong><br />

            <strong>BG_COLOR - </strong>This is the color that you want to set the background of the icon to. There are several colors that you can choose from. Just search for HTML5 color names and you will find a ton of colors to choose from. Here\'s a site that has a list of colors: <a href="https://www.tutorialspoint.com/html5/html5_color_names.htm" target="_blank">HTML5 - Color Names</a>  In this example, <strong><i>bg_color="LightBlue"</i></strong><br />


            <strong>POSITION - </strong>Position is the directon vertical offset you would like to set the center icon to. You can set it to up or down. In this example, <strong><i>position="up"</i></strong><br />

            <strong>SIZE - </strong>The sizes are as follow and can also be viewed on the font awesome site at: <a href="https://fontawesome.com/how-to-use/on-the-web/styling/sizing-icons" target="_blank">Font Awesome Sizing</a>. In this example: <strong><i>size="5x"</i></strong>
            <br />&nbsp;&nbsp;The sizes are: xs, sm, lg, 2x, 3x, 5x, 7x, and 10x. You do not need to add the prefix fa- before the size number. </p>
            
            
            <!-- 3. Rotate Icons -->
            
            <h2 class="card-title">3. Rotate Icon</h2>
            <p class="card-text">The Rotate Icon will allow you to rotate, flip, or mirror an icon. Uses rotate and flip.</p>

            <strong>Example:</strong><br /><br />
            &nbsp;&nbsp;&nbsp;
            <strong>[eds_font_awesome_rotate style="fas" name="snowboarding" rotation_amount="rotate-180" size="9x"]</strong></p><i class="fas fa-snowboarding fa-10x"></i>
            
            <p class="card-text">[eds_font_awesome_rotate] is the shortcode that you\'ll use for the Ed\'s Font Awesome Rotate Shortcode</p>
            
            <p class="card-text"><strong>STYLE - </strong>In the example above, the style is the style prefix. Ex. fab or fas. In this example <strong><i>style="fas".</i></strong> <br />
            <strong>NAME - </strong>The name is whatever the icon name you would like to be displayed. In this example,<strong> <i>name="snowboarding"</i></strong>. You do not need to add the prefix fa- to the name.<br />

            <strong>ROTATON_AMOUNT</strong> is the option that determine\'s the degree of rotation.  In this example, <strong><i>rotation_amount="rotate-180"</i></strong><br />
            <br /><strong>Here are the rotate options available:</strong>
            
            <div class="container">
                <table class="table">
                    <thead>
                    <tr> 
                        <th scope="col">Class</th>
                        <th scope="col">Rotation Amount</th>                    
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        
                        <td class="text-danger">rotate-90</td>
                        <td><strong>90°</strong> <i class="fas fa-snowboarding fa-rotate-90"></i></td>
                    
                        
                    </tr>
                    <tr>
                        
                        <td class="text-danger">rotate-180</td>
                        <td><strong>180°</strong>  <i class="fas fa-snowboarding fa-rotate-180"></i></td>
                        
                    </tr>
                    <tr>
                        
                        <td class="text-danger">rotate-270</td>
                        <td><strong>270°</strong>  <i class="fas fa-snowboarding fa-rotate-270"></i></td>
                    
                        
                    </tr>
                    <tr>
                        
                        <td class="text-danger">flip-horizontal</td>
                        <td><strong>mirrors icon horizontally</strong> <i class="fas fa-snowboarding fa-flip-horizontal"></i></td>
                    
                        
                    </tr>
                    <tr>
                        
                        <td class="text-danger">flip-vertical</td>
                        <td><strong>mirrors icon vertically</strong><i class="fas fa-snowboarding fa-flip-vertical"></i></td>
                        
                    </tr>
                    <tr>
                        
                        <td class="text-danger">flip-both</td>
                        <td><strong>mirrors icon vertically and horizontally <i class="fas fa-snowboarding fa-flip-both"></i> (requires 5.7.0 or greater)</strong></td>
                    
                        
                    </tr>
                    
                    </tbody>
                </table>
             </div>    

                <strong>SIZE - </strong>The sizes are as follow and can also be viewed on the font awesome site at: <a href="https://fontawesome.com/how-to-use/on-the-web/styling/sizing-icons" target="_blank">Font Awesome Sizing</a>. In this example: <strong><i>size="5x"</i></strong>
                <br />&nbsp;&nbsp;The sizes are: xs, sm, lg, 2x, 3x, 5x, 7x, and 10x. You do not need to add the prefix fa- before the size number. </p>
                
                <p class="card-text"><strong>NOTE:</strong> you don\'t have to include the pre-fix fa- before the icon name like fa-accusoft or size like fa-3x. Just include the name and size only. </p>
            
            </div>

        </div>
    
    
    </div>
		';

    echo $output;

} 																																// !SECTION END OF 8.1


// SECTION 8.2 - Admin Options Page
function efa_options_admin_page()
{

    $output = '
		<div class="container">
			<h2>Ed\'s Font Awesome Plugin Options</h2>
			
			<h3>Something Wonderful\'s Coming Soon!...</h3>
			<p>The option to integrate Pro features are in the works. Check back soon!</p>
			<ol class="fa-ul">
			<li><span class="fa-li"><i class="fas fa-check-square"></i></span>List icons can</li>
			<li><span class="fa-li"><i class="fas fa-check-square"></i></span>be used to</li>
			<li><span class="fa-li"><i class="fas fa-spinner fa-pulse"></i></span>replace bullets</li>
			<li><span class="fa-li"><i class="far fa-square"></i></span>in lists</li>
            <li><span class="fa-li"><i class="fa-brands fa-x-twitter"></i></span>X</li>
			</ol>
    
           
		   
		   
		   
		   
		   <div class="fa-4x">
  <span class="fa-layers fa-fw" style="background:MistyRose">
    <i class="fa-solid  fa-circle" style="color:Tomato"></i>
    <i class="fa-inverse fa-solid  fa-times" data-fa-transform="shrink-6"></i>
  </span>

  <span class="fa-layers fa-fw" style="background:MistyRose">
    <i class="fa-solid  fa-bookmark"></i>
    <i class="fa-inverse fa-solid  fa-heart" data-fa-transform="shrink-10 up-2" style="color:Tomato"></i>
  </span>

  <span class="fa-layers fa-fw" style="background:MistyRose">
    <i class="fa-solid  fa-play" data-fa-transform="rotate--90 grow-4"></i>
    <i class="fa-brands  fa-x-twitter fa-inverse" data-fa-transform="shrink-8 down-3"></i>
    
  </span>

  <span class="fa-layers fa-fw" style="background:MistyRose">
    <i class="fa-solid  fa-calendar"></i>
    <span class="fa-layers-text fa-inverse" data-fa-transform="shrink-8 down-3" style="font-weight:900">27</span>
  </span>

  <span class="fa-layers fa-fw" style="background:MistyRose">
    <i class="fa-solid  fa-certificate"></i>
    <span class="fa-layers-text fa-inverse" data-fa-transform="shrink-11.5 rotate--30" style="font-weight:900">NEW</span>
  </span>

  <span class="fa-layers fa-fw" style="background:MistyRose">
    <i class="fa-solid  fa-envelope"></i>
    <span class="fa-layers-counter" style="background:Tomato">1,419</span>
  </span>
</div>
            
            

            <div class="fa-3x">
			<i class="fas fa-spinner fa-spin"></i>
			<i class="fas fa-circle-notch fa-spin"></i>
			<i class="fas fa-sync fa-spin"></i>
			<i class="fas fa-cog fa-spin"></i>
			<i class="fas fa-spinner fa-pulse"></i>
			<i class="fas fa-stroopwafel fa-spin"></i>
		</div>

		</div>
					
					
		
	';

    echo $output;
} 																																// !SECTION END OF 8.2

// !SECTION END OF 8. ADMIN PAGES 