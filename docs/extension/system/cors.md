# CORS

A built-in [`System`](../system.md) extension (`Lav45\MockServer\Extension\Cors\CorsExtension`) that adds
[CORS](https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS) headers to **every** response — including `404`/`405` —
and answers `OPTIONS` preflight requests with a `204` built entirely from the request headers.

It ships **disabled by default**: the block is commented out in `etc/config.yaml`. Uncomment it to enable CORS.

## Enable

```yaml
extensions:
  - class: Lav45\MockServer\Extension\Cors\CorsExtension
    config:
      allowOrigin: [ '*' ]
      allowMethods: [ '*' ]
      allowHeaders: [ '*' ]
      exposeHeaders: [ '*' ]
      allowCredentials: false
      maxAge: 86400
```

The `extensions` list is the complete set — a custom config **replaces** the default one, so keep the other built-in
extensions you still need alongside `CorsExtension`.

## Options

Every key is optional. List values accept either a YAML list (`['GET', 'POST']`) or a comma-separated string
(`'GET, POST'`).

| Key                | Default | Response header                    | Meaning                                                                                                                   |
|--------------------|---------|------------------------------------|---------------------------------------------------------------------------------------------------------------------------|
| `allowOrigin`      | `['*']` | `Access-Control-Allow-Origin`      | `['*']` allows any origin; a list reflects the request `Origin` back when it matches, otherwise no CORS headers are sent. |
| `allowMethods`     | `['*']` | `Access-Control-Allow-Methods`     | `['*']` reflects the requested `Access-Control-Request-Method`; a list is sent as-is.                                     |
| `allowHeaders`     | `['*']` | `Access-Control-Allow-Headers`     | `['*']` reflects the requested `Access-Control-Request-Headers`; a list is sent as-is.                                    |
| `exposeHeaders`    | `['*']` | `Access-Control-Expose-Headers`    | `['*']` reflects the names of the headers already on the response; a list is sent as-is; `null` omits the header.         |
| `allowCredentials` | `false` | `Access-Control-Allow-Credentials` | `true` sends `Access-Control-Allow-Credentials: true`.                                                                    |
| `maxAge`           | —       | `Access-Control-Max-Age`           | Seconds the browser may cache the preflight. Omitted when not set.                                                        |

## Behaviour

- **Origin.** When `allowCredentials` is `false` and `allowOrigin` is `['*']`, the header is a literal `*`. Otherwise
  the
  matching request `Origin` is reflected and `Vary: Origin` is added. A request whose `Origin` is not allowed receives
  no
  CORS headers.
- **Preflight.** An `OPTIONS` request is answered directly with `204 No Content`; the extension owns it and never
  forwards it to routing. `Access-Control-Allow-Methods`, `Access-Control-Allow-Headers` and `Access-Control-Max-Age`
  are added on top of the common headers.
- **Every response.** As a `System` extension it wraps the whole pipeline, so the CORS headers also appear on `404` (no
  route) and `405` (method not allowed) responses.
