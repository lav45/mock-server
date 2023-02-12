# Response

The HTTP response you expect to receive from the remote server

## `response.delay`

### Summary

Number of seconds to wait

| Types | Default |
|-------|---------|
| float | `0`     |

### Example

```json
[
    {
        "response": {
            "delay": 5
        }
    }
]
```

## `response.content.status`

### Summary

Response HTTP status code

| Types   | Default |
|---------|---------|
| integer | `200`   |

### Example

```json
[
    {
        "response": {
            "content": {
                "status": 200
            }
        }
    }
]
```

## `response.content.headers`

### Summary

Response HTTP headers

| Types  | Default |
|--------|---------|
| object | `[]`    |

### Example

```json
[
    {
        "response": {
            "content": {
                "headers": {
                    "content-type": "application/json"
                }
            }
        }
    }
]
```

## `response.content.text`

### Summary

Response text content

| Types  | Default |
|--------|---------|
| string | `''`    |

### Example

```json
[
    {
        "response": {
            "content": {
                "text": "<html><body><h1>Hello world!</h1></body></html>"
            }
        }
    }
]
```

## `response.content.json`

Response content in json format

| Types         | Default |
|---------------|---------|
| array, object | `null`  |

### Example

```json
[
    {
        "response": {
            "content": {
                "json": {
                    "status": "OK"
                }
            }
        }
    }
]
```

## `response.proxy.url`

Redirects your [request](request.md) to the `proxy.url` and returns its response to you.

The parse param `{path}` from [request.url](request.md#requesturl) will be overwritten in `response.proxy.url`

For convenience, you can specify all the [request.method](request.md#requestmethod) used 

| Types  | Default |
|--------|---------|
| string | `null`  |

### Example

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
            "url": "/proxy/{path:.+}"
        },
        "response": {
            "proxy": {
                "url": "https://api.site.com/{path}"
            }
        }
    }
]
```

## `response.proxy.options`

Request options for [guzzle](https://docs.guzzlephp.org/en/stable/request-options.html) http client

| Types  | Default |
|--------|---------|
| object | `[]`    |

### Example

```json
[
    {
        "request": {
            "url": "/proxy/{path:.+}"
        },
        "response": {
            "proxy": {
                "url": "https://api.site.com/{path}",
                "options": {
                    "verify": false,
                    "headers": {
                        "Authorization": "Bearer JWT.token"
                    }
                }
            }
        }
    }
]
```
