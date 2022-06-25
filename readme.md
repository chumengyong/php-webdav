## 使用方法

### 开始

* 首先 Clone 本仓库 git clone https://github.com/chumengyong/php-webdav.git
* 修改 config.yaml, scope 为要管理的目录, 比如要管理 /root目录, 修改成这样
* ![](https://i-s2.328888.xyz/2022/06/25/62b6f0e8c410e.png)

* 然后 php -S [要监听的IP]:[要监听的端口]

### 本地挂载

* 推荐使用 RaiDriver 下载地址: https://www.raidrive.com/

* 配置成这样 ![](https://i-s2.328888.xyz/2022/06/25/62b6f1651378e.png)



* 然后就可以在 windows 上管理 VPS的文件了 ![](https://i-s2.328888.xyz/2022/06/25/62b6f1941ef16.png)



## bugs

* 目前下载超过 1G 的文件会报错, 正在解决中....



## futures

* 增加用户认证, 对每个用户设定管理路径
* 上传 修改 文件
* ....