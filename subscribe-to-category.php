<?php
/*
  Plugin Name: Subscribe to Category
  Plugin URI: http://dcweb.nu
  Description: Lets your visitor subscribe to posts for one or several categories.
  Version: 1.0.0
  Author: Daniel Söderström 
  Author URI: http://dcweb.nu/
  License: GPLv2 or later
*/



  define( 'STC_TEXTDOMAIN', 'stc_textdomain' );
  define( 'STC_SLUG', 'stc' );
  define( 'STC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
  define( 'STC_PLUGIN_PATH', dirname( __FILE__ ) );

  require_once( 'classes/class-main.php' );
  require_once( 'classes/class-settings.php' );
  require_once( 'classes/class-cron.php' );
  require_once( 'classes/class-subscribe.php' );

  // Create instance for main class
  add_action( 'plugins_loaded', array( 'STC_Main', 'get_instance' ) );

  // Register activation and deactivation hook
  register_activation_hook( __FILE__, array( 'STC_Main', 'activate' ) );
  register_deactivation_hook( __FILE__, array( 'STC_Main', 'deactivate' ) );


  /**
   * Utility class  -printing out some debugging data
   */
  
/*
  class Util {    
    static function debug() {
      $args = func_get_args();
      
      if( !empty( $args ) ) {
        foreach( $args as $arg ) {
          echo '<pre>'.print_r( $arg, true ).'</pre><br />';
        }
      }
      
    }
  }
*/

?>