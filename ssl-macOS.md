# Generating SSL Certificates on macOS

When generating SSL Certificates on macOS, you must ensure that you're using brew's openssl binary and not the one provided by the OS.

To do that, find out where your openssl is installed by running:

```bash
$ brew info openssl
```

You should see something like this:

```
openssl@1.1: stable 1.1.1i (bottled) [keg-only]
Cryptography and SSL/TLS Toolkit
https://openssl.org/
/usr/local/Cellar/openssl@1.1/1.1.1i (8,067 files, 18.5MB)
  Poured from bottle on 2020-12-11 at 11:31:46
From: https://github.com/Homebrew/homebrew-core/blob/HEAD/Formula/openssl@1.1.rb
License: OpenSSL
==> Caveats
A CA file has been bootstrapped using certificates from the system
keychain. To add additional certificates, place .pem files in
  /usr/local/etc/openssl@1.1/certs

and run
  /usr/local/opt/openssl@1.1/bin/c_rehash

openssl@1.1 is keg-only, which means it was not symlinked into /usr/local,
because macOS provides LibreSSL.

If you need to have openssl@1.1 first in your PATH run:
  echo 'export PATH="/usr/local/opt/openssl@1.1/bin:$PATH"' >> /Users/flavio/.bash_profile

For compilers to find openssl@1.1 you may need to set:
  export LDFLAGS="-L/usr/local/opt/openssl@1.1/lib"
  export CPPFLAGS="-I/usr/local/opt/openssl@1.1/include"

For pkg-config to find openssl@1.1 you may need to set:
  export PKG_CONFIG_PATH="/usr/local/opt/openssl@1.1/lib/pkgconfig"

==> Analytics
install: 855,315 (30 days), 2,356,331 (90 days), 7,826,269 (365 days)
install-on-request: 139,236 (30 days), 373,801 (90 days), 1,120,685 (365 days)
build-error: 0 (30 days)
```

The important part is this:

> echo 'export PATH="/usr/local/opt/openssl@1.1/bin:$PATH"' >> /Users/flavio/.bash_profile

Instead of running `./tests/server/ssl/generate.sh`, you should instead run:

```bash
$ PATH="/usr/local/opt/openssl@1.1/bin ./tests/server/ssl/generate.sh
```

You should now be good to go.
