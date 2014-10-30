<?php
  /**
   * 
   * Class for the settings page
   * @author Daniel Söderström <daniel.soderstrom@cybercom.com>
   * 
   */

  class STC_Settings {
    
    private $options; // holds the values to be used in the fields callbacks

    /**
     * Constructor
     */
    public function __construct() {

      // only in admin mode
      if( is_admin() ) {    
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
      }

    }

    /**
     * Add options page
     */
    public function add_plugin_page() {

      add_options_page(
          __( 'Subscribe to Category', STC_TEXTDOMAIN ), 
          __( 'Subscribe', STC_TEXTDOMAIN ), 
          'manage_options', 
          'stc-subscribe-settings', 
          array( $this, 'create_admin_page' )
      );

    }

    /**
     * Options page callback
     */
    public function create_admin_page() {
      
      // Set class property
      $this->options = get_option( 'stc_settings' );
      ?>
      <div class="wrap">
        <?php screen_icon(); ?>
        <h2><?php _e('Settings for subscribe to category', STC_TEXTDOMAIN ); ?></h2>           
        <form method="post" action="options.php">
        <?php
            // print out all hidden setting fields
            settings_fields( 'stc_option_group' );   
            do_settings_sections( 'stc-subscribe-settings' );
            submit_button(); 
        ?>
        </form>
      </div>
      <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {        
        register_setting(
            'stc_option_group', // Option group
            'stc_settings', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        // Google Maps
        add_settings_section(
            'setting_email_id', // ID
            __( 'E-mail settings', STC_TEXTDOMAIN ), // Title
            '', //array( $this, 'print_section_info' ), // Callback
            'stc-subscribe-settings' // Page
        );  


        add_settings_field(
            'stc_email_from',
            __( 'E-mail from: ', STC_TEXTDOMAIN ),
            array( $this, 'stc_email_from_callback' ), // Callback
            'stc-subscribe-settings', // Page
            'setting_email_id' // Section           
        );

    }

    /**
     * Sanitize setting fields
     * @param array $input 
     */
    public function sanitize( $input ) {
        $new_input = array();

        if( isset( $input['email_from'] ) )
            $new_input['email_from'] = sanitize_text_field( $input['email_from'] );

        return $new_input;
    }

    /** 
     * Printing section text
     */
    public function print_section_info(){
      _e( 'Add your E-mail settings', STC_TEXTDOMAIN );
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function stc_email_from_callback() {
      $default_email = get_option( 'admin_email' );
      ?>
        <input type="text" id="email_from" class="regular-text" name="stc_settings[email_from]" value="<?php echo isset( $this->options['email_from'] ) ? esc_attr( $this->options['email_from'] ) : '' ?>" />
        <p class="description"><?php printf( __( 'Enter the e-mail address for the sender, if empty the admin e-mail address %s is going to be used as sender.', STC_TEXTDOMAIN ), $default_email ); ?></p>
        <?php
    }

  }

?>