<?php
namespace app\kouhongji\controller;

use app\kouhongji\controller\HomeBase;

class Game extends HomeBase
{
	public function game(){
		return  $this->fetch('game');
	}
}