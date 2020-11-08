<?php
/**
 * @author AazzTech
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ATBDP_Metabox {

	/**
	 * Add meta boxes for ATBDP_POST_TYPE and ATBDP_SHORT_CODE_POST_TYPE
	 * and Save the meta data
	 */
	public function __construct() {
		if ( is_admin() ) {
			add_action('add_meta_boxes_'.ATBDP_POST_TYPE, array($this, 'listing_metabox'));
			add_action('transition_post_status',	array($this, 'publish_atbdp_listings'), 10, 3);
			add_action( 'edit_post', array($this, 'save_post_meta'), 10, 2);
			add_action('post_submitbox_misc_actions', array($this, 'post_submitbox_meta'));
			// load dynamic fields
			add_action( 'wp_ajax_atbdp_dynamic_admin_listing_form', array( $this, 'atbdp_dynamic_admin_listing_form' ) );
		}
	}

	public function atbdp_dynamic_admin_listing_form() {
		$directory_type = sanitize_text_field( $_POST['directory_type'] );
		$listing_id   = sanitize_text_field( $_POST['listing_id'] );
		ob_start();
		$this->render_listing_meta_fields( $directory_type, $listing_id );
		echo ob_get_clean();
		die();
	}

	public function listing_metabox( $post ) {
		add_meta_box('listing_form_info', __('Listing Information', 'directorist'), array($this, 'listing_form_info_meta'), ATBDP_POST_TYPE, 'normal', 'high');
	}

	public function render_listing_meta_fields( $type, $id ) {
		$form_data = $this->build_form_data( $type );
		foreach ( $form_data as $section ) {
			Directorist_Listing_Forms::instance($id)->add_listing_section_template( $section );
		}
	}

	public function listing_form_info_meta( $post ) {
		$all_types     = get_terms(array(
			'taxonomy'   => ATBDP_TYPE,
			'hide_empty' => false,
		));
		$terms   =  get_the_terms( $post->ID, ATBDP_TYPE );
		$current_type = !empty($terms) ? $terms[0]->term_id : '';
		$current_type = get_post_meta($post->ID, '_directory_type', true);
		wp_nonce_field( 'listing_info_action', 'listing_info_nonce' );
		?>
		<label><?php _e( 'Listing Type', 'directorist' ); ?></label>
		<select name="directory_type">
			<option value=""><?php _e( 'Select Listing Type', 'directorist' ); ?></option>
			<?php foreach ( $all_types as $type ):
			$default = get_term_meta( $type->term_id, '_default', true );
			$selected = selected( $type->term_id, $current_type );
			$selected = !empty( $current_type ) ? $selected : ( $default ? ' selected="selected"' : '' );
				?>
				<option value="<?php echo esc_attr( $type->term_id ); ?>" <?php echo $selected; ?> ><?php echo esc_attr( $type->name ); ?></option>
			<?php endforeach; ?>
		</select>
		<div id="directiost-listing-fields_wrapper" data-id="<?php echo esc_attr( $post->ID )?>"></div>
		<?php
	}

	public function build_form_data( $type ) {
		$form_data              = array();
		$submission_form_fields = get_term_meta( $type, 'submission_form_fields', true );
		$excluded_fields = array( 'title', 'description', 'location', 'category', 'tag', 'image_upload', 'privacy_policy', 'terms_conditions' );

		if ( !empty( $submission_form_fields['groups'] ) ) {
			foreach ( $submission_form_fields['groups'] as $group ) {

				$section           = $group;
				$section['fields'] = array();

				foreach ( $group['fields'] as $field ) {

					if ( in_array( $field, $excluded_fields ) ) {
						continue;
					}

					$section['fields'][ $field ] = $submission_form_fields['fields'][ $field ];
				}

				$form_data[] = $section;
			}
		}

		return $form_data;
	}

	/**
	 * @since 5.4.0
	 */
	public function publish_atbdp_listings( $new_status, $old_status, $post ){
		$nonce = isset( $_REQUEST['_wpnonce'] ) ? $_REQUEST['_wpnonce'] : null;
		if ( ($post->post_type == 'at_biz_dir') && ( $old_status == 'pending'  &&  $new_status == 'publish' ) && !wp_verify_nonce( $nonce, 'quick-publish-action' ) ){
			do_action('atbdp_listing_published', $post->ID);//for sending email notification
		}
	}

	/**
	 * It outputs expiration date and featured checkbox custom field on the submit box metabox.
	 * @param WP_Post $post
	 */
	public function post_submitbox_meta($post)
	{
		if(ATBDP_POST_TYPE !=$post->post_type) return; // vail if it is not our post type
		// show expiration date and featured listing.
		$expire_in_days         = get_directorist_option('listing_expire_in_days');
		$f_active               = get_directorist_option('enable_featured_listing');
		$never_expire           = get_post_meta($post->ID, '_never_expire', true);
		$never_expire           = !empty($never_expire) ? (int) $never_expire : (empty($expire_in_days) ? 1 : 0);

		$e_d                    = get_post_meta($post->ID, '_expiry_date', true);
		$e_d                    = !empty($e_d) ? $e_d : calc_listing_expiry_date();
		$expiry_date            = atbdp_parse_mysql_date($e_d);

		$featured               = get_post_meta($post->ID, '_featured', true);
		$listing_type           = get_post_meta($post->ID, '_listing_type', true);
		$listing_status         = get_post_meta($post->ID, '_listing_status', true);
		$default_expire_in_days = !empty($default_expire_in_days) ? $default_expire_in_days : '';
		// load the meta fields
		$data = compact('f_active', 'never_expire', 'expiry_date', 'featured', 'listing_type', 'listing_status', 'default_expire_in_days');
		ATBDP()->load_template('admin-templates/listing-form/expiration-featured-fields', array('data'=> $data));
	}

	/**
	 * Save Meta Data of ATBDP_POST_TYPE
	 * @param int       $post_id    Post ID of the current post being saved
	 * @param object    $post       Current post object being saved
	 */
	public function save_post_meta( $post_id, $post ) {
		
		if ( ! $this->passSecurity($post_id, $post) )  return; // vail if security check fails
		$metas = array();
		$expire_in_days = get_directorist_option('listing_expire_in_days');
		$p = $_POST; // save some character
		$listing_type = !empty( $_POST['directory_type'] ) ? sanitize_text_field( $_POST['directory_type'] ) : '';
		$submission_form_fields = [];
		$metas = [];
		if( $listing_type ){
		$term = get_term_by( 'id', $listing_type, 'atbdp_listing_types' );
		$submission_form = get_term_meta( $term->term_id, 'submission_form_fields', true );
		$submission_form_fields = $submission_form['fields'];
		}
		foreach( $submission_form_fields as $key => $value ){
			if( 'pricing' === $key ) {
				$metas[ '_atbd_listing_pricing' ] = $p['atbd_listing_pricing'] ? $p['atbd_listing_pricing'] : '';
				$metas[ '_price' ] = $p['price'] ? $p['price'] : '';
				$metas[ '_price_range' ] = $p['price_range'] ? $p['price_range'] : '';
			}
			$field_key = !empty( $value['field_key'] ) ? $value['field_key'] : '';
			if( ( $field_key !== 'listing_title' ) && ( $field_key !== 'listing_content' ) && ( $field_key !== 'tax_input' ) ){
				$key = '_'. $field_key;
				$metas[ $key ] = !empty( $p[ $field_key ] ) ? $p[ $field_key ] : '';
			}                    
		}
		$metas['_directory_type'] = $listing_type;
		if( !empty( $metas['_directory_type'] ) ){
			wp_set_object_terms($post_id, (int)$metas['_directory_type'], 'atbdp_listing_types');
		}
		$metas['_never_expire']      = !empty($p['never_expire']) ? (int) $p['never_expire'] : (empty($expire_in_days) ? 1 : 0);
		$metas['_featured']          = !empty($p['featured'])? (int) $p['featured'] : 0;
		$exp_dt = !empty($p['exp_date'])?atbdp_sanitize_array($p['exp_date']):array(); // get expiry date from the $_POST and then later sanitize it.
		//prepare expiry date, if we receive complete expire date from the submitted post, then use it, else use the default data
		if (!is_empty_v($exp_dt) && !empty($exp_dt['aa'])){
			$exp_dt = array(
				'year'  => (int) $exp_dt['aa'],
				'month' => (int) $exp_dt['mm'],
				'day'   => (int) $exp_dt['jj'],
				'hour'  => (int) $exp_dt['hh'],
				'min'   => (int) $exp_dt['mn']
			);
			$exp_dt = get_date_in_mysql_format($exp_dt);
		}else{
			$exp_dt = calc_listing_expiry_date(); // get the expiry date in mysql date format using the default expiration date.
		}
		$metas['_expiry_date']  = $exp_dt;
		$metas = apply_filters('atbdp_listing_meta_admin_submission', $metas);
		// save the meta data to the database
		foreach ($metas as $meta_key => $meta_value) {
			update_post_meta($post_id, $meta_key, $meta_value); // array value will be serialize automatically by update post meta
		}
		if (!empty($p['listing_prv_img'])){
			set_post_thumbnail( $post_id, sanitize_text_field($p['listing_prv_img']) );
		}else{
			delete_post_thumbnail($post_id);
		}

		$listing_status = get_post_meta($post_id, '_listing_status', true);
		$post_status = get_post_status($post_id);
		$current_d = current_time('mysql');

		// let's check is listing need to update
		if (('expired' === $listing_status) && ('private' === $post_status)){
			// check is plans module active
			if (is_fee_manager_active()){
				$package_id = 'null' != $_POST['admin_plan'] ? esc_attr($_POST['admin_plan']):'';
				if (!empty($package_id)){
					$package_length = get_post_meta($package_id, 'fm_length', true);
					// Current time
					// Calculate new date
					$date = new DateTime($current_d);
					$date->add(new DateInterval("P{$package_length}D")); // set the interval in days
					$expired_date = $date->format('Y-m-d H:i:s');
					$is_never_expaired = get_post_meta($package_id, 'fm_length_unl', true);
					if (($expired_date > $current_d) || !empty($is_never_expaired)) {
						wp_update_post( array(
							'ID'           => $post_id,
							'post_status' => 'publish', // update the status to private so that we do not run this func a second time
							'meta_input' => array(
								'listing_status' => 'post_status',
							), // insert all meta data once to reduce update meta query
						) );
					}
				}
			}else{
				// no plans extension active so update the listing status if admin manually change the listing expire date
				if (($exp_dt > $current_d) || !empty($p['never_expire'])) {
					wp_update_post( array(
						'ID'           => $post_id,
						'post_status' => 'publish', // update the status to private so that we do not run this func a second time
						'meta_input' => array(
							'listing_status' => 'post_status',
						), // insert all meta data once to reduce update meta query
					) );
				}
			}
		}
	}


	/**
	 * Check if the the nonce, revision, auto_save are valid/true and the post type is ATBDP_POST_TYPE
	 * @param int       $post_id    Post ID of the current post being saved
	 * @param object    $post       Current post object being saved
	 *
	 * @return bool            Return true if all the above check passes the test.
	 */
	private function passSecurity( $post_id, $post ) {
		if ( ATBDP_POST_TYPE == $post->post_type) {
		$is_autosave = wp_is_post_autosave($post_id);
		$is_revision = wp_is_post_revision($post_id);
		$nonce = !empty($_POST['listing_info_nonce']) ? $_POST['listing_info_nonce'] : '';
		$is_valid_nonce = wp_verify_nonce($nonce, 'listing_info_action');

		if ( $is_autosave || $is_revision || !$is_valid_nonce || ( !current_user_can( 'edit_'.ATBDP_POST_TYPE, $post_id )) ) return false;
		return true;
		}
		return false;
	}
}