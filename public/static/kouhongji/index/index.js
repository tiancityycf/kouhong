var o = new Vue({
    el: '#vm',
    data: {
        goods:[],
        musicOn:true,
        loadShow:true,
        ruleShow:false,
        topTit:"",
        ruleList:[]
    },
    created:function(){
        this.loadIndex();
    },
    methods:{
        loadIndex:function(){
            $.ajax({  
                type:"POST",
                url:"http://khj.local.com/h5khj/api/v1_0_1/good/index.html",
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
                    o.loadShow=false;
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
