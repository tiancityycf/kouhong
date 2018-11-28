<?php
namespace app\kouhongji\controller;

use app\kouhongji\controller\HomeBase;

class User extends HomeBase
{
	public function user(){
		return  $this->fetch('user');
	}
}