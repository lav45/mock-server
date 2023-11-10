# Environment

The data stored in the environment can be used simultaneously in the [Response](response.md) and in the [webhook](webhooks.md)

## Faker

You can use [Faker](https://fakerphp.github.io) to generate random data

```json
[
    {
        "env": {
            "id": "{{faker.uuid}}",
            "iban": "{{faker.iban('LV')}}",
            "dateTime": "{{faker.dateTimeBetween('-1 week', '+1 week').format('Y-m-d H:i:s')}}",
            "amount": 1000
        },
        "response": {
            "content": {
                "json": {
                    "id": "{env.id}",
                    "iban": "{env.iban}",
                    "createdAt": "{env.dateTime}",
                    "updatedAt": "{env.dateTime}",
                    "amountInText": "{env.amount} USD",
                    "amountSourceDataType": "{{env.amount}}"
                }
            }
        },
        "webhooks": [
            {
                "url": "https://api.site.com/webhook",
                "json": {
                    "id": "{env.id}",
                    "iban": "{env.iban}",
                    "amount": "{{env.amount}}",
                    "createdAt": "{env.dateTime}",
                    "updatedAt": "{env.dateTime}"
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