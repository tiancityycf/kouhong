var user_id = localStorage.getItem("user_id");
var o = new Vue({
    el: '#vm',
    data: {
        loadShow: true,
        userInfo: {},
        posterImg:"",
        posterImgShow:false
    },
    created: function () {
        this.loadUser();
    },
    methods: {
        loadUser: function () {
            $.ajax({
                type: "POST",
                url: "/h5khj/api/v1_0_1/user/index.html",
                data: {
                    user_id: user_id
                },
                success: function (res) {
                    res.data.data.user_info.money = parseInt(res.data.data.user_info.money);
                    o.userInfo = res.data.data.user_info;
                    o.posterImg =o.userInfo.qr_img;
                    localStorage.setItem("dis_money",res.data.data.user_info.dis_money);
                    localStorage.setItem("withdraw_limit",res.data.data.withdraw_limit);
                    o.loadShow = false;
                }
            })
        },
        codePoster: function () {
            o.posterImgShow=true;
        },
        closeImg:function(){
            o.posterImgShow=false;
        },
        goIndex: function () {
            location.href="../index/index.html"
        },
        goOrder:function(){
            location.href="../order/order.html"
        },
        tixian:function(){
            location.href="../tixian/tixian.html"
        }
    }
})