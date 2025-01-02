<?php declare(strict_types=1);

return static function (array $data): array {

    if (isset($data['webhooks'])) {
        $result = [];
        foreach ($data['webhooks'] as $webhook) {
            if (isset($webhook['options'])) {
                if (isset($webhook['options']['json'])) {
                    $json = $webhook['options']['json'];
                    if (array_key_exists('json', $webhook) === false) {
                        $webhook['json'] = $json;
                    }
                }
                if (isset($webhook['options']['text'])) {
                    $text = $webhook['options']['text'];
                    if (array_key_exists('text', $webhook) === false) {
                        $webhook['text'] = $text;
                    }
                }
                if (isset($webhook['options']['headers'])) {
                    $headers = $webhook['options']['headers'];
                    if (array_key_exists('headers', $webhook) === false) {
                        $webhook['headers'] = $headers;
                    }
                }
                unset($webhook['options']);
            }
            $result[] = $webhook;
        }
        $data['webhooks'] = $result;
    }

    if (isset($data['response']['proxy']['options']['headers'])) {
        $data['response']['proxy']['headers'] = $data['response']['proxy']['options']['headers'];
        unset($data['response']['proxy']['options']);
    }

    return $data;
};
