<?php declare(strict_types=1);

return static function (array $data): array {

    if (empty($data['response'])) {
        return $data;
    }
    if (isset($data['response']['type'])) {
        return $data;
    }

    $list = [
        'content',
        'proxy',
        'data',
    ];
    foreach ($list as $type) {
        if (isset($data['response'][$type])) {
            $data['response'] = ['type' => $type] + $data['response'][$type];
            return $data;
        }
    }

    $data['response'] = ['type' => 'content'] + $data['response'];

    return $data;
};
