# WordPress Cookie Library

A comprehensive PHP library for managing cookies in WordPress, providing secure cookie handling, multisite support, and advanced cookie operations.

## Features

- 🔒 **Secure by Default**: Built with security best practices
- 🌐 **Multisite Support**: Handle cookies across WordPress multisite networks
- ⏱️ **Time Utilities**: Convenient methods for setting cookie expiration times
- 🔄 **Multiple Operations**: Set and manage multiple cookies at once
- 🎯 **JSON Support**: Built-in JSON encoding and decoding
- ⚡ **WordPress Integration**: Seamless integration with WordPress functions
- 🛡️ **Security Prefixes**: Support for security-enhanced cookie prefixes
- 🧰 **Error Handling**: Comprehensive error tracking and logging

## Requirements

- PHP 7.4 or later
- WordPress 5.0 or later

## Installation

Install via Composer:

```bash
composer require arraypress/wp-cookie
```

## Basic Usage

### Setting Cookies

```php
use ArrayPress\WP\Cookie;

// Set a basic cookie
Cookie::set( 'my_cookie', 'cookie_value');

// Set a cookie with expiration time
Cookie::set( 'my_cookie', 'value', Cookie::hours( 2 ) );  // Expires in 2 hours

// Set a secure cookie with WordPress defaults
Cookie::set_secure( 'my_cookie', 'cookie_value');
```

### Time Utilities

```php
// Various expiration times
Cookie::set( 'seconds_cookie', 'value', Cookie::seconds( 30 ) );  // 30 seconds
Cookie::set( 'minutes_cookie', 'value', Cookie::minutes( 15 ) );  // 15 minutes
Cookie::set( 'hours_cookie', 'value', Cookie::hours( 2 ) );      // 2 hours
Cookie::set( 'days_cookie', 'value', Cookie::days( 7 ) );        // 7 days
Cookie::set( 'weeks_cookie', 'value', Cookie::weeks( 2 ) );      // 2 weeks
Cookie::set( 'months_cookie', 'value', Cookie::months( 6 ) );    // 6 months
Cookie::set( 'years_cookie', 'value', Cookie::years( 1 ) );      // 1 year
```

### Getting Cookie Values

```php
// Get a cookie value
$value = Cookie::get( 'my_cookie');

// Get with default value if cookie doesn't exist
$value = Cookie::get( 'my_cookie', 'default_value' );

// Check if a cookie exists
if ( Cookie::exists(  'my_cookie') ) {
    // Cookie exists
}
```

### Working with JSON Data

```php
// Set a cookie with JSON data
$data = [ 'key' => 'value', 'array' => [ 1, 2, 3 ] ];
Cookie::set_json( 'json_cookie', $data );

// Get and decode JSON data
$data = Cookie::get_json( 'json_cookie' );
```

### Multisite Support

```php
// Set a cookie for the current site in multisite
Cookie::set_site_cookie( 'site_cookie', 'site_value' );

// Set a network-wide cookie
Cookie::set_network_cookie( 'network_cookie', 'network_value' );
```

### Multiple Cookie Operations

```php
// Set multiple cookies at once
$cookies = [
    'cookie1' => 'value1',
    'cookie2' => 'value2'
];
Cookie::set_multiple( $cookies, Cookie::days(  1 ) );

// Delete multiple cookies
$names = [ 'cookie1', 'cookie2' ];
Cookie::delete_multiple( $names );
```

### Security Prefixes

```php
// Set a cookie with security prefixes
Cookie::set_prefixed( 'secure_cookie', 'value', [
    'secure' => true,
    'domain' => true
] ); // Results in: __Host-wp_secure_cookie
```

### Cookie Lifetime Management

```php
// Get remaining lifetime of a cookie
$remaining = Cookie::get_remaining_lifetime( 'my_cookie');
if ( $remaining !== null ) {
    echo "Cookie expires in {$remaining} seconds";
}
```

### Error Handling

```php
// Set a cookie and check for errors
if ( ! Cookie::set( 'my_cookie', 'value' ) ) {
    $error = Cookie::get_last_error();
    error_log("Cookie error: " . $error);
}
```

## Advanced Options

### Custom Cookie Options

```php
$options = [
    'expire'   => Cookie::days(  7 ),    // 7 days
    'path'     => '/custom/path',
    'domain'   => 'example.com',
    'secure'   => true,
    'httponly' => true,
    'samesite' => Cookie::SAMESITE_STRICT
];

Cookie::set_secure( 'custom_cookie', 'value', $options);
```

## Security Features

- Automatic value sanitization using `wp_kses`
- RFC-compliant cookie name validation
- SameSite attribute enforcement
- HTTPOnly flag enabled by default
- Secure flag enabled by default for HTTPS
- Support for security-enhanced cookie prefixes

## Error Handling

The library includes comprehensive error handling:

- Invalid cookie names
- Headers already sent
- JSON encoding/decoding errors
- Automatic error logging when WP_DEBUG is enabled

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the GPL-2.0-or-later License.

## Support

- [Documentation](https://github.com/arraypress/wp-cookie)
- [Issue Tracker](https://github.com/arraypress/wp-cookie/issues)