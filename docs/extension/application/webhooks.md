# WebHooks

> Built-in [`Application`](../application.md) extension — `Lav45\MockServer\Extension\WebHook\WebHookExtension`.
> Enabled by default; registered in the `extensions` block of `etc/config.yaml` — comment that line out to disable it.

Send asynchronous requests to a remote server.

All requests are executed one after the other.

## `webhooks[0].delay`

Number of seconds to wait

| Types | Default |
|-------|---------|
| float | `0`     |

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

HTTP Methods for accessing the resource

| Types  | Default |
|--------|---------|
| string | `POST`  |

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

URL to a remote server

| Types  | Default |
|--------|---------|
| string | `null`  |

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

## `webhooks[0].headers`

| Types  | Default |
|--------|---------|
| object | `{}`    |

```json
[
    {
        "webhooks": [
            {
                "url": "https://api.site.com/webhook",
                "headers": {
                    "X-API-Token": "e71ad173-dacf-493c-be55-643074fdf41c"
                }
            }
        ]
    }
]
```

> **Note:** When the webhook body is JSON, `content-type: application/json` is added automatically unless already set.

## `webhooks[0].body`

Request body — plain text string or JSON object/array.

| Types                 | Default |
|-----------------------|---------|
| string, array, object | `''`    |

```json
[
    {
        "webhooks": [
            {
                "url": "https://api.site.com/webhook",
                "body": {
                    "ping": true
                }
            }
        ]
    }
]
```

```json
[
    {
        "webhooks": [
            {
                "url": "https://api.site.com/webhook",
                "body": "<note><body>Don't forget me this weekend!</body></note>"
            }
        ]
    }
]
```