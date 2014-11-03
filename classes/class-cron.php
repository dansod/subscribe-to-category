<?php
  /**
   * 
   * Class for cron job
   * @author Daniel Söderström <info@dcweb.nu>
   * 
   */

  if( class_exists( 'STC_Cron' ) ) {
    $stc_cron = new STC_Cron();
  }

  class STC_Cron {
    private $settings = array();

    public function __construct(){
      $this->init(); 		
    }

    public function init(){
      add_action( 'stc_schedule_email', array( $this, 'stc_send_email' ) );
      $this->settings = get_option( 'stc_settings' );
    }

    /**
     * On the scheduled action hook, run a function.
     */
    public function stc_send_email() {
      global $wpdb;

      // get posts with a post meta value in outbox
      $meta_key = '_stc_notifier_status';
      $meta_value = 'outbox';

      $args = array(
        'post_type'   => 'post',
        'numberposts' => -1,
        'meta_key'    => $meta_key,
        'meta_value'  => $meta_value
      );

      $posts = get_posts( $args );

      // add categories to object
      $outbox = array();
      foreach ( $posts as $p ) {
        $p->categories = array();

        $cats = get_the_category( $p->ID );
        foreach( $cats as $cat ){
          $p->categories[] = $cat->term_id;
        }
        $outbox[] = $p;
      }

      if(!empty( $outbox )){
        $this->send_notifier( $outbox );
      }
    }

    /**
     * Send notifier to subscribers
     * @param  object $outbox
     */
    private function send_notifier( $outbox = '' ){
      $subscribers = $this->get_subscribers();
      
      $i = 0;
      $emails = array();
      foreach ($outbox as $post ) {
        
        foreach( $subscribers as $subscriber ) {      
          
          foreach( $subscriber->categories as $categories ) {
              
            if(in_array( $categories, $post->categories )){
              $emails[$i]['subscriber_id'] = $subscriber->ID;
              $emails[$i]['hash'] = get_post_meta( $subscriber->ID, '_stc_hash', true );
              $emails[$i]['email'] = $subscriber->post_title;
              $emails[$i]['post_id'] = $post->ID;
              $emails[$i]['post'] = $post;
              $i++; 
            }                 
          
          }
        
        }
      
      }

      //remove duplicates, we will just send one email to subscriber
      $emails = array_intersect_key( $emails , array_unique( array_map('serialize' , $emails ) ) ); 

      $website_name = get_bloginfo( 'name' );
      $email_title = $this->settings['title'];
      echo $email_title;

      

      $email_from = $this->settings['email_from'];
      if( !is_email( $email_from ) )
        $email_from = get_option( 'admin_email' ); // set admin email if email settings is not valid

      $headers  = 'MIME-Version: 1.0' . "\r\n";
      $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
      $headers .= 'From: '. $website_name.' <'.$email_from.'>' . "\r\n";
      
      // loop through subscribers and send notice
      foreach ($emails as $email ) {

        ob_start(); // start buffering and get content
        $this->email_html_content( $email );
        $message = ob_get_contents();
        ob_get_clean();

        $email_subject = $email_title;
        if( empty( $email_title ))
          $email_subject = $email['post']->post_title;

        $subject = '=?UTF-8?B?'.base64_encode( $email_subject ).'?=';

        wp_mail( $email['email'], $subject, $message, $headers );

      }

      //update some postmeta that email is sent
      foreach ($outbox as $post ) {
        update_post_meta( $post->ID, '_stc_notifier_status', 'sent' );
        update_post_meta( $post->ID, '_stc_notifier_sent_time', mysql2date( 'Y-m-d H:i:s', time() ) );
      }
        
    }

    /**
     * Render html to email. 
     * Setting limit to content as we still want the user to click and visit our site.
     * @param  object $email
     */    
    private function email_html_content( $email ){
      ?>
      <h3><a href="<?php get_permalink( $email['post_id']) ?>"><?php echo $email['post']->post_title; ?></a></h3>
      <div><?php echo apply_filters('the_content', $this->string_cut( $email['post']->post_content, 130 ) );?></div>
      <div style="border-bottom: 1px solid #cccccc; padding-bottom: 10px;"><a href="<?php echo get_permalink( $email['post_id'] ); ?>"> <?php _e('Click here to read full story', STC_TEXTDOMAIN ); ?></a></div>
      <div style="margin-top: 20px;"><a href="<?php echo wp_nonce_url( get_bloginfo('url') . '?stc_user=' . $email['hash'], 'unsubscribe_user', 'stc_nonce' ); ?>"><?php _e('Unsubscribe me', STC_TEXTDOMAIN ); ?></a></div>
      <?php
    }


    /**
     * Cut a text string closest word on a given length.
     * @param  string $string
     * @param  int $max_length
     * @return string
     */
    private function string_cut( $string, $max_length ){  

      // remove shortcode if there is
      $string = strip_shortcodes( $string ); 

      if( strlen( $string ) > $max_length ){  
        $string = substr( $string, 0, $max_length );  
        $pos = strrpos( $string, ' ' );  
          
        if($pos === false) {  
          return substr($string, 0, $max_length)." ... ";  
        }  
        return substr($string, 0, $pos)." ... ";  

      }else{  
        return $string;  
      }  
    }  

    /**
     * Get all subscribers with subscribed categories
     * @return object Subscribers
     */
    private function get_subscribers(){

      $args = array(
        'post_type'   => 'stc',
        'numberposts' => -1,
        'post_status' => 'publish'
      );

     $stc = get_posts( $args );

     $subscribers = array();
     foreach ($stc as $s) {
      $s->categories = array();

      $cats = get_the_category( $s->ID );
      foreach ($cats as $cat ) {
        $s->categories[] = $cat->term_id;
      }

      $subscribers[] = $s;
       
     }

     return $subscribers;

    }

}

?>