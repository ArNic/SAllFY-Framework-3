<?
/**
 * Пример использования корневого контроллера модуля
 * */
$modulePath=PATH.'/module/official/example';
include_once($modulePath.'/config.php');
include_once($modulePath.'/model/index.php');

if($act=='example'){
	include_once($modulePath.'/controller/main.php');
}
?>