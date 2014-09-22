<?

global $m_user_admin_c;
$m_user_admin_c=new m_user_admin_c($var,$idobj,$route,$sub,$filter,$pg,$modulePath);
class m_user_admin_c{
	var $var,$idobj,$route,$sub,$pg; # глобальные переменные поведения
	function __construct($var,$idobj,$route,$sub,$filter,$pg,$modulePath){
		$this->var=$var;
		if(!$var&&$var!=='0'&&$var!==0)$this->var=$idobj;
		$this->route=$route;
		$this->filter=$filter;
		$this->sub=$sub;
		$this->pg=$pg;
		$this->idobj=$idobj=($idobj=='index')?false:(int)$idobj;

		include_once($modulePath.'/view/admin.php');
		include_once($modulePath.'/model/admin.php');

		global $c_tools;
		$c_tools->bcAdd(M_USER_NAME,'/user/admin/');
		
		if($route)$this->line();
		else $this->listing();
	}
	function line(){
		global $m_user_admin_m,$c_tpl,$c_auth,$c_tools;
		$link='/user/admin/index.html';
		$err=false;
		if($this->route=='send'){
			$err=$m_user_admin_m->save($this->idobj);
			if(!$err) header('Location: '.$link);
		}
		if($this->route=='form'||$err){
			$obj=($this->idobj)?$m_user_admin_m->element($this->idobj):(object)array();
			$c_tools->bcAdd($c_tools->formHead('пользователя',tvar($obj->id),tvar($obj->title)));
			$c_tpl->get('m_user_admin_v->form','body',array($err,$obj),2);
		}elseif($this->idobj&&$this->route=='del'&&$c_auth->data->status=='admin'){
			$m_user_admin_m->del($this->idobj);
			header('Location: '.$link);
		}
	}
	function listing(){
		global $m_user_admin_m,$c_tpl;
		$array=$m_user_admin_m->listing($this->pg);
		$c_tpl->get('m_user_admin_v->listing','body',array($array),2);
	}
}
?>