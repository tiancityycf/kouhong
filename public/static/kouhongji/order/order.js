var user_id = localStorage.getItem("user_id");
var o = new Vue({
    el: '#vm',
    data: {
        orderList: [],
        tkShow: false,
        order_id: 0,
        name: "",
        phone: "",
        address: "",
        canClick:true,
        loadShow:false
    },
    created: function () {
        this.loadOrder();
    },
    methods: {
        loadOrder: function () {
            $.ajax({
                type: "POST",
                url: "/h5khj/api/v1_0_1/game/challenge_log.html",
                data: {
                    user_id: user_id
                },
                success: function (res) {
                    o.orderList = res.data
                }
            })
        },
        linqu: function (e) {
            o.order_id = e;
            o.tkShow = true;
        },
        submit: function () {
            if (!o.canClick) {
                return;
            }
            if (o.address === '') {
                alert("请输入收货地址")
                return;
            }
            if (o.phone === '') {
                alert("请输入收货人电话")
                return;
            }
            if (o.name === '') {
                alert("请输入收货人姓名")
                return;
            }
            o.canClick= false;
            o.loadShow=true;
            $.ajax({
                type: "POST",
                url: "/h5khj/api/v1_0_1/user_goods/receive.html",
                data: {
                    user_id: user_id,
                    challenge_id:o.order_id,
                    nickname:o.name,
                    phone:o.phone,
                    addr:o.address,
                    region:"region"
                },
                success: function (res) {
                    o.canClick= true;
                    o.loadShow=false;
                }
            })
        },
        closeTk:function(){
            o.tkShow = false;
        }
    }
})