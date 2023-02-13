# WebHooks

Send asynchronous requests to a remote server.

All requests are executed one after the other.

## `webhooks[0].delay`

### Summary

Number of seconds to wait

| Types | Default |
|-------|---------|
| float | `0`     |

### Example

```json
[
    {
        "webhooks": [
            {
                "delay": 1,
                "url": "https://api.site.com/webhook"
            }
        ]
    }
]
```

## `webhooks[0].method`

### Summary

HTTP Methods for accessing the resource

| Types  | Default |
|--------|---------|
| string | `POST`  |

### Example

```json
[
    {
        "webhooks": [
            {
                "method": "POST",
                "url": "https://api.site.com/webhook"
            }
        ]
    }
]
```

## `webhooks[0].url`

### Summary

URL to a remote server

| Types  | Default |
|--------|---------|
| string | `null`  |

### Example

```json
[
    {
        "webhooks": [
            {
                "url": "https://api.site.com/webhook"
            }
        ]
    }
]
```

## `webhooks[0].options`

Request options for [guzzle](https://docs.guzzlephp.org/en/stable/request-options.html) http client

| Types  | Default |
|--------|---------|
| object | `[]`    |

### Example

```json
[
    {
        "webhooks": [
            {
                "url": "https://api.site.com/webhook",
                "options": {
                    "verify": false,
                    "headers": {
                        "X-API-Token": "e71ad173-dacf-493c-be55-643074fdf41c"
                    },
                    "json": {
                        "ping": true
                    }
                }
            }
        ]
    }
]
```