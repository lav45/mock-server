# Response

The HTTP response you expect to receive from the remote server

## `response.delay`

### Summary

Number of seconds to wait

| Types | Default |
|-------|---------|
| float | `0`     |

### Example

```json
[
    {
        "response": {
            "delay": 5
        }
    }
]
```

## `response.content.status`

### Summary

Response HTTP status code

| Types   | Default |
|---------|---------|
| integer | `200`   |

### Example

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

## `response.content.headers`

### Summary

Response HTTP headers

| Types  | Default |
|--------|---------|
| object | `[]`    |

### Example

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

## `response.content.text`

### Summary

Response text content

| Types  | Default |
|--------|---------|
| string | `''`    |

### Example

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

## `response.content.json`

Response content in json format

| Types         | Default |
|---------------|---------|
| array, object | `null`  |

### Example

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

## `response.proxy.url`

Redirects your [request](request.md) to the `proxy.url` and returns its response to you.

The parse param `{path}` from [request.url](request.md#requesturl) will be overwritten in `response.proxy.url`

For convenience, you can specify all the [request.method](request.md#requestmethod) used 

| Types  | Default |
|--------|---------|
| string | `null`  |

### Example

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
                "url": "https://api.site.com/{path}"
            }
        }
    }
]
```

## `response.proxy.options`

Request options for [guzzle](https://docs.guzzlephp.org/en/stable/request-options.html) http client

| Types  | Default |
|--------|---------|
| object | `[]`    |

### Example

```json
[
    {
        "request": {
            "url": "/proxy/{path:.+}"
        },
        "response": {
            "proxy": {
                "url": "https://api.site.com/{path}",
                "options": {
                    "verify": false,
                    "headers": {
                        "Authorization": "Bearer JWT.token"
                    }
                }
            }
        }
    }
]
```

## Faker

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