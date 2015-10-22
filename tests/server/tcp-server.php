<?php

$socketServer = stream_socket_server('127.0.0.1:19999');
$client       = stream_socket_accept($socketServer);

fwrite($client, str_replace("\n", "\r\n", <<<EOR
HTTP/1.1 200 OK
Content-Type: text/plain

Test
EOR
));

while (!@feof($client)) {
    @fread($client, 1000);
}
