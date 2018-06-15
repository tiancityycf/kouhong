<?php 


namespace app\common\command;

use zhise\HttpClient;
use app\api\service\v1_0_2\Notify as NotifyService;
use app\admin\model\WithdrawLog as WithdrawLogModel;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;


/**
 * 
 */
class Test  extends Command
{

	protected function generateSign($data)
	{
		unset($data['sign']);
		ksort($data);

		$primary = '';
		foreach ($data as $key => $value) {
			$primary .= $key . '=' . $value . '&';
		}

		return strtoupper(md5($primary . 'key=T1qgjvr2Fm'));
	}


	protected function configure()
    {
        $this->setName('generate');
    }

    protected function execute(Input $input, Output $output)
    {
    	$allData = WithdrawLogModel::where('status', 0)->select();

		$i = 0;
		$j = 0;
		foreach ($allData as $key => $model) {
			if (strlen($model->trade_no) != 22) {
				$params = [
	                'appid' => 'wxce252f115355600f',
	                'user_id' => $model->user_id,
	                'open_id' => '',
	                'amount' => $model->amount,
	            ];

	            $params['sign'] = $this->generateSign($params);


				$url = 'https://tixian.zziyi.xyz/api/v1/withdraw_record/create_order';

				$result = HttpClient::post($url, $params);


				if ($result['status'] === 200 && $result['data']['data']['trade_no']) {
					$model->trade_no = $result['data']['data']['trade_no'];
					if ($model->save()) {
						$i++;
					} else {
						$j++;
					}
				} else {
					$j++;
				}



			}

			
		}

		echo "成功修改".$i."条数据，失败".$j."条数据\n";
    }
}