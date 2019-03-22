<?php

class jds {
    private $jsonDir;           // json文件存储目录
    private $listHost;          // 分类页host
    private $searchHost;        // 搜索页host
    private $priceUri;          // 商品价格接口
    private $infoUri;           // 商品信息接

    public function __construct(){
        $this->jsonDir = 'jsonfiles';
        $this->listHost = 'list.jd.com';
        $this->searchHost = 'search.jd.com';
        $this->priceUri = 'https://p.3.cn/prices/mgets?skuIds=J_';
        $this->infoUri = 'https://question.jd.com/question/getQuestionAnswerList.action?page=99999&productId=';
    }



    /**
     * getSkuList
     * @method               核心操作函数
     * @param   str   $uri   请求地址, 分类页地址或搜索页地址
     * @param   int   $page  需要获取的页数, 每页60条sku
     *
     * @return arr           商品信息
     */
    public function getSkuList($uri=null,$page=null){
        // 重构URL
        $host = self::getHost($uri);

        $type  = $host['type'];
        $param = $host['param'];
        $link  = $host['link'];
        if(!array_key_exists('page',$param)){
            $param['page'] = 1;
        }
        $pn = $param['page'];
        $sku = [];
        if($type === 'list'){
            for($k=0;$k<$page;$k++){
                $param['page'] = $pn+$k;
                $uri = $link.http_build_query($param);
                
                $doc = self::curlGet($uri);

                $tmp = self::getListDom($doc);
                $sku = array_merge_recursive($sku,$tmp);
            }  
        
        // self::getJsonFile(json_encode($sku))

        }else if($type === 'search'){
            $param['scrolling'] = 'y';  // 搜索页开启scrolling, 每页获取30条内容
            $page = $page*2;            // 页码*2, 获取完整一页数据
            for($k=0;$k<$page;$k++){
                $param['page'] = $pn+$k;
                $uri = $link.http_build_query($param);

                $doc = self::curlGet($uri);

                $tmp = self::getSearchDom($doc);
                $sku = array_merge_recursive($sku,$tmp);
            }
        }

        $skuInfo = self::getSkuInfo($sku);
            
        return $skuInfo;
    }
    


    /**
     * getHost
     * @method              重构URL
     * @param  str   $uri   请求地址
     *
     * @return arr          URL结构
     */
    private function getHost($uri){
        $result = [];
        $url = parse_url($uri);
        $type = $url['host'];                                           // 获取主机名
        $link = $url['scheme'].'://'.$url['host'].$url['path'].'?';     // 拼接请求前缀地址

        parse_str($url['query'],$param);                                // 提取参数
        
        if($type === $this->listHost){
            $result['type'] = 'list';
        }else if($type === $this->searchHost){
            $result['type'] = 'search';
        }else{
            $result['type'] = false;
        }
    
        $result['param'] = $param;
        $result['link'] = $link;
        return $result;
    
    }



    /**
     * getListDom
     * @method            获取商品分类页SKU
     * @param  str  $page 页面DOM
     *
     * @return arr        页面SKU
     */
    private function getListDom($page){
        // 匹配SKU
        $pattern = '/j-sku-item\"[\s\S]*data-sku="(.*)\"/Ui';
        preg_match_all($pattern, $page, $result);

        return $result[1];

    }



    /**
     * getSearchDom
     * @method           获取商品搜索页SKU
     * @param  str $page 页面DOM
     *
     * @return arr       页面SKU
     */
    private function getSearchDom($page){
        // 匹配SKU
        $pattern = '/gl-item\"[\s\S]*data-sku="(.*)\"/Ui';
        preg_match_all($pattern, $page, $result);
        return $result[1];

    }



    /**
     * getSkuInfo
     * @mehtod          获取商品详情
     * @param  arr $sku SKU
     *
     * @return arr      商品信息
     */
    private function getSkuInfo($sku=null){
        $result = [];
        foreach($sku as $skuID){
            $priceUri = $this->priceUri.$skuID;    // 拼接商品价格接口
            $infoUri = $this->infoUri.$skuID;      // 拼接商品信息接口
            
            $price = json_decode(self::curlGet($priceUri),true)[0];         // 商品价格数据集合
            $info = json_decode(self::curlGet($infoUri),true)['skuInfo'];   // 商品信息数据集合
            
            // 处理curl请求异常
            while(empty($info)){
                $info = json_decode(self::curlGet($infoUri),true)['skuInfo'];
            }
            
            $result[] = [
                'sku' => $skuID,                                     // 京东商品标识ID
                'priceNormal' => $price['p'],                        // 售价
                'pricePlus'=>                       
                    empty($price['tpp'])?$price['p']:$price['tpp'],  // plus售价
                'brand' => $info['brandName'],                       // 品牌
                'model' => $info['shortName'],                       // 型号
                'name' => $info['fullName'],                         // 商品全名
                'imgUrl' => $info['imgUrl'],                         // 商品图
                'fcID' => $info['firstCategory'],                    // 一级分类ID
                'scID' => $info['secondCategory'],                   // 二级分类ID
                'tcID' => $info['thirdCategory'],                    // 三级分类ID
                'fcName' => $info['firstCategoryName'],              // 一级分类名
                'scName' => $info['secondCategoryName'],             // 二级分类名
                'tcName' => $info['thirdCategoryName'],              // 三级分类名
            ];
        }

        return $result;
    
    }



    /**
     * curlGet
     * @method          get请求 
     * @param  str $uri 请求地址
     *
     * @return str      页面DOM
     */
    private function curlGet($uri){
        $ch = curl_init();

        $ip = '60.172.229.61';
        $timeout = 15;

        curl_setopt($ch,CURLOPT_URL,$uri);
        curl_setopt($ch,CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch,CURLOPT_HTTPHEADER,array('X-FORWARDED-FOR:'.$ip.'','CLIENT-IP:'.$ip.''));
        curl_setopt($ch,CURLOPT_USERAGENT,"Mozilla/5.0 (compatible; Baiduspider/2.0; +http://www.baidu.com/search/spider.html)");
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_HEADER,0);
        curl_setopt ($ch, CURLOPT_REFERER, "https://www.baidu.com/ "); 
        curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);

        $result = curl_exec($ch);

        curl_close($ch);

        return $result;
    }
    


    /**
     * getJsonFile      本地保存sku文件
     * @method                
     * @param   json    sku数据
     *
     * @return  empty   无返回数据
     */
    private function getJsonFile($json){
        is_dir($this->jsonDir) || mkdir($this->jsonDir, 0755);
        file_put_contents($this->jsonDir.'/'.time().'.json',$json);
    }


    
}