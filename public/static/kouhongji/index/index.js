var o = new Vue({
    el: '#vm',
    data: {
        goods:[],
        musicOn:true,
        loadShow:true,
        tkShow:false,
        ruleShow:false,
        balanceShow:false,
        payShow:false,
        canClick:true,
        topTit:"",
        ruleList:[],
        payList:[],
        money:0
    },
    created:function(){
        this.loadIndex();
    },
    methods:{
        loadIndex:function(){
            $.ajax({  
                type:"POST",
                url:"/h5khj/api/v1_0_1/good/index.html",
                data:{
                    user_id:user_id
                },
                success:function(res){
                    o.topTit=res.data.notice.title;
                    o.ruleList =res.data.rules;
                    for(let i=0;i<res.data.good_info.length;i++){
                        res.data.good_info[i].price=parseInt(res.data.good_info[i].price);
                        res.data.good_info[i].sale_price=parseInt(res.data.good_info[i].sale_price);
                    }
                    o.goods=res.data.good_info;
                    o.money=res.data.user_info.money;
                    o.loadShow=false;
                }
            })
        },
        startGame:function(e){
            if(!o.canClick){
                return;
            }
            o.canClick=false;
            $.ajax({  
                type:"POST",
                url:"/h5khj/api/v1_0_1/game/start.html",
                data:{
                    user_id:user_id,
                    goods_id:e
                },
                success:function(res){
                    if(res.data.status==1){
                        location.href="../game/index.html?game=2&orderId="+res.data.challenge_id+"&goods_id="+e
                    }else{
                        o.tkShow=true;
                        o.balanceShow=true;
                        o.canClick=true;
                        o.money =localStorage.getItem("money");
                        o.loadPayList();
                    }
                }
            })
        },
        loadPayList:function(){
            $.ajax({  
                type:"POST",
                url:"/h5khj/api/v1_0_1/recharge_amount/amount_list.html",
                data:{
                    user_id:user_id
                },
                success:function(res){
                    o.payList=res.data;
                }
            })
        },
        buy:function(e){
            if(!o.canClick){
                return;
            }
            o.canClick=false;
            $.ajax({  
                type:"POST",
                url:"/h5khj/api/v1_0_1/wx_pay/unifiedorder.html",
                data:{
                    user_id:user_id,
                    type:0,
                    recharege_id:e
                },
                success:function(res){
                    wx.chooseWXPay({
                        timestamp: res.data.return_param.timeStamp, // 支付签名时间戳，注意微信jssdk中的所有使用timestamp字段均为小写。但最新版的支付后台生成签名使用的timeStamp字段名需大写其中的S字符
                        nonceStr: res.data.return_param.nonceStr, // 支付签名随机串，不长于 32 位
                        package: res.data.return_param.package, // 统一支付接口返回的prepay_id参数值，提交格式如：prepay_id=\*\*\*）
                        signType: res.data.return_param.signType, // 签名方式，默认为'SHA1'，使用新版支付需传入'MD5'
                        paySign: res.data.return_param.paySign, // 支付签名
                        success: function (aa) {
                            // o.canClick=true;
                            // o.closeTk();
                            alert(aa)
                        },
                        fail:function(aa){
                            console.log("充值失败")
                            alert(JSON.stringify(aa))
                        }
                    });
                }
            })
        },
        payMoney:function(){
            o.balanceShow=false;
            o.payShow=true;
        },
        musicSet:function(){
            o.musicOn=!o.musicOn;
            if(!o.musicOn){
                $("#bgMusic")[0].pause();
            }else{
                $("#bgMusic")[0].play();
            }
        },
        openRule:function(){
            o.ruleShow=true;
            o.tkShow=true;
        },
        closeTk:function(){
            o.ruleShow=false;
            o.tkShow=false;
            o.balanceShow=false;
            o.payShow=false;
        },
        goUser:function(){
            location.href="../user/user.html"
        },
        tryGame:function(){
            location.href="../game/index.html?game=1"
        }
    }
})    
