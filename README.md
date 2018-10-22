项目介绍
* Gitlab 托管地址：ssh://git@gitlab.ganaa.cn:22022/backend/qmxz.git
* 测试环境部署说明
1. 代码从版本库更新
2. 复制项目目录下 
config/database_dev.php 到 config/database.php 
3. 如果个人需要修改database.php，先修改config/database_dev.php，再删掉自己本地database.php,重新执行2步骤
4. 本地配置hosts 
127.0.0.1 qmxz.com
5. web服务器（nginx、apache等）项目路径配置到 项目下的public目录，重启服务
6. 后台管理地址：http://qmxz.com/admin.html
7. 接口访问地址：待定

线上部署用walle部署，线上用到的配置文件：database_production.php 

* Nginx Proxy qmxz.conf
```
upstream qmxz_backend {
        ip_hash;
        server 172.16.0.235:2088 weight=1 max_fails=3 fail_timeout=15s;
        server 172.16.0.18:2088 weight=1 max_fails=3 fail_timeout=15s;
}

log_format  access '$remote_addr -[$time_local] "$request" '
'"$status" '
'"$http_user_agent" '
'"$bytes_sent" '     
'"$args" '   
'"$upstream_addr" "$upstream_status" "req_time=$request_time" "up_res_time=$upstream_response_time" ';

server {
        listen 443 ssl http2;
        ssl_certificate /usr/local/nginx/conf/ssl/1_qmxz.wqop2018.com_bundle.crt;
        ssl_certificate_key /usr/local/nginx/conf/ssl/2_qmxz.wqop2018.com.key;
        ssl_protocols TLSv1 TLSv1.1 TLSv1.2;
        ssl_ciphers EECDH+CHACHA20:EECDH+AES128:RSA+AES128:EECDH+AES256:RSA+AES256:EECDH+3DES:RSA+3DES:!MD5;
        ssl_prefer_server_ciphers on;
        ssl_session_timeout 10m;
        ssl_session_cache builtin:1000 shared:SSL:10m;
        ssl_buffer_size 1400;
        add_header Strict-Transport-Security max-age=15768000;
        ssl_stapling on;
        ssl_stapling_verify on;
        server_name qmxz.wqop2018.com;
        access_log on;

        access_log /data/wwwlogs/qmxz.wqop2018.com_proxy.log access;
        index index.html index.htm index.php;
        root /data/wwwroot/default/qmxz/public;
        location / {
                proxy_pass http://qmxz_backend;
                proxy_set_header Host $host;
                proxy_set_header X-Real-IP $remote_addr;
                proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
                proxy_connect_timeout 60;
                proxy_send_timeout 60;
                proxy_read_timeout 60;

        }

}

```


* Nginx RealServer qmxz_2088.conf需要检查 PHP配置是否正确 fastcgi_pass
```
log_format  access2088 '$proxy_add_x_forwarded_for -[$time_local] "$request" '
'"$status" '
'"$http_user_agent" '
'"$bytes_sent" '
'"$args" '
'"$upstream_addr" "$upstream_status" "req_time=$request_time" "up_res_time=$upstream_response_time" ';

server {
        listen 2088;
        server_name 134.175.37.131;
        access_log /data/wwwlogs/qmxz.wqop2018.com_2088.log access2088;
        index index.html index.htm index.php;
        root /data/wwwroot/default/qmxz/public;

        location ~ \.php/?.* {
                fastcgi_pass unix:/dev/shm/php-cgi.sock;
                fastcgi_index  index.php;
                include fastcgi_params;
                set $real_script_name $fastcgi_script_name;

                if ($fastcgi_script_name ~ "^(.+?\.php)(/.+)$") {
                        set $real_script_name $1;
                        set $path_info $2;
                }
                fastcgi_param SCRIPT_FILENAME $document_root$real_script_name;
                fastcgi_param SCRIPT_NAME $real_script_name;
                fastcgi_param PATH_INFO $path_info;
        }

        if (!-e $request_filename) {
                rewrite ^(.*)$ /index.php$1 last;
                break;
        }

}

```
* Cron定时任务介绍


* 需要的服务

/usr/local/nginx/sbin/nginx -c /usr/local/nginx/conf/nginx.conf

/usr/local/php/sbin/php-fpm

* 扩容方案

1. 新加服务器，并配置nginx php-fpm基础服务，
2. copy主服务器qmxz_2088.conf 到新机器nginx配置目录下，并重新加载nginx配置文件
3. 主服务器配置：qmxz.conf 
qmxz_backend 新增新机器的ip 端口等信息
4. 重启proxy的nginx，并测试



