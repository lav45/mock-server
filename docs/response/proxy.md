# Response type `proxy`

## `response.url`

Redirects your [request](../request.md) to the `url` and returns its response to you.

The parse param `{path}` from [request.path](../request.md#requestpath) will be overwritten in `response.url`.

For convenience, you can specify all the [request.method](../request.md#requestmethod) used.

The request to the proxy endpoint will use the `request.method`.

| Types  | Default   |
|--------|-----------|
| string | `require` |

```json
[
    {
        "request": {
            "method": [
                "GET",
                "POST",
                "PUT",
                "DELETE",
                "OPTIONS"
            ],
            "path": "/proxy/{path:.+}"
        },
        "response": {
            "type": "proxy",
            "url": "https://api.site.com/{request.params.path}"
        }
    }
]
```

> **Note:** Query parameters from the original request are automatically appended to `response.url`.
> If `response.url` already contains query parameters, those defined in `response.url` take precedence
> when keys collide.

## `response.delay`

Number of seconds to wait

| Types | Default |
|-------|---------|
| float | `0.0`   |

```json
[
    {
        "response": {
            "type": "proxy",
            "delay": 0.2
        }
    }
]
```

## `response.headers`

Additional headers to send to the proxy endpoint. These headers are merged with the original request headers; when keys
collide, `response.headers` take precedence.

| Types  | Default |
|--------|---------|
| object | `{}`    |

```json
[
    {
        "request": {
            "path": "/proxy/{path:.+}"
        },
        "response": {
            "type": "proxy",
            "url": "https://api.site.com/{request.params.path}",
            "headers": {
                "Authorization": "Bearer JWT.token"
            }
        }
    }
]
```

> **Note:** Headers from the original request are automatically forwarded to the proxy endpoint, except those listed in
> the `FILTER_HEADERS` environment variable (`host`, `content-length`, `connection`, `keep-alive`, `transfer-encoding` by
> default). When the proxied body is JSON, `content-type: application/json` is added automatically unless already set.

## `response.content`

The `content` will be passed to the proxy endpoint.

| Types               | Default |
|---------------------|---------|
| array\|string\|null | `null`  |

```json
[
    {
        "request": {
            "path": "/proxy/{path:.+}"
        },
        "response": {
            "type": "proxy",
            "url": "https://api.site.com/content-wrapper",
            "content": {
                "account": {
                    "id": "{{faker.uuid}}"
                }
            }
        }
    }
]
```
