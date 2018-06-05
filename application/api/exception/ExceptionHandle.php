<?php

namespace app\api\exception;

use Exception;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\ValidateException;
/**
 * 自定义异常处理类
 */
class ExceptionHandle extends Handle
{
	/**
	 * @param  \Exception $e
	 * @return json
	 */
	public function render(Exception $e)
    {
        if ($e instanceof ValidateException) {
            return result(400, $e->getMessage());
        } elseif ($e instanceof HttpException) {
            return result($e->getStatusCode(), $e->getMessage());
        } else {
            //return result(500, '系统繁忙');
            return result(500, $e->getMessage());
        }

    }
}