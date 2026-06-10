<?php declare(strict_types=1);

return static function (array $data): array {

    if (isset($data['response'])) {
        $data['response'] = replaceRequestData($data['response']);
    }
    if (isset($data['webhooks'])) {
        $data['webhooks'] = replaceRequestData($data['webhooks']);
    }
    return $data;
};

function replaceRequestData(array $data): array
{
    $result = [];
    foreach ($data as $key => $value) {
        if (is_string($value)) {
            if (str_contains($value, '{request.get')) {
                $value = str_replace('{request.get', '{request.query', $value);
            }
            if (str_contains($value, '{request.body')) {
                $value = str_replace('{request.body', '{request.rawBody', $value);
            }
        } elseif (is_array($value)) {
            $value = replaceRequestData($value);
        }
        $result[$key] = $value;
    }
    return $result;
}
