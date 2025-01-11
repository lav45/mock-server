<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Parser;

final readonly class GroupParser implements Parser
{
    public function __construct(
        private EnvParser   $envParser,
        private FakerParser $fakerParser,
        private ParamParser $paramParser,
    ) {}

    public function replace(mixed $data): mixed
    {
        $data = $this->envParser->replace($data);
        $data = $this->fakerParser->replace($data);
        return $this->paramParser->replace($data);
    }

    public function withData(array $data): self
    {
        return new self(
            $this->envParser,
            $this->fakerParser,
            $this->paramParser->withData($data),
        );
    }
}
