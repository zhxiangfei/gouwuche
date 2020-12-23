<?php
namespace app\index\controller;
use think\Controller;
use think\cache\driver\Redis;

class Index extends Controller
{

	private $expire = 43200;	//redis缓存过期时间  12h
	private $redis = null;
	private $cachekey = null;	//缓存变量名
	private $basket = [];		//私有数组，存放商品信息

	private $user_id = '110';
	
	/**
	 * 购物车初始化，传入用户id
	 */
	public function __construct()
	{
		parent::__construct();

		$this->redis = new \Redis();		// 实例化
		$this->redis->connect('127.0.0.1','6379');
		$this->redis->auth('zxf123456');

		$this->cachekey = 'user'.$this->user_id.'.cart';	//redis缓存键名拼接用户id与字符串为对象用户购物车缓存键名  user110.cart
		$this->basket = json_decode($this->redis->get($this->cachekey),true);	//获取对象用户的redis购物车商品缓存信息并解码为数组
	}

	/**
	 * 获取所有商品信息
	 */
    public function index()
    {
		$ids = input('post.ids');
		// 如果获取部分商品信息（搜索进来）
    	if (!empty($ids)) {

    		// 获取部分商品信息
    		$list = $this->getPartGoods($ids);

    		// 获取部分商品数量
    		$totalnum = $this->getPartGoodsNum($ids);

    	}else{
    		// 默认全部列表
	    	$list = $this->basket;
	    	// 获取所有商品数量
	    	$totalnum = $this->getAllDoodsNum();
    	}
    	$this->assign(['list'=>$list,'totalnum'=>$totalnum]);
        return $this->fetch();
    }

    /**
     * 添加商品到购物车
     * @param 商品id 商品属性id 商品名称 数量 价格
     */
    public function addbasket()
    {

    	$data = request()->param();
    	// 判断对象是否已经存在redis购物车缓存中
    	if ($this->isExist($data['goods_id'],$data['attr_id'])) {

    		// 存在缓存中，增加该商品数量
    		return $this->add($data['goods_id'],$data['attr_id'],$data['number']);
    	}

    	// 对象商品不在redis缓存中时
    	$tmp = [];
    	$tmp['goods_id'] = intval($data['goods_id']);	//商品id
    	$tmp['attr_id'] = intval($data['attr_id']);	//商品属性id
    	$tmp['goods_name'] = $data['goods_name'];		//商品名
    	$tmp['attr_name'] = $data['attr_name'];			//商品属性名称
    	$tmp['goods_number'] = intval($data['number']);			//商品数量，新增的商品默认加入数量为1
    	$tmp['price'] = intval($data['price']);			//商品价格
    	$tmp['freight'] = intval($data['freight']);		//运费

    	$tmp['subtotal'] = $tmp['goods_number'] * $tmp['price'] + $tmp['freight'];	//商品总价

    	$this->basket[] = $tmp;		// 把新的商品信息追加到之前的商品缓存数组中，每件属性商品对应一个索引键值

    	
    	// 把新的购物车信息编码为json字符串，并重新存入到redis购物车缓存中
    	$this->redis->setex($this->cachekey,$this->expire,json_encode($this->basket));

    	// return 1;
    	echo "<script>alert('添加成功');window.location.replace(document.referrer);;</script>";
    }

    /**
     * 判断商品是否已经存在
     * @param 商品id 商品属性id
     */
    public function isExist($id,$attr_id)
    {
    	$isExist = false;
    	// 当对象用户redis购物车商品缓存不为空时
    	if (!empty($this->basket)) {

    		foreach ($this->basket as $key => $value) {
    			// 判断当前商品是否存在
    			if ($value['goods_id'] == $id && $value['attr_id'] == $attr_id) {
    				$isExist = true;
    				break;
    			}
    		}
    	}
    	return $isExist;
    }

    /**
     * 添加商品
     */
    public function add($id,$attr_id,$number)
    {

    	$goods_number = 0;	//加入不成功时默认添加数量为0
    	// 商品id不为空并且商品在redis购物车商品缓存中
    	if (!empty($id) && $this->isExist($id,$attr_id)) {

    		$cache_detail = $this->basket;		//获取用户购物车所有商品
    		foreach ($cache_detail as $key => $value) {

    			if ($value['goods_id'] == $id && $value['attr_id'] == $attr_id) {

    				// 只修改商品数量和总价
    				$value['goods_number'] = $value['goods_number'] + $number;	//增加购物车商品数量
    				$value['subtotal'] = $value['goods_number'] * $value['price'] + $value['freight'];	//重新计算总价  数量*单价+运费
 

    				$this->basket[$key] = $value;	//把该商品重新放到redis缓存中
    				$this->redis->setex($this->cachekey,$this->expire,json_encode($this->basket));

    				$goods_number = $value['goods_number'];
    				break;

    			}
    		}
    	}
    	return $goods_number;	//返回商品数量
    }

    /**
     * 获取部分商品
     */
    public function getPartGoods($ids)
    {
    	// 字符串转数组
    	$ids = explode(',', $ids);
    	$goods = [];
    	// 循环ids数组，循环redis缓存数组，当商品id一致时，取出来存到goods数组中
    	foreach ($ids as $v) {
    		foreach ($this->basket as $key => $value) {
    			if ($value['goods_id'] == $v) {

    				$goods[] = $value;
    			}
    		}
    	}
    	return $goods;
    }

    /**
     * 获取部分商品总数
     */
    public function getPartGoodsNum($ids)
    {
    	// 字符串转数组
    	$ids = explode(',', $ids);
    	$number = 0;	//默认为0
    	foreach ($ids as $v) {

	    	foreach ($this->basket as $key => $value) {

	    		// 取出redis缓存中有该id的商品数量
	    		if ($value['goods_id'] == $v) {
	    			$number += $value['goods_number'];
	    		}
	    	}
    	}
    	return $number;
    }

    /**
     * 获取全部商品数量
     */
    public function getAllDoodsNum()
    {
    	$number = 0;
    	if (!empty($this->basket)) {
	    	foreach ($this->basket as $key => $value) {
	    		$number += $value['goods_number'];
	    	}
    	}
    	return $number;
    }

    /**
     * 某一商品数量减一
     */
    public function reduce()
    {
    	$data = request()->param();
    	$goods_number = 0;	//默认减0

    	// 如果接收的数据不为空，并且该商品信息存在
    	if (!empty($data) && $this->isExist($data['id'],$data['attr_id'])) {

    		// 获取redis缓存里的数据
    		$cache_detail = $this->basket;

    		// 循环判断，从缓存商品列表中找到该条商品，数量并减一
    		foreach ($cache_detail as $key => $value) {
    			if ($value['goods_id'] == $data['id'] && $value['attr_id'] == $data['attr_id']) {

    				// 先判断当前商品的数量是否大于要删除的数量
    				if ($value['goods_number'] < $data['number']) {

    					echo "<script>alert('商品数量不足');window.history.back();</script>";
    					break;
    				}
    				// 如果当前商品数量为1，则删除
    				if ($value['goods_number'] <= 1) {

    					// 循环判断找出该商品,并删除
				    	foreach ($this->basket as $key => $value) {
				    		if ($value['goods_id'] == $data['id']) {
				    			// 从数组中移除当前商品
				    			array_splice($this->basket, $key, 1);
				    		}
				    	}
    					// 重新存入缓存
    					$this->redis->setex($this->cachekey,$this->expire,json_encode($this->basket));
    					$goods_number = 0;

    				}else{

	    				// 数量减
	    				$value['goods_number'] = $value['goods_number'] - $data['number'];
	    				$goods_number = $value['goods_number'];
	    				// 计算总价
	    				$value['subtotal'] = $value['goods_number'] * $value['price'];
	    				
	    				// 把新的数据追加到$this->basket
	    				$this->basket[$key] = $value;

	    				// 重新存入缓存
	    				$this->redis->setex($this->cachekey,$this->expire,json_encode($this->basket));
    				}
    			}
    		}
    	}
    	// return $goods_number;
    	echo "该商品当前数量为".$goods_number;
    }

    /**
     * 删除商品
     */
    public function del()
    {
    	$id = input('id');
    	// 循环判断,并删除
    	foreach ($this->basket as $key => $value) {
    		if ($value['goods_id'] == $id) {
    			// 从数组中移除当前商品
    			array_splice($this->basket, $key, 1);
    		}
    	}
    	$this->redis->setex($this->cachekey,$this->expire,json_encode($this->basket));
    	// return true;
    	echo "<script>alert('删除成功');window.location.replace(document.referrer);;</script>";
    }

    /**
     * 编辑商品
     */
    public function edit()
    {
    	$data = input('post.');
    	if (!empty($data) && $this->isExist($data['id'],$data['attr_id']) && $data['number'] > 0) {

    		// 取出缓存中的数据
    		$cache_detail = $this->basket;

    		// 循环判断,取出当前商品信息,并修改
    		foreach ($cache_detail as $key => $value) {

    			if ($value['goods_id'] == $data['id'] & $value['attr_id'] == $data['attr_id']) {

    				// 商品数量
    				$value['goods_number'] = intval($data['number']);
    				// 商品总价 数量*单价+运费
    				$value['subtotal'] = $value['goods_number'] * $value['price'] + $value['freight'];
    				// 赋值
    				$this->basket[$key] = $value;

    				// 重新存储到缓存
    				$this->redis->setex($this->cachekey,$this->expire,json_encode($this->basket));
    				echo "该商品当前数量为".$value['goods_number'];
    			}
    		}
    	}
    }

    /**
     * 清空购物车
     */
    public function emptyCart()
    {
    	$this->redis->rm($this->cachekey);
    	echo "<script>alert('购物车清空成功');window.location.replace(document.referrer);;</script>";
    }

}
