# mini-phpfr
mini-phpfr 迷你php框架

### 目录结构
```shell
├── app
│   ├── Controllers
│   ├── Services
│   └── Views
├── bootstrap
├── config
├── data
├── public
├── runtime
│   ├── cache
│   ├── logs
│   ├── session
│   ├── temp
│   └── volt

```

### cli模式运行
```shell
php public/cli.php task main/main aa bb cc

#或
php public/cli.php task main:main aa bb cc
php public/cli.php task main:test

```