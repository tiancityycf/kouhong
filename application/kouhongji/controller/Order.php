<?php
namespace app\kouhongji\controller;

use app\kouhongji\controller\HomeBase;

class Order extends HomeBase
{
	public function order(){
		return  $this->fetch('order');
	}
}