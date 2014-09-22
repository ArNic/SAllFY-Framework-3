<?
require_once('config.php');
$c_tpl=new c_tpl(null,"design.php",false,true);
include_once(PATH.TPL_PATH.'/base.php');

if(!$c_auth->exist){
}elseif(!$act||$act=='index'){
}

if(in_array($act,$defMod)) include_once(PATH.'/module/official/'.$act.'/controller/index.php');

if(isset($breadCump)){
	if(!isset($c_tpl->title)||!$c_tpl->title) $c_tpl->get('','title',$c_tools->bcMakeTitle(),4);
	$c_tpl->get('tplBase->breadCrumbs','breadCrumbs',$breadCump);
}