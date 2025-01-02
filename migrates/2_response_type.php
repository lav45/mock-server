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
            $result = ['type' => $type] + $data['response'][$type];
            if (isset($data['response']['delay'])) {
                $result['delay'] = $data['response']['delay'];
            }
            $data['response'] = $result;
            return $data;
        }
    }
    return $data;
};
