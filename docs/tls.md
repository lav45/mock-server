# TLS / SSL

The server can additionally serve **HTTPS** on a dedicated port alongside the plain HTTP port. It is configured with a
`tls:` block in the server config (`/app/etc/config.yaml` by default, or the file pointed to by `CONFIG_PATH`) — not via
an environment variable.

It ships **disabled by default**: the block is commented out in `etc/config.yaml`. Uncomment it and point `cert`/`key`
at your certificate to enable HTTPS. The plain HTTP port keeps listening — TLS is served in addition to it, not instead.

## Enable

```yaml
tls:
  port: 8443
  cert: /app/etc/ssl/cert.pem   # certificate + intermediaries
  key: /app/etc/ssl/key.pem     # private key (optional, defaults to cert)
  passphrase: null              # optional private key passphrase
```

## Options

| Key          | Default | Meaning                                                                                              |
|--------------|---------|------------------------------------------------------------------------------------------------------|
| `port`       | `8443`  | Port for the HTTPS listener. Must be a valid port number.                                            |
| `cert`       | —       | Path to the PEM certificate (with any intermediaries). Required; must be an existing, readable file. |
| `key`        | `cert`  | Path to the PEM private key. When omitted, the key is read from the `cert` file. Must be readable.   |
| `passphrase` | `null`  | Passphrase for the private key, if it is encrypted.                                                  |

Invalid values (missing/unreadable `cert` or `key`, or a bad `port`) abort startup with an `InvalidArgumentException`.

## Run (Docker)

Mount your config and certificates into the container and publish the HTTPS port:

```shell
docker run --rm -it --init \
    -v $(pwd)/mocks:/app/mocks \
    -v $(pwd)/config.yaml:/app/etc/config.yaml \
    -v $(pwd)/ssl:/app/etc/ssl:ro \
    -p 8080:8080 \
    -p 8443:8443 \
    lav45/mock-server:latest
```

### Checking

```shell
~$ curl -k https://127.0.0.1:8443
{"text":"Hello world!"}
```

`-k` skips certificate verification, which is handy with a self-signed certificate during local development.
