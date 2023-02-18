# Request

Parameters for finding the [response](response.md) to your request

## `request.url`

You can get acquainted with the syntax in detail [nikic/fast-route](https://github.com/nikic/FastRoute#defining-routes)

| Types  | Default |
|--------|---------|
| string | `/`     |

```json
[
    {
        "request": {
            "url": "/user/{id}"
        }
    }
]
```

## `request.method`

HTTP Methods for accessing the resource

| Types         | Default   |
|---------------|-----------|
| string, array | `['GET']` |

```json
[
    {
        "request": {
            "method": "GET"
        }
    },
    {
        "request": {
            "method": [
                "POST",
                "PUT",
                "DELETE"
            ]
        }
    }
]
```