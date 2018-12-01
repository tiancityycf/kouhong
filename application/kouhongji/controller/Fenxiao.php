<?php
namespace app\kouhongji\controller;

use app\kouhongji\controller\HomeBase;

class Fenxiao extends HomeBase
{
	public function fenxiao(){
		return  $this->fetch('fenxiao');
	}
}