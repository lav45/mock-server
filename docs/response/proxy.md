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

Response HTTP headers

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
