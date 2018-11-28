<?php
namespace app\kouhongji\controller;

use app\kouhongji\controller\HomeBase;

class Index extends HomeBase
{
	public function index(){
		return  $this->fetch('index');
	}
}