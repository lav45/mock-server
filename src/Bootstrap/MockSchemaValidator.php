<?php declare(strict_types=1);

namespace Lav45\MockServer\Bootstrap;

use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;

final readonly class MockSchemaValidator
{
    private object $schema;
    private Validator $validator;

    public function __construct(
        string $schemaFile = __DIR__ . '/../../schema/mock.schema.json',
    ) {
        $content = \file_get_contents($schemaFile);
        if ($content === false) {
            throw new \RuntimeException('Unable to read mock schema: "' . $schemaFile . '"');
        }
        $this->schema = \json_decode($content, associative: false, flags: JSON_THROW_ON_ERROR);
        $this->validator = new Validator();
    }

    public function validate(array $mock): void
    {
        // The mock root is always an object; an empty PHP array would otherwise be encoded as a JSON array.
        $data = $mock === []
            ? new \stdClass()
            : \json_decode(
                \json_encode($mock, JSON_THROW_ON_ERROR),
                associative: false,
                flags: JSON_THROW_ON_ERROR,
            );

        $result = $this->validator->validate($data, $this->schema);
        if ($result->isValid()) {
            return;
        }

        $errors = new ErrorFormatter()->formatKeyed($result->error());
        throw new \InvalidArgumentException(
            'Mock does not match schema: ' . \json_encode($errors, JSON_THROW_ON_ERROR),
        );
    }
}
