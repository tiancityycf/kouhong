
<?php
$game = $_GET['game'];
$orderId = $_GET['orderId'];
?>
<!DOCTYPE html>
<html lang="en" style="font-size: 79.5424px;"><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="edge">
    <meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <title></title>
    <link rel="stylesheet" href="./css/game.css">
    <script type="text/javascript" src="./js/bodymovin.js"></script>
    <script type="text/javascript" src="./js/jweixin-1.3.2.js"></script>
    <script typet="text/javascript" src="./js/jquery-3.3.1.min.js"></script>
    <script type="text/javascript" src="./js/jquery.cookie.js"></script>
    <script type="text/javascript" src="./js/JicemoonMobileTouch.js"></script>
    <?php if($game==1){?>
    <script type="text/javascript" src="./js/HardestGame1.js"></script>
    <script type="text/javascript" src="./js/index1.js"></script>
    <?php }elseif($game==2){ ?>
    <script type="text/javascript" src="./js/HardestGame.js"></script>
    <script type="text/javascript" src="./js/index.js"></script>
    <?php } ?>
</head>
<body>
    <audio id="back_music" preload="" src="https://txcdn.ylll111.xyz/khj/audio/bg_audio.mp3" loop="true"></audio>
    <audio id="split_audio" preload="" src="https://txcdn.ylll111.xyz/khj/audio/split_audio.mp3"></audio>
    <audio id="collision_audio" preload="" src="https://txcdn.ylll111.xyz/khj/audio/collision_audio.mp3"></audio>
    <audio id="Countdown_10s_audio" preload="" src="https://txcdn.ylll111.xyz/khj/audio/Countdown_10s_audio.mp3"></audio>
    <audio id="gameFail_audio" preload="" src="https://txcdn.ylll111.xyz/khj/audio/gameFail_audio.mp3"></audio>
    <audio id="gameSuccess_audio" preload="" src="https://txcdn.ylll111.xyz/khj/audio/gameSuccess_audio.mp3"></audio>
    <audio id="insert_audio" preload="" src="https://txcdn.ylll111.xyz/khj/audio/insert_audio.mp3"></audio>
    <audio id="success_audio" preload="" src="https://txcdn.ylll111.xyz/khj/audio/success_audio.mp3"></audio>
    <?php if($game==1){?>
    <div class="levelSwitchBox" id="levelSwitchBox" style="display: block;">
        <img id="levelSwitchBoxMain" class="levelSwitchBoxMain" src="https://txcdn.ylll111.xyz/khj/level_1_main.jpg">
    </div>
    <div class="PopupBox" id="gameOverBox" style="display: none;">
        <div id="gameOverBoxTitle">闯关失败</div>
        <div class="PopupBoxBtn" id="gameOverBoxBtn">重新闯关</div>
    </div>
    <div class="PopupBox" id="gameSuccessBox" style="display: none;">
        <div id="gameSuccessBoxText">体验结束</div>
        <div class="PopupBoxBtn" id="gameSuccessBoxBtn">马上赢口红</div>
    </div>
    <div class="layoutRoot" id="app">
        <div class="game" id="game" style="width: 596px; height: 938px;">
            <div class="account">
                <span>
                </span>
            </div>
            <div class="bulletsNumBox" style="display: none;">
                <img class="bulletsNum" id="bulletsNum1" src="https://txcdn.ylll111.xyz/khj/1.png">
            </div>
            <canvas style="position: relative;z-index: 3" id="gameStage" width="596" height="938"></canvas>
            <div id="bm" style="width: 100%; height: 100%;position: fixed;background-color: rgba(0,0,0,0);top: 5.3rem; transform: translate(-5%,-1%); z-index: 2">
                </div>
                <div class="tips">
                    <p id="currentLevel">当前关数: <span>2</span></p>
                    <p id="gameTip"></p>
                </div>

                <div class="levelbox" id="levelbox">
                    <div class="level"><img id="level_1" src="https://txcdn.ylll111.xyz/khj/level_icon_1_active.png"></div>
                    <div class="level"><img id="level_2" src="https://txcdn.ylll111.xyz/khj/level_icon_2_active.png"></div>
                    <div class="level"><img id="level_3" src="https://txcdn.ylll111.xyz/khj/level_3.png"></div>
                </div>
                <div id="timebox">15</div>
            </div>
        </div>
    </div>
    <?php }elseif($game==2){ ?>
    <div class="levelSwitchBox" id="levelSwitchBox" style="display: block;">
        <img id="levelSwitchBoxMain" class="levelSwitchBoxMain" src="https://txcdn.ylll111.xyz/khj/level_1_main.jpg">
    </div>
    <div class="PopupBox" id="gameOverBox" style="display: none;">
        <div id="gameOverBoxTitle">闯关失败</div>
        <div class="PopupBoxBtn" id="gameOverBoxBtn">重新闯关</div>
    </div>
    <div class="PopupBox" id="gameSuccessBox" style="display: none;">
        <input type="hidden" id="orderId" value="<?php echo $orderId;?>" />
        <div id="gameSuccessBoxText">恭喜您，闯关成功</div>
        <div class="PopupBoxBtn" id="gameSuccessBoxBtn">点击我的口红领取</div>
    </div>
    <div class="layoutRoot " id="app" data-game_id="" data-openid="">
        <div class="game" id="game" style="width: 596px; height: 938px;">
            <div class="account">
                <span></span>
            </div>
            <div class="bulletsNumBox">
                <img class="bulletsNum" id="bulletsNum1" src="https://txcdn.ylll111.xyz/khj/6.png">
            </div>
            <canvas style="position: relative;z-index: 3" id="gameStage" width="596" height="938"></canvas>
            <div id="bm" style="width: 100%; height: 100%;position: fixed;background-color: rgba(0,0,0,0);top: 5.3rem; transform: translate(-5%,-1%); z-index: 2">
            </div>
            <div class="tips">
                <p id="currentLevel">当前关数: <span>1</span></p>
                <p id="gameTip"></p>
            </div>

            <div class="levelbox" id="levelbox">
                <div class="level"><img id="level_1" src="https://txcdn.ylll111.xyz/khj/level_icon_1_active.png"></div>
                <div class="level"><img id="level_2" src="https://txcdn.ylll111.xyz/khj/level_icon_2.png"></div>
                <div class="level"><img id="level_3" src="https://txcdn.ylll111.xyz/khj/level_icon_3.png"></div>
            </div>
            <div id="timebox">0</div>
        </div>
    </div>
    <?php } ?>
    <script type="text/javascript">
        var loadedMusic = false;
        document.body.addEventListener('touchmove', function (e) {
            e.preventDefault(); //阻止默认的处理方式(阻止下拉滑动的效果)
        }, {passive: false});
        var baseUrl = function GetRequest() {
            var url = location.search;  //获取url中"?"符后的字符串
            var theRequest = new Object();
            if (url.indexOf("?") != -1) {
                url = url.split("?")[1];
                strs = url.split("&");
                for (var i = 0; i < strs.length; i++) {
                    theRequest[strs[i].split("=")[0]] = (strs[i].split("=")[1]);
                }
            }
            console.log(theRequest)
            return theRequest;

        }
        var jsonParamsAlias = baseUrl();
        // var jsonParams = {
        //     "game_id" : jsonParamsAlias.gid,
        //     "game_pay" : jsonParamsAlias.pay,
        //     "product_id" : jsonParamsAlias.pid,
        //     "randomNum" : jsonParamsAlias.rand,
        //     "forecast_result": jsonParamsAlias.res,
        //     "user_id" : jsonParamsAlias.uid
        // }
        var jsonParams = {
            "game_id" : "",
            "game_pay" : "",
            "product_id" : "", //商品id
            "randomNum" : "",
            "forecast_result": "", //預測結果
            "user_id" : "" //用戶id
        }
        if (jsonParamsAlias.slient) {
            $('audio').prop('muted', true);
        }
        if (jsonParamsAlias.h5 && jsonParamsAlias.h5 == "1") {
            window.isH5 = true;
        }
        var cookieDelTime = new Date(Math.floor(new Date(new Date().getTime()+150000)));
        $.cookie('game_cookie', null);
        $.cookie('game_cookie', JSON.stringify(jsonParams), { expires: cookieDelTime });
        console.log($.cookie('game_cookie'));
        var anim = bodymovin.loadAnimation({
            wrapper: document.querySelector('#bm'),
            animType: 'svg',
            loop: false,
            autoplay: false,
            prerender: true,
            path: './js/data.json'
        });
        function play(){
            anim.goToAndStop(0, true)
            anim.play()
        }
        document.addEventListener('DOMContentLoaded', function () {
            function audioAutoPlay() {
                var audio = document.getElementById('back_music');
                audio.play();
                document.addEventListener("WeixinJSBridgeReady", function () {
                    audio.play();
                }, false);
            }
            audioAutoPlay();
        });
        document.addEventListener('visibilitychange', function(e) {
            function audioStop() {
                var audio = document.getElementById('back_music');
                document.hidden ? audio.pause() : audio.play();
                document.addEventListener("WeixinJSBridgeReady", function () {
                    document.hidden ? audio.pause() : audio.play();
                }, false);
            }
            audioStop();
        });
    </script>
</body>
</html>