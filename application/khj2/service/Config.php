<?php

namespace app\khj2\service;

use app\khj2\model\Config as ConfigModel;
use think\facade\Config as ThinkConfig;

class Config
{
    /**
     * 获取系统配置
     * @return mixed
     */
    public static function get($key = '')
    {
		return ConfigModel::getAll();
    }

    public function getAll()
    {
            $value  = ConfigModel::getAll();

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
