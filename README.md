# Mock Server

HTTP mocking application for testing and fast prototyping

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

## Features

- [Request routing](./docs/request.md)
- [Response delay](./docs/response.md)
- [WebHook](./docs/webhook.md)
- [Proxy](./docs/proxy.md)
- Recursive scanning target folder `/app/mocks/**.json`
