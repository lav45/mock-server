<?php declare(strict_types=1);

return static function (array $data): array {

    if (isset($data['response'])) {
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

    if (isset($data['webhooks'])) {
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
};
