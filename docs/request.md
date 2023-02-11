# Request

## Routing

### Url params

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

### Array of methods for accessing the resource

```json
[
    {
        "request": {
            "method": [
                "POST",
                "PUT",
                "DELETE"
            ],
            "url": "/company"
        },
        "response": {
            "status": 200,
            "body": "OK"
        }
    },
    {
        "request": {
            "method": "GET",
            "url": "/company"
        },
        "response": {
            "status": 200,
            "body": []
        }
    }
]
```