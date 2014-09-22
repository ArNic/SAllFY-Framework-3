<?php

global $m_example_index_m;
$m_example_index_m=new m_exapmle_index_m();
/**
 * Пример класса для главных функций модуля (в перспективе "раздела").
 * */
class m_example_index_m{
	var $table=M_EXAMLE_TYPE;
	var $prefix=PREFIX;
	/**
	 * Пример файла установки.
	 *
	 * В данном файле нет ничего кроме установки БД. Но, можно также включить и установку неких файлов.
	 * Желательно использовать для установки только эту функцию, т.к. именно она будет исполняться (в перспективе) в мастере установок.
	 *
	 */
	function install(){
		global $c_db;
		$tbl=$this->table;
		$field=$fieldOpt=$tableOpt=array();
		$field['id']=array('int(11)','NOT NULL','auto_increment'); # идентификатор - системный
		$field['text']=array('varchar(10)','NOT NULL',''); # некое поле
		$fieldOpt[]='PRIMARY KEY (`id`)';
		$tableOpt='ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 PACK_KEYS=0';
		$c_db->tableInstall($tbl,$tableOpt,$field,$fieldOpt);
	}
}