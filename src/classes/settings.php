<?php
/**
 * Class to control the registration and handling of all settings values.
 */
class Purgely_Settings {
	/**
	 * The settings values for the plugin.
	 *
	 * @since 1.0.0.
	 *
	 * @var array Holds all of the individual settings for the plugin.
	 */
	public static $settings = array();

	/**
	 * Get the valid settings for the plugin.
	 *
	 * @since 1.0.0.
	 *
	 * @return array The valid settings including default values and sanitize callback.
	 */
	public static function get_registered_settings() {
		return array(
			'fastly_key'                    => array(
				'sanitize_callback' => 'purgely_sanitize_key',
				'default'           => PURGELY_FASTLY_KEY,
			),
			'fastly_service_id'             => array(
				'sanitize_callback' => 'purgely_sanitize_key',
				'default'           => PURGELY_FASTLY_SERVICE_ID,
			),
			'allow_purge_all'               => array(
				'sanitize_callback' => 'purgely_sanitize_checkbox',
				'default'           => PURGELY_ALLOW_PURGE_ALL,
			),
			'api_endpoint'                  => array(
				'sanitize_callback' => 'esc_url',
				'default'           => PURGELY_API_ENDPOINT,
			),
			'enable_stale_while_revalidate' => array(
				'sanitize_callback' => 'purgely_sanitize_checkbox',
				'default'           => PURGELY_ENABLE_STALE_WHILE_REVALIDATE,
			),
			'stale_while_revalidate_ttl'    => array(
				'sanitize_callback' => 'absint',
				'default'           => PURGELY_STALE_WHILE_REVALIDATE_TTL,
			),
			'enable_stale_if_error'         => array(
				'sanitize_callback' => 'purgely_sanitize_checkbox',
				'default'           => PURGELY_ENABLE_STALE_IF_ERROR,
			),
			'stale_if_error_ttl'            => array(
				'sanitize_callback' => 'absint',
				'default'           => PURGELY_STALE_IF_ERROR_TTL,
			),
			'surrogate_control_ttl'         => array(
				'sanitize_callback' => 'absint',
				'default'           => PURGELY_SURROGATE_CONTROL_TTL,
			),
			'default_purge_type'            => array(
				'sanitize_callback' => 'sanitize_key',
				'default'           => PURGELY_DEFAULT_PURGE_TYPE,
			),
		);
	}

	/**
	 * Get an array of settings values.
	 *
	 * This method negotiates the database values and the constant values to determine what the current value should be.
	 * The database value takes precedence over the constant value.
	 *
	 * @since 1.0.0.
	 *
	 * @return array The current settings values.
	 */
	public static function get_settings() {
		$negotiated_settings = self::$settings;

		if ( empty( $negotiated_settings ) ) {
			$registered_settings = self::get_registered_settings();
			$saved_settings      = get_option( 'purgely-settings', array() );
			$negotiated_settings = array();

			foreach ( $registered_settings as $key => $values ) {
				$value = '';

				if ( isset( $saved_settings[ $key ] ) ) {
					$value = $saved_settings[ $key ];
				} else if ( isset( $values['default'] ) ) {
					$value = $values['default'];
				}

				if ( isset( $values['sanitize_callback'] ) ) {
					$value = call_user_func( $values['sanitize_callback'], $value );
				}

				$negotiated_settings[ $key ] = $value;
			}

			self::set_settings( $negotiated_settings );
		}

		return $negotiated_settings;
	}

	/**
	 * Get the value of an individual setting.
	 *
	 * @since 1.0.0.
	 *
	 * @param  string $setting The setting name.
	 * @return mixed           The setting value.
	 */
	public static function get_setting( $setting ) {
		$value = '';

		$negotiated_settings = self::get_settings();
		$registered_settings = self::get_registered_settings();

		if ( isset( $negotiated_settings[ $setting ] ) ) {
			$value = $negotiated_settings[ $setting ];
		} elseif ( isset( $registered_settings[ $setting ]['default'] ) ) {
			$value = $registered_settings[ $setting ]['default'];
		}

		return $value;
	}

	/**
	 * Set the settings values.
	 *
	 * @since 1.0.0.
	 *
	 * @param  array $settings The current settings values.
	 * @return void
	 */
	public static function set_settings( $settings ) {
		self::$settings = $settings;
	}
}
