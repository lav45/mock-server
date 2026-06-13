<?php declare(strict_types=1);

final readonly class v2_response_body extends Migration
{
    protected function execute(array $data): array
    {
        if (isset($data['response']['text'])) {
            $data['response']['body'] = $data['response']['text'];
            unset($data['response']['text']);
        }
        return $data;
    }
}
