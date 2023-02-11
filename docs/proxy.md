# Proxy

response.options - see [guzzle request options](https://docs.guzzlephp.org/en/stable/request-options.html)

```json
[
    {
        "request": {
            "method": "GET",
            "url": "/proxy/{path:.+}"
        },
        "response": {
            "proxyUrl": "https://api.site.com/v1/{path}",
            "options": {
                "verify": false,
                "headers": {
                    "Authorization": "Bearer JWT.token"
                }
            }
        }
    }
]
```