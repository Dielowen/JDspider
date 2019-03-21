<?php

require 'src/JDS.class.php';
set_time_limit(90);
$jds = new jds();

$uri = 'https://list.jd.com/list.html?cat=670,12800,12801&page=1&delivery=1';
// $uri = 'https://list.jd.com/list.html?cat=670,12800,12801';

$page = 2;
$skuList = $jds->getSkuList($uri,$page);

print_r($skuList);
