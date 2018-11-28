var o = new Vue({
    el: '#vm',
    data: {
        goods:[1,2,3,4,5,6,7,8,9,10],
        musicOn:true,
        ruleShow:false,
        topTit:"挑战送口红",
    },
    methods:{
        login:function(){
            console.log("login")
            $.ajax({  
                type:"GET",
                // url:"http://khj.local.com/h5khj/api/v1_0_1/user/login.html",
                url:"http://khj.local.com/h5khj/api/v1_0_1/user/login.html",
                data:{},
                // dataType: 'jsonp',
                success:function(res){
                    console.log(res)
                }
            })
        },
        musicSet:function(){
            o.musicOn=!o.musicOn
        },
        openRule:function(){
            o.ruleShow=true;
        },
        closeTk:function(){
            o.ruleShow=false;
        },
        goUser:function(){
            location.href="../user/user.html"
        }
    }
})
o.login();