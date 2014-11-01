<?php
  /**
   * 
   * Class for the settings page
   * @author Daniel Söderström <info@dcweb.nu>
   * 
   */
  
if( class_exists( 'STC_Settings' ) ) {
  $stc_setting = new STC_Settings();
}

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
    public function page_init(){        

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

        add_settings_field(
            'stc_title',
            __( 'Title: ', STC_TEXTDOMAIN ),
            array( $this, 'stc_title_callback' ), // Callback
            'stc-subscribe-settings', // Page
            'setting_email_id' // Section           
        );

        register_setting(
          'stc_option_group', // Option group
          'stc_settings', // Option name
          array( $this, 'input_validate_sanitize' ) // Callback function for validate and sanitize input values
        );

    }

    /**
     * Sanitize setting fields
     * @param array $input 
     */
    public function input_validate_sanitize( $input ) {
        //util::debug( $input );
        $output = array();

        if( isset( $input['email_from'] ) ){

          // sanitize email input
          $output['email_from'] = sanitize_email( $input['email_from'] ); 

          if ( is_email( $output['email_from'] ) || !empty( $output['email_from'] ) )
            $output['email_from'] = $input['email_from'];
          else
            add_settings_error( 'setting_email_id', 'invalid-email', __( 'You have entered an invalid email.', STC_TEXTDOMAIN ) );


        }

        if( isset( $input['title'] ) ){
          $output['title'] = $input['title'];
          //echo "title";
        }

        return $output;

/*
        foreach( $input as $key => $value  ){

          switch ( $key ) {
            case 'email_from':
              # code...
              break;

            case 'title':
              # code...
              break;
    
          }


        }

  */    
/*
        $new_input = array();

        if( isset( $input['email_from'] ) )
            $new_input['email_from'] = sanitize_text_field( $input['email_from'] );

        return $new_input;
        */
    }


 public function sunny_demo_validate_input_cloudflare_email ( $input ) {
      // Get old value from DB
      $sunny_cloudflare_email = get_option( 'sunny_demo_cloudflare_email' );

      // Don't trust users
      $input = sanitize_email( $input );

      if ( is_email( $input ) || !empty( $input ) ) {
          $output = $input;
      }
      else
        add_settings_error( 'sunny_demo_cloudflare_account_section', 'invalid-email', __( 'You have entered an invalid email.', $this->plugin_slug ) );

    return $output;

      } //end sunny_demo_validate_input_cloudflare_email



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

    /** 
     * Get the settings option array and print one of its values
     */
    public function stc_title_callback() {
      $default_email = get_option( 'admin_email' );
      ?>
        <input type="text" id="email_from" class="regular-text" name="stc_settings[title]" value="<?php echo isset( $this->options['title'] ) ? esc_attr( $this->options['title'] ) : '' ?>" />
        <p class="description"><?php printf( __( 'Enter the e-mail address for the sender, if empty the admin e-mail address %s is going to be used as sender.', STC_TEXTDOMAIN ), $default_email ); ?></p>
        <?php
    }

  }

?>