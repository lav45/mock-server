<?php declare(strict_types=1);

final readonly class v8_request_query extends Migration
{
    protected function execute(array $data): array
    {
        if (isset($data['response']) && is_array($data['response'])) {
            $data['response'] = $this->replaceRequestData($data['response']);
        }
        if (isset($data['webhooks']) && is_array($data['webhooks'])) {
            $data['webhooks'] = $this->replaceRequestData($data['webhooks']);
        }
        return $data;
    }

    private function replaceRequestData(array $data): array
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
                if (str_contains($value, '{request.post')) {
                    $value = str_replace('{request.post', '{request.body', $value);
                }
            } elseif (is_array($value)) {
                $value = $this->replaceRequestData($value);
            }
            $result[$key] = $value;
        }
        return $result;
    }
}
