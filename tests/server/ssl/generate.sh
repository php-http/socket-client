#/bin/bash

C=FR
ST=Ile-de-France
L=Paris
O="PHP-HTTP"
CN="socket-adapter"

openssl req -out ca.pem -new -x509 -subj "/C=$C/ST=$ST/L=$L/O=$O/CN=socket-server"

openssl genrsa -out server.key 1024 -subj "/C=$C/ST=$ST/L=$L/O=$O/CN=socket-adapter"
openssl req -key server.key -new -out server.req -subj "/C=$C/ST=$ST/L=$L/O=$O/CN=socket-adapter"
openssl x509 -req -in server.req -CA ca.pem -CAkey privkey.pem -CAserial file.srl -out server.pem

openssl genrsa -out client.key 1024 -subj "/C=$C/ST=$ST/L=$L/O=$O/CN=socket-adapter-client"
openssl req -key client.key -new -out client.req -subj "/C=$C/ST=$ST/L=$L/O=$O/CN=socket-adapter-client"
openssl x509 -req -in client.req -CA ca.pem -CAkey privkey.pem -CAserial file.srl -out client.pem

cat client.pem client.key > client-and-key.pem
cat server.pem server.key > server-and-key.pem
