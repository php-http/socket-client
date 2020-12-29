#!/bin/bash

set -eo pipefail

cd $(dirname $0)

C=FR
ST=Ile-de-France
L=Paris
O="PHP-HTTP"
CN="socket-adapter"

openssl req -out ca.pem -new -x509 -subj "/C=$C/ST=$ST/L=$L/O=$O/CN=socket-server" -passout pass:password
openssl genrsa -out server.key
openssl req -key server.key -new -out server.req -subj "/C=$C/ST=$ST/L=$L/O=$O/CN=socket-adapter" -passout pass:password
openssl x509 -req -in server.req -CA ca.pem -CAkey privkey.pem -CAserial file.srl -out server.pem -passin pass:password

openssl genrsa -out client.key
openssl req -key client.key -new -out client.req -subj "/C=$C/ST=$ST/L=$L/O=$O/CN=socket-adapter-client" -passout pass:password
openssl x509 -req -in client.req -CA ca.pem -CAkey privkey.pem -CAserial file.srl -out client.pem -passin pass:password

cat client.pem client.key > client-and-key.pem
cat server.pem server.key > server-and-key.pem
