# Mock-Server

HTTP mocking application for testing

## Installing

```shell
# Build it into you project
~$ composer require lav45/mock-server
~$ php vendor/bin/mock-server --host=0.0.0.0 --port=8080 --mocks=./mocks

# OR just pul docker image
~$ docker pull lav45/mock-server:latest
~$ docker run --rm -i -v $(pwd)/mocks:/mocks -p 8080:8080 lav45/mock-server:latest
```

## Example

Create json file in the `/mocks` folder

### Minimal:
```json
[{}]
```
Open: http://0.0.0.0:8080/

### Hello world!
```json
[{
    "response": {
        "body": "Hello world!"
    }
}]
```
Open: http://0.0.0.0:8080/

### Resource method
```json
[
    {
        "request": {
            "method": "GET",
            "path": "/user",
            "headers": {
                "content-type": "application/json"
            }
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
            "path": "/user",
            "headers": {
                "content-type": "application/json"
            }
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
`request.path`: "/user/{id}" rewrite in `response.body.method`: "GET /user/{id}"
```json
[
    {
        "request": {
            "method": "GET",
            "path": "/user/{id}",
            "headers": {
                "content-type": "application/json"
            }
        },
        "response": {
            "status": 200,
            "headers": {
                "content-type": "application/json"
            },
            "body": {
                "method": "GET /user/{id}",
                "status": "OK"
            }
        }
    }
]
```
Open: GET http://0.0.0.0:8080/user/5