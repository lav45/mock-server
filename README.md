# Mock Server

HTTP mocking application for testing and fast prototyping

## Features
- [Env](./docs/env.md) - storage of variables that can be used in the Response and WebHooks
  - [faker](./docs/env.md#faker) - generate random data
- [Request](./docs/request.md)
  - [routing](./docs/request.md#requesturl)
- [Response](./docs/response.md)
  - [delay](./docs/response.md#responsedelay)
  - [content](./docs/response.md#content)
  - [proxy](./docs/response.md#proxy)
  - [faker](./docs/response.md#faker) - generate random data
  - [data provider](./docs/response.md#data-provider)
  - [request parameters](./docs/response.md#request-parameters) - You can get data from your request and use it in the response
- [WebHooks](./docs/webhooks.md)
  - [faker](./docs/webhooks.md#faker) - generate random data
- Load a new mock file without restarting the server

## Quick start

Create mock file `./mocks/index.json` and put the content in it

```json
[{
  "response": {
    "content": {
      "text": "Hello world!"
    }
  }
}]
```

Run the Mock Server

```shell
docker run --rm -it -v $(pwd)/mocks:/app/mocks -p 8080:8080 lav45/mock-server:latest
```

Checking

```shell
curl http://127.0.0.1:8080/
```

Run in development mode
```shell
./build.sh
./composer install
docker run --rm -it -v $(pwd):/app -p 8080:8080 -e DEBUG=1 mock-server-dev:latest
```

Testing
```shell
./build.sh
./composer install
./composer test
```