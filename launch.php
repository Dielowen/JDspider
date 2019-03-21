<?php
// 引入操作类别
require 'src/JDS.class.php';

// 设置脚本运行时间
set_time_limit(90);

// 初始化核心
$jds = new jds();

// 京东分类页URL
$uri = 'https://list.jd.com/list.html?cat=670,12800,12801&page=1&delivery=1';
// $uri = 'https://list.jd.com/list.html?cat=670,12800,12801';

// 定义获取多少页内容
$page = 2;

$skuList = $jds->getSkuList($uri,$page);

print_r($skuList);
