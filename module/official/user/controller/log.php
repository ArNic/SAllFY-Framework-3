<?
global $m_user_log_c;
$m_user_log_c=new m_user_log_c($modulePath);

class m_user_log_c{
	function __construct($modulePath){
		global $type;
		include_once($modulePath.'/view/log.php');
		include_once($modulePath.'/model/log.php');

		if($type=='logout') $this->logout();
		elseif($type=='login') header('Location: '.M_USER_LINK_USER.'/');
		else $this->profile();
	}
	function profile(){
		global $m_user_log_m,$c_tpl,$c_auth;
		$status=$m_user_log_m->profileF();
		if($status=='OK') header('Location: '.M_USER_LOCATION);
		$c_tpl->get('m_user_log_v->profile','body',array($c_auth->data,$status),2);
	}
	function logout(){
		global $c_auth;
		$c_auth->logout();
		header('Location: '.M_USER_LINK_USER.'/');
	}
}
?>