# WebHook

Send an asynchronous request to a remote server

## `webhook.delay`

### Summary

Number of seconds to wait

| Types | Default |
|-------|---------|
| float | `0`     |

### Example

```json
[
    {
        "webhook": {
            "delay": 5
        }
    }
]
```

## `webhook.method`

### Summary

HTTP Methods for accessing the resource

| Types  | Default |
|--------|---------|
| string | `POST`  |

### Example

```json
[
    {
        "webhook": {
            "method": "POST"
        }
    }
]
```

## `webhook.url`

### Summary

URL to a remote server

| Types  | Default |
|--------|---------|
| string | `null`  |

### Example

```json
[
    {
        "webhook": {
            "url": "https://api.site.com/webhook"
        }
    }
]
```

## `webhook.options`

Request options for [guzzle](https://docs.guzzlephp.org/en/stable/request-options.html) http client

| Types  | Default |
|--------|---------|
| object | `[]`    |

### Example

```json
[
    {
        "webhook": {
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
    }
]
```