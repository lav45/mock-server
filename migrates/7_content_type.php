<?php declare(strict_types=1);

return static function (array $data): array {

    if (isset($data['response'])) {
        $withJson = false;
        $type = $data['response']['type'] ?? 'content';

        if ($type === 'content') {
            $withJson = isset($data['response']['body']) && is_array($data['response']['body']);
        }
        if ($type === 'proxy') {
            $withJson = isset($data['response']['content']) && is_array($data['response']['content']);
        }

        if ($withJson && isset($data['response']['headers']['content-type']) === false) {
            $data['response']['headers'] ??= [];
            $data['response']['headers']['content-type'] = 'application/json';
        }
    }

    if (isset($data['webhooks'])) {
        $webhooks = [];
        foreach ($data['webhooks'] as $webhook) {
            $withJson = isset($webhook['body']) && is_array($webhook['body']);
            if ($withJson && isset($webhook['headers']['content-type']) === false) {
                $webhook['headers'] ??= [];
                $webhook['headers']['content-type'] = 'application/json';
            }
            $webhooks[] = $webhook;
        }
        $data['webhooks'] = $webhooks;
    }

    return $data;
};
