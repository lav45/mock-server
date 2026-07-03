# Faker

[FakerPHP](https://fakerphp.github.io) generates random data. Call any Faker formatter from a template with the
`{{faker.*}}` syntax — for example `{{faker.uuid}}`, `{{faker.iban('LV')}}` or
`{{faker.dateTimeBetween('-1 week', '+1 week').format('Y-m-d H:i:s')}}`.

Faker works **anywhere templates are resolved** — in [`env`](env.md), a [response](extension/application/response.md)
body, a [webhook](extension/application/webhooks.md) body, [conditions](extension/application/conditions.md), or a [direct](extension/application/direct.md) reply.

The generation locale is set by the `LOCALE` environment variable (default `en_US`).

## Example

```json
[
    {
        "response": {
            "type": "content",
            "body": {
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
