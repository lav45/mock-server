<?php declare(strict_types=1);

namespace Lav45\MockServer\Bootstrap;

use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;

final class SchemaValidator
{
    private object|null $schema = null;

    public function __construct(
        private readonly Validator $validator = new Validator(),
    ) {}

    public function withSchema(string $schemaFile): self
    {
        if (\file_exists($schemaFile) === false || \is_readable($schemaFile) === false) {
            throw new \RuntimeException('Unable to read schema file: "' . $schemaFile . '"');
        }
        $content = \file_get_contents($schemaFile);
        $schema = \json_decode($content, associative: false, flags: JSON_THROW_ON_ERROR);

        return clone($this, [
            'schema' => $schema,
        ]);
    }

    public function validate(array $data): void
    {
        // A schema root is always an object; an empty PHP array would otherwise be encoded as a JSON array.
        $payload = $data === []
            ? new \stdClass()
            : \json_decode(
                \json_encode($data, JSON_THROW_ON_ERROR),
                associative: false,
                flags: JSON_THROW_ON_ERROR,
            );

        $result = $this->validator->validate($payload, $this->schema);
        if ($result->isValid()) {
            return;
        }

        $errors = new ErrorFormatter()->formatKeyed($result->error());
        throw new \InvalidArgumentException(
            "Data does not match schema:\n"
            . \json_encode($errors, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        );
    }
}
