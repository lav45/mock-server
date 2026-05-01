<?php declare(strict_types=1);

return static function (array $data): array {

    if (isset($data['response'])) {
        $data['response'] = replaceData($data['response']);
    }
    if (isset($data['webhooks'])) {
        $data['webhooks'] = replaceData($data['webhooks']);
    }
    return $data;
};

function replaceData(array $data): array
{
    $result = [];
    foreach ($data as $key => $value) {
        if (is_string($value) && str_contains($value, '{request.urlParams')) {
            $value = str_replace('{request.urlParams', '{request.params', $value);
        } elseif (is_array($value)) {
            $value = replaceData($value);
        }
        $result[$key] = $value;
    }
    return $result;
}