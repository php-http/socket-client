<?php

if (file_exists(__DIR__.'/server.sock')) {
    unlink(__DIR__.'/server.sock');
}

$socketServer = stream_socket_server('unix://'.__DIR__.'/server.sock');
$client = stream_socket_accept($socketServer);

fwrite($client, str_replace("\n", "\r\n", <<<'EOR'
HTTP/1.1 200 OK
Content-Type: text/plain

Test
EOR
));

while (!@feof($client)) {
    @fread($client, 1000);
}

unlink(__DIR__.'/server.sock');
