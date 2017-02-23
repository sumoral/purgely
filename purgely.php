<?php
/**
 * Plugin Name: Purgely
 * Description: A plugin to manage Fastly caching behavior and purging.
 * Author:      Zack Tollman, WIRED Tech Team
 * Version:     1.0.1
 * Text Domain: purgely
 * Domain Path: /languages
 */

/**
 * Singleton for kicking off functionality for this plugin.
 */
class Purgely {
	/**
	 * The one instance of Purgely.
	 *
	 * @var Purgely
	 */
	private static $instance;

	/**
	 * The Purgely_Surrogate_Key object to manage Surrogate Keys.
	 *
	 * @var Purgely_Surrogate_Keys_Header    The Purgely_Surrogate_Key object to manage Surrogate Keys.
	 */
	private static $surrogate_keys_header;

	/**
	 * The Purgely_Surrogate_Control_Header to manage the TTL.
	 *
	 * @var Purgely_Surrogate_Control_Header    The Purgely_Surrogate_Control_Header to manage the TTL.
	 */
	private static $surrogate_control_header;

	/**
	 * An array of cache control headers to manage the caching behavior.
	 *
	 * @var Purgely_Cache_Control_Header[]    An array of cache control headers to manage the caching behavior.
	 */
	private static $cache_control_headers = array();

	/**
	 * Current plugin version.
	 *
	 * @since 1.0.0
	 *
	 * @var   string    The semantically versioned plugin version number.
	 */
	var $version = '1.0.1';

	/**
	 * File path to the plugin dir (e.g., /var/www/mysite/wp-content/plugins/purgely).
	 *
	 * @since 1.0.0.
	 *
	 * @var   string    Path to the root of this plugin.
	 */
	var $root_dir = '';

	/**
	 * File path to the plugin src files (e.g., /var/www/mysite/wp-content/plugins/purgely/src).
	 *
	 * @since 1.0.0.
	 *
	 * @var   string    Path to the root of this plugin.
	 */
	var $src_dir = '';

	/**
	 * File path to the plugin main file (e.g., /var/www/mysite/wp-content/plugins/mixed-content-detector/purgely.php).
	 *
	 * @since 1.0.0.
	 *
	 * @var   string    Path to the plugin's main file.
	 */
	var $file_path = '';

	/**
	 * The URI base for the plugin (e.g., http://domain.com/wp-content/plugins/purgely).
	 *
	 * @since 1.0.0.
	 *
	 * @var   string    The URI base for the plugin.
	 */
	var $url_base = '';

	/**
	 * Instantiate or return the one Purgely instance.
	 *
	 * @return Purgely
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initiate actions.
	 *
	 * @return Purgely
	 */
	public function __construct() {
		// Set the main paths for the plugin.
		$this->root_dir  = dirname( __FILE__ );
		$this->src_dir   = $this->root_dir . '/src';
		$this->file_path = $this->root_dir . '/' . basename( __FILE__ );
		$this->url_base  = untrailingslashit( plugins_url( '/', __FILE__ ) );

		// Include dependent files.
		include $this->src_dir . '/config.php';
		include $this->src_dir . '/utils.php';
		include $this->src_dir . '/classes/settings.php';
		include $this->src_dir . '/classes/related-urls.php';
		include $this->src_dir . '/classes/purge-request-collection.php';
		include $this->src_dir . '/classes/purge-request.php';
		include $this->src_dir . '/classes/header.php';
		include $this->src_dir . '/classes/header-cache-control.php';
		include $this->src_dir . '/classes/header-surrogate-control.php';
		include $this->src_dir . '/classes/header-surrogate-keys.php';
		include $this->src_dir . '/classes/surrogate-key-collection.php';

		if ( is_admin() ) {
			include $this->src_dir . '/settings-page.php';
		}

		// Handle all automatic purges.
		include $this->src_dir . '/wp-purges.php';

		// Initialize the key collector.
		$this::$surrogate_keys_header = new Purgely_Surrogate_Keys_Header();

		// Initialize the surrogate control header.
		$this::$surrogate_control_header = new Purgely_Surrogate_Control_Header( Purgely_Settings::get_setting( 'surrogate_control_ttl' ) );

		// Add the surrogate keys.
		add_action( 'wp', array( $this, 'set_standard_keys' ), 100 );

		// Set the default stale while revalidate and stale if error values.
		if ( true === Purgely_Settings::get_setting( 'enable_stale_while_revalidate' ) ) {
			$this->add_cache_control_header( Purgely_Settings::get_setting( 'stale_while_revalidate_ttl' ), 'stale-while-revalidate' );
		}

		if ( true === Purgely_Settings::get_setting( 'enable_stale_if_error' ) ) {
			$this->add_cache_control_header( Purgely_Settings::get_setting( 'stale_if_error_ttl' ), 'stale-if-error' );
		}

		// Send the surrogate keys.
		add_action( 'wp', array( $this, 'send_surrogate_keys' ), 101 );

		// Set and send the surrogate control header.
		add_action( 'wp', array( $this, 'send_surrogate_control' ), 101 );

		// Set and send the surrogate control header.
		add_action( 'wp', array( $this, 'send_cache_control' ), 101 );

		// Load in WP CLI.
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			include $this->src_dir . '/wp-cli.php';
		}

		// Load the textdomain.
		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );
	}

	/**
	 * Set all the surrogate keys for the requests.
	 *
	 * @return void
	 */
	public function set_standard_keys() {
		global $wp_query;
		$key_collection = new Purgely_Surrogate_Key_Collection( $wp_query );
		$keys           = $key_collection->get_keys();

		$this::$surrogate_keys_header->add_keys( $keys );
	}

	/**
	 * Send the currently registered surrogate keys.
	 *
	 * This function takes all of the surrogate keys that are currently recorded and flattens them into a single header
	 * and sends the header. Any other keys need to be set by 3rd party code before "init", 101.
	 *
	 * This function does allow for a filtering of the keys before they are sent, to allow for the keys to be
	 * de-registered when and if necessary.
	 *
	 * @return void
	 */
	public function send_surrogate_keys() {
		$keys_header = $this::$surrogate_keys_header;
		$keys        = apply_filters( 'purgely_surrogate_keys', $keys_header->get_keys() );

		do_action( 'purgely_pre_send_keys', $keys );

		$this::$surrogate_keys_header->set_keys( $keys );
		$this::$surrogate_keys_header->send_header();

		do_action( 'purgely_post_send_keys', $keys );
	}

	/**
	 * Add a key to the list.
	 *
	 * @param  string $key The key to add to the list.
	 * @return array             The full list of keys.
	 */
	public function add_key( $key ) {
		return $this::$surrogate_keys_header->add_key( $key );
	}

	/**
	 * Set the TTL for the object and send the header.
	 *
	 * This is the main function for setting the TTL for the page. To change it, use the "purgely_surrogate_control"
	 * filter. Additionally, the "purgely_set_ttl" helper function can be used. The filter and function do the same
	 * thing.
	 *
	 * Note that any alterations must be done before init, 101.
	 *
	 * The default set here is 5 minutes. This has proven to be a reasonable default for caches for WordPress pages.
	 *
	 * @return void
	 */
	public function send_surrogate_control() {
		/**
		 * If a user is logged in, surrogate control headers should be ignored. We do not want to cache any logged in
		 * user views. WordPress sets a "Cache-Control:no-cache, must-revalidate, max-age=0" header for logged in views
		 * and this should be sufficient for keeping logged in views uncached.
		 */
		if ( is_user_logged_in() ) {
			return;
		}

		$surrogate_control = $this::$surrogate_control_header;
		$seconds = apply_filters( 'purgely_surrogate_control', $surrogate_control->get_seconds() );

		do_action( 'purgely_pre_send_surrogate_control', $seconds );
		$surrogate_control->send_header();
		do_action( 'purgely_post_send_surrogate_control', $seconds );
	}

	/**
	 * Set the TTL for the current request.
	 *
	 * @param  int $seconds The amount of seconds to cache the object for.
	 * @return int                The amount of seconds to cache the object for.
	 */
	public function set_ttl( $seconds ) {
		$this::$surrogate_control_header->set_seconds( $seconds );
		return $this::$surrogate_control_header->get_seconds();
	}

	/**
	 * Send each of the control control headers.
	 *
	 * @return void
	 */
	public function send_cache_control() {
		/**
		 * If a user is logged in, surrogate control headers should be ignored. We do not want to cache any logged in
		 * user views. WordPress sets a "Cache-Control:no-cache, must-revalidate, max-age=0" header for logged in views
		 * and this should be sufficient for keeping logged in views uncached.
		 */
		if ( is_user_logged_in() ) {
			return;
		}

		$headers = $this::$cache_control_headers;

		do_action( 'purgely_pre_send_cache_control', $headers );

		if ( is_array( $headers ) ) {
			foreach ( $headers as $header_object ) {
				$header_object->send_header();
			}
		}

		do_action( 'purgely_post_send_cache_control', $headers );
	}

	/**
	 * Adds a new cache control header.
	 *
	 * @param  int    $seconds   The time to set the directive for.
	 * @param  string $directive The cache control directive to set.
	 * @return array                   Array of cache control headers to send.
	 */
	public function add_cache_control_header( $seconds, $directive ) {
		$header    = new Purgely_Cache_Control_Header( $seconds, $directive );
		$headers   = $this::$cache_control_headers;
		$headers[] = $header;

		$this::$cache_control_headers = $headers;
		return $this::$cache_control_headers;
	}

	/**
	 * Load the plugin text domain.
	 *
	 * @since 1.0.0.
	 *
	 * @return void
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'purgely', false, basename( dirname( __FILE__ ) ) . '/languages/' );
	}
}

/**
 * Instantiate or return the one Purgely instance.
 *
 * @return Purgely
 */
function get_purgely_instance() {
	return Purgely::instance();
}

get_purgely_instance();
