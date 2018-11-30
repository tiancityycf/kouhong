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
                    o.loadShow = false;
                }
            })
        },
        codePoster: function () {
            alert("点击了我的海报")
            var data=["https://txcdn.ylll111.xyz/khj/f1ba173256c5aeb28d33e583287ef381.jpg",o.userInfo.qr_img];
            var c = document.getElementById("myCanvas"),ctx = c.getContext('2d'); 
            c.width = window.screen.width;  // 画布宽   
            c.height = window.screen.height;  // 画布高
            ctx.rect(0, 0, c.width, c.height);  
            ctx.fillStyle = '#fff';
            ctx.fill();
            function drawing(n) {
                alert("点击了我的海报1")
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
                    alert("点击了我的海报2")
                    // alert(c.toDataURL("image/png"))
                    //保存生成作品图片
                    // o.posterImg=c.toDataURL("image/png");
                    o.posterImgShow=true;
                    alert("点击了我的海报2.5")
                    alert(o.posterImg)
                    alert(o.posterImgShow)
                }
            }
            drawing(0);
            alert("点击了我的海报3")
        },
        closeImg:function(){
            o.posterImgShow=false;
        },
        goIndex: function () {
            location.href = "../index/index.html"
        }
    }
})