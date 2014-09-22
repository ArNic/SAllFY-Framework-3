<?
/**
 * Пример использования корневого контроллера модуля
 * */
$modulePath=PATH.'/module/official/user';
include_once($modulePath.'/config.php');
include_once($modulePath.'/model/index.php');
$m_user_index_m->install();
?>