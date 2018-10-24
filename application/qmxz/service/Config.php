<?php

namespace app\qmxz\service;

use app\qmxz\model\Config as ConfigModel;
use think\facade\Cache;
use think\facade\Config as ThinkConfig;

class Config
{
    /**
     * 获取系统配置
     * @return mixed
     */
    public static function get($key = '')
    {
        $cache    = Cache::init();
        $cacheKey = config('config_key');

        if (!$cache->has($cacheKey)) {
            $value  = ConfigModel::getAll();
            $expire = ThinkConfig::get('cache_conf_time');
            Cache::set($cacheKey, $value, $expire);
        } else {
            $value = $cache->get($cacheKey);
        }

        if ($key === '') {
            return $value;
        } elseif (array_key_exists($key, $value)) {
            if ($value[$key]['type'] == 1) {
                return $value[$key]['value'];
            } elseif ($value[$key]['type'] == 2) {
                return json_decode($value[$key]['value']);
            }
        } else {
            return '';
        }
    }

    public function getAll()
    {
        $cache    = Cache::init();
        $cacheKey = config('config_key');

        if (!$cache->has($cacheKey)) {
            $value  = ConfigModel::getAll();
            $expire = ThinkConfig::get('cache_conf_time');
            Cache::set($cacheKey, $value, $expire);
        } else {
            $value = $cache->get($cacheKey);
        }

        $data = [];
        if ($value) {
            foreach ($value as $key => $v) {
                if ($v['type'] == 1) {
                    $data[$key] = $v['value'];
                } else {
                    $data[$key] = json_decode($v['value']);
                }
            }
        }

        return $data;
    }
}
