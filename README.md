**<h3> ThinkPHP5+Redis实现购物车功能 </h3>**
本篇文章是通过ThinkPHP5和Redis实现购物车，功能包括：购物车列表、添加购物车、获取部分商品、获取部分商品总数量、获取全部商品总数量、商品减一、修改商品数量、删除商品、清空购物车，这些功能基本上能够满足购物车的需求，代码写的不够严谨，但大致逻辑就是这样。

*前提：安装PHP运行环境，安装Redis，PHP安装Redis扩展，需要同时满足以上三个条件才能使用Redis。*

参考文章：

- [Linux CentOS7 配置LAMP环境](https://www.cnblogs.com/zxf100/p/14119054.html)
- [Linux CentOS7下安装Redis](https://www.cnblogs.com/zxf100/p/14120430.html)
- [PHP+Redis的使用，Linux下为PHP安装Redis扩展](https://www.cnblogs.com/zxf100/p/14166760.html)

<h4>一、先看一个运行截图（主要实现功能，页面没有优化）</h4>
![](https://img2020.cnblogs.com/blog/1149706/202012/1149706-20201222100121024-994069946.png)

<h4>二、代码说明</h4>
购物车实现代码：index/index/index

有一点需要注意：“use think\cache\driver\Redis;”把redis引进来，这个Redis文件是TP5自带的，不用下载就可以直接用。代码里有注释，其他的就不再说明了，其他文件也没有什么需求配置的

<h4>三、常见错误</h4>
列一下我在使用过程中遇到的问题

1. 在“清空购物车”这个功能里，有一句“ $this->redis->rm($this->cachekey);”，执行这句的时候会报错，"Function Redis::delete() is deprecated"，意思是delete()这个函数已经被弃用了，可以查找到Redis文件里rm（）这个函数，里面用到了delete()这个函数，只需要把delete()改成del就可以了，因为php-redis 5 已经把这个函数弃用了
![](https://img2020.cnblogs.com/blog/1149706/202012/1149706-20201222101226780-1458092135.png)

其他弃用函数

![](https://img2020.cnblogs.com/blog/1149706/202012/1149706-20201222101134483-711583207.png)
