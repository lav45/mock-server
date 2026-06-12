<?php declare(strict_types=1);

final readonly class v6_response_body extends Migration
{
    protected function execute(array $data): array
    {
        if (isset($data['response']) && is_array($data['response'])) {
            $type = $data['response']['type'] ?? 'content';

            $response = [];
            foreach ($data['response'] as $key => $value) {
                if ($key === 'json') {
                    if ($type === 'content') {
                        $key = 'body';
                    } elseif ($type === 'data') {
                        $key = 'items';
                    }
                }
                if ($key === 'text') {
                    $key = 'body';
                }
                $response[$key] = $value;
            }
            $data['response'] = $response;
        }

        if (isset($data['webhooks']) && is_array($data['webhooks'])) {
            $webhooks = [];
            foreach ($data['webhooks'] as $webhook) {
                $webhookItem = [];
                foreach ($webhook as $key => $value) {
                    if ($key === 'json' || $key === 'text') {
                        $key = 'body';
                    }
                    $webhookItem[$key] = $value;
                }
                $webhooks[] = $webhookItem;
            }
            $data['webhooks'] = $webhooks;
        }

        return $data;
    }
}
