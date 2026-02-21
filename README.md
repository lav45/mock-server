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
    - [Faker](docs/env.md#faker) - Generate random data
- [Request](docs/request.md)
    - [Routing](docs/request.md#requesturl)
- [Response](docs/response.md)
    - [Content](docs/response/content.md)
      - [Delay](docs/response/content.md#responsedelay)
      - [Faker](docs/response/content.md#faker) - Generate random data
      - [Request parameters](docs/response/content.md#request-parameters) - You can get data from your request and use it in the response
    - [Proxy](docs/response/proxy.md)
    - [Data provider](docs/response/data.md)
- [WebHooks](docs/webhooks.md)
    - [Faker](docs/webhooks.md#faker) - Generate random data
- **Auto reload mock file without restarting the server**

## Quick start

Create mock file `./mocks/index.json` and put the content in it:

```json
[
    {
        "request": {
            "method": "GET",
            "url": "/"
        },
        "response": {
            "type": "content",
            "text": "Hello world!"
        }
    }
]
```

### Run the Mock Server (Docker)

```shell
docker run --rm -it -v $(pwd)/mocks:/app/mocks -p 8080:8080 lav45/mock-server:latest
```

### Checking

```shell
curl http://127.0.0.1:8080/
```

### Upgrade mocks data

If you are upgrading from an older version, you may need to migrate your mock files:

```shell
docker run --rm -it -v $(pwd)/mocks:/app/mocks lav45/mock-server:latest upgrade
```

## Environment Variables

| Variable             | Default      | Description                                                                                      |
|----------------------|--------------|--------------------------------------------------------------------------------------------------|
| `PORT`               | `8080`       | Port the server will listen on.                                                                  |
| `MOCKS_PATH`         | `/app/mocks` | Path to the directory containing mock JSON files.                                                |
| `LOCALE`             | `en_US`      | Locale used for Faker data generation.                                                           |
| `LOG_LEVEL`          | `info`       | Logging level (`debug`, `info`, `notice`, `warning`, `error`, `critical`, `alert`, `emergency`). |
| `FILE_WATCH_TIMEOUT` | `0.2`        | Timeout for mock file watcher in seconds.                                                        |

## Development

### Local Setup

```shell
./composer install
```

### Build Docker Containers

```shell
./build.sh
```

### Run in Development Mode

```shell
docker run --rm -it -v $(pwd):/app -p 8080:8080 mock-server:server
```

## Tests

### Running Unit Tests

```shell
./composer phpunit test/Unit
```

### Running E2E Functional Tests

Requires Docker and built images (`./build.sh`).

```shell
./e2e-test.sh phpunit test/Functional
```