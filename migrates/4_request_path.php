<?php declare(strict_types=1);

return static function (array $data): array {

    if (isset($data['request']['url'])) {
        $data['request']['path'] = $data['request']['url'];
        unset($data['request']['url']);
    }

    return $data;
};
