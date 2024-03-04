<?php

require_once __DIR__.'/../Semaphore.php';

$context = stream_context_create([
    'ssl' => [
        'local_cert' => __DIR__.'/ssl/server-and-key.pem',
        'cafile' => __DIR__.'/ssl/ca.pem',
        'capture_peer_cert' => true,
    ],
]);

$socketServer = stream_socket_server('tcp://127.0.0.1:19999', $errNo, $errStr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $context);
stream_socket_enable_crypto($socketServer, false);

$client = stream_socket_accept($socketServer);
stream_set_blocking($client, true);
stream_socket_enable_crypto($client, true, STREAM_CRYPTO_METHOD_TLSv1_2_SERVER);

// Verify client certificate
$name = null;

if (isset(stream_context_get_options($context)['ssl']['peer_certificate'])) {
    $client_cert = stream_context_get_options($context)['ssl']['peer_certificate'];
    $name = openssl_x509_parse($client_cert)['subject']['CN'];
}

if ('socket-adapter-client' == $name) {
    fwrite($client, str_replace("\n", "\r\n", <<<EOR
HTTP/1.1 200 OK
Content-Type: text/plain

Test
EOR
    ));
} else {
    fwrite($client, str_replace("\n", "\r\n", <<<EOR
HTTP/1.1 403 Invalid ssl certificate
Content-Type: text/plain

Test
EOR
    ));
}

while (!@feof($client)) {
    @fread($client, 1000);
}
Http\Client\Socket\Tests\Semaphore::release();
