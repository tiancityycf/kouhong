<?php

namespace app\zstzw\service\v1_0_4;

use think\facade\Cache;
use app\admin\model\Word as WordModel;
use app\zstzw\service\Config as ConfigService;

use model\UserRecord as UserRecordModel;
use model\UserLevel as UserLevelModel;
use model\UserLevelWord as UserLevelWordModel;

/**
 * 词语服务类
 */
class Word
{
    /**
     * 随机取题
     * @param  $userId 用户id
     * @return array
     */
    public function getRandWords($userId)
    {
        $wordLevelArr = ConfigService::get('redpacket_word_level');
        $wordLevelCountArr = ConfigService::get('redpacket_word_level_count');
        $wordLevelTimeLimitArr = ConfigService::get('redpacket_word_level_time_limit');

        $cacheKey = config('word_key');
        $cache = Cache::init();
        $handler = $cache->handler();
        $wordIds = [];

        foreach ($wordLevelArr as $index => $wordLevel) {
            if (!Cache::has($cacheKey . $wordLevel)) {
                $orderedWordIds = WordModel::getAllIdsByLevel($wordLevel);
                // phpredis低版本不支持sAddArray
                // $handler->sAddArray($cacheKey . $wordLevel, $orderedWordIds);
                if (!empty($orderedWordIds)) {
                    call_user_func_array([$handler, "sadd"], array_merge([$cacheKey . $wordLevel], $orderedWordIds));
                }
            }
            $randWordIds = $handler->sRandMember($cacheKey . $wordLevel, $wordLevelCountArr[$index]);
            $wordIds = array_merge($wordIds, $randWordIds);
        }

        // if (count($wordIds) < array_sum($wordLevelCountArr)) {
        //     throw new \think\exception\HttpException();         
        // }

        $wordIdsStr = implode(',', $wordIds);
        $all = WordModel::where('id', 'in', $wordIds)->orderRaw('field (id, ' . $wordIdsStr . ')')->select();

        $words = [];
        $loop = 0;
        $skip = 0;
        $loopLimit = array_shift($wordLevelCountArr);
        foreach ($all as $key => $word) {
            $loop++;
            preg_match_all('/./u', $word['word'], $hanziArr);
            $trueHanzi = $hanziArr[0][$word['mix_num'] - 1];
            $hanziArr[0][$word['mix_num'] - 1] = '__';
            $words[$key]['word'] = implode('', $hanziArr[0]);
            $words[$key]['valid'] = hanzi_encode($trueHanzi);
            $mixArr = [$trueHanzi, $word['mix_char']];
            shuffle($mixArr);
            $words[$key]['option'] = $mixArr;
            $words[$key]['time_limit'] = $wordLevelTimeLimitArr[$skip]; // 单位秒
            if ($loop == $loopLimit) {
                $loopLimit = array_shift($wordLevelCountArr);
                $skip++;
                $loop = 0;
            }
            $words[$key]['primary'] = str_encode($word['word']);
            $words[$key]['pinyin'] = str_encode($word['pinyin']);
            $words[$key]['intro'] = str_encode($word['intro']);
            $words[$key]['caution'] = str_encode($word['caution']);
        }
        
        return $words;
    }

    /**
     * 随机取题(重构)
     * @param  $userId 用户id
     * @return array
     */
    public function getWords($userId)
    {
        $userRecord = UserRecordModel::where('user_id', $userId)->find();

        $info = UserLevelWordModel::where('user_level_id', $userRecord->user_level)->select();

        //echo "<pre>"; print_r($info);exit();

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

                    $i++;
                }
            }
        }

        return $word_data;
    }
}
