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
class Db{
	protected static $instance;
	private static $_mysqli;
	private static $prefix;
	private static $table;
	private $field = '*';
	private $join = '';
	private $where = array();
	private $limit = '';
	private $order = '';
	private $group = '';
	private $results = '';
	
	public function __construct(){
		
	}
	
	public static function instance(){
        if (is_null(self::$instance)) {
            self::$instance = self::connect();
        }
        return self::$instance;
    }
	
	public static function connect(){
        if (empty(self::$_mysqli)){
            $options = Config::get('db');
			self::$_mysqli = mysqli_init();
			self::$_mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);
			if(!self::$_mysqli->real_connect($options["hostname"], $options["username"], $options["password"], $options['database'])){
				die('Connect Error (' . self::$_mysqli->connect_errno . ') '. self::$_mysqli->connect_error());
			}
			self::$prefix=$options["prefix"];
			self::$_mysqli->set_charset($options["charset"]);
        }
        return new \rp\Db();
    }
	
	public static function name($table){
		$con=self::connect();
		self::$table=self::$prefix . $table;
		return $con;
	}
	
	public static function table($table){
		$con=self::connect();
		self::$table=$table;
		return $con;
	}
	
	public function field($field='*'){
		if(is_array($field)){
			$field=join(",",$field);
		}
		$this->field=$field;
		return $this;
	}
	
	public function alias($alias=''){
		!empty($alias) && self::$table.=" as ".$alias;
		return $this;
	}

	public function join($table, $condition = null, $type = 'left'){
		if(is_array($table) && count($table) != count($table,true)){
			foreach($table as $key=>$val){
				if(is_array($val) && !empty($val)){
					$jtype=(isset($val[2]) && !empty($val[2])) ? $val[2] : $type;
					$jointable = is_array($val[0]) ? $val[0][0].' '.$val[0][1] : (false !== strpos($val[0], '(') ? $val[0] : self::$prefix . $val[0]);
					$this->join.=" ".$jtype." join ". $jointable;
					!empty($val[1]) && $this->join.=" on ".$val[1];
				}
			}
		}else{
			if(is_array($table)){
				$jointable = $table[0].' '.$table[1];
			}else if(false !== strpos($table, '(')) {
				$jointable = $table;
            }else{
				$jointable = self::$prefix . $table;
			}
			
			$this->join.=" ".$type." join ". $jointable;
			!empty($condition) &&  $this->join.=" on ".$condition;
		}
		return $this;
	}

	public function where($where){
		if(empty($where)) return $this;
		if(is_array($where)){
			$whereStr=array();
			foreach($where as $k=>$v){
				if((false === strpos($k, '(') || 0 !== strpos($k, '(')) && (strpos($k, '&') || strpos($k, '|'))){
					$k='('.$k.')';
				}
				$kn=explode('#',str_replace(array('&','|','(',')'),array('#','#','',''),$k));
				$kn = array_map(function($item){return '/'.$item.'/';}, $kn);
				$k=str_replace(array('&','|'),array(' and ',' or '),$k);
				if(is_array($v)){
					$v=count($v) == count($v,1) ? $this->parseItem($v) : array_map(array($this,'buildValue'), $v);
				}else{
					$v=$this->parseValue($v);
				}
				$whereStr[]=preg_replace($kn,$v,$k);
			}
			$this->where=array_merge($this->where,$whereStr);
		}else{
			$this->where[]=$where;
		}
		return $this;
	}
	
	private function buildWhere(){
		return !empty($this->where) ? " where ".join(" and ",$this->where) : "";
	}
	
	private function buildValue($sv){
		return is_array($sv) ? $this->parseItem($sv) : $this->parseValue($sv);
	}
	
	private function parseItem($value){
		if(in_array(strtolower($value[0]),array('=','<>','!=','>','<','>=','<=','like','not like','in','not in','between','not between','exists','not exists','exp','find_in_set'))){
			switch($value[0]){
				case 'in':
				case 'not in':
				case 'exists':
				case 'not exists':
					return '$0 '.$value[0].'('.$value[1].')';
					break;
				case 'between':
				case 'not between':
					return '($0 '.$value[0].' '.$this->escapeString($value[1]).' and '.$this->escapeString($value[2]).')';
					break;
				case 'exp':
					return "($0 regexp '".$this->escapeString($value[1])."')";
					break;
				case 'find_in_set':
					return "find_in_set('".$this->escapeString($value[1])."',$0)";
				default:
					return count($value) > 1 ? "$0 ".$value[0]." '".$this->escapeString($value[1])."'" : "$0 = '".$this->escapeString($value[0])."'";
			}
		}else{
			$value = array_map(array($this,'parseValue'), $value);
		}
		return $value;
	}

	private function parseValue($value){
		return '$0 '.(in_array(strtolower($value),array('null','not null')) ? 'is '.$value : "= '".$this->escapeString($value)."'");
	}
	
	
	public function limit($limit){
		!empty($limit) && $this->limit=" limit ".$limit;
		return $this;
	}
	
	public function order($order,$by="desc"){
		if(is_array($order)){
			$strs=array();
			foreach($order as $k=>$v){
				$strs[]=$k." ".$v;
			}
			$order=join(" , ",$strs);
			$this->order=" order by ".$order;
		}else{
			!empty($order) && $this->order=" order by ".$order." ".$by;
		}
		return $this;
	}
	
	public function group($group){
		!empty($group) && $this->group=" group by ".$group;
		return $this;
	}
	
	public function find($type="assoc"){
		$sql="select ".$this->field." from ".self::$table.$this->join.$this->buildWhere().$this->group.$this->order.$this->limit;
		$this->results=$this->execute($sql);
		$res=$this->result($type);
		$this->_reset_sql();
		return $res;
	}
	
	public function count($key="*"){
		$sql="select count(".$key.") as me_count from ".self::$table.$this->join.$this->buildWhere().$this->group.$this->order;
		$this->results=$this->execute($sql);
		$res=$this->result();
		$this->_reset_sql();
		return $res["me_count"];
	}
	
	public function sum($field){
		$sql="select sum(".$field.") as me_sum from ".self::$table.$this->join.$this->buildWhere().$this->group.$this->order;
		$this->results=$this->execute($sql);
		$res=$this->result();
		$this->_reset_sql();
		return $res["me_sum"];
	}
	
	public function select($type="assoc"){
		$sql="select ".$this->field." from ".self::$table.$this->join.$this->buildWhere().$this->group.$this->order.$this->limit;
		$this->results=$this->execute($sql);	
		$res=$this->result($type,"all");
		$this->_reset_sql();
		return $res;
	}
	
	public function query($sql){
		$this->results=$this->execute($sql);
		$this->_reset_sql();
		return $this;
	}
	
	public function result($type="assoc",$n="one"){
		$res=array();
		if(!$this->results) return;
		if($n=="one"){
			switch($type){
				case "row":$res=$this->results->fetch_row();break;
				case "assoc":$res=$this->results->fetch_assoc();break;
				case "array":$res=$this->results->fetch_array();break;
				case "object":$res=$this->results->fetch_object();break;
				case "num":$res=$this->results->num_rows;break;
				default :$res=$this->results->fetch_assoc();break;
			}
		}else{
			switch($type){
				case "row":
					while($row=$this->results->fetch_row()){
						$res[]=$row;
					}
					break;
				case "assoc":
					while($row=$this->results->fetch_assoc()){
						$res[]=$row;
					}
					break;
				case "array":
					while($row=$this->results->fetch_array()){
						$res[]=$row;
					}
					break;
				case "object":
					while($row=$this->results->fetch_object()){
						$res[]=$row;
					}
					break;
				case "all":$res=$this->results->fetch_all();break;
				case "num":$res=$this->results->num_rows;break;
				default:
					while($row=$this->results->fetch_assoc())
						{$res[]=$row;}
					break;
			}
		}
		$this->results->free();
		return $res;
	}

	public function insert($data=array()){
		if(count($data) == count($data, 1)){
			$datakey=$data;
			$dataval=array($data);
		}else{
			$datakey=$data[0];
			$dataval=$data;
		}
		$key_arr=array_keys($datakey);
		$val_arr=array();
		foreach($dataval as $k=>$v){
			if(!is_array($v)) continue;
			$val_arr[]="('".join("','",$v)."')";
		}
		$key="(`".join("`,`",$key_arr)."`)";
		$vals=join(",",$val_arr).";";
		$sql="insert into ".self::$table.$key." values".$vals;
		$this->execute($sql);
		$this->_reset_sql();
		return !empty($this->insert_id()) ? $this->insert_id() : $this->affected_rows();
	}
	
	public function update($data=array()){
		$strs=array();
		foreach($data as $k=>$v){
			$strs[]=$v === NULL  ? "`".$k."` = NULL" : "`".$k."` ='".$v."'";
		}
		$updata=join(" , ",$strs);
		$sql="update ".self::$table." SET ".$updata.$this->buildWhere();
		$this->_reset_sql();
		return $this->execute($sql);
	}
	
	public function setInc($field,$val=1){
		$sql="update ".self::$table." SET `".$field."`=".$field."+".$val." ".$this->buildWhere();
		$this->_reset_sql();
		return $this->execute($sql);
	}
	
	public function setDec($field,$val=1){
		$sql="update ".self::$table." SET `".$field."`=".$field."-".$val." ".$this->buildWhere();
		$this->_reset_sql();
		return $this->execute($sql);
	}
	
	public function dele(){
		$sql="delete from ".self::$table.$this->buildWhere().$this->order.$this->limit;
		$this->_reset_sql();
		return $this->execute($sql);
	}
	
	
	public function insert_id(){
		return self::$_mysqli->insert_id;
	}
	
	public function affected_rows(){
		return self::$_mysqli->affected_rows;
	}
	
	public function server_info(){
		return self::$_mysqli->server_info;
	}
	
	public function server_version(){
		return self::$_mysqli->server_version;
	}
	
	protected function _reset_sql(){
		self::$table='';
		$this->field = '*';
		$this->join = '';
		$this->where = array();
		$this->limit = '';
		$this->order = '';
		$this->group = '';
		$this->results = '';
	}
	
	protected function execute($sql){
		$res=self::$_mysqli->query($sql);
		if(!$res){$this->error(self::$_mysqli->error_list,$sql);}
		return $res;
	}
	protected function escapeString($str){
        return addslashes($str);
    }
	protected function error($msg,$sql=""){
		if(!Config::get('webConfig.isDevelop')){
			global $App;
			echo $App->isAjax() ? json(array('code'=>-1,'msg'=>'SQL执行错误')) : rpMsg('SQL执行错误');exit;
		}else{
			$error="";
			if(is_array($msg)){
				foreach($msg as $k=>$v){
					$error.="Error Number:".$v["errno"]."<br>".$v['error'];
				}
			}else{
				$error.=$msg;
			}
			!empty($sql) && $error.="<p>".$sql."</p>";
			$heading="A Database Error Occurred";
			$message=array();
			$trace = debug_backtrace();
			foreach ($trace as $call){
				if (isset($call['file'], $call['class'])){
					if (DIRECTORY_SEPARATOR !== '/'){
						$call['file'] = str_replace('\\', '/', $call['file']);
					}
					$message[] = 'Filename: '.$call['file'].'    On Line Number: '.$call['line'];
				}
			}
			$message = '<p>'.(is_array($message) ? implode('</p><p>', $message) : $message).'</p>';
			echo "<h3>".$heading."</h3>";
			echo "<div style='border: 1px solid #ccc;padding: 10px;color: #313131;font-size: 15px;'>".$error."</div><div style='font-size: 13px;color: #444444;line-height: 13px;'>".$message."</div>";
			exit(8);
		}
	}
	
}