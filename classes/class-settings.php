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
        add_action( 'admin_init', array( $this, 'register_settings' ) );
      }

    }

    /**
     * Add options page
     */
    public function add_plugin_page() {
      
      if( isset( $_POST['action'] ) && $_POST['action'] == 'export' ){
        if( $_POST['export_to_excel'] == 1 )
          $this->export_to_excel();
      }
      
      //$this->export_to_excel();
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
            do_settings_sections( 'stc-style-settings' );
            submit_button(); 
        ?>
        </form>
        <?php $this->export_to_excel_form(); ?>
      </div>
      <?php
    }

    /**
     * Register and add settings
     */
    public function register_settings(){        

        // Email settings
        add_settings_section(
            'setting_email_id', // ID
            __( 'E-mail settings', STC_TEXTDOMAIN ), // Title
            '', //array( $this, 'print_section_info' ), // Callback
            'stc-subscribe-settings' // Page
        );  

        // Styleing settings
        add_settings_section(
            'setting_style_id', // ID
            __( 'Stylesheet (CSS) settings', STC_TEXTDOMAIN ), // Title
            '', //array( $this, 'print_section_info' ), // Callback
            'stc-style-settings' // Page
        );  

        add_settings_field(
            'stc_custom_css',
            __( 'Custom CSS: ', STC_TEXTDOMAIN ),
            array( $this, 'stc_css_callback' ), // Callback
            'stc-style-settings', // Page
            'setting_style_id' // Section           
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
        }

        if( isset( $input['exclude_css'] ) ){
          $output['exclude_css'] = $input['exclude_css'];
        }

        return $output;
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

    /** 
     * Get the settings option array and print one of its values
     */
    public function stc_title_callback() {
      ?>
        <input type="text" id="email_from" class="regular-text" name="stc_settings[title]" value="<?php echo isset( $this->options['title'] ) ? esc_attr( $this->options['title'] ) : '' ?>" />
        <p class="description"><?php printf( __( 'Enter the e-mail address for the sender, if empty the admin e-mail address %s is going to be used as sender.', STC_TEXTDOMAIN ), $default_email ); ?></p>
        <?php
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function stc_css_callback() { ?>

      <label for="exclude_css"><input type="checkbox" value="1" id="exclude_css" name="stc_settings[exclude_css]" <?php checked( '1', $this->options['exclude_css'] ); ?>><?php _e('Exclude custom CSS', STC_TEXTDOMAIN ); ?></label>
      <p class="description"><?php _e('Check this option if your theme supports Bootstrap framework or if you want to place your own CSS for Subscribe to Category in your theme.', STC_TEXTDOMAIN ); ?></p>


    <?php
    }



    public function export_to_excel_form(){
      ?>
      <h3><?php _e( 'Export', STC_TEXTDOMAIN ); ?></h3>
      <form method="post" action="options-general.php?page=stc-subscribe-settings">
      <table class="form-table">
        <tbody>
          <tr>
            <th scope="row"><?php _e('Export options', STC_TEXTDOMAIN ); ?></th>
            <td>
              <label for="export-to-excel"><input type="checkbox" name="export_to_excel" id="export-to-excel" value="1"><?php _e( 'Export to Excel', STC_TEXTDOMAIN ); ?></label>
            </td>
          </tr>
        </tbody>
      </table>
      <input type="hidden" value="export" name="action">
      <input type="submit" value="Export to Excel" class="button button-primary" id="submit" name="">
      </form>
      
      <?php
    }


  /**
   * Export method for excel
   */
  public function export_to_excel(){
    //global $wpdb;
    //
    $args = array(
      'post_type'     => 'stc',
      'post_status'   => 'publish',
    );

    $posts = get_posts( $args );
    $i = 0;
    $export = array();
    foreach ($posts as $p) {
      
      $cats = get_the_category( $p->ID ); 
      foreach ($cats as $c) {
        $c_name .= $c->name . ', ';
      }
      $in_categories = substr( $c_name, 0, -2);
      $c_name = false; // unset variable

      $export[$i]['id'] = $p->ID;
      $export[$i]['email'] = $p->post_title;
      $export[$i]['categories'] = $in_categories;
      $export[$i]['created'] = $p->post_date;
      
      $i++;
    }
    //util::debug( $export );

/*
    $results = $wpdb->get_results( "SELECT * FROM $table", ARRAY_A );
    
      // filename for download 
      */
      $time = date('Ymd_His'); 
      $filename = $time . '.xls';

      header("Content-Disposition: attachment; filename=\"$filename\""); 
      header("Content-Type:   application/vnd.ms-excel; ");
      header("Content-type:   application/x-msexcel; ");


      $flag = false; 
      foreach ($export as $row ) {
        if(! $flag ) { 
          // display field/column names as first row 
          echo "\r\n" . implode("\t", array_keys( $row )) . "\r\n"; 
          $flag = true; 
        } 

        array_walk($row, array($this, 'clean_data_for_excel') ); 
        echo implode("\t", array_values($row) ). "\r\n"; 
      } 

      exit;

    }

     
     public function clean_data_for_excel( &$str ) { 
      $str = iconv('UTF-8', 'ISO-8859-1', $str );
      $str = preg_replace("/\t/", "\\t", $str ); 
      $str = preg_replace("/\r?\n/", "\\n", $str ); 
    } 





  }

?>