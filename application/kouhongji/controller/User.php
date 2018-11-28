<?php
namespace app\kouhongji\controller;

use think\Controller;

class User extends Controller
{
	public function user(){
		return  $this->fetch('user');
	}
}