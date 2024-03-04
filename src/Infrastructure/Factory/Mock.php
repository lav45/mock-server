<?php declare(strict_types=1);

namespace lav45\MockServer\Infrastructure\Factory;

use lav45\MockServer\Application\DTO\Mock\v1\Mock as MockDTO;
use Sunrise\Hydrator\Hydrator;

final readonly class Mock
{
    public static function create(array $data): MockDTO
    {
        $data = self::prepareData($data);
        return self::hydrate($data);
    }

    private static function hydrate(array $data): MockDTO
    {
        return (new Hydrator())->hydrate(MockDTO::class, $data);
    }

    private static function prepareData(array $data): array
    {
        $data += [
            'request' => [],
            'response' => [],
            'webhooks' => [],
            'env' => [],
        ];

        $data['response'] += ['content' => []];

        if (isset($data['response']['data'])) {
            $data['response']['data'] += ['pagination' => []];
        }
        return $data;
    }
}