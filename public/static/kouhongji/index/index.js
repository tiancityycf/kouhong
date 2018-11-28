var o = new Vue({
    el: '#vm',
    data: {
        goods:[1,2,3,4,5,6,7,8,9,10],
        musicOn:true,
        topTit:"挑战送口红",
    },
    methods:{
        musicSet:function(){
            o.musicOn=!o.musicOn
        },
        openRule:function(){

        },
        goUser:function(){
            location.href="../user/user.html"
        }
    }
})