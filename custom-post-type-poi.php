<?php
/*
Plugin Name: Custom Post Type Point of Interests
Plugin URI: http://horttcore.de
Description: A custom post type to manage point of interests
Version: 0.2
Author: Ralf Hortt
Author URI: http://horttcore.de
License: GPL2
*/



/**
 *
 *  Custom Post Type Point of Interests
 *
 */
class Custom_Post_Type_POI
{



	/**
	 * Plugin constructor
	 *
	 * @access public
	 * @author Ralf Hortt
	 **/
	public function __construct()
	{

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'admin_print_scripts-post.php', array( $this, 'admin_enqueue_scripts' ), 1000 );
		add_action( 'admin_print_scripts-post-new.php', array( $this, 'admin_enqueue_scripts' ), 1000 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ), 1000 );
		add_action( 'wp_ajax_get_lat_lng', array( $this, 'get_lat_lng' ) );
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
		add_action( 'save_post', array( $this, 'save_post' ) );

		load_plugin_textdomain( 'custom-post-type-poi', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/'  );

	} // end __construct



	/**
	 * Add meta boxes
	 *
	 * @access public
	 * @author Ralf Hortt
	 **/
	public function add_meta_boxes()
	{

		add_meta_box( 'poi-info', __( 'Location', 'custom-post-type-poi' ), array( $this, 'metabox_poi_info' ), 'poi' );
		add_meta_box( 'poi-map', __( 'Map', 'custom-post-type-poi' ), array( $this, 'metabox_poi_map' ), 'poi' );

	} // end add_meta_boxes



	/**
	 * Register scripts
	 *
	 * @access public
	 * @author Ralf Hortt
	 **/
	public function admin_enqueue_scripts()
	{

		wp_register_script( 'custom-post-type-poi-admin', plugins_url( dirname( plugin_basename( __FILE__ ) ) . '/javascript/custom-post-type-poi-admin.js' ), array( 'jquery', 'jquery-ui-core', 'jquery-ui-datepicker' ), FALSE, TRUE );

	} // end admin_enqueue_scripts



	/**
	 * Register styles
	 *
	 * @access public
	 * @author Ralf Hortt
	 **/
	public function admin_enqueue_styles()
	{

		wp_register_style( 'custom-post-type-poi-admin', plugins_url( dirname( plugin_basename( __FILE__ ) ) . '/css/custom-post-type-poi-admin.css' ) );
		wp_enqueue_style( 'custom-post-type-poi-admin' );

	} // end admin_enqueue_styles



	/**
	 * Register styles
	 *
	 * @access public
	 * @return json die/array Latitude and longitude
	 * @author Ralf Hortt
	 **/
	public function get_lat_lng( $args = FALSE, $return = FALSE )
	{

		if ( FALSE === $args || empty( $args ) ) :

			$args = array(
				$_POST['street'],
				$_POST['number'],
				$_POST['zip'],
				$_POST['city'],
				$_POST['country'],
			);

		endif;

		$args = array_map( 'sanitize_text_field', $args );
		$args = array_map( 'urlencode', $args );

		$url = 'http://maps.googleapis.com/maps/api/geocode/json?address=' . implode( '+', $args ) . '&sensor=false';

		$response = wp_remote_get( $url );
		$response = json_decode( wp_remote_retrieve_body( $response ) );

		if ( isset($response->results[0]->geometry->location) ) :

			$lat = $response->results[0]->geometry->location->lat;
			$lng = $response->results[0]->geometry->location->lng;
			$data = array( 'latitude' => $lat, 'longitude' => $lng );

			if ( TRUE === $return ) :
				return apply_filters( 'poi-get-lat-lng', $data, $args );
			else :
				die( json_encode( apply_filters( 'poi-get-lat-lng', $data, $args ) ) );
			endif;

		endif;

	} // end get_lat_lng



	/**
	 * Map
	 *
	 * @static
	 * @param obj $post Post object
	 * @param array $args Arguments
	 * @author Ralf Hortt
	 **/
	public static function map( $post_id, $args = '' )
	{

		$defaults = array(
			'height' => '350',
			'width' => '100%',
		);

		$options = wp_parse_args( $args, $defaults );

		$location = apply_filters( 'poi-location', get_post_meta( $post_id, '_poi-location', TRUE ) );

		if ( !$location ) :

			_e( 'Please enter a location for this Point of Interest to display a map', 'custom-post-type-poi' );

		else :

			$args = array(
				$location['street'],
				$location['street-number'],
				$location['zip'],
				$location['city'],
				$location['country'],
			);

			$args = array_map( 'sanitize_text_field', $args );
			$args = array_map( 'urlencode', $args );
			$args = array_filter( $args );

			if ( empty( $args ) ) :

				_e( 'Please enter a location for this Point of Interest to display a map', 'custom-post-type-poi' );

			else :

				?>
				<iframe width="<?php echo $options['width'] ?>" height="<?php echo $options['height'] ?>" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="http://maps.google.de/maps?f=q&amp;source=s_q&amp;hl=de&amp;geocode=&amp;q=<?php echo implode( '+', $args ) ?>&amp;ie=UTF8&amp;output=embed"></iframe><br />
				<small><a href="https://maps.google.de/maps?f=q&amp;source=s_q&amp;hl=de&amp;geocode=&amp;q=<?php echo implode( '+', $args ) ?>&amp;ie=UTF8">Größere Kartenansicht</a></small>
				<?php

			endif;

		endif;

	} // end map



	/**
	 * Point of Interest info meta box
	 *
	 * @access public
	 * @param obj $post Post object
	 * @author Ralf Hortt
	 **/
	public function metabox_poi_info( $post )
	{

		$location = apply_filters( 'poi-location', get_post_meta( $post->ID, '_poi-location', TRUE ) );
		wp_enqueue_script( 'custom-post-type-poi-admin' );
		?>

		<?php do_action( 'poi-location-table-before', $location, $post ) ?>

		<table class="form-table">

			<?php do_action( 'poi-location-table-first', $location, $post ) ?>

			<tr>
				<th><label for="poi-street"><?php _e( 'Street', 'custom-post-type-poi' ); ?></label> / <label for="poi-city"><?php _e( 'Nr', 'custom-post-type-poi' ); ?></label></th>
				<td><input type="text" name="poi-street" id="poi-street" value="<?php if ( isset( $location['street'] ) ) echo $location['street'] ?>"> <input type="text" name="poi-street-number" id="poi-street-number" value="<?php if ( isset( $location['street-number'] ) ) echo $location['street-number'] ?>"></td>
			</tr>
			<tr>
				<th><label for="poi-zip"><?php _e( 'ZIP', 'custom-post-type-poi' ); ?></label> / <label for="poi-city"><?php _e( 'City', 'custom-post-type-poi' ); ?></label></th>
				<td><input type="text" name="poi-zip" id="poi-zip" value="<?php if ( isset( $location['zip'] ) ) echo $location['zip'] ?>"> <input type="text" name="poi-city" id="poi-city" value="<?php if ( isset( $location['city'] ) ) echo $location['city'] ?>"></td>
			</tr>
			<tr>
				<th><label for="poi-address-additional"><?php _e( 'Address additional', 'custom-post-type-poi'  ); ?></label></th>
				<td><input type="text" name="poi-address-additional" id="poi-address-additional" value="<?php if ( isset( $location['address-additional'] ) ) echo $location['address-additional'] ?>" /></td>
			</tr>
			<tr>
				<th><label for="poi-region"><?php _e( 'Region', 'custom-post-type-poi'  ); ?></label> / <label for="poi-country"><?php _e( 'Country', 'custom-post-type-poi'  ); ?></label></th>
				<td><input type="text" name="poi-region" id="poi-region" value="<?php if ( isset( $location['region'] ) ) echo $location['region'] ?>" /> <input type="text" name="poi-country" id="poi-country" value="<?php if ( isset( $location['country'] ) ) echo $location['country'] ?>" /></td>
			</tr>
			<tr>
				<th><label for="poi-lat"><?php _e( 'Latitude', 'custom-post-type-poi'  ); ?></label> / <label for="poi-lng"><?php _e( 'Longitude', 'custom-post-type-poi'  ); ?></label></th>
				<td>
					<input type="text" name="poi-lat" id="poi-lat" value="<?php if ( isset( $location['lat'] ) ) echo $location['lat'] ?>" />
					<input type="text" name="poi-lng" id="poi-lng" value="<?php if ( isset( $location['lng'] ) ) echo $location['lng'] ?>" /> <a href="#" class="get-lat-lng"><?php _e( 'Get latitude / longitude', 'custom-post-type-poi' ); ?></a><br>
					<small><?php _e( 'Note: Latitude and Longitude will be checked against Google GeoAPI', 'custom-post-type-poi' ); ?></small>
				</td>
			</tr>

			<?php do_action( 'poi-location-table-last', $location, $post ) ?>

		</table>

		<?php do_action( 'poi-location-table-after', $location, $post ) ?>

		<?php
		wp_nonce_field( 'save-poi-info', 'poi-info-nonce' );

	} // end metabox_poi_info



	/**
	 * Point of Interest info meta box
	 *
	 * @access public
	 * @param obj $post Post object
	 * @author Ralf Hortt
	 **/
	public function metabox_poi_map( $post )
	{

		$this->map( $post->ID );

	} // end metabox_poi_map



	/**
	 * Update messages
	 *
	 * @access public
	 * @param array $messages Messages
	 * @return array Messages
	 * @author Ralf Hortt
	 **/
	public function post_updated_messages( $messages ) {

		global $post, $post_ID;

		$messages['poi'] = array(
			0 => '', // Unused. Messages start at index 1.
			1 => sprintf( __('Point of Interest updated. <a href="%s">View Point of Interest</a>', 'custom-post-type-poi'), esc_url( get_permalink($post_ID) ) ),
			2 => __('Custom field updated.', 'custom-post-type-poi'),
			3 => __('Custom field deleted.', 'custom-post-type-poi'),
			4 => __('Point of Interest updated.', 'custom-post-type-poi'),
			/* translators: %s: date and time of the revision */
			5 => isset($_GET['revision']) ? sprintf( __('Point of Interest restored to revision from %s', 'custom-post-type-poi'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => sprintf( __('Point of Interest published. <a href="%s">View Point of Interest</a>', 'custom-post-type-poi'), esc_url( get_permalink($post_ID) ) ),
			7 => __('Point of Interest saved.', 'custom-post-type-poi'),
			8 => sprintf( __('Point of Interest submitted. <a target="_blank" href="%s">Preview Point of Interest</a>', 'custom-post-type-poi'), esc_url( add_query_arg( 'preview', 'TRUE', get_permalink($post_ID) ) ) ),
			9 => sprintf( __('Point of Interest scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview Point of Interest</a>', 'custom-post-type-poi'), date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
			10 => sprintf( __('Point of Interest draft updated. <a target="_blank" href="%s">Preview Point of Interest</a>', 'custom-post-type-poi'), esc_url( add_query_arg( 'preview', 'TRUE', get_permalink($post_ID) ) ) ),
		);

		return $messages;

	} // end post_updated_messages



	/**
	 *
	 * Registe post type
	 *
	 * @access public
	 * @return void
	 * @author Ralf Hortt
	 */
	public function register_post_type()
	{

		$labels = array(
			'name' => _x( 'Point of Interests', 'post type general name', 'custom-post-type-poi' ),
			'singular_name' => _x( 'Point of Interest', 'post type singular name', 'custom-post-type-poi' ),
			'add_new' => _x( 'Add New', 'Point of Interest', 'custom-post-type-poi' ),
			'add_new_item' => __( 'Add New Point of Interest', 'custom-post-type-poi' ),
			'edit_item' => __( 'Edit Point of Interest', 'custom-post-type-poi' ),
			'new_item' => __( 'New Point of Interest', 'custom-post-type-poi' ),
			'view_item' => __( 'View Point of Interest', 'custom-post-type-poi' ),
			'search_items' => __( 'Search Point of Interest', 'custom-post-type-poi' ),
			'not_found' =>  __( 'No Point of Interests found', 'custom-post-type-poi' ),
			'not_found_in_trash' => __( 'No Point of Interests found in Trash', 'custom-post-type-poi' ),
			'parent_item_colon' => '',
			'menu_name' => __( 'Point of Interests', 'custom-post-type-poi' )
		);

		$args = array(
			'labels' => $labels,
			'public' => TRUE,
			'publicly_queryable' => TRUE,
			'show_ui' => TRUE,
			'show_in_menu' => TRUE,
			'query_var' => TRUE,
			'rewrite' => array( 'slug' => _x( 'point-of-interest', 'Post Type Slug', 'custom-post-type-poi' ) ),
			'capability_type' => 'post',
			'has_archive' => TRUE,
			'hierarchical' => FALSE,
			'menu_position' => NULL,
			'supports' => array('title', 'editor', 'excerpt', 'thumbnail' ),
			'menu_icon' => '',
		);

		register_post_type( 'poi', $args );

	} // end register_post_type



	/**
	 * Save post callback
	 *
	 * @access public
	 * @param int $post_id Post id
	 * @return void
	 * @author Ralf Hortt
	 **/
	public function save_post( $post_id )
	{

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;

		if ( !isset( $_POST['poi-info-nonce'] ) || !wp_verify_nonce( $_POST['poi-info-nonce'], 'save-poi-info' ) )
			return;

		if ( !$_POST['poi-lat'] || !$_POST['poi-lng'] ) :

			$args = array(
				$_POST['poi-street'],
				$_POST['poi-street-number'],
				$_POST['poi-zip'],
				$_POST['poi-city'],
				$_POST['poi-country'],
			);

			$latlng = $this->get_lat_lng( $args, TRUE );
			$lat = $latlng['latitude'];
			$lng = $latlng['longitude'];

		endif;

		// Location
		if ( $_POST['poi-city'] ) :

			$poi = array(
				'street' => $_POST['poi-street'],
				'street-number' => $_POST['poi-street-number'],
				'zip' => $_POST['poi-zip'],
				'city' => $_POST['poi-city'],
				'address-additional' => $_POST['poi-address-additional'],
				'region' => $_POST['poi-region'],
				'country' => $_POST['poi-country'],
				'lat' => $_POST['poi-lat'],
				'lng' => $_POST['poi-lng'],
			);

			if ( isset( $lat ) )
				$poi['lat'] = $lat;

			if ( isset( $lng ) )
				$poi['lng'] = $lng;

			$poi = array_map( 'sanitize_text_field', $poi );

			update_post_meta( $post_id, '_poi-location', apply_filters( 'poi-location-save', $poi, $post_id ) );

			do_action( 'save-poi-meta', $poi );

		else :

			delete_post_meta( $post_id, '_poi-location' );

		endif;

	} // end save_post



} // end Custom_Post_Type_POI

new Custom_Post_Type_POI;
