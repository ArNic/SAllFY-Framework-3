<?
global $m_user_unlog_c;
$m_user_unlog_c=new m_user_unlog_c($type,$var,$idobj,$route,$sub,$filter,$pg,$modulePath);

class m_user_unlog_c{
	function __construct($type,$var,$idobj,$route,$sub,$filter,$pg,$modulePath){
		include_once($modulePath.'/view/log.php');
		include_once($modulePath.'/model/log.php');

		if($type=='register') $this->register();
		elseif($type=='activate') $this->activate();
		elseif($type=='restore') $this->restore();
		elseif($type=='logout') header('Location: '.M_USER_LINK_USER.'/');
		else $this->login();
	}
	function login(){
		global $m_user_unlog_m,$c_tpl;
		$status=$m_user_unlog_m->login();
		if($status=='OK') header('Location: '.M_USER_LINK_USER.'/');
		else $c_tpl->get('m_user_unlog_v->login','body',$status);
	}
	function register(){
		global $m_user_unlog_m,$c_tpl,$c_tools;
		$status=$m_user_unlog_m->register();
		if($status[1]=='OK')header('Location: /');
		elseif($status=='REG')header('Location: '.M_USER_LINK_USER.'/profile.html');
		else{
			$c_tools->bcAdd('Регистрация на проекте');
			$c_tpl->get('m_user_unlog_v->register','body',$status,2);
		}
	}
	function activate(){
		global $m_user_unlog_m,$c_tpl;
	    list($status,$mail)=$m_user_unlog_m->activate();
		if($status=='OK') header('Location: '.M_USER_LINK_USER.'/profile.html');
		else $c_tpl->get('m_user_unlog_v->activate','body',array($mail,$status),2);
	}
	function restore(){
		global $m_user_unlog_m,$c_tpl;
		$status=$m_user_unlog_m->restore();
		if($status=='OK') $c_tpl->get('m_user_unlog_v->restoreS','body');
		else $c_tpl->get('m_user_unlog_v->restore','body');
	}
}
?>