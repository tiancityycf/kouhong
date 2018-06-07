<?php

namespace app\api\service\v1_0_1;

use think\facade\Cache;
use app\api\model\Word as WordModel;
use app\api\service\Config as ConfigService;

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

        $cacheKey = CACHE_APP_NAME . ':' . CACHE_APP_UNIQ . ':word:level:';
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
}
