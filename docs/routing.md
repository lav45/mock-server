# Resource method

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

# With routing params

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