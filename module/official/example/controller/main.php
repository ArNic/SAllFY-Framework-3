<?
global $m_example_main_c;
$m_example_main_c=new m_example_main_c($var,$idobj,$route,$sub,$filter,$pg,$modulePath);

class m_example_main_c{
	var $var,$idobj,$route,$sub,$pg; # глобальные переменные поведения
	function __construct($var,$idobj,$route,$sub,$filter,$pg,$modulePath){
		$this->var=$var;
		if(!$var&&$var!=='0'&&$var!==0)$this->var=$idobj;
		$this->idobj=$idobj;
		$this->route=$route;
		$this->filter=$filter;
		$this->sub=$sub;
		$this->pg=$pg;
		$idobj=(int)$idobj;

		include_once($modulePath.'/view/main.php');
		include_once($modulePath.'/model/main.php');

		$this->show();
	}
	function show(){
		global $c_tpl,$m_example_index_m;
		$text=$m_example_index_m->example();
		$c_tpl->get('m_example_main_v->show','body',$text);
	}
}
?>