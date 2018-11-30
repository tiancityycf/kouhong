<?php
namespace app\kouhongji\controller;

use app\kouhongji\controller\HomeBase;

class Tixian extends HomeBase
{
	public function tixian(){
		return  $this->fetch('tixian');
	}
}