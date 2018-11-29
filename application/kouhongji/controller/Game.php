<?php
namespace app\kouhongji\controller;

use app\kouhongji\controller\HomeBase;

class Game extends HomeBase
{
	public function index(){
		return  $this->fetch('index');
	}
}