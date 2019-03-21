<?php

// 通过京东分类页或搜索页, 抓取商品数据
// 分类页前缀 list.jd.com
// 搜索页前缀 search,jd.com

class jds {
    private $jsonDir;
    private $listHost;          // 分类页host
    private $searchHost;        // 搜索页host
    private $priceUri;          // 商品价格接口
    private $infoUri;           // 商品信息接口


    public function __construct(){
        $this->jsonDir = 'jsonfiles';
        $this->listHost = 'list.jd.com';
        $this->searchHost = 'search.jd.com';
        $this->priceUir = 'https://p.3.cn/prices/mgets?skuIds=J_';
        $this->infoUri = 'https://question.jd.com/question/getQuestionAnswerList.action?page=99999&productId=';
    }

    public function getSkuList($uri=null,$page=null){
        $host = self::getHost($uri);

        $type = $host['type'];
        $param = $host['param'];
        $link = $host['link'];
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
            
            $skuInfo = self::getSkuInfo($sku);
            
            return $skuInfo;
            

        }else if($type === 'search'){
            echo '搜索页';
        }
    }
    
    private function getHost($uri){
        $result = [];
        $url = parse_url($uri);
        $type = $url['host'];
        $link = $url['scheme'].'://'.$url['host'].$url['path'].'?';

        parse_str($url['query'],$param);
        
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


    private function getListDom($page){
        // $pattern = '/class=\"gl-item\"[\s\S]*gl-i-wrap j-sku-item\" data-sku="(.*)\"[\s\S]*img width=\"220\" height=\"220\" data-img=\"1\"[\s\S]*=\"(.*)\"/U';
        $pattern = '/j-sku-item\"[\s\S]*data-sku="(.*)\"/Ui';

        preg_match_all($pattern, $page, $result);

        return $result[1];

    }

    private function getSkuInfo($sku=null){
        $result = [];
        foreach($sku as $skuID){
            $priceUri = $this->priceUir.$skuID;
            $infoUri = $this->infoUri.$skuID;
            
            $price = json_decode(self::curlGet($priceUri),true)[0];
            $info = json_decode(self::curlGet($infoUri),true)['skuInfo'];
            
            
            while(empty($info)){
                $info = json_decode(self::curlGet($infoUri),true)['skuInfo'];
            }
            

            $result[] = [
                'sku' => $skuID,
                'priceNormal' => $price['p'],
                'pricePlus'=> empty($price['tpp'])?$price['p']:$price['tpp'],

                'brand' => $info['brandName'],
                'model' => $info['shortName'],
                'name' => $info['fullName'],
                'imgUrl' => $info['imgUrl'],
                'fcID' => $info['firstCategory'],
                'scID' => $info['secondCategory'],
                'tcID' => $info['thirdCategory'],
                'fcName' => $info['firstCategoryName'],
                'tcName' => $info['secondCategoryName'],
                'tcName' => $info['thirdCategoryName'],

            ];
            
        }

        return $result;

    }

    private function curlGet($uri){
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL,$uri);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 30000);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $result = curl_exec($ch);

        curl_close($ch);

        return $result;
    }
    

    // save json file
    private function getJsonFile($json){
        is_dir($this->jsonDir) || mkdir($this->jsonDir, 0755);
        file_put_contents($this->jsonDir.'/'.time().'.json',$json);
    }


}