# Mock Server

HTTP mocking application for testing and fast prototyping.

[![Version](https://img.shields.io/docker/v/lav45/mock-server/latest?sort=semver)](https://hub.docker.com/r/lav45/mock-server)
[![Docker Pulls](https://img.shields.io/docker/pulls/lav45/mock-server.svg)](https://hub.docker.com/r/lav45/mock-server)
[![Docker Image Size](https://img.shields.io/docker/image-size/lav45/mock-server)](https://hub.docker.com/r/lav45/mock-server)
[![Codecov](https://codecov.io/gh/lav45/mock-server/branch/master/graph/badge.svg)](https://codecov.io/gh/lav45/mock-server)

[Docker image](https://hub.docker.com/r/lav45/mock-server)

## Overview

Mock Server is a high-performance HTTP mocking application built with PHP and the [Amp](https://amphp.org/) asynchronous
framework. It allows you to quickly create mock APIs for testing and development, with support for dynamic responses,
webhooks, and automatic reloading of mock files.

## Features

- [Env](docs/env.md) - Storage of variables that can be used in the Response and WebHooks
    - [Server environment](docs/env.md#server-environment) - Server environment
- [Faker](docs/faker.md) - Generate random data with FakerPHP
- [Date](docs/date.md) - Current date and time via templates
- [Request](docs/request.md)
    - [Routing](docs/request.md#requestpath)
- [Response](docs/extension/application/response.md)
    - [Content](docs/extension/application/response/content.md)
        - [Request parameters](docs/extension/application/response/content.md#request-parameters) - You can get data
          from your request and use it in the response
    - [Proxy](docs/extension/application/response/proxy.md)
    - [Data provider](docs/extension/application/response/data.md)
- [Delay](docs/extension/application/delay.md) - Delay any response by `response.delay` seconds, regardless of its type
- [Conditions](docs/extension/application/conditions.md) - Conditional response overrides evaluated before the default
  response
- [WebHooks](docs/extension/application/webhooks.md)
- [Direct](docs/extension/application/direct.md) - Delegate response generation to a remote server
- [CORS](docs/extension/system/cors.md) - CORS headers on every response (disabled by default)
- [TLS/SSL](docs/tls.md) - Serve HTTPS alongside the plain HTTP port (disabled by default)
- [Custom Extensions](docs/extension.md) - Add your own HTTP middleware to the request/response pipeline
    - [System](docs/extension/system.md) - Cross-cutting logic applied to every response
    - [Application](docs/extension/application.md) - Logic that consumes user data from the matched mock
- **Auto reload mock file without restarting the server**

## Quick start

Create mock file `./mocks/index.json` and put the content in it:

```json
[
    {
        "request": {
            "method": "GET",
            "path": "/"
        },
        "response": {
            "type": "content",
            "body": {
                "text": "Hello world!"
            }
        }
    }
]
```

### Run the Mock Server (Docker)

```shell
~$ docker run --rm -it --init -v $(pwd)/mocks:/app/mocks -p 8080:8080 lav45/mock-server:latest
```

### Checking

```shell
~$ curl http://127.0.0.1:8080
HTTP/1.1 200 OK
content-type: application/json
connection: keep-alive
keep-alive: timeout=15
date: Sat, 20 Jun 2026 19:47:09 GMT
transfer-encoding: chunked

{"text":"Hello world!"}
```

### Upgrade old mocks data from 4.x to 5.x

If you are upgrading from an older version, you may need to migrate your mock files:

```shell
docker run --rm -it -u "$(id -u):$(id -g)" -v $(pwd)/mocks:/app/mocks lav45/mock-server:latest bin/migrate
```

## Environment Variables

| Variable          | Default                                                       | Description                                                                                                               |
|-------------------|---------------------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------|
| `PORT`            | `8080`                                                        | Port the server will listen on.                                                                                           |
| `MOCKS_PATH`      | `/app/mocks`                                                  | Path to the directory containing mock JSON files.                                                                         |
| `LOCALE`          | `en_US`                                                       | Locale used for Faker data generation.                                                                                    |
| `LOG_LEVEL`       | `info`                                                        | Logging level [`debug`, `info`, `notice`, `warning`, `error`, `critical`, `alert`, `emergency`].                          |
| `FILTER_HEADERS`  | `host,content-length,connection,keep-alive,transfer-encoding` | Comma-separated list of request headers to strip when forwarding the request to a remote server.                          |
| `MAX_BUFFER_SIZE` | `32`                                                          | Maximum size (in MB) of a proxied/direct response body buffered into memory; a larger body aborts with a BufferException. |

## Development

### Build docker images

```shell
./build.sh
```

### Install require dependency

```shell
./composer install
```

### Running in development mode

```shell
docker run --rm -it --init -v $(pwd):/app:ro -p 8080:8080 mock-server:server bin/start
```

### Running in the tracking mode for changes in mock files

```shell
docker run --rm -it --init -v $(pwd):/app:ro -p 8080:8080 mock-server:server bin/watch bin/start
```

## Tests

### Running Unit Tests

```shell
./unit-test.sh
./unit-test.sh coverage:html # Generate coverage report in ./coverage/
```

### Running E2E Functional Tests

```shell
./e2e-test.sh
```
