var user_id = localStorage.getItem("user_id");
var o = new Vue({
    el: '#vm',
    data: {
        loadShow: true,
        userInfo: {}
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
            var data=["https://txcdn.ylll111.xyz/khj/f1ba173256c5aeb28d33e583287ef381.jpg",o.userInfo.qr_img],base64=[];
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
                    //保存生成作品图片
                    base64.push(c.toDataURL("image/png"));
                    console.log(base64)
                    // wx.previewImage({
                    //     current: base64[0], // 当前显示图片的http链接
                    //     urls: [base64[0]] // 需要预览的图片http链接列表
                    // });
                    wx.previewImage({
                        current: "https://txcdn.ylll111.xyz/khj/f1ba173256c5aeb28d33e583287ef381.jpg", // 当前显示图片的http链接
                        urls: ["https://txcdn.ylll111.xyz/khj/f1ba173256c5aeb28d33e583287ef381.jpg"] // 需要预览的图片http链接列表
                    });
                }
            }
            drawing(0);
        },
        goIndex: function () {
            location.href = "../index/index.html"
        }
    }
})