<?php declare(strict_types=1);

require_once __DIR__ . '/../../../migrates/Migration.php';

final readonly class v1_request_path extends Migration
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
