# Request

Parameters for finding the [response](response.md) to your request

## `request.url`

### Summary

Mockserver keep track of this `request.url`, if they match, the [response.content.json.request](response.md#responsecontentjson) is returned to you.

`request.url`: "/user/{id}" rewrite "{id}" in `response.content.json.request`: "GET /user/{id}".

You can get acquainted with the syntax in detail [nikic/fast-route](https://github.com/nikic/FastRoute#defining-routes)

| Types  | Default |
|--------|---------|
| string | `/`     |

### Example

```json
[
    {
        "request": {
            "url": "/user/{id}"
        },
        "response": {
            "content": {
                "json": {
                    "request": "GET /user/{id}"
                }
            }
        }
    }
]
```

## `request.method`

### Summary

HTTP Methods for accessing the resource

| Types         | Default   |
|---------------|-----------|
| string, array | `['GET']` |

### Example

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