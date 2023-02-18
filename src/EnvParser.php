<?php

namespace lav45\MockServer;

use Closure;
use Yiisoft\Arrays\ArrayHelper;

/**
 * Class EnvParser
 * @package lav45\MockServer
 */
class EnvParser
{
    /** @var array */
    private array $data = [];

    /**
     * @param FakerParser $faker
     * @throws InvalidConfigException
     */
    public function __construct(private readonly FakerParser $faker)
    {
    }

    /**
     * @param array $data
     */
    public function addData(array $data)
    {
        $this->data = array_merge_recursive($this->data, $data);
    }

    /**
     * @param array|\Generator $data
     * @return array
     * @throws InvalidConfigException
     */
    public function replace($data)
    {
        $data = $this->replaceFaker($data);
        return $this->replaceKey($data);
    }

    /**
     * @param array|\Generator $data
     * @return array
     */
    public function replaceKey($data)
    {
        return $this->recursiveMap($data, function ($value) {
            return $this->replaceAttribute($value);
        });
    }

    /**
     * @param array|\Generator $data
     * @return array
     * @throws InvalidConfigException
     */
    public function replaceFaker($data)
    {
        return $this->recursiveMap($data, function ($value) {
            return $this->faker->parse($value);
        });
    }

    /**
     * @param array|\Generator $data
     * @param Closure $func
     * @return array
     */
    protected function recursiveMap($data, Closure $func)
    {
        $result = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $this->recursiveMap($value, $func);
            } elseif (is_string($value)) {
                $result[$key] = $func($value);
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * @param string $value
     * @return string|mixed
     */
    public function replaceAttribute($value)
    {
        $callback = function ($matches) {
            $key = trim($matches[1], '{} ');
            $key = explode('.', $key);
            return ArrayHelper::getValue($this->data, $key);
        };

        preg_match('/({{\s?[.\w]+\s?}})/u', $value, $matches);
        if ($matches) {
            return $callback($matches);
        }

        return preg_replace_callback('/({\s?[.\w]+\s?})/u', $callback, $value);
    }
}