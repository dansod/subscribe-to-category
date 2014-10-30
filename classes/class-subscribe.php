<?php
  /**
   * 
   * Class for subscribe
   * @author Daniel Söderström <daniel.soderstrom@cybercom.com>
   * 
   */

  class STC_Subscribe {

  	private $data = array();
  	private $error = array();
    private $notice = array();
    private $settings = array();
    private $post_type = 'stc';

  	function __construct(){
  		$this->init();
  	}

  	/**
  	 * Init method
  	 * @return [type] [description]
  	 */
  	private function init(){

      add_action( 'init', array( $this, 'register_post_type') );
      add_action( 'create_category', array( $this, 'update_subscriber_categories') );

      add_action( 'wp', array( $this, 'collect_get_data' ) );
  		add_action( 'wp', array( $this, 'collect_post_data') );

  		add_shortcode( 'stc-subscribe', array( $this, 'stc_subscribe_render' ) );
      add_action( 'transition_post_status', array( $this, 'new_post_submit' ), 10, 3 );

      // save settings to array
      $this->settings = get_option( 'stc_settings' );

  	}

    /**
     * Adding a newly created category to subscribers who subscribes to all categories
     * @param $category_id The id for newly created category
     */
    public function update_subscriber_categories( $category_id ){

      $args = array(
        'post_type'   => 'stc',
        'post_status' => 'publish',
        'meta_key'    => '_stc_all_categories',
        'meta_value'  => '1',
      );

      $subscribers = get_posts( $args );

      if(!empty( $subscribers )){
        foreach ($subscribers as $s ) {
          
          $categories = $s->post_category;
          $categories[] = $category_id;
    
          $post_data = array(
            'ID'            => $s->ID,
            'post_category' => $categories
          );    

          wp_update_post( $post_data );        
        }
      }

    }

    /**
     * Collecting data through _GET
     * 
     */
    public function collect_get_data(){

      if (isset($_GET['stc_nonce']) && wp_verify_nonce( $_GET['stc_nonce'], 'unsubscribe_user' )) {
        if(isset( $_GET['stc_user'] ))
          $this->unsubscribe_user();
      }   

      if (isset( $_GET['stc_status'] ) && $_GET['stc_status'] == 'success' ) {
        $this->notice[] = __( 'Thanks for your subscription!', STC_TEXTDOMAIN );
        $this->notice[] = __( 'If you want to unsubscribe there is a link for unsubscription attached in the email.', STC_TEXTDOMAIN );
      }

    }

    /**
     * Unsubscribe user from subscription
     * 
     */
    private function unsubscribe_user(){
      global $wpdb;
      $meta_key = '_stc_hash';
      $meta_value = $_GET['stc_user'];

      $user_id = $wpdb->get_var( $wpdb->prepare(
        "SELECT id FROM $wpdb->posts AS post 
        LEFT JOIN $wpdb->postmeta AS meta ON post.ID = meta.post_id 
        WHERE meta.meta_key = %s AND meta.meta_value = %s 
        AND post.post_type = %s
        ", $meta_key, $meta_value, $this->post_type )
      );



      if(empty( $user_id ))
        return false;

        $subscriber_email = get_the_title( $user_id );
        wp_delete_post( $user_id );

        $notice[] = sprintf( __( 'We have successfully removed your email %s from our database.', STC_TEXTDOMAIN ), '<span class="stc-notice-email">' . $subscriber_email . '</span>' );
        
        return $this->notice = $notice;

    }

    /**
     * Listen for every new post and update post meta if post type 'post'
     * @param  string $old_status 
     * @param  string $new_status 
     * @param  object $post
     */
    public function new_post_submit( $old_status, $new_status, $post ){

      // bail if not the correct post type
      if( $post->post_type != 'post' )
        return false;

      // We wont send email notice if a post i updated
      if( $new_status == 'new' ){
        update_post_meta( $post->ID, '_stc_notifier_status', 'outbox' ); // updating post meta
      }
      
    }


    /**
     * Sending an email to a subscriber with a confirmation link to unsubscription
     * @param  int $stc_id post id for subscriber
     * @return [type]         [description]
     */
    private function send_unsubscribe_mail( $stc_id = '' ){
      
      // bail if not numeric
      if( empty( $stc_id ) || !is_numeric( $stc_id ) )
        return false;

      // get title and user hash
      $stc['email'] = get_the_title( $stc_id );
      $stc['hash'] = get_post_meta( $stc_id, '_stc_hash', true );

      // Website name to print as sender
      $website_name = get_bloginfo( 'name' );


      $email_from = $this->settings['email_from'];
      if( !is_email( $email_from ) )
        $email_from = get_option( 'admin_email' ); // set admin email if email settings is not valid

      // Email headers
      $headers  = 'MIME-Version: 1.0' . "\r\n";
      $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
      $headers .= 'From: '. $website_name.' <'.$email_from.'>' . "\r\n";

      // Setting subject
      $title = sprintf( __('Unsubscribe from %s', STC_TEXTDOMAIN),  get_bloginfo( 'name' ) );


      ob_start(); // start buffer
      $this->email_html_content( $stc );
      $message = ob_get_contents();
      ob_get_clean();
      
      // encode subject to match åäö for some email clients
      $subject = '=?UTF-8?B?'.base64_encode( $title ).'?=';
      wp_mail( $stc['email'], $subject, $message, $headers );
  }

    /**
     * Returns the content for email unsubscription
     * @param  array $stc 
     * @return string
     */
    private function email_html_content( $stc = '' ){
      if(empty( $stc ))
        return false;
      ?>
        <h3><?php printf( __('Unsubscribe from %s', STC_TEXTDOMAIN ), get_bloginfo( 'name' ) ); ?></h3>
        <div style="margin-top: 20px;"><a href="<?php echo wp_nonce_url( get_permalink() . '?stc_user=' . $stc['hash'], 'unsubscribe_user', 'stc_nonce' ); ?>"><?php _e('Follow this link to confirm your unsubscription', STC_TEXTDOMAIN ); ?></a></div>
      <?php

    }


    /**
     * Collect data from _POST for subscription
     * @return string Notice to user
     *
     */
  	public function collect_post_data(){
  		
      // correct form submitted
  		if( isset( $_POST['action']) && $_POST['action'] == 'stc_subscribe_me' ) {

        // if there is an unsubscription event
        if( isset( $_POST['stc-unsubscribe'] ) && $_POST['stc-unsubscribe'] == 1 ){

          // check if email is valid
          if( is_email( $_POST['stc_email'] ) )
            $data['email'] = $_POST['stc_email'];
          else
            $error[] = __( 'You need to enter a valid email address', STC_TEXTDOMAIN );
          
          // check if user exists and through error if not          
          if(empty( $error )){

            $this->data = $data;
            $result = $this->subscriber_exists();

            if( empty( $result ))
              $error[] = __( 'Email address not found in database', STC_TEXTDOMAIN );
          }

          if(! empty ($error ))
            return $this->error = $error;

          $this->send_unsubscribe_mail( $result );

          $notice[] = __('We have received your request to unsubscribe from our newsfeed. Please check your email and confirm your unsubscription.', STC_TEXTDOMAIN );

          return $this->notice = $notice;
        }


				// bail if nonce fail
				if( ! isset( $_POST['stc_nonce'] ) || ! wp_verify_nonce( $_POST['stc_nonce'], 'wp_nonce_stc' ) )
   				wp_die('Error when validating nonce ...');


        // check if email is valid and save an error if not
 				$error = false;
 				if( is_email( $_POST['stc_email'] ) )
 					$data['email'] = $_POST['stc_email'];
 				else
 					$error[] = __( 'You need to enter a valid email address', STC_TEXTDOMAIN );

        
        // subscribe for all categories
        $data['all_categories'] = false;
        if( isset( $_POST['stc_all_categories']) )
          $data['all_categories'] = true;

        // is there a category selected
 				if(! empty( $_POST['stc_categories'] ))
 					$data['categories'] = $_POST['stc_categories'];
 				else
 					$error[] = __( 'You need to select some categories', STC_TEXTDOMAIN );

        // save user to subscription post type if no error
 				if(empty( $error )){
 					$this->data = $data;
 					$post_id = $this->insert_or_update_subscriber();

          $stc_hash = get_post_meta( $post_id, '_stc_hash', true );
          $url_querystring = '?stc_status=success&stc_hash=' . $stc_hash;

				}else{
 					return $this->error = $error;
 				}

				wp_redirect( get_permalink() . $url_querystring );
        exit;
			
      }
  			

  	}

  	/**
  	 * Check if subscriber already exists
  	 * @return int post_id
  	 */
  	private function subscriber_exists(){
  		global $wpdb;
  		$data = $this->data;
  		
  		$result = $wpdb->get_row( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = %s", $data['email'], 'stc') );

  		if(empty( $result ))
  			return false;

  		return $result->ID;

  	}

  	/**
  	 * Update user with selected categories if user exists, else add user as new user.
  	 * @param  string $post_data currently not in use
  	 */
  	private function insert_or_update_subscriber( $post_data = '' ){
  		$data = $this->data;

  		if(empty( $data ))
  			$data = $post_data;

  		if(empty( $data ))
  			return false;

      // already exists, grab the post id
  		$post_id = $this->subscriber_exists();

  		$post_data = array(
  			'ID'            => $post_id,
				'post_type'     => 'stc',
			  'post_title'    => $data['email'],
			  'post_status'   => 'publish',
			  'post_author'   => 1,
			  'post_category' => $data['categories']
			);		

  		// update post if subscriber exist, else insert as new post
  		if(!empty( $post_id )){
  			$post_id = wp_update_post( $post_data );
  		}else{
  			$post_id = wp_insert_post( $post_data );
        update_post_meta( $post_id, '_stc_hash', md5( $data['email'].time() ) );
  		}

      // update post meta if the user subscribes to all categories
      if( $data['all_categories'] == true )
        update_post_meta( $post_id, '_stc_all_categories', 1 );
      else 
        delete_post_meta( $post_id, '_stc_all_categories' );

      return $post_id;
  	
  	}

  	/**
  	 * Render html to subscribe to categories
  	 * @return [type] [description]
     *
     * @todo add some filter 
  	 */
  	public function stc_subscribe_render(){
      //start buffering
  		ob_start();
  		$this->html_render();
  		$form = ob_get_contents();
  		ob_get_clean();
  		//$form = apply_filters( 'stc_form', $form, 'teststring' );
  		return $form;
  	}


    /**
     * Adding jQuery to footer
     */
    public function add_script_to_footer(){
      ?>
      <script type="text/javascript">
      jQuery(function($){
          
          $('#stc-unsubscribe-checkbox').click( function() {
            
            if( $(this).prop('checked') == true ) {
              $('.stc-categories').hide();
              $('#stc-subscribe-btn').hide();
              $('#stc-unsubscribe-btn').show();
            }else{
              $('.stc-categories').show();
              $('#stc-subscribe-btn').show();
              $('#stc-unsubscribe-btn').hide();
            }
          });

          $('#stc-all-categories').click( function() {
            if( $(this).prop('checked') == true ) {
              $('div.stc-categories-checkboxes').hide();
              $('div.stc-categories-checkboxes input[type=checkbox]').each(function () {
              $(this).prop('checked', true);
            }); 
            }else{
              $('div.stc-categories-checkboxes').show();
              $('div.stc-categories-checkboxes input[type=checkbox]').each(function () {
              $(this).prop('checked', false);
            });
            }              
            
          });

      });
      </script>

      <?php

    }

  	/**
  	 * Html for subscribe form
  	 * @return [type] [description]
  	 */
  	public function html_render(){

      // add hook when we have a request to render html
  		add_action('wp_footer', array( $this, 'add_script_to_footer' ), 20);
  		
      
      // getting all categories
      $args = array( 'hide_empty' => 0 );
  		$cats = get_categories( $args );


      // if error store email address in field value so user dont need to add it again
  		if(!empty( $this->error)){
  			if( isset( $_POST['stc_email']) )
  				$email = $_POST['stc_email'];
  		}

      // Is there a unsubscribe action
      $post_stc_unsubscribe = false;
      if( isset( $_POST['stc-unsubscribe'] ) && $_POST['stc-unsubscribe'] == 1 )
        $post_stc_unsubscribe = 1;

  		?>

  		<div class="stc-subscribe-wrapper well">

  			<?php if(!empty( $this->error )) : //printing error if exists ?>
  				<?php foreach( $this->error as $error ) : ?>
  					<div class="stc-error"><?php echo $error; ?></div>
  				<?php endforeach; ?>
  			<?php endif; ?>

        <?php if(!empty( $this->notice )) : //printing notice if exists ?>
          <?php foreach( $this->notice as $notice ) : ?>
            <div class="stc-notice"><?php echo $notice; ?></div>
          <?php endforeach; ?>
        <?php else: ?>

  			<form role="form" method="post">
          <div class="form-group">
  				  <label for="stc-email"><?php _e( 'E-mail Address: ', STC_TEXTDOMAIN ); ?></label>
  				  <input type="text" id="stc-email" class="form-control" name="stc_email" value="<?php echo !empty( $email ) ? $email : NULL; ?>"/>
          </div>

          <div class="checkbox">
            <label>
              <input type="checkbox" id="stc-unsubscribe-checkbox" name="stc-unsubscribe" value="1" <?php checked( '1', $post_stc_unsubscribe ); ?>>
              <?php _e( 'Unsubscribe me', STC_TEXTDOMAIN ) ?>
            </label>
          </div>

          <div class="stc-categories"<?php echo $post_stc_unsubscribe == 1 ? ' style="display:none;"' : NULL; ?>>
            <h3><?php _e('Categories', STC_TEXTDOMAIN ); ?></h3>
            <div class="checkbox">
              <label>
                <input type="checkbox" id="stc-all-categories" name="stc_all_categories" value="1">
                <?php _e('All categories', STC_TEXTDOMAIN ); ?>
              </label>
            </div>
            <div class="stc-categories-checkboxes">
    				<?php foreach ($cats as $cat ) : ?>
            <div class="checkbox">
      				<label>
      					<input type="checkbox" name="stc_categories[]" value="<?php echo $cat->cat_ID ?>">
      					<?php echo $cat->cat_name; ?>
      				</label>
            </div>
  				  <?php endforeach; ?>
          </div><!-- .stc-categories-checkboxes -->
          </div><!-- .stc-categories -->

  				<input type="hidden" name="action" value="stc_subscribe_me" />
  				<?php wp_nonce_field( 'wp_nonce_stc', 'stc_nonce', true, true ); ?>
          <button id="stc-subscribe-btn" type="submit" class="btn btn-default"<?php echo $post_stc_unsubscribe == 1 ? ' style="display:none;"' : NULL; ?>><?php _e( 'Subscribe me', STC_TEXTDOMAIN ) ?></button>
          <button id="stc-unsubscribe-btn" type="submit" class="btn btn-default"<?php echo $post_stc_unsubscribe != 1 ? ' style="display:none;"' : NULL; ?>><?php _e( 'Unsubscribe', STC_TEXTDOMAIN ) ?></button>
  			</form>
        <?php endif; ?>

  		</div><!-- .stc-subscribe-wrapper -->

  		<?php
  	}

  	/**
  	 * Register custom post type for subscribers
  	 */
  	public function register_post_type(){

			$labels = array( 
			    'name' => _x( 'Subscribers', STC_TEXTDOMAIN ),
			    'singular_name' => _x( 'Subscribe', STC_TEXTDOMAIN ),
			    'add_new' => _x( 'Add new subscriber', STC_TEXTDOMAIN ),
			    'add_new_item' => __( 'Add new subscriber', STC_TEXTDOMAIN ),
			    'edit_item' => __( 'Edit subscriber', STC_TEXTDOMAIN ),
			    'new_item' => __( 'New subscriber', STC_TEXTDOMAIN ),
			    'view_item' => __( 'Show subscriber', STC_TEXTDOMAIN ),
			    'search_items' => __( 'Search subscribers', STC_TEXTDOMAIN ),
			    'not_found' => __( 'Not found', STC_TEXTDOMAIN ),
			    'not_found_in_trash' => __( 'Nothing found in trash', STC_TEXTDOMAIN ),
			    'menu_name' => __( 'Subscribers', STC_TEXTDOMAIN ),
			);

			$args = array( 
			    'labels' => $labels,
			    'hierarchical' => true,
			    'supports' => array( 'title' ),
			    'public' => true,
			    'show_ui' => true,
			    'show_in_menu' => true,
			    'show_in_nav_menus' => true,
			    'publicly_queryable' => false,
			    'exclude_from_search' => true,
			    'has_archive' => false,
			    'query_var' => true,
			    'can_export' => true,
			    'rewrite' => true,
			    'capability_type' => 'post',
			    'taxonomies' => array( 'category' )
			);

			register_post_type( 'stc', $args );

  	}

  }

?>