<?php declare(strict_types=1);

final readonly class v5_url_params extends Migration
{
    protected function execute(array $data): array
    {
        if (isset($data['response']) && is_array($data['response'])) {
            $data['response'] = $this->replaceData($data['response']);
        }
        if (isset($data['webhooks']) && is_array($data['webhooks'])) {
            $data['webhooks'] = $this->replaceData($data['webhooks']);
        }
        return $data;
    }

    private function replaceData(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            if (is_string($value) && str_contains($value, '{request.urlParams')) {
                $value = str_replace('{request.urlParams', '{request.params', $value);
            } elseif (is_array($value)) {
                $value = $this->replaceData($value);
            }
            $result[$key] = $value;
        }
        return $result;
    }
}
