// waint until the page and jQuery have loaded before running the code below
jQuery(document).ready(function($) {
   
    // stop our admin menus from collapsing
    if( $('body[class*=" efa_"]').length || $('body[class*= post-type-efa_"]').length ) {
        
        $efa_menu_li = $('#toplevel_pag_efa_dashboard_admin_page');
        
        $efa_menu_li
        .removeClass('wp-not-current-submenu')
        .addClass('wp-has-current-submenu')
        .addClass('wp-menu-open');
        
        $('a:first',$efa_menu_li)
        .removeClass('wp-not-current-submenu')
        .addClass('wp-has-submenu')
        .addClass('wp-has-current-submenu')
        .addClass('wp-menu-open');
    
    }
    
});