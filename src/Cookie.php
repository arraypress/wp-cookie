<?php
/**
 * Cookie Utility Class for WordPress
 *
 * This class provides a set of methods for handling cookies in WordPress,
 * including setting, getting, checking, and deleting cookies with proper
 * WordPress integration and security measures.
 *
 * @package     ArrayPress/WP-Utils
 * @copyright   Copyright 2024, ArrayPress Limited
 * @license     GPL-2.0-or-later
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\Utils;

class Cookie {

	/**
	 * Default cookie expiration time (30 days)
	 *
	 * @var int
	 */
	public const DEFAULT_EXPIRE = 2592000;

	/**
	 * Cookie attributes
	 *
	 * @var array
	 */
	private static array $cookie_attributes = [
		'expires',
		'path',
		'domain',
		'secure',
		'httponly',
		'samesite'
	];

	/**
	 * Common SameSite values
	 *
	 * @var string
	 */
	public const SAMESITE_STRICT = 'Strict';

	/**
	 * Store the last error message
	 *
	 * @var string|null
	 */
	private static ?string $last_error = null;

	/**
	 * Set a cookie.
	 *
	 * @param string $name     The name of the cookie.
	 * @param string $value    The value of the cookie.
	 * @param int    $expire   The time the cookie expires as Unix timestamp. If set to 0, the cookie will expire when the browser closes (session cookie).
	 * @param string $path     The path on the server in which the cookie will be available on.
	 * @param string $domain   The (sub)domain that the cookie is available to.
	 * @param bool   $secure   If set to true the cookie will only be transmitted over a secure HTTPS connection.
	 * @param bool   $httponly If set to true the cookie will be made accessible only through the HTTP protocol.
	 *
	 * @return bool Whether the cookie was successfully set.
	 */
	public static function set(
		string $name,
		string $value,
		int $expire = 0,
		string $path = '/',
		string $domain = '',
		bool $secure = true,
		bool $httponly = true
	): bool {
		if ( ! self::validate_name( $name ) ) {
			self::set_error( 'Invalid cookie name' );

			return false;
		}

		if ( headers_sent() ) {
			self::set_error( 'Headers already sent' );

			return false;
		}

		$value = self::sanitize_value( $value );

		$result = setcookie( $name, $value, [
			'expires'  => $expire,
			'path'     => $path,
			'domain'   => $domain,
			'secure'   => $secure,
			'httponly' => $httponly,
			'samesite' => self::SAMESITE_STRICT
		] );

		if ( $result ) {
			$_COOKIE[ $name ] = $value;
		}

		return $result;
	}

	/**
	 * Set a cookie with WordPress-specific security settings.
	 *
	 * @param string $name    The name of the cookie
	 * @param string $value   The value of the cookie
	 * @param array  $options Optional. Override default options
	 *
	 * @return bool Whether the cookie was successfully set
	 */
	public static function set_secure( string $name, string $value, array $options = [] ): bool {
		$defaults = [
			'expire'   => self::DEFAULT_EXPIRE,
			'path'     => COOKIEPATH,
			'domain'   => COOKIE_DOMAIN,
			'secure'   => is_ssl(),
			'httponly' => true,
			'samesite' => self::SAMESITE_STRICT
		];

		$options = array_merge( $defaults, $options );

		return self::set(
			$name,
			$value,
			$options['expire'],
			$options['path'],
			$options['domain'],
			$options['secure'],
			$options['httponly']
		);
	}

	/**
	 * Set a site-specific cookie for multisite.
	 *
	 * @param string $name    The name of the cookie
	 * @param string $value   The value of the cookie
	 * @param array  $options Additional options for the cookie
	 *
	 * @return bool Whether the cookie was successfully set
	 */
	public static function set_site_cookie( string $name, string $value, array $options = [] ): bool {
		if ( is_multisite() ) {
			$site_path       = parse_url( get_site_url(), PHP_URL_PATH );
			$options['path'] = ! empty( $site_path ) ? $site_path : '/';
		}

		return self::set_secure( $name, $value, $options );
	}

	/**
	 * Set a network-wide cookie for multisite.
	 *
	 * @param string $name    The name of the cookie
	 * @param string $value   The value of the cookie
	 * @param array  $options Additional options for the cookie
	 *
	 * @return bool Whether the cookie was successfully set
	 */
	public static function set_network_cookie( string $name, string $value, array $options = [] ): bool {
		if ( is_multisite() ) {
			$options['path']   = '/';
			$options['domain'] = parse_url( network_site_url(), PHP_URL_HOST );
		}

		return self::set_secure( $name, $value, $options );
	}

	/**
	 * Get a cookie value.
	 *
	 * @param string $name    The name of the cookie.
	 * @param mixed  $default The default value to return if the cookie is not set.
	 *
	 * @return mixed The value of the cookie if it exists, otherwise the default value.
	 */
	public static function get( string $name, $default = null ) {
		return $_COOKIE[ $name ] ?? $default;
	}

	/**
	 * Check if a cookie exists.
	 *
	 * @param string $name The name of the cookie.
	 *
	 * @return bool True if the cookie exists, false otherwise.
	 */
	public static function exists( string $name ): bool {
		return isset( $_COOKIE[ $name ] );
	}

	/**
	 * Delete a cookie.
	 *
	 * @param string $name   The name of the cookie.
	 * @param string $path   The path on the server in which the cookie will be available on.
	 * @param string $domain The (sub)domain that the cookie is available to.
	 *
	 * @return bool Whether the cookie was successfully deleted.
	 */
	public static function delete( string $name, string $path = '/', string $domain = '' ): bool {
		if ( self::exists( $name ) ) {
			unset( $_COOKIE[ $name ] );

			return self::set( $name, '', time() - HOUR_IN_SECONDS, $path, $domain );
		}

		return false;
	}

	/**
	 * Get all cookies.
	 *
	 * @return array An array of all cookies.
	 */
	public static function get_all(): array {
		return $_COOKIE;
	}

	/**
	 * Set multiple cookies at once.
	 *
	 * @param array $cookies An associative array of cookie names and values.
	 * @param int   $expire  The time the cookies expire as Unix timestamp.
	 * @param array $options Additional options for the cookies.
	 *
	 * @return bool Whether all cookies were successfully set.
	 */
	public static function set_multiple( array $cookies, int $expire = 0, array $options = [] ): bool {
		$result = true;
		foreach ( $cookies as $name => $value ) {
			$result = $result && self::set_secure(
					$name,
					$value,
					array_merge( $options, [ 'expire' => $expire ] )
				);
		}

		return $result;
	}

	/**
	 * Delete multiple cookies at once.
	 *
	 * @param array $names   An array of cookie names to delete.
	 * @param array $options Additional options for deleting the cookies.
	 *
	 * @return bool Whether all cookies were successfully deleted.
	 */
	public static function delete_multiple( array $names, array $options = [] ): bool {
		$result = true;
		foreach ( $names as $name ) {
			$result = $result && self::delete(
					$name,
					$options['path'] ?? '/',
					$options['domain'] ?? ''
				);
		}

		return $result;
	}

	/**
	 * Get the value of a cookie and decode it from JSON.
	 *
	 * @param string $name    The name of the cookie.
	 * @param mixed  $default The default value to return if the cookie is not set or invalid JSON.
	 *
	 * @return mixed The decoded value of the cookie if it exists and is valid JSON, otherwise the default value.
	 */
	public static function get_json( string $name, $default = null ) {
		$value = self::get( $name );
		if ( $value === null ) {
			return $default;
		}
		$decoded = json_decode( $value, true );

		return ( json_last_error() === JSON_ERROR_NONE ) ? $decoded : $default;
	}

	/**
	 * Set a cookie with a JSON encoded value.
	 *
	 * @param string $name    The name of the cookie.
	 * @param mixed  $value   The value to be JSON encoded.
	 * @param array  $options Additional options for the cookie.
	 *
	 * @return bool Whether the cookie was successfully set.
	 */
	public static function set_json( string $name, $value, array $options = [] ): bool {
		$json_value = wp_json_encode( $value );
		if ( $json_value === false ) {
			return false;
		}

		return self::set_secure( $name, $json_value, $options );
	}

	/**
	 * Get the remaining lifetime of a cookie in seconds.
	 *
	 * @param string $name The name of the cookie.
	 *
	 * @return int|null The remaining lifetime in seconds, or null if the cookie doesn't exist or is a session cookie.
	 */
	public static function get_remaining_lifetime( string $name ): ?int {
		if ( ! self::exists( $name ) ) {
			return null;
		}

		$cookie_string = $_SERVER['HTTP_COOKIE'] ?? '';
		if ( empty( $cookie_string ) ) {
			return null;
		}

		$cookies = self::parse_cookie_string( $cookie_string );
		if ( ! isset( $cookies[ $name ] ) ) {
			return null;
		}

		$cookie_data = $cookies[ $name ];
		if ( ! isset( $cookie_data['attributes']['expires'] ) ) {
			return null;
		}

		$expire = strtotime( $cookie_data['attributes']['expires'] );
		if ( $expire === false ) {
			return null;
		}

		$remaining = $expire - time();

		return $remaining > 0 ? $remaining : null;
	}

	/**
	 * Set a cookie with recommended security prefixes.
	 *
	 * @param string $name    The name of the cookie
	 * @param string $value   The value of the cookie
	 * @param array  $options Additional options for the cookie
	 *
	 * @return bool Whether the cookie was successfully set
	 */
	public static function set_prefixed( string $name, string $value, array $options = [] ): bool {
		$prefix = 'wp_';
		if ( ! empty( $options['secure'] ) ) {
			$prefix = '__Secure-' . $prefix;
		}
		if ( ! empty( $options['domain'] ) ) {
			$prefix = '__Host-' . $prefix;
		}

		return self::set_secure( $prefix . $name, $value, $options );
	}

	/**
	 * Get the last error message.
	 *
	 * @return string|null The last error message or null if no error
	 */
	public static function get_last_error(): ?string {
		return self::$last_error;
	}

	/**
	 * Validate cookie name according to RFC specifications.
	 *
	 * @param string $name The name to validate
	 *
	 * @return bool Whether the name is valid
	 */
	private static function validate_name( string $name ): bool {
		return (bool) preg_match( '/^[a-zA-Z0-9!#$%&\'*+\-.^_`|~]+$/', $name );
	}

	/**
	 * Sanitize cookie value.
	 *
	 * @param string $value The value to sanitize
	 *
	 * @return string The sanitized value
	 */
	private static function sanitize_value( string $value ): string {
		return wp_kses( $value, [] );
	}

	/**
	 * Parse raw cookie header string into an array.
	 *
	 * @param string $cookie_string Raw cookie header string
	 *
	 * @return array Associative array of cookie data
	 */
	private static function parse_cookie_string( string $cookie_string ): array {
		$cookies = [];
		$parts   = explode( ';', $cookie_string );

		foreach ( $parts as $part ) {
			$part = trim( $part );
			if ( empty( $part ) ) {
				continue;
			}

			$cookie_parts = explode( '=', $part, 2 );
			if ( count( $cookie_parts ) !== 2 ) {
				continue;
			}

			$key   = trim( $cookie_parts[0] );
			$value = trim( $cookie_parts[1], ' "\'' );

			$key_lower = strtolower( $key );
			if ( in_array( $key_lower, self::$cookie_attributes, true ) ) {
				$cookies['attributes'][ $key_lower ] = $value;
			} else {
				$cookies[ $key ] = [
					'value'      => $value,
					'attributes' => []
				];
			}
		}

		return $cookies;
	}

	/**
	 * Set an error message.
	 *
	 * @param string $message The error message
	 */
	private static function set_error( string $message ): void {
		self::$last_error = $message;
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'WP Cookie Error: ' . $message );
		}
	}

	/** Utilities ******************************************************************/

	/**
	 * Get a timestamp for seconds from now.
	 *
	 * @param int $seconds Number of seconds from now
	 *
	 * @return int Unix timestamp
	 */
	public static function seconds( int $seconds = 1 ): int {
		return time() + $seconds;
	}

	/**
	 * Get a timestamp for minutes from now.
	 *
	 * @param int $minutes Number of minutes from now
	 *
	 * @return int Unix timestamp
	 */
	public static function minutes( int $minutes = 1 ): int {
		return time() + ( $minutes * MINUTE_IN_SECONDS );
	}

	/**
	 * Get a timestamp for hours from now.
	 *
	 * @param int $hours Number of hours from now
	 *
	 * @return int Unix timestamp
	 */
	public static function hours( int $hours = 1 ): int {
		return time() + ( $hours * HOUR_IN_SECONDS );
	}

	/**
	 * Get a timestamp for days from now.
	 *
	 * @param int $days Number of days from now
	 *
	 * @return int Unix timestamp
	 */
	public static function days( int $days = 1 ): int {
		return time() + ( $days * DAY_IN_SECONDS );
	}

	/**
	 * Get a timestamp for weeks from now.
	 *
	 * @param int $weeks Number of weeks from now
	 *
	 * @return int Unix timestamp
	 */
	public static function weeks( int $weeks = 1 ): int {
		return time() + ( $weeks * WEEK_IN_SECONDS );
	}

	/**
	 * Get a timestamp for months from now.
	 *
	 * @param int $months Number of months from now
	 *
	 * @return int Unix timestamp
	 */
	public static function months( int $months = 1 ): int {
		return time() + ( $months * MONTH_IN_SECONDS );
	}

	/**
	 * Get a timestamp for years from now.
	 *
	 * @param int $years Number of years from now
	 *
	 * @return int Unix timestamp
	 */
	public static function years( int $years = 1 ): int {
		return time() + ( $years * YEAR_IN_SECONDS );
	}

}