# Socket Client for PHP HTTP

[![Latest Version](https://img.shields.io/github/release/php-http/socket-client.svg?style=flat-square)](https://github.com/php-http/socket-client/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Build Status](https://img.shields.io/travis/php-http/socket-client.svg?style=flat-square)](https://travis-ci.org/php-http/socket-client)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/php-http/socket-client.svg?style=flat-square)](https://scrutinizer-ci.com/g/php-http/socket-client)
[![Quality Score](https://img.shields.io/scrutinizer/g/php-http/socket-client.svg?style=flat-square)](https://scrutinizer-ci.com/g/php-http/socket-client)
[![Total Downloads](https://img.shields.io/packagist/dt/php-http/socket-client.svg?style=flat-square)](https://packagist.org/packages/php-http/socket-client)

The socket client use the stream extension from PHP, which is integrated into the core.

## Install

Via Composer

``` bash
$ composer require php-http/socket-client
```

## Features

 * TCP Socket Domain (tcp://hostname:port)
 * UNIX Socket Domain (unix:///path/to/socket.sock)
 * TLS / SSL Encyrption
 * Client Certificate (only for php > 5.6)

## Usage

The SocketHttpClient class need a [message factory](https://github.com/php-http/message-factory) in order to work:

```php
$options = [];
$client = new new Http\Socket\SocketHttpClient($messageFactory, $options);
```

The `$options` array allow to configure the socket client.

## Options

Here is the list of available options:

 * remote_socket: Specify the remote socket where the library should send the request to
 
 Can be a tcp remote : tcp://hostname:port
 Can be a unix remote : unix://hostname:port
 
 Do not use a tls / ssl scheme, this is handle by the ssl option.
 If not set, the client will try to determine it from the request uri or host header.
 
 * timeout : Timeout for writing request or reading response on the remote
 * ssl : Activate or deactivate the ssl / tls encryption
 * stream_context_options : Custom options for the context of the stream, same as [PHP stream context options](http://php.net/manual/en/context.php)
 
 As an example someone may want to pass a client certificate when using the ssl, a valid configuration for this
 use case would be:
 
 ```php
 $options = [
    'stream_context_options' => [
        'ssl' => [
            'local_cert' => '/path/to/my/client-certificate.pem'
        ]
    ]
 ]
 $client = new Http\Socket\SocketHttpClient($messageFactory, $options);
 ```

 * stream_context_params : Custom parameters for the context of the stream, same as [PHP stream context parameters](http://php.net/manual/en/context.params.php)
 * write_buffer_size : When sending the request we need to bufferize the body, this option specify the size of this buffer, default is 8192,
 if you are sending big file with your client it may be interesting to have a bigger value in order to increase performance.

## Testing

First launch the http server:

```bash
$ ./vendor/bin/http_test_server > /dev/null 2>&1 &
```

Then the test suite:

``` bash
$ composer test
```


## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CONDUCT](CONDUCT.md) for details.


## Security

If you discover any security related issues, please contact us at [security@php-http.org](mailto:security@php-http.org).


## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
