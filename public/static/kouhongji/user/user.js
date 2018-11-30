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
                    localStorage.setItem("dis_money",res.data.data.user_info.dis_money);
                    localStorage.setItem("withdraw_limit",res.data.data.withdraw_limit);
                    o.loadShow = false;
                }
            })
        },
        codePoster: function () {
            var data=["/static/kouhongji/image/qrCodebg.jpg",o.userInfo.qr_img];
            var c = document.getElementById("myCanvas"),ctx = c.getContext('2d'); 
            c.width = window.screen.width;  // 画布宽   
            c.height = window.screen.height;  // 画布高
            ctx.rect(0, 0, c.width, c.height);  
            ctx.fillStyle = '#fff';
            ctx.fill();
            function drawing(n) {
                if (n < 2) {
                    var img = new Image;
                    img.src = data[n];
                    img.onload = function () {
                        if (n === 1) {
                            ctx.drawImage(img, c.width/2-60, c.height/2-60, 120, 120);
                        }
                        else if (n === 0) {
                            ctx.drawImage(img, 0, 0, c.width, c.height);
                        }
                        drawing(n + 1);//递归
                    }
                } else {
                    o.updateImg(c.toDataURL("image/png"))
                }
            }
            drawing(0);
        },
        updateImg:function(img){
            $.ajax({
                type: "POST",
                url: "/h5khj/api/v1_0_1/user/saveCode.html",
                data: {
                    user_id: user_id,
                    img_content:img
                },
                success: function (res) {
                    o.posterImg=res.data.path;
                    o.posterImgShow=true;
                }
            })
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