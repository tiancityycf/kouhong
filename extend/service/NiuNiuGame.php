<?php

namespace service;



class NiuNiuGame
{

    /**
     * @param $card
     * @return int 结果 -1 每牛；  0  牛牛；   1 - 9 牛一 到 牛九
     */
    public static function  JudgeCowCow($card)
    {
        if(!is_array($card) || count($card) !== 5)
        {
            return -1;
        }
        $cow = -1;
        //计算5张牌总值，cow计算牛几。
        $cardall = 0;
        $n=0;//存储10、J、Q、K张数。

        for($i=0;$i<4;$i++)//对5张牌从大到小排序。
        {
            for($j=$i+1;$j<5;$j++)
                    if($card[$i] < $card[$j])
                    {
                        $a = $card[$i];
                        $card[$i] = $card[$j];
                        $card[$j]=$a;
                    }
        }
        for($i=0;$i<5;$i++)
        {
            if($card[$i] >= 10)
            {
                $n++;
                $card[$i] = 10;
            }
            $cardall += $card[$i];
        }
        //10 10 6 5 3 牛牛时5张牌总和肯定是10的倍数，五张牌总和是10的倍数未必是牛牛，下面代码不成立
        //if(cardall %10 == 0)
        //{
        //    print(0);//牛牛
        //    exit(0);
        //}
        switch ($n)
        {
            case 0:  //5张牌中没有一张10、J、Q、K。
            {
                for($i=0;$i<4;$i++)
                {
                    for($j=$i + 1;$j<5;$j++)
                        if(($cardall - $card[$i]- $card[$j])%10==0)
                        {
                            $cow=($card[$i] + $card[$j])%10;
                            return $cow;
                        }
                }
                break;
            }
            case 1:  //5张牌中有一张10、J、Q、K。
            {
                //先判断是否有牛牛，不能判断剩余四张相加为10倍数为牛牛，如 Q 8 5 4 3
                //只能先判断两张是否是10的倍数，如果是再判断剩余是否是10的倍数；有限判断出牛牛；再来判断三张是否有10的倍数，有的话有牛，否则无牛
                for($i =1; $i < 4; $i ++)
                {
                    for($j = $i +1; $j < 5; $j++)
                    {
                        if(($card[$i] + $card[$j]) % 10 == 0)
                        {
                            $cow=($cardall - $card[0])%10;
                            return $cow;
                        }
                    }
                }
                //判断是否有牛
                for($i=1; $i<5; $i++)  //剩下四张牌有三张加起来等于10
                {
                    if(($cardall - $card[0] - $card[$i])%10==0)
                    {
                        $cow=($cardall-$card[0])%10;
                        break;
                    }
                }
                break;
            }
            case 2:  //5张牌中有两张10、J、Q、K。  三张是个牛就有问题，应该优先输出
            {

                if(($cardall - $card[0] - $card[1])%10 == 0)//优先牛牛输出 如 J Q 2 3 5；这里先检查剩余是否为牛牛，否则算法有漏洞
                {
                    $cow = 0;
                }
                else
                {
                    //10 10 6 5 3     n=2  i=3  j=4   cardall = 34
                    for($i=$n;$i<4;$i++)//剩下三（四）张牌有两张加起来等于10。
                    {
                        for($j=$i+1;$j<5;$j++)
                        {
                            if(($card[$i]+$card[$j])==10)
                            {
                                $temp = $cardall;
                                for($k=0;$k<$n;$k++)//总值减去10、J、Q、K的牌。
                                    $temp -= $card[$k]; // 18
                                $cow = $temp%10;  //8
                            }
                        }
                        /*print_r('cardall:'.$cardall);
                        print_r("<br />");
                        print_r('cow'.$cow);
                        print_r("<br />");*/
                    }

                }
                break;
            }

            case 3:  //5张牌中有三张10、J、Q、K。
            case 4:  //5张牌中有四张10、J、Q、K。
            case 5:  //5张牌中五张都是10、J、Q、K。
            {
                for($i=0;$i<$n;$i++)//总值减去10、J、Q、K的牌。
                {
                    $cardall -= $card[$i];
                }
                $cow = $cardall%10;
                break;
            }

        }

        return $cow;
    }

    //生成一副扑克牌数组， key 1到54， value 1到13
    public static function pukepai()
    {
        $arr = [];
        for ($i=1; $i <= 54; $i++) { 
            $arr[$i] = ceil($i/4);
        }

        return $arr;
    }

    //随机取5张牌
    public function qupai()
    {
        $arr = self::pukepai();

        $key_arr = array_rand($arr, 5);

        $data = [];
        foreach ($key_arr as $v) {
            $data[$v] = $arr[$v];
        }

        $value_arr = array_values($data);

        return ['data' => $data, 'key_arr' => $key_arr, 'value_arr' => $value_arr];
    }
}