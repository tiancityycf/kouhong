<?php
namespace app\kouhongji\controller;

use app\kouhongji\controller\HomeBase;

class Tixianrecord extends HomeBase
{
	public function tixianrecord(){
		return  $this->fetch('tixianrecord');
	}
}