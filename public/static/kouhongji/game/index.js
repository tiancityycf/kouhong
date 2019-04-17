var hg = null;
var user_id =localStorage.getItem("user_id");
function addClass(element, new_name) {
  if (!element || !new_name) return false;
  if (element.className) {
    var old_class_name = element.className;
    element.className = old_class_name + " " + new_name;
  } else {
    element.className = new_name;
  }
  return true;
}

function removeClass(element, class_name) {
  if (!element || !class_name) return false;
  if (!element.className) return false;
  var all_names = element.className.split(" ");
  for (var i = 0; i < all_names.length; i++) {
    if (all_names[i] === class_name) {
      all_names.splice(i, 1);
      element.className = "";
      for (var j = 0; j < all_names.length; j++) {
        element.className += " ";
        element.className += all_names[j];
      }
      return true;
    }
  }
}
var orderId = null;
$(function () {
  $(".close").on("click", function () {
    if (window.isH5) {
      window.history.go(-1);
    } else {
      wx.miniProgram.navigateBack();
    }
  })
  document.getElementById("levelSwitchBox").addEventListener("webkitAnimationEnd", function () {
    $("#levelSwitchBox").css("display", "none")
    $("#levelSwitchBoxMain").attr("src", "https://txcdn.ylll111.xyz/khj/level_2_main.jpg?v=1.0.0")
    $("#levelSwitchBox").removeClass("hidden")
  })
  $("#levelSwitchBox").addClass("hidden")
  if (!hg) {
    createHg(false)
  }
});

function playAudioInWechat(obj) {
  if (window.WeixinJSBridge) {
    WeixinJSBridge.invoke('getNetworkType', {}, function (e) {
      obj.currentTime = 0;
      obj.play();
      console.log(22, obj);
    }, false);
  } else {
    document.addEventListener("WeixinJSBridgeReady", function () {
      WeixinJSBridge.invoke('getNetworkType', {}, function (e) {
        obj.currentTime = 0;
        obj.play();
      });
      console.log(22, obj);
    }, false);
  }
  console.log(22, obj);
}
function createHg(GAMEMODE) {
  //初始化游戏, 两个参数分别表示"游戏所处的canvas画布元素"和"关卡设置, 可以省略(省略后将使用默认设置)"
  hg = new HardestGame(document.getElementById("gameStage"), GAMEMODE);
  //游戏成功过关
  hg.levelSuccessHandle = function () {
    if (hg.level < 4) {
      playAudioInWechat($("#success_audio").get(0));
      document.getElementById("currentLevel").getElementsByTagName("span")[0].innerHTML = hg.level;
      var time = 4;
      var interval = setInterval(function () {
        time--;
        if (time <= 0) {
          clearInterval(interval);
          hg.gameContinue(true);
        }
      }, 1000);
    } else {
      var level = hg.level - 1

      var audio = document.getElementById('back_music');
      audio.pause();
      playAudioInWechat($("#gameSuccess_audio").get(0));
      $("#app").addClass("blur")
      var signData = "user_id=" + user_id + "challenge_id=" + $('#orderId').val() + "goods_id=" + $('#goods_id').val() +"is_win=1";
      $.ajax({  
        type:"POST",
        url:"/h5khj/api/v1_0_1/game/end.html",
        data:{
            user_id:user_id,
            challenge_id:$('#orderId').val(),
            goods_id:$('#goods_id').val(),
            is_win:1,
            sign:hex_md5(signData)
        },
        success:function(res){
          if(res.data.status==0){
            alert("状态异常！")
            setTimeout(function(){
              history.go(-1)
            },1000)
          }else{
            $("#gameSuccessBox").css("display", "block")
          }
        }
      })
      $("#gameSuccessBoxBtn").on("click", function () {
        window.location.replace("../order/order.html")
      })
    }
  }
  //游戏失败结束
  hg.gameOverHandle = function () {
    clearInterval(timeboxInterval);
    hg = null;
    if (hg) {
      delete hg;
    }
    var audio = document.getElementById('back_music');
    audio.pause();
    playAudioInWechat($("#gameFail_audio").get(0));
    $("#gameOverBox").css("display", "block");
    $("#app").addClass("blur");
    var level = this.level;
    var signData = "user_id=" + user_id + "challenge_id=" + $('#orderId').val() + "goods_id=" + $('#goods_id').val() +"is_win=0";
    $.ajax({  
      type:"POST",
      url:"/h5khj/api/v1_0_1/game/end.html",
      data:{
          user_id:user_id,
          challenge_id:$('#orderId').val(),
          goods_id:$('#goods_id').val(),
          is_win:0,
          sign:hex_md5(signData)
      },
      success:function(res){
        if(res.data.status==0){
          alert("状态异常！")
          setTimeout(function(){
            history.go(-1)
          },1000)
        }else{
          $("#gameOverBox").css("display", "block");
        }
      }
    })
    $("#gameOverBoxBtn").on("click", function () {
      history.go(-1)
    })
  }



  //初始化游戏
  hg.init();
  hg.canvas.parentNode.style.width = hg.canvas.width + "px";
  hg.canvas.parentNode.style.height = hg.canvas.height + "px";
  //游戏开始
  hg.gameStart();
  document.getElementById("currentLevel").getElementsByTagName("span")[0].innerHTML = hg.level;
  return true;
}