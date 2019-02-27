var user_id = localStorage.getItem("user_id");
var o = new Vue({
    el: '#vm',
    data: {
        dataList:[]
    },
    created: function () {
        this.loadRecord();
    },
    methods:{
        loadRecord: function () {
            $.ajax({
                type: "POST",
                url: "/h5khj/api/v1_0_1/user/withdrawList.html",
                data: {
                    user_id: user_id
                },
                success: function (res) {
                    o.dataList=res.data.withdraw_list
                }
            })
        },
        wxTixian:function(){
            location.href="http://wxpay.wudee.cc/api/v1_3/wxpay/index"
        }
    }
})