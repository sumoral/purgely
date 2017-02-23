<?php
/**
 * Define the endpoint for the API.
 *
 * @since 1.0.0.
 */
if ( ! defined( 'PURGELY_API_ENDPOINT' ) ) {
	define( 'PURGELY_API_ENDPOINT', 'https://api.fastly.com/' );
}

/**
 * Define the user API key.
 *
 * @since 1.0.0.
 */
if ( ! defined( 'PURGELY_FASTLY_KEY' ) ) {
	define( 'PURGELY_FASTLY_KEY', '' );
}

/**
 * Define the service ID.
 *
 * @since 1.0.0.
 */
if ( ! defined( 'PURGELY_FASTLY_SERVICE_ID' ) ) {
	define( 'PURGELY_FASTLY_SERVICE_ID', '' );
}

/**
 * Allow plugin to issue full purges or not.
 *
 * @since 1.0.0.
 */
if ( ! defined( 'PURGELY_ALLOW_PURGE_ALL' ) ) {
	define( 'PURGELY_ALLOW_PURGE_ALL', false );
}

/**
 * Turn stale-while-revalidate on or off.
 *
 * @since 1.0.0.
 */
if ( ! defined( 'PURGELY_ENABLE_STALE_WHILE_REVALIDATE' ) ) {
	define( 'PURGELY_ENABLE_STALE_WHILE_REVALIDATE', true );
}

/**
 * Set the default stale-while-revalidate TTL.
 *
 * @since 1.0.0.
 */
if ( ! defined( 'PURGELY_STALE_WHILE_REVALIDATE_TTL' ) ) {
	define( 'PURGELY_STALE_WHILE_REVALIDATE_TTL', 60 * 60 * 24 ); // 24 hours
}

/**
 * Turn stale-if-error on or off.
 *
 * @since 1.0.0.
 */
if ( ! defined( 'PURGELY_ENABLE_STALE_IF_ERROR' ) ) {
	define( 'PURGELY_ENABLE_STALE_IF_ERROR', true );
}

/**
 * Set the default stale-if-error TTL.
 *
 * @since 1.0.0.
 */
if ( ! defined( 'PURGELY_STALE_IF_ERROR_TTL' ) ) {
	define( 'PURGELY_STALE_IF_ERROR_TTL', 60 * 60 * 24 ); // 24 hours
}

/**
 * Set the default surrogate control TTL.
 *
 * @since 1.0.0.
 */
if ( ! defined( 'PURGELY_SURROGATE_CONTROL_TTL' ) ) {
	define( 'PURGELY_SURROGATE_CONTROL_TTL', 60 * 5 ); // 5 minutes
}

/**
 * Set the default purge type for all purges.
 *
 * The currently supported values are "soft" and "instant".
 *
 * @since 1.0.0.
 */
if ( ! defined( 'PURGELY_DEFAULT_PURGE_TYPE' ) ) {
	define( 'PURGELY_DEFAULT_PURGE_TYPE', 'soft' );
}
