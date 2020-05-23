<?php
namespace rp\install;
use rp\Url;
use rp\View;
use rp\Db;
use rp\Cache;

class Index{
	private $links;
	
	public function index(){
		
		return View::display('/index');
	}
	
	public function step1(){
		$license=CMSPATH .'/data/defend/license.txt';
		$licenseData=@file_get_contents($license);
		$licenseData=strip_tags($licenseData); 
		$licenseData=str_replace(PHP_EOL,'<br>',$licenseData);
		return json(array('code'=>200, 'msg'=>'success', 'data'=>$licenseData));
	}
	
	public function step2(){
		$data=array(
			'server'=>input('SERVER.SERVER_SOFTWARE'),
			'phpver'=>version_compare("5.6", PHP_VERSION, ">") ? '<font>RPCMS需要PHP版本最低5.6</font>' : PHP_VERSION,
			'cmspath'=>CMSPATH,
			'gd2'=>'<font>不支持</font>',
			'mbstring'=>'<font>不支持</font>',
			'mysqli'=>'<font>不支持</font>',
			'config.php'=>GetFilePermsOct(CMSPATH . '/config.php'),
			'data'=>GetFilePermsOct(CMSPATH . '/data'),
			'plugin'=>GetFilePermsOct(CMSPATH . '/plugin'),
			'templates'=>GetFilePermsOct(CMSPATH . '/templates'),
			'uploads'=>GetFilePermsOct(CMSPATH . '/uploads'),
		);
		if(function_exists("gd_info")){
			$info = gd_info();
			$data['gd2'] = $info['GD Version'];
		}
		if(function_exists("mb_language")){
		   $data['mbstring'] = mb_language();
		}
		if(function_exists("mysqli_get_client_info")){
			$data['mysqli'] = strtok(mysqli_get_client_info(), '$');
		}
		//$data['config.php'] = $data['config.php'] == '0755' ? $data['config.php'] : '<font>不可写'.$data['config.php'].'</font>';
		$data['data'] = $data['data'] >= '0755' ? $data['data'] : '<font>不可写</font>';
		$data['plugin'] = $data['plugin'] >= '0755' ? $data['plugin'] : '<font>不可写</font>';
		$data['templates'] = $data['templates'] >= '0755' ? $data['templates'] : '<font>不可写</font>';
		$data['uploads'] = $data['uploads'] >= '0755' ? $data['uploads'] : '<font>不可写</font>';
		return json(array('code'=>200, 'msg'=>'success', 'data'=>$data));
	}
	
	public function step4(){
		global $App;
		$data=input('post.');
		$data['tablepre']=!empty($data['tablepre']) ? $data['tablepre'] : 'me_';
		if(empty($data['dbhost']) || empty($data['dbuser']) || empty($data['dbpsw']) || empty($data['dbname']) || empty($data['username']) || empty($data['userpsw'])){
			return json(array('code'=>-1, 'msg'=>'数据错误，请填写完整信息！'));
		}
		if(!$this->links=@mysqli_connect($data['dbhost'], $data['dbuser'], $data['dbpsw'])){
			return json(array('code'=>-1, 'msg'=>'无法连接数据库服务器，请检查配置！'));
		}
		if(!mysqli_select_db($this->links,$data['dbname'])){
			if(!@mysqli_query($this->links,"CREATE DATABASE IF NOT EXISTS `".$data['dbname']."`;")){
				return json(array('code'=>-1, 'msg'=>'成功连接数据库，但是指定的数据库不存在并且无法自动创建，请先通过其他方式建立数据库！'));
			}
			mysqli_select_db($this->links,$data['dbname']);
		}
		$query = mysqli_query($this->links,"SELECT COUNT(*) as nums FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='".$data['dbname']."' AND TABLE_NAME='".$data['tablepre']."config'");
		$row=mysqli_fetch_row($query);
		if($row[0] > 0){
			return json(array('code'=>-1, 'msg'=>'您已经安装RPCMS，请手动删除所有数据表后再安装'));
		}
		$installSql=CMSPATH . '/data/defend/sql.sql';
		if(!file_exists($installSql)){
			return json(array('code'=>-1, 'msg'=>'安装的数据库文件丢失'));
		}
		$sql = @file_get_contents($installSql);
		$this->_sql_execute($sql,$data['tablepre']);
		$this->_sql_execute("INSERT INTO ".$data['tablepre']."user (`username`,`password`,`nickname`,`role`,`status`) VALUES ('".$data['username']."','".psw($data['userpsw'])."','".$data['username']."','admin','0')");
		$this->_sql_execute("INSERT INTO ".$data['tablepre']."config (`cname`,`cvalue`) VALUES ('webconfig','a:24:{s:9:\"isDevelop\";i:0;s:9:\"webStatus\";i:0;s:9:\"cateAlias\";i:0;s:8:\"logAlias\";i:0;s:9:\"pageAlias\";i:0;s:8:\"tagAlias\";i:0;s:13:\"commentStatus\";i:0;s:12:\"commentCheck\";i:0;s:9:\"commentCN\";i:0;s:12:\"commentVcode\";i:0;s:7:\"webName\";s:0:\"\";s:7:\"keyword\";s:0:\"\";s:11:\"description\";s:0:\"\";s:3:\"icp\";s:0:\"\";s:9:\"totalCode\";s:0:\"\";s:9:\"closeText\";s:0:\"\";s:8:\"pagesize\";i:10;s:9:\"fileTypes\";s:53:\"rar,zip,gz,gif,jpg,jpeg,png,txt,pdf,docx,doc,xls,xlsx\";s:8:\"fileSize\";i:20;s:11:\"attImgWitch\";i:400;s:12:\"attImgHeight\";i:400;s:11:\"commentSort\";s:3:\"new\";s:11:\"commentPage\";i:3;s:15:\"commentInterval\";i:30;}'),('template','defaults'),('temp_defaults', 'a:3:{s:6:\"layout\";s:5:\"right\";s:8:\"appWidth\";s:4:\"1000\";s:7:\"bgColor\";s:7:\"#f1f1f1\";}');");
		$this->_sql_execute("INSERT INTO ".$data['tablepre']."links (`sitename`,`sitedesc`,`siteurl`) VALUES ('RPCMS', 'RPCMS内容管理系统', 'http://www.rpcms.cn');");
		$data['baseUrl']=$App->baseUrl;
		if($this->setConfig($data)){
			\rp\Config::set(include CMSPATH . '/config.php');
			Cache::update();
			$lock=@file_put_contents(CMSPATH .'/data/install.lock', 'installed');
			return json(array('code'=>200, 'msg'=>'success', 'data'=>$data['baseUrl']));
		}
		return json(array('code'=>-1, 'msg'=>'config.php写入失败，请确保文件存在并拥有读写权限'));
	}
	
	private function setConfig($data){
		$app_default_path=\rp\Config::get('app_default_path');
		$config="<?php
	return array(
		//数据库信息
		'db'=>array(
			'hostname'=>'".$data['dbhost']."',
			'username'=>'".$data['dbuser']."',
			'password'=>'".$data['dbpsw']."',
			'database'=>'".$data['dbname']."',
			'prefix'=>'".$data['tablepre']."',
			'charset'=>'utf8',
		),
		//cms安装目录，适用于子文件适用
		'app_default_path'       => '".$app_default_path."',
		// 域名根，如：rpcms.com
		'domain_root'        => '',
		//二级域名绑定关系
		'domain_root_rules'        => array(),
		//默认跳转地址，当没有referer的时候
		'app_default_referer'    => '".$data['baseUrl']."',
		//数据加密key
		'app_key'                => 'rpcms',
		//默认module
		'default_module'         => 'index',
		//默认controller
		'default_controller'     => 'Index',
		//默认action
		'default_action'         => 'index',
		//禁止通过URL访问的module，多个用“,”隔开
		'deny_module'			 => '',
		//自定义后台地址，请勿和伪静态命名和二级域名重复，否则可能会被规则覆盖
		'diy_admin'        		 => '".$data['diyname']."',
		//url后缀
		'url_html_suffix'        => 'html',
		//是否缓存模板，当适用模板标签的时候必须开启
		'tpl_cache'     => true,
		//模板禁用函数
		'tpl_deny_func_list'     => 'echo,exit',
		//验证码
		'captha_style_width'     => 90,
		'captha_style_height'    => 30,
		//默认启用的hook，请勿修改
		'default_hook'=>array(
			'admin_left_menu'=>[],
			'admin_top_menu'=>[],
		),
	);";
		$configFile = CMSPATH.'/config.php';
		return @file_put_contents($configFile, $config);
	}
	
	private function _sql_execute($sql,$tablepre = '',$default = 'me_') {
		$sqls = $this->_sql_split($sql,$tablepre,$default);
		if(is_array($sqls)){
			foreach($sqls as $sql){
				if(trim($sql) != ''){
					mysqli_query($this->links,$sql);
				}
			}
		}else{
			mysqli_query($this->links,$sqls);
		}
		return true;
	}
	private function _sql_split($sql,$tablepre = '',$default='') {
		$tablepre = !empty($tablepre) ? $tablepre : $default;
		$sql = str_replace('%pre%', $tablepre, $sql);
		$sql = str_replace("\r", "\n", $sql);
		$ret = array();
		$num = 0;
		$queriesarray = explode(";\n", trim($sql));
		unset($sql);
		foreach($queriesarray as $query){
			$ret[$num] = '';
			$queries = explode("\n", trim($query));
			$queries = array_filter($queries);
			foreach($queries as $query){
				$str1 = substr($query, 0, 1);
				if($str1 != '#' && $str1 != '-') $ret[$num] .= $query;
			}
			$num++;
		}
		return $ret;
	}

}
