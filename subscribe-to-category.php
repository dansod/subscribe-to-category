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
define( 'STC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'STC_PLUGIN_PATH', dirname( __FILE__ ) );

function stc_load_textdomain() {
  load_plugin_textdomain( STC_TEXTDOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' ); 
}
add_action( 'init', 'stc_load_textdomain' );

  require_once( 'classes/class-settings.php' );
  require_once( 'classes/class-cron.php' );
  require_once( 'classes/class-subscribe.php' );

  if( class_exists( 'STC_Settings' ) ) {
    $stc_setting = new STC_Settings();
  }

  if( class_exists( 'STC_Cron' ) ) {
    $stc_cron = new STC_Cron();
  }

  if( class_exists( 'STC_Subscribe' ) ) {
    $stc_subscribe = new STC_Subscribe();
  }


  /**
   * Utility class
   */
  class Util {  
    
    /**
     * printing out some debugging data
     */
    static function debug() {
      $args = func_get_args();
      
      if( !empty( $args ) ) {
        foreach( $args as $arg ) {
          echo '<pre>'.print_r( $arg, true ).'</pre><br />';
        }
      }
      
    }
  }

?>