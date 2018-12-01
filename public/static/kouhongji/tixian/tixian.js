var user_id = localStorage.getItem("user_id");
var o = new Vue({
    el: '#vm',
    data: {
        user_amount: 0,
        tixian_amount: 0,
        tkShow: false,
        withdraw_limit: 0,   // 需要满足多少元才能提现
        orderNo: "",
        kfTkShow: false,
        loadShow:false,
        bindKeyInput:"",
        orderNo2:"",
        canclick:false
    },
    created: function () {
        this.user_amount=localStorage.getItem("dis_money");
        this.withdraw_limit=localStorage.getItem("withdraw_limit");
    },
    watch:{
        bindKeyInput:function(e){
            o.tixian_amount= parseFloat(e)
        }
    },
    methods: {
        tixianAll: function () {
            o.bindKeyInput=o.user_amount;
            o.tixian_amount=parseFloat(o.user_amount)
        },
        fuzhi: function () { 
            var rwm = document.getElementById('orderInput');
            rwm.focus();
            rwm.setSelectionRange(0, rwm.value.length);
            document.execCommand("Copy");
            o.tkShow=false;
            o.kfTkShow=true;
        },
        tixianBtn: function () {
            var reg = /^([1-9]\d*|0)(\.\d{1,2})?$/;
            if (!reg.test(o.tixian_amount)) {
                alert("最多可输入俩位小数")
                return;
            }else if (o.tixian_amount <= 0) {
                alert("请输入提现金额")
                return;
            } else if (o.tixian_amount > o.user_amount) {
                alert("可提现金额不足")
                return;
            } else if (o.tixian_amount < o.withdraw_limit) {
                alert("最低提现金额为" + o.withdraw_limit + "元")
                return;
            }
            o.loadShow=true;
            $.ajax({
                type: "POST",
                url: "/h5khj/api/v1_0_1/user/withdraw.html",
                data: {
                    user_id: user_id,
                    type:1,
                    amount:o.tixian_amount
                },
                success: function (res) {
                    o.loadShow=false;
                    if (res.code == 200) {
                        if (res.data.status == 1) {
                            o.user_amount= +(o.user_amount - o.tixian_amount).toFixed(2);
                            localStorage.setItem("dis_money",o.user_amount);
                            o.orderNo=res.data.trade_no;
                            o.orderNo2=o.orderNo;
                            o.tkShow=true;
                        } else {
                            alert("提现失败")
                        }
                    } else {
                        alert("糟糕，好像出了点问题")
                    }
                },
            })
        },
        wxTixian:function(){
            // setTimeout(function(){
            //     o.canclick=true;
            // },1000)
            // if(o.canclick){
                location.href="http://wxpay.wudee.cc/api/v1_3/wxpay/index"
            // }
        },
        gotixianRecord:function(){
            location.href="../tixianRecord/tixianRecord.html"
        },
        againGame:function(){
            location.href="../index/index.html"
        },
        closeTk:function(){
            o.tkShow= false;
            o.kfTkShow= false; 
            o.canclick=false;
        }
    }
})
