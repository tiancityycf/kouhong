var user_id = localStorage.getItem("user_id");
var o = new Vue({
    el: '#vm',
    data: {
        orderList:[]
    },
    created: function () {
        this.loadOrder();
    },
    methods:{
        loadOrder: function () {
            $.ajax({
                type: "POST",
                url: "/h5khj/api/v1_0_1/game/challenge_log.html",
                data: {
                    user_id: user_id
                },
                success: function (res) {
                    o.orderList =res.data
                }
            })
        },
    }
})