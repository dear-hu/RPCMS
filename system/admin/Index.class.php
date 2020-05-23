<?php
namespace rp\admin;
use rp\View;
use rp\Db;
use rp\Cache;

class Index extends Base{
	
	public function __construct(){
		parent::__construct();
	}
	
	public function index(){
		$totalData=array(
			'logs'=>Db::name('logs')->where('status = 0')->count(),
			'page'=>Db::name('pages')->where('status = 0')->count(),
			'cate'=>Db::name('category')->count(),
			'tages'=>Db::name('tages')->count(),
			'comment'=>Db::name('comment')->where('status = 0')->count(),
		);
		View::assign('totalData',$totalData);
		return View::display('/index');
	}
	
	public function webset(){
		View::assign('option',Cache::read('option'));
		return View::display('/webset_index');
	}
	
	public function webPost(){
		$data=input('post.');
		if(Db::name('config')->where('cname="webconfig"')->find()){
			$res=Db::name('config')->where('cname="webconfig"')->update(array('cvalue'=>addslashes(json_encode($data))));
		}else{
			$res=Db::name('config')->insert(array('cname'=>'webconfig','cvalue'=>addslashes(json_encode($data))));
		}
		Cache::update('option');
		return json(array('code'=>200, 'msg'=>'修改配置成功'));
	}
	
	public function updatePsw(){
		$nickname=input('post.nickname');
		$password=input('post.password');
		$password2=input('post.password2');
		if(empty($nickname)){
			return json(array('code'=>-1,'msg'=>'昵称不可为空'));
		}
		if(!empty($password) && $password != $password2){
			return json(array('code'=>-1,'msg'=>'两次密码输入不一致'));
		}
		$updata=array('nickname'=>$nickname);
		if(!empty($password)){
			$updata['password']=psw($password);
		}
		if($res=Db::name('user')->where('id='.$this->user['id'])->update($updata)){
			return json(array('code'=>200,'msg'=>'修改成功','data'=>!empty($password) ? 1 : 0));
		}
		return json(array('code'=>-1,'msg'=>'修改失败，请稍后重试'));
	}
	
	public function attrSelect(){
		$logid=intval(input('logid')) ? intval(input('logid')) : '';
		$pageId=intval(input('pageId')) ? intval(input('pageId')) : '';
		if(empty($logid) && empty($pageId)){
			return json(array('code'=>-1,'msg'=>'数据错误，ID为空'));
		}
		$where=!empty($logid) ? 'logId='.$logid : 'pageId='.$pageId;
		$attr=Db::name('attachment')->where($where)->select();
		foreach($attr as &$v){
			$v['downurl']=str_replace($this->App->baseUrl,'',url('/index/down')).'?token='.$v['token'];
		}
		return json(array('code'=>200, 'msg'=>'success', 'data'=>$attr));
	}
	
	public function attrDele(){
		$id=intval(input('id')) ? intval(input('id')) : '';
		$attrId=intval(input('attrId')) ? intval(input('attrId')) : '';
		$type=input('type') == 'pages' ? 'pages' : 'logs';
		if(empty($id) && empty($attrId)){
			return json(array('code'=>-1,'msg'=>'数据错误，ID数据为空'));
		}
		$where=$type == 'logs' ? 'logId='.$id : 'pageId='.$id;
		$attr=Db::name('attachment')->where('id='.$attrId.' and '.$where)->find();
		if(!empty($attr) && Db::name('attachment')->where('id='.$attrId.' and '.$where)->dele()){
			@unlink(CMSPATH . $attr['filepath']);
		}
		return json(array('code'=>200, 'msg'=>'success'));
	}
	
	public function cacheUpdate(){
		$type=input('type');
		switch($type){
			case 'all':
				Cache::update();
				break;
			case 'cms':
				Cache::update(array('option','total','links','template'));
				break;
			case 'user':
				Cache::update('user');
				break;
			case 'nav':
				Cache::update('nav');
				break;
			case 'logs':
				Cache::update(array('logRecord','logAlias'));
				break;
			case 'cate':
				Cache::update('category');
				break;
			case 'tages':
				Cache::update('tages');
				break;
			case 'pages':
				Cache::update('pages');
				break;
			case 'temp':
				$cashFiles=CMSPATH .'/data/cache/index';
				if(file_exists($cashFiles)){
					deleteFile($cashFiles);
				}
				break;
			case 'plugin':
				$cashFiles=CMSPATH .'/data/cache/plugin';
				if(file_exists($cashFiles)){
					deleteFile($cashFiles);
				}
				break;
		}
		return json(array('code'=>200, 'msg'=>'更新成功'));
	}
}
