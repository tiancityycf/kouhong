var user_id =localStorage.getItem("user_id");
var o = new Vue({
    el: '#vm',
    data: {
        loadShow:true,
        userInfo:{}
    },
    created:function(){
        this.loadUser();
    },
    methods:{
        loadUser:function(){
            $.ajax({  
                type:"POST",
                url:"http://khj.local.com/h5khj/api/v1_0_1/user/index.html",
                data:{
                    user_id:user_id
                },
                success:function(res){
                    res.data.data.user_info.money=parseInt(res.data.data.user_info.money);
                    o.userInfo =res.data.data.user_info;
                    localStorage.setItem("money",o.userInfo.money);
                    o.loadShow=false;
                }
            })
        },
        goIndex:function(){
            location.href="../index/index.html"
        }
    }
})