# WebHook

webhook.options - see [guzzle request options](https://docs.guzzlephp.org/en/stable/request-options.html)

```json
[
    {
        "request": {
            "method": "POST",
            "url": "/user"
        },
        "response": {
            "status": 200,
            "body": "OK"
        },
        "webhook": {
            "delay": 1,
            "method": "POST",
            "url": "https://api.site.com/webhook",
            "options": {
                "verify": false,
                "http_errors": false,
                "headers": {
                    "X-API-Token": "e71ad173-dacf-493c-be55-643074fdf41c"
                },
                "form_params": {
                    "status": "OK"
                }
            }
        }
    },
    {
        "request": {
            "method": "PUT",
            "url": "/user"
        },
        "response": {
            "status": 200,
            "body": "OK"
        },
        "webhook": {
            "delay": 1,
            "method": "POST",
            "url": "https://api.site.com/webhook",
            "options": {
                "verify": false,
                "http_errors": false,
                "auth": [
                    "login",
                    "password"
                ],
                "json": {
                    "type": "user.create",
                    "data": {
                        "id": 100
                    }
                }
            }
        }
    }
]
```