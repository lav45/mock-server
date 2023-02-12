# Mock Server

HTTP mocking application for testing and fast prototyping

## Features

- [Request](./docs/request.md)
  - [routing](./docs/request.md#requesturl)
- [Response](./docs/response.md)
  - [delay](./docs/response.md#responsedelay)
  - [content.json](./docs/response.md#responsecontentjson)
  - [proxy](./docs/response.md#responseproxyurl)
- [WebHook](./docs/webhook.md)

## Quick start

Create mock file `./mocks/test.json` and put the content in it

```json
[{
  "response": {
    "content": {
      "text": "Hello world!"
    }
  }
}]
```

Starting the Mock Server

```shell
docker run --rm -i -v $(pwd)/mocks:/app/mocks -p 8080:8080 lav45/mock-server:latest
```

Checking

```shell
curl http://127.0.0.1:8080/
```
