# Mock Server

HTTP mocking application for testing and fast prototyping

## Features

- [Request](./docs/request.md)
  - [routing](./docs/request.md#routing)
- [Response](./docs/response.md) 
  - [delay](./docs/response.md#delay)
  - [proxy](./docs/response.md#proxy)
- [WebHook](./docs/webhook.md)

## Quick start

Create mock file `./mocks/test.json` and put the content in it

```json
[{
    "response": {
        "body": "Hello world!"
    }
}]
```

Starting the server

```shell
~$ docker run --rm -i -v $(pwd)/mocks:/app/mocks -p 8080:8080 lav45/mock-server:latest
```

Checking

```shell
~$ curl http://0.0.0.0:8080/
Hello world!
```
