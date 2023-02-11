# Mock-Server

HTTP mocking application for testing

## Installing
run docker image

```shell
~$ docker pull lav45/mock-server:latest
~$ docker run --rm -i \
    -v $(pwd)/mocks:/app/mocks \
    -p 8080:8080 \
    --name mock-server \
    lav45/mock-server:latest --host=0.0.0.0 --port=8080 --mocks=/app/mocks
```

## Examples
Create json file in the `./mocks` folder

### Hello world!
```shell
~$ cat ./mocks/test.json
```
```json
[{
    "response": {
        "body": "Hello world!"
    }
}]
```
Open: http://0.0.0.0:8080/

### Proxy
response.options - see [guzzle request options](https://docs.guzzlephp.org/en/stable/request-options.html)

```json
[{
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
}]
```

### WebHook
webhook.options - see [guzzle request options](https://docs.guzzlephp.org/en/stable/request-options.html)

```json
[{
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
}, {
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
            "auth": ["login", "password"],
            "json": {
                "type": "user.create",
                "data": {"id": 100}
            }
        }
    }
}]
```

### Resource method
```json
[
    {
        "request": {
            "method": "GET",
            "url": "/user"
        },
        "response": {
            "status": 200,
            "headers": {
                "content-type": "application/json"
            },
            "body": {
                "method": "GET /user",
                "status": "OK"
            }
        }
    },
    {
        "request": {
            "method": "POST",
            "url": "/user"
        },
        "response": {
            "status": 200,
            "headers": {
                "content-type": "application/json"
            },
            "body": {
                "method": "POST /user",
                "status": "OK"
            }
        }
    }
]
```
Open: GET http://0.0.0.0:8080/user
Open: POST http://0.0.0.0:8080/user

### With routing params
`request.url`: "/user/{id}" rewrite in `response.body.method`: "GET /user/{id}" and `response.body.id`: "{id}"
```json
[
    {
        "request": {
            "method": "GET",
            "url": "/user/{id}"
        },
        "response": {
            "status": 200,
            "headers": {
                "content-type": "application/json"
            },
            "body": {
                "id": "{id}",
                "method": "GET /user/{id}",
                "status": "OK"
            }
        }
    }
]
```
Open: GET http://0.0.0.0:8080/user/5