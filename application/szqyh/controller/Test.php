<?php
namespace app\szqyh\controller;

use controller\BasicAdmin;
use api_data_service\v1_0_9_1\Word as WordService;

class Test extends BasicAdmin
{
	public function test()
	{
		$word = new WordService();

		$arr = $word->getArr();

		$new_arr = $word->chaiArr($arr, 8);

		echo "<pre>";
		print_r($arr);
		print_r($new_arr);
	}
}