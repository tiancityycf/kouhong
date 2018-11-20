<?php

namespace app\khj\controller\api\v1_0_1;

use app\khj\service\v1_0_1\CronTab as CronTabService;
use controller\BasicController;

/**
 * 脚本制器类
 */
class CronTab extends BasicController
{
    /**
     * 抓取商品信息脚本
     * @return [type] [description]
     */
    public function captureData()
    {

        $cron_tab = new CronTabService($this->configData);
        $result   = $cron_tab->captureData();

        return result(200, 'ok', $result);
    }

    /**
     * 测试把远程图片上传到另一个站点
     * @return [type] [description]
     */
    public function testUpload()
    {

        $cron_tab = new CronTabService($this->configData);
        $result   = $cron_tab->testUpload();

        return result(200, 'ok', $result);
    }
}
