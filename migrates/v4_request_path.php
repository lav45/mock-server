<?php declare(strict_types=1);

final readonly class v4_request_path extends Migration
{
    protected function execute(array $data): array
    {
        if (isset($data['request']['url'])) {
            $data['request']['path'] = $data['request']['url'];
            unset($data['request']['url']);
        }
        return $data;
    }
}
