# WebHooks

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

## `webhooks[0].options`
Deprecated! Will be removed in the next version.

## `webhooks[0].headers`

| Types  | Default |
|--------|---------|
| object | `[]`    |

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

## `webhooks[0].json`

| Types  | Default |
|--------|---------|
| object | `[]`    |

```json
[
    {
        "webhooks": [
            {
                "url": "https://api.site.com/webhook",
                "json": {
                    "ping": true
                }
            }
        ]
    }
]
```

## `webhooks[0].text`

| Types  | Default |
|--------|---------|
| string | ''      |

```json
[
    {
        "webhooks": [
            {
                "url": "https://api.site.com/webhook",
                "text": "<note><body>Don't forget me this weekend!</body></note>"
            }
        ]
    }
]
```

## Faker

You can use [Faker](https://fakerphp.github.io) to generate random data

```json
[
    {
        "webhooks": [
            {
                "url": "https://api.site.com/webhook",
                "json": {
                    "id": "{{faker.uuid}}",
                    "createdAt": "{{faker.unixTime}}"
                }
            }
        ]
    }
]
```

Webhook will send the data:

```json
{
    "id": "ea6143fe-bf40-3f1a-90d3-e6872204888d",
    "createdAt": 1043055018
}
```