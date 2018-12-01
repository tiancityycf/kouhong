var user_id = localStorage.getItem("user_id");
var o = new Vue({
    el: '#vm',
    data: {
        list:[]
    },
    created: function () {
        this.loadRecord();
    },
    methods:{
        loadRecord: function () {
            $.ajax({
                type: "POST",
                url: "/h5khj/api/v1_0_1/user/userRelationList.html",
                data: {
                    user_id: user_id
                },
                success: function (res) {
                    o.list=res.data
                }
            })
        }
    }
})