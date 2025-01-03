# Mock Server

HTTP mocking application for testing and fast prototyping

[Docker image](https://hub.docker.com/r/lav45/mock-server)

## Features

- [Env](./docs/env.md) - Storage of variables that can be used in the Response and WebHooks
    - [Faker](./docs/env.md#faker) - Generate random data
- [Request](./docs/request.md)
    - [Routing](./docs/request.md#requesturl)
- [Response](./docs/response.md)
    - [Delay](./docs/response.md#responsedelay)
    - [Content](./docs/response.md#content)
    - [Proxy](./docs/response.md#proxy)
    - [Faker](./docs/response.md#faker) - Generate random data
    - [Server environment](./docs/env.md#server-environment) - Server environment
    - [Data provider](./docs/response.md#data-provider)
    - [Request parameters](./docs/response.md#request-parameters) - You can get data from your request and use it in the
      response
- [WebHooks](./docs/webhooks.md)
    - [Faker](./docs/webhooks.md#faker) - Generate random data
- Load a new mock file without restarting the server

## Quick start

Create mock file `./mocks/index.json` and put the content in it

```json
[
    {
        "request": {
            "method": "GET",
            "url": "/"
        },
        "response": {
            "text": "Hello world!"
        }
    }
]
```

### Run the Mock Server

```shell
docker run --rm -it -v $(pwd)/mocks:/app/mocks -p 8080:8080 lav45/mock-server:latest
```

### Checking

```shell
curl http://127.0.0.1:8080/
```

### Upgrade mocks data

```shell
docker run --rm -it -v $(pwd)/mocks:/app/mocks lav45/mock-server:latest bin/upgrade
```

## Build containers

```shell
./build.sh
```

## Run in development mode

```shell
docker run --rm -it -v $(pwd):/app -p 8080:8080 -e DEBUG=1 -e LOG_LEVEL=debug mock-server-prod:latest
```

## Testing

```shell
./composer test
```
