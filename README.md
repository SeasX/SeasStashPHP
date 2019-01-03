# SeasStashPHP
SeasLog -> Swoole -> ClickHouse

## 快速启动
* 安装docker和docker-compose
* 创建docker网络`docker create network SeasStash`
* cd 到项目根目录执行`composer install`
* 启动项目`docker-compose up -d`

## Seaslog配置
##### 日志存储介质 1File 2TCP 3UDP (默认为1)
* seaslog.appender = 2或3

##### 接收ip 默认127.0.0.1 (当使用TCP或UDP时必填)
* seaslog.remote_host = "SeasStashPHP所在机器的IP"

##### 接收端口 默认514 (当使用TCP或UDP时必填)
* seaslog.remote_port = 514 TCP
* seaslog.remote_port = 5014 UDP

## 默认配置
1. 默认数据库名`seaslog`表名`logs`

2. 模板配置，Clickhouse数据库表根据此模板创建`seaslog.default_template = "%T | %L | %R | %m | %I | %Q | %F | %U | %M"`

3. 日期格式`seaslog.default_datetime_format = "Y-m-d H:i:s"`

