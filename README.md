项目介绍
* Gitlab 托管地址：ssh://git@gitlab.ganaa.cn:22022/backend/qmxz.git
* 测试环境部署说明
1.代码从版本库更新
2.复制项目目录下 
config/database_dev.php 到 config/database.php 
3.如果个人需要修改database.php，先修改config/database_dev.php，再删掉自己本地database.php,重新执行2步骤
4.本地配置hosts 
127.0.0.1 qmxz.com
5.web服务器（nginx、apache等）项目路径配置到 项目下的public目录，重启服务
6.后台管理地址：http://qmxz.com/admin.html
7.接口访问地址：待定

线上部署用walle部署，database.php 需要修改成线上的数据库连接和密码

* Nginx
```

```

* Cron定时任务介绍


* 需要的服务
