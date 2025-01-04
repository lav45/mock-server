<?php declare(strict_types=1);

return static function (array $data): array {
    if (empty($data['response'])) {
        return $data;
    }
    $data['response'] = replaceResponseData($data['response']);
    return $data;
};

function replaceResponseData(array $data): array
{
    foreach ($data as $key => $value) {
        if (is_string($value) && str_contains($value, '{response.data.')) {
            $data[$key] = str_replace('{response.data.', '{response.', $value);
        } elseif (is_array($value)) {
            $data[$key] = replaceResponseData($value);
        }
    }
    return $data;
}
