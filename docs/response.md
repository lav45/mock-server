# Response

The HTTP response you expect to receive from the remote server

## Base options

### `response.delay`

Number of seconds to wait

| Types | Default |
|-------|---------|
| float | `0`     |

```json
[
    {
        "response": {
            "delay": 0.2
        }
    }
]
```

## Content

### `response.content.status`

Response HTTP status code

| Types   | Default |
|---------|---------|
| integer | `200`   |

```json
[
    {
        "response": {
            "content": {
                "status": 200
            }
        }
    }
]
```

### `response.content.headers`

Response HTTP headers

| Types  | Default |
|--------|---------|
| object | `[]`    |

```json
[
    {
        "response": {
            "content": {
                "headers": {
                    "content-type": "application/json"
                }
            }
        }
    }
]
```

### `response.content.text`

Response text content

| Types  | Default |
|--------|---------|
| string | `''`    |

```json
[
    {
        "response": {
            "content": {
                "text": "<html><body><h1>Hello world!</h1></body></html>"
            }
        }
    }
]
```

### `response.content.json`

Response content in json format

| Types         | Default |
|---------------|---------|
| array, object | `null`  |

```json
[
    {
        "response": {
            "content": {
                "json": {
                    "status": "OK"
                }
            }
        }
    }
]
```

### Faker

You can use [Faker](https://fakerphp.github.io) to generate random data

```json
[
    {
        "response": {
            "content": {
                "json": {
                    "id": "{{faker.uuid}}",
                    "iban": "{{faker.iban('LV')}}",
                    "time": "{{faker.dateTimeBetween('-1 week', '+1 week').getTimestamp()}}",
                    "dateTime": "{{faker.dateTimeBetween('-1 week', '+1 week').format('d.m.Y H:i:s')}}",
                    "flag": "{{faker.boolean}}",
                    "location": "{{faker.localCoordinates()}}",
                    "el": "{{faker.randomElements(['a', 'b', 'c'], 1, false)}}"
                }
            }
        }
    }
]
```

Response:

```json
{
    "id": "ea6143fe-bf40-3f1a-90d3-e6872204888d",
    "iban": "LV89ORDR6OQ6J4G22N0T3",
    "time": 1676696670,
    "dateTime": "14.02.2023 08:20:34",
    "flag": true,
    "location": {
        "latitude": -39.658608,
        "longitude": 76.24428
    },
    "el": [
        "c"
    ]
}
```

## Proxy

### `response.proxy.url`

Redirects your [request](request.md) to the `proxy.url` and returns its response to you.

The parse param `{path}` from [request.url](request.md#requesturl) will be overwritten in `response.proxy.url`

For convenience, you can specify all the [request.method](request.md#requestmethod) used

| Types  | Default |
|--------|---------|
| string | `null`  |

```json
[
    {
        "request": {
            "method": [
                "GET",
                "POST",
                "PUT",
                "DELETE",
                "OPTIONS"
            ],
            "url": "/proxy/{path:.+}"
        },
        "response": {
            "proxy": {
                "url": "https://api.site.com/{request.urlParams.path}"
            }
        }
    }
]
```

### `response.proxy.options`
Deprecated! Will be removed in the next version.

### `response.proxy.headers`

| Types  | Default |
|--------|---------|
| object | `[]`    |

```json
[
    {
        "request": {
            "url": "/proxy/{path:.+}"
        },
        "response": {
            "proxy": {
                "url": "https://api.site.com/{request.urlParams.path}",
                "headers": {
                    "Authorization": "Bearer JWT.token"
                }
            }
        }
    }
]
```

### `response.proxy.content`

| Types               | Default |
|---------------------|---------|
| array\|string\|null | null    |

```json
[
    {
        "request": {
            "url": "/proxy/{path:.+}"
        },
        "response": {
            "proxy": {
                "url": "https://api.site.com/content-wrapper",
                "content": {
                  "account": {
                    "id": "{{faker.uuid}}"
                  }
                }
            }
        }
    }
]
```

## Data provider

### `response.data.status`

Response HTTP status code

| Types   | Default |
|---------|---------|
| integer | `200`   |

```json
[
    {
        "response": {
            "data": {
                "status": 200
            }
        }
    }
]
```

### `response.data.headers`

Response HTTP headers

| Types  | Default                                |
|--------|----------------------------------------|
| object | `{"content-type": "application/json"}` |

```json
[
    {
        "response": {
            "data": {
                "headers": {
                    "content-type": "application/json"
                }
            }
        }
    }
]
```

### `response.data.pagination.pageParam`

HTTP GET request name of the parameter storing the current page index.

| Types  | Default  |
|--------|----------|
| string | `"page"` |

### `response.data.pagination.pageSizeParam`

HTTP GET request name of the parameter storing the page size.

| Types  | Default      |
|--------|--------------|
| string | `"per-page"` |

### `response.data.pagination.defaultPageSize`

HTTP GET request the default page size.

| Types   | Default |
|---------|---------|
| integer | `20`    |

### `response.data.json`

An array of data in json format

| Types | Default |
|-------|---------|
| array | `[]`    |

```json
[
    {
        "response": {
            "data": {
                "json": [
                    {"id": "537b0bc3-57c2-383b-8819-040dc731963f", "name": "Dana Kilback"},
                    {"id": "{{faker.uuid}}", "name": "{{faker.name}}"}
                ]
            }
        }
    }
]
```

### `response.data.file`

The path to a file with an array of data in json format

The file contains a data set as from the example `response.data.json`

| Types  | Default |
|--------|---------|
| string | `null`  |

```json
[
    {
        "response": {
            "data": {
                "file": "/app/mocks/__data/file.json"
            }
        }
    }
]
```

### Information parameters of pagination

- `response.data.pagination.totalItems` - total number of data items.
- `response.data.pagination.currentPage` - current page number (1-based).
- `response.data.pagination.totalPages` - total number of pages of data.
- `response.data.pagination.pageSize` - number of data items in each page.

### `response.data.result`

| Types  | Default                     |
|--------|-----------------------------|
| string | `"{{response.data.items}}"` |

#### Examples:

1) Pagination information in the headers

```json
[{
    "response": {
        "data": {
            "headers": {
                "X-Pagination-Total-Count": "{{response.data.pagination.totalItems}}",
                "X-Pagination-Current-Page": "{{response.data.pagination.currentPage}}",
                "X-Pagination-Page-Count": "{{response.data.pagination.totalPages}}",
                "X-Pagination-Per-Page": "{{response.data.pagination.pageSize}}"
            },
            "file": "/app/mocks/__data/file.json",
            "result": "{{response.data.items}}"
        }
    }
}]
```

Response headers:
```
content-type: application/json
x-pagination-current-page: 2
x-pagination-page-count: 16
x-pagination-per-page: 2
x-pagination-total-count: 32
```

Response body:
```json
[
    {
        "id": "27b803dc-ceda-36f7-af87-5fbe2055ec0f",
        "name": "Danielle Lebsack"
    },
    {
        "id": "ee6e4d31-f6b2-3aad-a636-6e941ffbaa94",
        "name": "Dr. Verona Satterfield"
    }
]
```

2) Pagination information in the response data

```json
[{
    "response": {
        "data": {
            "file": "/app/mocks/__data/file.json",
            "result": {
                "data": "{{response.data.items}}",
                "pagination": "{{response.data.pagination}}"
            }
        }
    }
}]
```

Response body:
```json
{
    "data": [
        {
            "id": "27b803dc-ceda-36f7-af87-5fbe2055ec0f",
            "name": "Danielle Lebsack"
        },
        {
            "id": "ee6e4d31-f6b2-3aad-a636-6e941ffbaa94",
            "name": "Dr. Verona Satterfield"
        }
    ],
    "pagination": {
        "totalItems": 32,
        "currentPage": 2,
        "totalPages": 16,
        "pageSize": 2
    }
}
```


## Request parameters

You can get data from your request and use it in the response

`request`
- `urlParams` - [Parameters](request.md#requesturl) obtained from the url
- `get` - Data from a GET HTTP request.
- `post` - Data from a POST HTTP request. Contains form data or body json data

Parentheses when describing the path to the parameter
- `{` - The value will be inserted into the string as in a template
- `{{` - The value will be inserted without changing the data type. There can be only one value in the template, all other data will be erased.

```shell
curl --location 'http://127.0.0.1:8080/request/100?id=200' \
--header 'Content-Type: application/json' \
--data '{"id": 300}'
```

```json
[
    {
        "request": {
            "method": ["GET", "POST"],
            "url": "/request/{id:\\d+}"
        },
        "response": {
            "content": {
                "json": {
                    "ID1": "ID: {request.get.id}",
                    "ID2": "ID: {{request.get.id}}",

                    "ID3": "ID: {request.post.id}",
                    "ID4": "ID: {{request.post.id}}",

                    "get": "{{request.get}}",
                    "post": "{{request.post}}",
                    "urlParams": "{{request.urlParams}}"
                }
            }
        }
    }
]
```

Response:
```json
{
    "ID1": "ID: 200",
    "ID2": "200",
    "ID3": "ID: 300",
    "ID4": 300,
    "get": {
        "id": "200"
    },
    "post": {
        "id": 300
    },
    "urlParams": {
        "id": "100"
    }
}
```

