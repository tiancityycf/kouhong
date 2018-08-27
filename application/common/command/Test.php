<?php 


namespace app\common\command;

use zhise\HttpClient;
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

		return strtoupper(md5($primary . 'key=HYmi2GO0OX'));
	}


	protected function configure()
    {
        $this->setName('generate');
    }

    protected function execute(Input $input, Output $output)
    {
    	WithdrawLogModel::where('status', 0)->chunk(2000, function($models){
    		foreach ($models as $model) {
    			$params = [
	                'appid' => 'wx26dff9d76708c142',
	                'user_id' => $model->user_id,
	                'open_id' => '',
	                'amount' => $model->amount,
	            ];

	            $params['sign'] = $this->generateSign($params);


				$url = 'http://wxpay.wudee.cc/api/v1/withdraw_record/create_order';

				$result = HttpClient::post($url, $params);

				if ($result['status'] === 200 && $result['data']['data']['trade_no']) {
					$model->trade_no = $result['data']['data']['trade_no'];
					$model->save();
				}
    		}
    	});
    }
}