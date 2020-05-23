<?php
namespace plugin\appcenter;

use rp\Plugin;

class Index extends Plugin{
	
	private $params;
	private $token;
	public function __construct($params=''){
		parent::__construct();
		$this->params=$params;
		$config=$this->getConfig();
		$this->token=isset($config['token']) ? $config['token'] : '';
	}
	
	/*插件安装*/
	public function install(){
		//echo 11;
	}
	
	/*插件卸载*/
	public function uninstall(){
		//echo 22;
	}

	/*
	*挂载钩子
	*钩子执行方法格式：钩子所在文件的命名空间::执行方法
	*return array(钩子名称=>钩子执行方法)
	*/
	public function addHook(){
		$data=array(
			'admin_top_menu'=>'plugin\appcenter\index::addTopMenu',
		);
		return $data;
	}
	
	
	public function addTopMenu($args=''){
		echo '<li class="top_item"> <a href="'.url('plugin/run').'?to=appcenter/index/index">应用中心</a></li>';
	}
	
	
	public function index(){
		$curl=new \plugin\appcenter\lib\send();
		$action=!empty(input('act')) ? input('act') : 'index';
		$parse=parse_url(input('server.REQUEST_URI'));
		parse_str($parse['query'],$query);
		$data=array_slice($query,1);
		$data['token']=$this->token;
		switch($action){
			case 'index':
				$res=$curl->http_curl('index',$data);
				break;
			case 'search':
				$res=$curl->http_curl('search',$data);
				break;
			case 'plugin':
				$res=$curl->http_curl('plugin',$data);
				break;
			case 'temp':
				$res=$curl->http_curl('temp',$data);
				break;
			case 'app':
				$res=$curl->http_curl('app',$data);
				break;
			case 'down':
				$appType=input('post.type');
				$id=intval(input('post.id'));
				if(empty($id)){
					return json(array('code'=>-1,'msg'=>'应用数据错误'));
				}
				$data['id']=$id;
				$data['php']=PHP_VERSION;
				$data['cms']=RPCMS_VERSION;
				$res=$curl->http_curl('download',$data);
				if(stripos($res,'{"code"') === 0){
					return $res;
				}
				$tempDir=$appType == 'temp' ? TMPPATH.'/index' : PLUGINPATH;
				$tempFile=$tempDir.'/rpcmsapp_'.getGuid().'.zip';
				if(!file_put_contents($tempFile,$res)){
					return json(array('code'=>-1,'msg'=>'应用下载失败，检查目录权限或手动下载'));
				}
				$zip=new \rp\zip($tempFile);
				$res=$zip->unzip($tempDir);
				if($res['code'] == 200){
					@unlink($tempFile);
					return json(array('code'=>200,'msg'=>'应用安装成功！'));
				}
				return $res;
				break;
			case 'login':
				$token=input('post.token');
				$res=$curl->http_curl('login','token='.$token);
				$resArr=json_decode($res,true);
				if($resArr['code'] == 200){
					$this->setConfig('appcenter',array('token'=>$token));
				}
				return $res;
				break;
			case 'loginOut':
				$this->setConfig('appcenter',array('token'=>''));
				redirect(url('plugin/run').'?to=appcenter/index/index');
				break;
			case 'myapp':
				$res=$curl->http_curl('myapp',$data);
				break;
			case 'author':
				$res=$curl->http_curl('author',$data);
				break;
			default:
				
		}
		$this->assign('html',$res);
		return $this->display('template/index');
	}
}
