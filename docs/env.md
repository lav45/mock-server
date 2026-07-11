# Environment

The data stored in the environment can be used simultaneously in the [Response](extension/application/response.md) and
in the [webhook](extension/application/webhooks.md)

## Example

Store values in `env` and reuse them across the response and webhooks. The values can be static or generated with a
template such as [`{{faker.*}}`](faker.md):

```json
[
    {
        "env": {
            "id": "{{faker.uuid}}",
            "iban": "{{faker.iban('LV')}}",
            "createdAt": "{{faker.dateTimeBetween('-1 week', '+1 week').format('Y-m-d H:i:s')}}",
            "now": "{{date.format('Y-m-d H:i:s')}}",
            "amount": 1000
        },
        "response": {
            "type": "content",
            "body": {
                "id": "{{env.id}}",
                "iban": "{{env.iban}}",
                "createdAt": "{{env.createdAt}}",
                "updatedAt": "{{env.now}}",
                "amountInText": "{env.amount} USD",
                "amountSourceDataType": "{{env.amount}}"
            }
        },
        "webhooks": [
            {
                "url": "https://api.site.com/webhook",
                "body": {
                    "id": "{{env.id}}",
                    "iban": "{{env.iban}}",
                    "amount": "{{env.amount}}",
                    "createdAt": "{{env.createdAt}}",
                    "updatedAt": "{{env.now}}"
                }
            }
        ]
    }
]
```

Response:

```json
{
    "id": "ea6143fe-bf40-3f1a-90d3-e6872204888d",
    "iban": "LV89ORDR6OQ6J4G22N0T3",
    "createdAt": "2023-02-17 04:24:55",
    "updatedAt": "2023-02-17 04:24:55",
    "amountInText": "1000 USD",
    "amountSourceDataType": 1000
}
```

Webhook will send the data:

```json
{
    "id": "ea6143fe-bf40-3f1a-90d3-e6872204888d",
    "iban": "LV89ORDR6OQ6J4G22N0T3",
    "amount": 1000,
    "createdAt": "2023-02-17 04:24:55",
    "updatedAt": "2023-02-17 04:24:55"
}
```

## Global environment via config

Values defined under the `env` block of the server config (`etc/config.yaml`, or the file pointed to by `CONFIG_PATH`)
are available in **every** mock as `{{env.KEY}}` — no need to repeat them in each file:

```yaml
env:
  DOMAIN: api.server.com
  version: 1
```

```json
[
    {
        "response": {
            "type": "content",
            "body": {
                "url": "https://{env.DOMAIN}/v{env.version}"
            }
        }
    }
]
```

Response:

```json
{
    "url": "https://api.server.com/v1"
}
```

A mock's own `env` block is merged with the global one, so both sets of keys are visible. If the same key exists in
both, the mock's value **overrides** the global one.

## Server environment

You can pass the environment parameters when starting the container

```shell
docker run --rm -it --init -v $(pwd)/mocks:/app/mocks -p 8080:8080 -e DOMAIN=api.server.com lav45/mock-server:latest
```

```json
[
    {
        "response": {
            "type": "content",
            "body": {
                "domain": "{{env.DOMAIN}}",
                "url": "https://{env.DOMAIN}/v1",
                "undefined": "{{env.SSS}}"
            }
        }
    }
]
```

Response:

```json
{
    "domain": "api.server.com",
    "url": "https://api.server.com/v1",
    "undefined": null
}
```
