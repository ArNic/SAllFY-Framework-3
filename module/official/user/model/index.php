<?php


global $m_user_index_m;
$m_user_index_m=new m_user_index_m();
class m_user_index_m{
    var $table='user';
    var $prefix=PREFIX;
    public function install(){
        global $c_db;
        $tbl=$this->table;
		$field=$fieldOpt=$tableOpt=array();
		$field['id']=array('int(11)','NOT NULL','auto_increment'); # идентификатор - системный
		$field['group']=array('varchar(255)','NOT NULL'); # к каким группам относится

		$fieldOpt[]='PRIMARY KEY (`id`)';
		$fieldOpt[]='FULLTEXT KEY `group` (`group`)';

		$tableOpt='ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 PACK_KEYS=0';
		$c_db->tableInstall($tbl,$tableOpt,$field,$fieldOpt);
    }
}