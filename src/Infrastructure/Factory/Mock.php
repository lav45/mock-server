<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Factory;

use Lav45\MockServer\Application\Data\Mock\v1\Mock as MockData;
use Sunrise\Hydrator\Hydrator;

final readonly class Mock
{
    public static function create(array $data): MockData
    {
        $data = self::prepareData($data);
        return self::hydrate($data);
    }

    private static function hydrate(array $data): MockData
    {
        return (new Hydrator())->hydrate(MockData::class, $data);
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
