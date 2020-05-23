<?php
// +----------------------------------------------------------------------
// | RPCMS
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.rpcms.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: ralap <www.rpcms.cn>
// +----------------------------------------------------------------------

namespace rp;

use rp\Db;

class Cache{
	
	protected static $instance;
	protected static $cacheData=array();
	
	public function __construct(){
		
	}
	
	public static function instance(){
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
	
	public static function read($name){
		$cache=self::instance();
		if(isset(self::$cacheData[$name])){
			return self::$cacheData[$name];
		}else{
			$cachefile = CMSPATH . '/data/'.$name.'.php';
			if(!is_file($cachefile) || filesize($cachefile) <= 0){
				if (method_exists($cache, 'me_' . $name)){
					call_user_func(array($cache, 'me_' . $name));
				}
			}
			if($fp = fopen($cachefile, 'r')){
				$data = fread($fp, filesize($cachefile));
				fclose($fp);
				clearstatcache();
				$data=str_replace("<?php exit;//", '', $data);
				self::$cacheData[$name] = json_decode($data,true);
				return self::$cacheData[$name];
			}
		}
    }
	
	public static function update($name=''){
		$cache=self::instance();
		if(!empty($name) && is_string($name)){
            if(method_exists($cache, 'me_' . $name)){
                call_user_func(array($cache, 'me_' . $name));
            }
            return true;
        }
		if(is_array($name)){
			foreach($name as $v){
                if(method_exists($cache, 'me_' . $v)){
                    call_user_func(array($cache, 'me_' . $v));
                }
            }
            return true;
		}
		if(empty($name)){
            $nameArr = get_class_methods($cache);
            foreach ($nameArr as $method) {
                if(preg_match('/^me_/', $method)){
                    call_user_func(array($cache, $method));
                }
            }
			return true;
        }
	}
	
	/*系统配置缓存*/
	private function me_option(){
		$option=Db::name('config')->where('cname = "webconfig"')->field('cvalue')->find();
        $this->cacheWrite($option['cvalue'], 'option');
	}
	
	/*用户缓存*/
	private function me_user(){
		$user=Db::name('user')->where('status = 0')->field('id,username,nickname,role,status')->select();
		$user=array_column($user,NULL,'id');
		$this->cacheWrite(json_encode($user), 'user');
	}
	
	/*导航缓存*/
	private function me_nav() {
        $nav_cache = array();
		$nav=Db::name('nav')->order(array('topId'=>'asc','sort'=>'ASC'))->select();
		$category=$this->read('category');
		foreach($nav as $k=>$v){
			$v['url']=Url::nav($v['types'],$v['typeId'],$v['url'],true);
			if($v['topId'] == 0){
				$nav_cache[$v['id']]=$v;
				$nav_cache[$v['id']]['children']=array();
			}elseif(isset($nav_cache[$v['topId']])){
				$nav_cache[$v['topId']]['children'][] = $v;
			}
		}
        $this->cacheWrite(json_encode($nav_cache), 'nav');
    }
	
	/*统计缓存*/
	private function me_total(){
		$log=Db::name('logs')->field('count(*) as logNum,authorId,status')->group('authorId,status')->select();
		$pages=Db::name('pages')->field('count(*) as pageNum,authorId')->group('authorId')->select();
		$comment=Db::name('comment')->field('count(*) as commentNum,authorId,status')->group('authorId,status')->select();
		$total=array(
			'logNum'=>0,
			'logOk'=>0,
			'logExa'=>0,
			'logDraft'=>0,
			'logLower'=>0,
			'pageNum'=>0,
			'commentNum'=>0,
			'commentOk'=>0,
			'commentExa'=>0,
		);
		foreach($log as $lk=>$lv){
			$total['logNum']+=$lv['logNum'];
			$total['logOk']+=$lv['status'] == 0 ? $lv['logNum'] : 0;
			$total['logExa']+=$lv['status'] == 1 ? $lv['logNum'] : 0;
			$total['logDraft']+=$lv['status'] == 2 ? $lv['logNum'] : 0;
			$total['logLower']+=$lv['status'] == -1 ? $lv['logNum'] : 0;
			if(!isset($total['logU'.$lv['authorId']])){
				$total['logU'.$lv['authorId']]=array(
					'logNum'=>0,
					'logOk'=>0,
					'logExa'=>0,
					'logDraft'=>0,
					'logLower'=>0,
				);
			}
			$total['logU'.$lv['authorId']]['logNum']+=$lv['logNum'];
			$total['logU'.$lv['authorId']]['logOk']+=$lv['status'] == 0 ? $lv['logNum'] : 0;
			$total['logU'.$lv['authorId']]['logExa']+=$lv['status'] == 1 ? $lv['logNum'] : 0;
			$total['logU'.$lv['authorId']]['logDraft']+=$lv['status'] == 2 ? $lv['logNum'] : 0;
			$total['logU'.$lv['authorId']]['logLower']+=$lv['status'] == -1 ? $lv['logNum'] : 0;
		}
		foreach($pages as $pk=>$pv){
			$total['pageNum']+=$pv['pageNum'];
			if(!isset($total['pageU'.$pv['authorId']])){
				$total['pageU'.$pv['authorId']]=array(
					'pageNum'=>0,
				);
			}
			$total['pageU'.$pv['authorId']]['pageNum']+=$pv['pageNum'];
		}
		foreach($comment as $ck=>$cv){
			$total['commentNum']+=$cv['commentNum'];
			$total['commentOk']+=$cv['status'] == 0 ? $cv['commentNum'] : 0;
			$total['commentExa']+=$cv['status'] == 1 ? $cv['commentNum'] : 0;
			if(!isset($total['commentU'.$cv['authorId']])){
				$total['commentU'.$cv['authorId']]=array(
					'commentNum'=>0,
					'commentOk'=>0,
					'commentExa'=>0,
				);
			}
			$total['commentU'.$cv['authorId']]['commentNum']+=$cv['commentNum'];
			$total['commentU'.$cv['authorId']]['commentOk']+=$cv['status'] == 0 ? $cv['commentNum'] : 0;
			$total['commentU'.$cv['authorId']]['commentExa']+=$cv['status'] == 1 ? $cv['commentNum'] : 0;
		}
		$this->cacheWrite(json_encode($total), 'total');
	}
	
	/*文章归档缓存*/
	private function me_logRecord(){
		$log=Db::name('logs')->field('count(*) as logNum,DATE_FORMAT(createTime,\'%Y-%m\') as ym')->group('ym')->order('ym','desc')->select();
		$logRecord=array();
		foreach($log as $k=>$v){
			$logRecord[]=array(
				'record'=>date('Y年m月',strtotime($v['ym'])),
				'date'=>str_replace('-','',$v['ym']),
				'logNum'=>$v['logNum'],
			);
		}
		$this->cacheWrite(json_encode($logRecord), 'logRecord');
	}
	
	/*文章别名缓存*/
	private function me_logAlias(){
		$log=Db::name('logs')->field('id,alias')->select();
		$logAlias = array_column($log,'alias','id');
		$this->cacheWrite(json_encode($logAlias), 'logAlias');
	}
	
	/*分类缓存*/
	private function me_category() {
        $cate_cache = array();
		$options=Config::get('db');
		$prefix=$options["prefix"];
		$category=Db::name('category')->alias('a')->join('(select cateId,count(*) as logNum FROM '.$prefix.'logs where status =0 group by cateId) b','a.id=b.cateId','left')->order(array('a.topId'=>'asc','a.sort'=>'ASC'))->field('a.*,b.logNum')->select();
		foreach($category as $k=>$v){
			$cate_cache[$v['id']]=$v;
			$cate_cache[$v['id']]['children']=array();
			if($v['topId'] != 0 && isset($cate_cache[$v['topId']])){
				$cate_cache[$v['topId']]['children'][] = $v['id'];
			}
		}
        $this->cacheWrite(json_encode($cate_cache), 'category');
    }
	
	/*友情链接缓存*/
	private function me_links() {
		$links=Db::name('links')->where('status =0')->field('sitename,sitedesc,siteurl')->order('sort','asc')->select();
        $this->cacheWrite(json_encode($links), 'links');
    }
	
	/*tag标签缓存*/
	private function me_tages(){
		$allTag=Db::name('logs')->where('status =0')->field('status,GROUP_CONCAT(tages) as alltages')->group('status')->find();
		$allTagArr=array_filter(explode(',',$allTag['alltages']));
		$allTagArr=array_count_values($allTagArr);
		$tages=Db::name('tages')->select();
		foreach($tages as $k=>$v){
			$tages[$k]['logNum']=isset($allTagArr[$v['id']]) ? $allTagArr[$v['id']] : 0;
		}
		$tages=array_column($tages,NULL,'id');
		$this->cacheWrite(json_encode($tages), 'tages');
	}
	
	/*单页主要信息缓存*/
	private function me_pages(){
		$pages=Db::name('pages')->where('status =0')->field('id,title,alias,seo_key,seo_desc,password,authorId,comnum,template,createTime,extend,isRemark')->select();
		$pages=array_column($pages,NULL,'id');
		$this->cacheWrite(json_encode($pages), 'pages');
	}

	private function me_template(){
		$template=array('name'=>'defaults','config'=>array());
		$temp=Db::name('config')->where('cname = "template"')->field('cvalue')->find();
		if(empty($temp)){
			$template['name']='defaults';
		}else{
			$template['name']=$temp['cvalue'];
		}
		if($cfg=Db::name('config')->where('cname = "temp_'.$template['name'].'"')->field('cvalue')->find()){
			$template['config']=json_decode($cfg['cvalue'],true);
		}
		$this->cacheWrite(json_encode($template), 'template');
	}
	
    private function cacheWrite($cacheData, $cacheName){
		$cachefile = CMSPATH . '/data/'.$cacheName.'.php';
		$cacheData = "<?php exit;//" . $cacheData;
		self::$cacheData[$cacheName]= null;
		return @file_put_contents($cachefile,$cacheData);
    }
	
}