<?
global $c_log;
$c_log=new c_log();
class c_log{
    public function install(){
        global $c_db;

        $tbl='log';
        $field=$fieldOpt=$tableOpt=array();
        $field['id']=array('int(11)','NOT NULL','auto_increment');
        $field['ip']=array('varchar(255)','NOT NULL');
        $field['event']=array('varchar(255)','NOT NULL');
        $field['browser']=array('text','NOT NULL');
        $field['address']=array('text','NOT NULL');
        $field['referer']=array('text','NOT NULL');
        $field['uid']=array('int(11)','NOT NULL');
        $field['dt']=array('bigint(20)','NOT NULL');
        $field['v_act']=array('varchar(255)','NOT NULL');
        $field['v_type']=array('varchar(255)','NOT NULL');
        $field['v_var']=array('varchar(255)','NOT NULL');
        $field['v_idobj']=array('varchar(255)','NOT NULL');
        $field['v_route']=array('varchar(255)','NOT NULL');
        $field['v_sub']=array('varchar(255)','NOT NULL');
        $field['v_idobjS']=array('varchar(255)','NOT NULL');
        $field['post']=array('text','NOT NULL');
        $field['get']=array('text','NOT NULL');

        $fieldOpt[]='PRIMARY KEY (`id`)';
        $tableOpt='ENGINE=MyISAM DEFAULT CHARSET=utf8';
        $c_db->tableInstall($tbl,$tableOpt,$field,$fieldOpt,PREFIX_CORE);
    }
			# id
			# IP
			# Браузер
			# Адресная строка
			# Реферер
			# ID пользователя
			# DT
			# $act
			# $type
			# $var
			# $idobj
			# $route
			# $sub
			# $idobjS
			# serialize POST
			# serialize GET

	/**
	 * Добавляет событие лога в БД
	 * @param $str название события
	 */
	function insert($str){
		global $c_db,$c_auth,$act,$type,$var,$idobj,$route,$sub,$idobjS;
		$array=array();
		$array['ip']=$_SERVER['REMOTE_ADDR'];        
		$array['event']=$str;        
		$array['browser']=$_SERVER['HTTP_USER_AGENT'];
		$array['address']=$_SERVER['REQUEST_URI'];         
		$array['referer']=$_SERVER['HTTP_REFERER'] ;       
		$array['uid']=@$c_auth->data->id;
		$array['dt']=time();                         
		$array['v_act']=$act;                            
		$array['v_type']=$type;                           
		$array['v_var']=$var;                            
		$array['v_idobj']=$idobj;                          
		$array['v_route']=$route;                          
		$array['v_sub']=$sub;                            
		$array['v_idobjS']=$idobjS;                         
		$array['post']=serialize($_POST);              
		$array['get']=serialize($_GET);
		$c_db->table='log';
		$c_db->insert($array,PREFIX_CORE);
	}
}
?>