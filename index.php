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
error_reporting(0);
session_start();
date_default_timezone_set('PRC');

defined('CMSPATH') or define('CMSPATH', dirname(__FILE__));
defined('LIBPATH') or define('LIBPATH', CMSPATH . '/system');
defined('PLUGINPATH') or define('PLUGINPATH', CMSPATH . '/plugin');
defined('TMPPATH') or define('TMPPATH', CMSPATH . '/templates');
defined('UPLOADPATH') or define('UPLOADPATH',  'uploads');
defined('RPCMS_VERSION') or define('RPCMS_VERSION',  '1.5.2');
include_once LIBPATH . '/Common.fun.php';
spl_autoload_register("autoLoadClass");
doStrslashes();
\rp\Config::set(include_once CMSPATH . '/config.php');
$App=new \rp\App();
$App->run();
