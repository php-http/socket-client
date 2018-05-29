<?php

require_once __DIR__.'/../Semaphore.php';

$context = stream_context_create([
    'ssl' => [
        'local_cert' => __DIR__.'/ssl/server-and-key.pem',
    ],
]);

$socketServer = stream_socket_server('tcp://127.0.0.1:19999', $errNo, $errStr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $context);
stream_socket_enable_crypto($socketServer, false);

$client = stream_socket_accept($socketServer);
stream_set_blocking($client, true);
if (@stream_socket_enable_crypto($client, true, STREAM_CRYPTO_METHOD_TLSv1_2_SERVER)) {
    fwrite($client, str_replace("\n", "\r\n", <<<EOR
HTTP/1.1 200 OK
Content-Type: text/plain

Test
EOR
    ));
} else {
    fwrite($client, str_replace("\n", "\r\n", <<<EOR
HTTP/1.1 400 Bad Request
Content-Type: text/plain

Test
EOR
    ));
}

while (!@feof($client)) {
    @fread($client, 1000);
}

\Http\Client\Socket\Tests\Semaphore::release();
