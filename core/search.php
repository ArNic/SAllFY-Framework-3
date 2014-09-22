<?
$c_tools->fw_error('c_search','c_db');
global $c_search;
$c_search=new c_search();
/**
 * Полнотекстовый поиск
 * Существует отдельная таблица, которая содержит чистый текст, для полнотекстового поиска без тегов и спецсимволов.
 * Определение владельца текста проводится при помощи 5-х параметров
 * <ul>
 * <li> База (base), является префикс таблиц модуля (например core_ или module_)
 * <li> Модуль (module), например news
 * <li> Подмодуль или интерфейс (submodule) - необязательно, например необходимо добавить в поиск некиэ админский элемент.
 * 	Часто используется в GaP структурах.
 * <li> Родитель (sub)
 * <li> Идентификатор записи (id).
 * </ul>
 * Дата, время, заголовок существуют для того, чтобы сразу же выводить записи.
 */
class c_search{
    var $table='search';
	function install(){
        global $c_db;
        $tbl=$this->table;
        $field=$fieldOpt=$tableOpt=array();
        $field['base']=array('varchar(20)','NOT NULL'); # prefix
        $field['module']=array('varchar(30)','NOT NULL'); # module
        $field['submodule']=array('varchar(30)','NOT NULL'); # interface
        $field['id']=array('int(11)','NOT NULL'); # id
        $field['title']=array('varchar(255)','NOT NULL'); # title
        $field['text']=array('longtext','NOT NULL'); # fulltext
        $field['sub']=array('int(11)','NOT NULL'); # sub
        $field['date']=array('date','NOT NULL'); #date
        $field['time']=array('time','NOT NULL'); #time
        $fieldOpt[]='PRIMARY KEY (`base`,`module`,`submodule`,`id`)';
        $fieldOpt[]='FULLTEXT KEY `text` (`text`,`title`)';
        $tableOpt='ENGINE=MyISAM DEFAULT CHARSET=utf8 PACK_KEYS=0 CHARSET=utf8';
        $c_db->tableInstall($tbl,$tableOpt,$field,$fieldOpt,PREFIX_CORE);
	}
	/**
	 * Добавляет запись в индекс поиска
	 *
	 * @param  $id идетификатор строки модуля
	 * @param  $module модуль записываемой строки
	 * @param  $text текст, предназначенный для поиска
	 * @param string $title заголовок (для вывода и поиска)
	 * @param int $sub категория (если есть)
	 * @param int $date дата (рекомендуется указывать дату записи модуля)
	 * @param int $time время (рекомендуется указывать время записи модуля)
	 * @param string $submodule подмодуль (редкое явление)
	 * @param string $base префикс базы модуля
	 * @return void
	 */
	function lineSend($id,$module,$text,$title='',$sub=0,$date=0,$time=0,$submodule='',$base=PREFIX){
        global $c_db;
        $c_db->table=$this->table;
        $text=stripslashes($text);
        $text=strip_tags($text);
        $text=preg_replace("!(\&.*?)\;!",' ',$text);
        $text=preg_replace("![^a-zа-я0-9\s]!iu",' ',$text);
        $title=stripslashes($title);
        $title=strip_tags($title);
        $title=preg_replace("!(\&.*?)\;!",' ',$title);
        $title=preg_replace("![^a-zа-я0-9\s]!iu",' ',$title);
        if(!$date) $date=date('Y-m-d');
        if(!$time) $time=date('H:i:s');
        $insert=$update=array();
        $insert['base']=$base;
        $insert['module']=$module;
        $insert['submodule']=$submodule;
        $insert['id']=$id;
        $insert['sub']=$update['sub']=$sub;
        $insert['date']=$update['date']=$date;
        $insert['time']=$update['time']=$time;
        $insert['text']=$update['text']=$text;
        $insert['title']=$update['title']=$title;
        $c_db->insupd($insert,$update,PREFIX_CORE);
	}
	/**
	 * Удаляет запись из индекса поиска
	 *
	 * @param  $id id строки модуля
	 * @param  $module модуль удаляемой строки
	 * @param string $submodule подмодуль (редкое явление)
	 * @param string $base префикс базы модуля
	 * @return void
	 */
	function lineRemove($id,$module,$submodule='',$base=PREFIX){
		global $c_db;
		$c_db->table=$this->table;
        if($id){
            $where['base']=$base;
            $where['module']=$module;
            $where['submodule']=$submodule;
            $where['id']=$id;
            $c_db->delete($where,1,PREFIX_CORE);
        }
	}
	/**
	 * Возвращает результаты поиска с пагинацией
	 *
	 * @param  $str строка поиска
	 * @param array $where дополнительные условия отбора (удобно для локализации поиска)
	 * @param int $pg страница
	 * @param int $size число записей на странице
	 * @param bool $full true - искать фразу целиком (внимание! ищет через LIKE), false - просто слова указанные в поиске
	 * @param int $maxsize минимальное число символов. Если $full=false
	 * @return array|object
	 */
	function get($str,$where=array(),$pg=0,$size=20,$full=false,$maxsize=3){
		global $c_db;
		$c_db->table=$this->table;
		$where=$c_db->where($where);
		if($where){
			substr($where,0,6);
			$where=" && ".$where;
		}
		$str=preg_replace("![^a-zа-я0-9+-<>()\s]!iu",'',$str);

		if(!$full){
			# ограничиваем поиск 3 символами
			$str=explode(' ',$str);
			foreach($str as $k=>$v){
				if(mb_strlen($v)<4) unset($str[$k]);
			}
			$str=implode(' ',$str);
			# конец
			$str=str_replace(' ','*',$str);
			$str.='*';
			$where="MATCH (`title`,`text`) AGAINST ('$str' IN BOOLEAN MODE) $where";
		}else{
			if(mb_strlen($str)>$maxsize) $where="`title` LIKE '%".$str."%' OR `text` LIKE '%".$str."%'";
			else $where="FALSE";
		}

		return $c_db->listing($where,$size,$pg*$size,'-`date`,-`time`','*','',PREFIX_CORE);
	}
}
?>