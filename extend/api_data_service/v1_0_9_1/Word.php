<?php

namespace api_data_service\v1_0_9_1;

use think\facade\Cache;
use app\admin\model\Word as WordModel;
use api_data_service\Config as ConfigService;

use model\UserRecord as UserRecordModel;
use model\UserLevel as UserLevelModel;
use model\UserLevelWord as UserLevelWordModel;

/**
 * 词语服务类
 */
class Word
{
    /**
     * 随机取题(重构)
     * @param  $userId 用户id
     * @return array
     */
    public function getWords($userId)
    {
        $userRecord = UserRecordModel::where('user_id', $userId)->find();

        $info = UserLevelWordModel::where('user_level_id', $userRecord->user_level)->select();
        $word_total = UserLevelWordModel::where('user_level_id', $userRecord->user_level)->sum('word_num');
        $arr = $this->getArr();
        $chai_arr = $this->chaiArr($arr, $word_total);

        $cacheKey = config('word_key');
        $cache = Cache::init();
        $handler = $cache->handler();
        $wordIds = [];
        $i = 0;
        $word_data = [];
        foreach ($info as $key => $value) {
            if ($value->word_num > 0) {
                if (!Cache::has($cacheKey . $value->word_level)) {
                    $orderedWordIds = WordModel::getAllIdsByLevel($value->word_level);
                    // phpredis低版本不支持sAddArray
                    // $handler->sAddArray($cacheKey . $wordLevel, $orderedWordIds);
                    if (!empty($orderedWordIds)) {
                        call_user_func_array([$handler, "sadd"], array_merge([$cacheKey . $value->word_level], $orderedWordIds));
                    }
                }

                $randWordIds = $handler->sRandMember($cacheKey . $value->word_level, $value->word_num);

                //$wordIds = array_merge($wordIds, $randWordIds);
                $randWordIds = implode(',', $randWordIds);
                $all = WordModel::where('id', 'in', $randWordIds)->orderRaw('field (id, ' . $randWordIds . ')')->select();

                foreach ($all as $k => $word) {

                    preg_match_all('/./u', $word->word, $hanziArr);
                    $trueHanzi = $hanziArr[0][$word->mix_num - 1];
                    $hanziArr[0][$word->mix_num - 1] = '__';
                    $word_data[$i]['word'] = implode('', $hanziArr[0]);
                    $word_data[$i]['primary'] = str_encode($word->word);
                    $word_data[$i]['pinyin'] = str_encode($word->pinyin);
                    $word_data[$i]['intro'] = str_encode($word->intro);
                    $word_data[$i]['caution'] = str_encode($word->caution);
                    $word_data[$i]['valid'] = hanzi_encode($trueHanzi);
                    $mixArr = [$trueHanzi, $word->mix_char];
                    shuffle($mixArr);
                    $word_data[$i]['option'] = $mixArr;
                    $word_data[$i]['time_limit'] = $value->word_time;

                    $word_data[$i]['wrong'] = $chai_arr[$i + 1];
                    $arr = $this->unsetArr($arr, $chai_arr[$i + 1]);
                    $word_data[$i]['right'] = array_keys($arr);

                    $i++;
                }
            }
        }

        return $word_data;
    }


    public function getArr()
    {
        $arr = [];
        for ($i=2; $i <= 30; $i++) { 
            $arr[$i] = $i;
        }

        return $arr;
    }

    public function chaiArr($arr, $word_total)
    {
        $count = count($arr);

        $num = ceil($count/$word_total);

        $data = [];
        for ($i=1; $i <= $word_total ; $i++) {
            if ($i == $word_total) {
                $key_arr = array_keys($arr);
            } else {
                $key_arr = array_rand($arr, $num);
            }
            
            $data[$i] = $key_arr;
            $arr = $this->unsetArr($arr, $key_arr);
            
            $count -= $num;
            
        }

        return $data;

    }

    public function unsetArr($arr, $chai_arr)
    {
        foreach ($chai_arr as $v) {
            unset($arr[$v]);
        }

        return $arr;
    }

}
