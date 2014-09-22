<?

$modulePath=PATH.'/module/official/user';
include_once($modulePath.'/config.php');
include_once($modulePath.'/model/index.php');

$authArr=$c_auth->statusArr;

if(in_array(14,$authArr)){
	if(in_array(8,$authArr)&&$type=='admin'){
		include_once($modulePath.'/model/admin.php');
		include_once($modulePath.'/view/admin.php');
		include_once($modulePath.'/controller/admin.php');
	}else{
		include_once($modulePath.'/model/log.php');
		include_once($modulePath.'/view/log.php');
		include_once($modulePath.'/controller/log.php');
	}
}else{
	include($modulePath.'/model/unlog.php');
	include($modulePath.'/view/unlog.php');
	include($modulePath.'/controller/unlog.php');
}
?>