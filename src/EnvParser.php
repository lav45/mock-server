<?php

namespace lav45\MockServer;

use lav45\MockServer\components\RequestHelper;

/**
 * Class ParamParser
 * @package lav45\MockServer
 */
class EnvParser
{
    /** @var array */
    private array $env;

    /**
     * @param array $env
     * @param FakerParser $faker
     * @throws InvalidConfigException
     */
    public function __construct(
        array                        $env,
        private readonly FakerParser $faker
    )
    {
        $this->env = $this->replaceFaker($env);
    }

    /**
     * @param array $data
     * @return array
     * @throws InvalidConfigException
     */
    public function replace(array $data)
    {
        $data = $this->replaceFaker($data);
        return $this->replaceEnv($data);
    }

    /**
     * @param array $data
     * @return array
     */
    private function replaceEnv(array $data)
    {
        $result = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $this->replaceEnv($value);
            } elseif (is_string($value)) {
                if (RequestHelper::parceAttribute($this->env, $value, 'env') === false) {
                    $value = RequestHelper::replaceAttributes($this->env, $value, 'env');
                }
                $result[$key] = $value;
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * @param array $data
     * @return array
     * @throws InvalidConfigException
     */
    private function replaceFaker(array $data)
    {
        $result = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $this->replaceFaker($value);
            } elseif (is_string($value)) {
                $result[$key] = $this->faker->parse($value);
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }
}