<?php
/**
 * STC Main class
 * @author Daniel Söderström <info@dcweb.nu>
 */

class STC_Main {

	protected $plugin_slug = 'stc';
	protected static $instance = null;
	private $options = array();

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 */
	private function __construct() {

		// store options in to an array
		$this->set_options();

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
		
		// Activate plugin when new blog is added
		//add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

		// load public css
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
	
		// load public scripts
		//add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

	}

	/**
	 * Store options to an array
	 */
	private function set_options(){
		$this->options = get_option( 'stc_settings');
	}

	/**
	 * Create instance of classes
	 */
	public function create_instance(){

    if( class_exists( 'STC_Settings' ) ) {
      $stc_setting = new STC_Settings();
    }

    if( class_exists( 'STC_Cron' ) ) {
      $stc_cron = new STC_Cron();
    }

    if( class_exists( 'STC_Subscribe' ) ) {
      $stc_subscribe = new STC_Subscribe();
    }

	}

	/**
	 * Return the plugin slug.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Fired when the plugin is activated.
	 */
	public static function activate( $network_wide ) {

		
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide  ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_activate();

					restore_current_blog();
				}

			} else {
				self::single_activate();
			}

		} else {
			self::single_activate();
		}
	
	}

	/**
	 * Fired when the plugin is deactivated.
	 */
	public static function deactivate( $network_wide ) {

		
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_deactivate();

					restore_current_blog();

				}

			} else {
				self::single_deactivate();
			}

		} else {
			self::single_deactivate();
		}
		

	}

	/**
	 * Add some settings when plugin is activated
	 * - Cron schedule
	 */
	private static function single_activate() {
		
		//check if event is already scheduled
	  $timestamp = wp_next_scheduled( 'stc_schedule_email' );
	  if( $timestamp == false ){
	    wp_schedule_event( time(), 'hourly', 'stc_schedule_email' );
	  }		
	}

	/**
	 * Remove some settings on deactivation
	 * - delete options
	 * - delete hook
	 */
	private static function single_deactivate() {

		delete_option( 'stc_settings' );

		// kill hook for scheduled event
		wp_clear_scheduled_hook( 'stc_schedule_email' );

	}

	/**
	 * Get all blog ids of blogs
	 */
	private static function get_blog_ids() {

		global $wpdb;

		// get an array of blog ids
		$sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";

		return $wpdb->get_col( $sql );

	}

	/**
	 * Load the plugin text domain for translation.
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( STC_TEXTDOMAIN, false, basename( plugin_dir_path( dirname( __FILE__ ) ) ) . '/languages/' ); 
	}

	/**
	 * Register and enqueue public style sheet.
	 */
	public function enqueue_styles() {
		$options = $this->options;
		
		if( $options['exclude_css'] == false ) // check options for css
			wp_enqueue_style( 'stc-style', STC_PLUGIN_URL . '/css/stc-style.css', array() );
	}

	/**
	 * Register and enqueues public JavaScript files.
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'stc-script', STC_PLUGIN_URL . '/js/stc-scripts.js', array( 'jquery' ) );
	}

}
