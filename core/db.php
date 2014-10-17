<?/**
function checkLoad(){
	$load=sys_getloadavg();
	if($load[0]>15){
		print('Извините сервер перегружен. Обновите страницу.');
		exit;
	}
}
checkLoad();
*/
$c_tools->fw_error('c_db',false,'HOST|USER|PASS|DBNAME');
global $c_db;
$c_db=new c_db(HOST,USER,PASS,DBNAME);
/**
 * Взаимодействие с базой данных.
 */
class c_db{
	/**
	 * Конструктор.
	 * Отчечает за автоматическое соединение с БД и его настройки.
	 * @param string $host хост
	 * @param string $user пользователь
	 * @param string $pas пароль
	 * @param string $bd база данных
	 */
	function __construct($host='',$user='',$pas='',$bd=''){
		$this->db_id=mysql_connect($host,$user,$pas) or die('Максимальное число посетителей, зайдите пожалуйста позже.');
		mysql_select_db($bd,$this->db_id) or die("db: '$bd' select error");
		mysql_query('SET NAMES utf8');
   	}
	/**
	 * Деструктор.
	 * Отвечает за закрытие подключения к БД, вывод отчета (если настроен)
	 */
	function __destruct(){
		$this->close();
		if($this->SumQuerys){
			if($this->shQ=='display'){print_r($this->SumQuerys);}
			elseif($this->shQ=='file'){file_put_contents(PATH.'/mysql.txt',print_r($this->SumQuerys,true));}
			elseif($this->shQ=='firebug'){
				$itog=(object)array('time'=>0,'sum'=>0,'line'=>array(),'calc'=>array(),'alert'=>false);
				$summ=0;
				$limit='> '.str_replace('.',',',$this->atime).' сек';
				foreach($this->SumQuerys as $k=>$v){
					$tbl[]=(object)array($limit=>(($v->alert)?'+'.($v->time-$this->atime):0),'время'=>$v->time,'запрос'=>$v->query);
					$itog->time+=$v->time;
					$itog->sum++;
					if(!$itog->alert&&$v->alert){$itog->alert=true;}
				}
				$queryPerSec=(int)(1/$itog->time);
				$itog->line[]='MySQL запросы страницы';
				$itog->line[]='Запросов:'.$itog->sum;
				$itog->line[]='Время:'.$itog->time;
				if($itog->alert){$itog->line[]='(!) У вас есть медленные запросы';}
				$itog->calc[]='а~'.$queryPerSec.' запросов/сек';
				$itog->calc[]='~'.number_format($queryPerSec*60,0,'.',' ').' запросов/мин';
				$itog->calc[]='~'.number_format($queryPerSec*60*60,0,'.',' ').' запросов/час';
				$itog->calc[]='~'.number_format($queryPerSec*60*60*24,0,'.',' ').' запросов/сутки';
				$content='';
				$content.='<script>';
				$content.='var firebugconsoletable='.json_encode($tbl).';';
				$content.='console.group("'.implode(' | ',$itog->line).'");';
				$content.='console.table(firebugconsoletable);';
				if(count($itog->calc)>0){
					$content.='console.groupCollapsed("Дополнительные расчеты");';
					foreach($itog->calc as $k=>$v){
						$content.='console.info("'.$v.'");';
					}
					$content.='console.groupEnd();';
				}
				$content.='console.groupEnd();';
				if(isset($this->error_alert)&&$this->error_alert)$content.='alert("Ошибки в БД");';
				$content.='</script>';
				print $content;
			}
		}
	}
	/**
	 * @name Основные методы и переменные
	 * @{
	 */
		/**
		 * Ссылка на соединение БД (идентифиактор).
		 * @var #Fmysql_connect|bool|?
		 */
		public $db_id=false;
		/**
		 * Ссылка на запрос (Идентифиактор). Используется для получения данных о запросе или его раезультате.
		 * @var null
		 */
		public $query_id=NULL;
		/**
		 * Описание ошибки СУБД. Если при использовании методов данного класса запрос к бд выполнился с ошибкой, сюда будет записано описание ошибки, возвращаемое СУБД.
		 * @var bool
		 */
		public $mysql_error=FALSE;
		/**
		 * Номер ошибки СУБД. Если при использовании методов данного класса запрос к бд выполнился с ошибкой, сюда будет записан номер ошибки, возвращаемый СУБД.
		 * @var null
		 */
		public $mysql_error_num=NULL;
		/**
		 * Получение одной записи запроса в виде числового массива.
		 * @param string $query запрос
		 * @return array числовой массив данных
		 */
		function qrow($query){
			$this->query($query);
			return mysql_fetch_row($this->query_id);
		}
		/**
		 * Получение очередной записи, вызванного до этого запроса, в виде ассоциативного массива.
		 * @param string $query_id - идентификатор записи (по умолчанию последнего запроса)
		 * @return array ассоциативный массив
		 */
		function get_row($query_id = ''){
			if ($query_id == ''){$query_id = $this->query_id;}
			return mysql_fetch_assoc($query_id);
		}
		/**
		 * Получение очередной записи, вызванного до этого запроса, в виде числового массива.
		 * @param string $query_id - идентификатор записи (по умолчанию последнего запроса)
		 * @return array числовой массив
		 */
		function get_array($query_id = ''){
			if ($query_id == ''){$query_id = $this->query_id;}
			return mysql_fetch_array($query_id);
		}
		/**
		 * Получение очередной записи, вызванного до этого запроса, в виде объекта.
		 * @param string $query_id - идентификатор записи (по умолчанию последнего запроса)
		 * @return object
		 */
		function fetch($query_id = ''){
			if ($query_id == ''){$query_id = $this->query_id;}
			return $d=@mysql_fetch_object($query_id);
		}
		/**
		 * Получить все записи в виде массива объектов, вызванного до этого запроса.
		 * @param string $field указать, если надо возвратить только одно поле записей, а не объект
		 * @return array
		 */
		function fetchAll($field=''){
			$arr=array();
			while ($d=$this->fetch($this->query_id)){
				if ($field == ''){$arr[] = $d;}
				else{$arr[] = $d->$field;}
			}
			return @$arr;
		}
		/**
		 * Получить все записи запроса в виде массива объектов.
		 * @param $query запрос
		 * @return array
		 */
		function qAll($query){
			$this->query($query);
			return $this->fetchAll();
		}
		/**
		 * Получение очередной записи, вызванного до этого запроса, в виде объекта.
		 * @param string $query_id - идентификатор записи (по умолчанию последнего запроса)
		 * @return array
		 */
		function fetch_row($query_id=''){
		   if($query_id == ''){$query_id = $this->query_id;}
		   return @mysql_fetch_row($query_id);
	   }
		/**
		 * Получение одной записи запроса в виде объекта.
		 * @param string $query запрос
		 * @return object|stdClass
		 */
		function qfetch($query){
			$this->query($query);
			return @mysql_fetch_object($this->query_id);
		}
		/**
		 * Получение количества задействованных строк, вызванного до этого запроса SHOW или SELECT.
		 * @param string $query_id
		 * @return int
		 */
		function num_rows($query_id=''){
			if($query_id == ''){$query_id = $this->query_id;}
			return mysql_num_rows($query_id);
		}
		/**
		 * Получение количества задействованных строк, вызванного до этого запроса INSERT, UPDATE, REPLACE или DELETE.
		 * @return int
		 */
		function affected_rows(){
			return @mysql_affected_rows($this->db_id);
		}
		/**
		 * id добавленной записи.
		 * @return int
		 */
		function insert_id(){
			return mysql_insert_id($this->db_id);
		}
		/**
		 * Получить названия полей в виде массива, вызванного до этого запроса.
		 * @param string $query_id
		 * @return array
		 */
		function get_result_fields($query_id = ''){
			if($query_id == ''){$query_id = $this->query_id;}
			while ($field = mysql_fetch_field($query_id)){$fields[] = $field;}
			return $fields;
		}
		/**
		 * Освобождает память от результата запроса.
		 * Нуждается в вызове только в том случае, если вы всерьёз обеспокоены тем, сколько памяти используют ваши запросы к БД, возвращающие большое количество данных. Вся память, используемая для хранения этих данных автоматически очистится в конце работы скрипта.
		 * @param string $query_id
		 */
		function free( $query_id = '' )	{
			if ($query_id == ''){$query_id = $this->query_id;}
			@mysql_free_result($query_id);
		}
	/**
	 * @}
	 * @name Переменные для формирования отчета по запросам к БД
	 * @{
	 */
		/**
		 * Вывод отчета о запросах.
		 * false - ничего не выводить, 'firebug' - в файрбаг, 'file' - в файл, 'display' - на экран просто в виде print_r
		 * @var bool|string
		 */
		public $shQ=false;
		/**
		 * Содержание последнего запроса.
		 * @var null|string
		 */
		private $last_query=NULL;
		/**
		 * Список всех запросов, сформированных за время работы скрипта.
		 * Работает только при $ShQ!=false
		 * @var array
		 */
		private $SumQuerys=array();
		/**
		 * Суммарное время на все запросы.
		 * @var float
		 */
		private $vtime;
		/**
		 * Тревожная граница на исполнение запроса.
		 * Отмечает запрос как медленный, если запрос превышает указанный период.
		 * @var float
		 */
		private $atime=0.003; # Max alert time
		/**
		 * Статус наличия ошибочных запросов.
		 * Используется не только для вывода стандарнтного отчета, но и отчета об ошибках, который сохраняется в файл.
		 * @var int
		 */
		var $error=0;
		/**
		 * Добавляет ошибку в конец файла с данными о том, где она произошла.
		 * @param $error ошибка
		 * @param $error_num номер ошибки
		 * @param string $query запрос
		 */
		private function display_error($error, $error_num, $query = '')	{
			$e = "		------------------------------------------
			err: $error
			num: $error_num
			que: $query
			rem: $_SERVER[REMOTE_ADDR]
			http: $_SERVER[REQUEST_URI]
			------------------------------------------";
			$testfo = file_put_contents(PATH."/mysql_error.log", "$e \n",FILE_APPEND);
		}
	/**
	 * @}
	 * @name Конструирование запросов
	 * @{
	 */
		/**
		 * Запрос к СУБД.
		 * @param $query запрос
		 * @param bool $show_error выводить ли ошибку
		 * @return mixed ссылка на запрос или пустота
		 */
		public function query($query, $show_error=true){
			// ----------------------------------------------------------------------------
			$time = microtime(true);
			$this->last_query = $query;
			// ----------------------------------------------------------------------------
			if(!($this->query_id = mysql_query($query, $this->db_id))){
				$this->error=1;
				$this->error_alert=1;
				$this->mysql_error = mysql_error();
				$this->mysql_error_num=mysql_errno();
				if($show_error){$this->display_error($this->mysql_error, $this->mysql_error_num, $query);}
			}else{$this->error=0;}
			// ----------------------------------------------------------------------------
			if($this->shQ){
				$time = round(microtime(true)-$time,5);
				$alert=($time>$this->atime)?true:false;
				if($this->error)$time.=' << ERRROR >>'.$this->mysql_error;
				$this->vtime+=$time;
				$this->SumQuerys[]=(object) array('alert'=>$alert,'time'=>$time,'query'=>$query);
			}
			//------------------------------------------------------------------------------
			return $this->query_id;
		}
		/**
		 * Защищает запрос от инъекций.
		 * @param $source строка апроса или его кусок
		 * @return string защищенная строка
		 */
		public function safesql($source){
			if ($this->db_id){return @mysql_real_escape_string ($source);}
			else return @mysql_scape_string($source);
		}
		/**
		 * Закрыть текущее соединение с БД.
		 */
		public function close(){
        	@mysql_close($this->db_id);
    	}
		/**
		 * Проверка на наличие ошибки в запросе.
		 * @return bool true - ошибка есть | false - ошибки нет
		 */
		function error(){
			return($this->mysql_error!='')?true:false;
		}
	/**
	 * @}
	 * @name Функциональные методы и переменные конструктора запросов
	 * @{
	 */
		/**
		 * Имя таблицы.
		 * Определяет для какой таблицы генерировать запрос.
		 * Свойство необходимо для конструктора классов.
		 * @var string
		 */
		public $table; # constructor
		/**
		 * Вектор инициализации. Для того, чтобы расшифровать данные.
		 * @var bool|string
		 */
		public $iv=false;
		/**
		 * Формирует конструкцию WHERE ...
		 * @param  $where массив или строка условия <ul>
		 * <li>если получит строкой, то обработает только на безопасность</li>
		 * <li>если получит массивом, то будет работать по следующим паре ключ/значение</li>
		 * <ul>
		 * <li>'a'=>'1' -аналог- `a`='1'</li>
		 * <li>'!a'=>'1' - аналог `a`!='1'</li>
		 * <li>'a'=>array('1','text') -аналог- `a` in ('1','text')</li>
		 * <li>'!a'=>array('1','text') -аналог- `a` notin ('1','text')</li>
		 * <li>`'a|>='=>'1' -аналог- `a`>='1'</li>
		 * </ul></ul>
		 * @return string
		 */
		function where($where=null){
			if(is_array($where)&&count($where)>0){
				$arr=array();
				foreach ($where as $k => $v){
					$cond=explode('|',$k);
					if(count($cond)==1){
						$k=explode('!',$k);
						$not=(count($k)>1)?true:false;
						if(is_array($v)&&(count($v)>1||(count($v)==1&&strlen($v[0])>0))){
							$eq=($not)?'NOT IN':'IN';
							$arr[]="`".$k[0]."` $eq ('".implode("','",$v)."')";
						}elseif(is_string($v)||is_integer($v)){
							$eq=($not)?'!=':'=';
							$arr[]="`".$k[0]."` $eq '".$this->safesql($v)."'";
						}
					} else $arr[]="`".$cond[0]."` ".$cond[1]." '$v'";
				}
				if(count($arr)>0){$where="WHERE ".implode(" && ", $arr);}
				else $where='';
			}elseif(is_string($where) && $where)$where="WHERE $where";
			else $where='';
			return $where;
    	}
		/**
		 * Формирует конструкцию ORDER BY <red>внимание не безопасно</red>.
		 * @param  $arr массив или строка условия <ul>
		 * <li>если массив: ключ - имя поля, значение - сортировка (1 - по убыванию, 0 - по возрастанию)</li>
		 * <li>если строка: просто проверка на соответсвие</li>
		 * </ul>
		 * @return string
		 */
		function order($arr=null){
			$rez='';
			if(is_array($arr)&&count($arr)>0){
				$rez='ORDER BY ';
				foreach($arr as $k=>$v){$tmp[]='`'.$k.'` '.(($v==0)?'ASC':'DESC');}
				$rez.=implode(',',$tmp);
			}elseif(is_string($arr)&&$arr){$rez='ORDER BY '.$arr;}
			return $rez;
   		}
		/**
		 * Формирует конструкцию LIMIT
		 * @param int $start начало выборки
		 * @param int $limit сколько строк (если 0 то неограниченно)
		 * @return string
		 */
		function limit($start=0,$limit=0){
			$start=(int)$start;
			$limit=(int)$limit;
			$str='';
			if($limit){
				if($start)$str.=$start;
				$str.=(($start)?',':'').$limit;
				$str='LIMIT '.$str;
			}
			return $str;
		}
		/**
		 * Сформировать данные для запроса из массива.
		 * Если в записи есть поле, начинающееся с "crypt_", то запрос будет зашифрован
		 * @param $ar массив
		 * @param string $sep разделитель, которым будут собарны все элементы
		 * @param bool $split разбить на масссив из двух строк (поля и значения) или записать в одну строку
		 * @return array|string массив, если $split = true иначе строка
		 */
		function makeSQL($ar,$sep=',',$split=false){
			if(!$split){
				$str=array();
				foreach($ar as $k=>$v){
					$v=$this->safesql($v);
					if(strpos($k,'crypt_')===0){
						$v=$this->cryptEn($v,$this->iv);
						$this->iv=$this->safesql($v[1]);
						$v=base64_encode($v[0]);
					}
					$str[]="`$k`='".$v."'";
				}
				if($this->iv&&count($str)>0) $str[]="`IVC`='".$this->safesql($this->iv)."'";
				return implode($sep,$str);
			}else{
				$str=(object)array();
				foreach($ar as $k=>$v){
					$str->k[]="`$k`";
					$v=$this->safesql($v);
					if(strpos($k,'crypt_')===0){
						$v=$this->cryptEn($v,$this->iv);
						$this->iv=$v[1];
						$v=base64_encode($v[0]);
					}
					$str->v[]="'".$v."'";
				}
				if(count($str->k)>0&&$this->iv){
					$str->k[]="`IVC`";
					$str->v[]="'".$this->safesql($this->iv)."'";
				}
				return array(implode($sep,$str->k),implode($sep,$str->v));
			}
		}
		/**
		 * Сформировать строку данных для запроса с разделителем "&&"
		 * @param $ar массив данных
		 * @return array|string
		 */
		function makeSQLE($ar){
			return $this->makeSQL($ar," && ");
		}	/**
	 * @}
	 * @name Конструирование запросов
	 * @{
	 */
		/**
		 * Получить первую строку из БД.
		 * @param bool|array|string $where условие отбора
		 * @param string $field выбираемы поля
		 * @param string $prefix префик таблицы
		 * @return an|object
		 * @see where
		 */
		function show($where,$field='*',$prefix=PREFIX){
			$table=$this->table;
			return $this->qfetch("SELECT ".$field." FROM `".$prefix."$table` ".$this->where($where)." LIMIT 1");
    	}
		/**
		 * Формирование списка (по умолчанию постраничный вывод).
		 * @param bool|array|string $where условие отбора
		 * @param int $limit сколько записей выбрать (на странице)
		 * @param int $start первая запис в текущей странице
		 * @param bool $order сортировка
		 * @param string $fields получаемые поля
		 * @param bool $group группировка
		 * @param string $prefix префикс таблицы
		 * @param array $mod модификации массив где значения: <ul>
		 * <li>unpage - без постраничного вывода</li>
		 * <li>id - поле id для подстановки ключом массива списка (если как ключ - то значением будет имя поля для подстановки)</li>
		 * </ul>
		 * @return array|object <ul>
		 * <li>С пагинацией объект с ключами:
		 * <ul>
		 * <li>rez - массив выбранных записей</li>
		 * <li>parts - сколько всего страниц (исходя из количества выводимых записей на страницу)</li>
		 * <li>part - текущая часть</li>
		 * <li>found - всего найдено записей</li>
		 * <li>limit - сколько записей на странице</li>
		 * </ul>
		 * </li>
		 * <li>Без пагинации - просто массив с результатом выборки
		 * @see where, limit, order
		 */
		function listing($where=false,$limit=5,$start=0,$order=false,$fields='*',$group=false,$prefix=PREFIX,$mod=array()){
			$table=$this->table;
			$unpage=(in_array('unpage',$mod));
			if(array_key_exists('id',$mod))$id=$mod['id'];
			elseif(in_array('id',$mod)) $id='id';
			else $id=false;

			if(!$limit&&!$unpage){$limit=5;}
			if(!$start){$start=0;}
			if($group){$group='GROUP BY '.$group;}
			$arr=(!$unpage)?(object)array():array();
			$q="SELECT ";
			if(!$unpage) $q.="SQL_CALC_FOUND_ROWS ";
			$q.="$fields FROM `".$prefix."$table` ".$this->where($where)." $group ".$this->order($order)." ".$this->limit($start,$limit);
			$this->query($q);
			$i=0;
			if(!$unpage) $arr->rez=array();
			while($d=$this->fetch($this->query_id)){
				$key=($id)?$d->$id:$i++;
				if($unpage)$arr[$key]=$d;
				else $arr->rez[$key]=$d;
			}
			if(!$unpage){
				list($arr->found)=$this->qrow("SELECT FOUND_ROWS()");
				$arr->parts=ceil($arr->found/$limit);
				$arr->part=ceil(($limit+$start)/$limit);
				$arr->limit=$limit;
			}
			return $arr;
    	}
		/**
		 * Формирование списка без пагинации.
		 * @param bool|array|string $where условие отбора
		 * @param string $order сортировка
		 * @param int $limit сколько записей выбрать
		 * @param int $start с какой записи начинать
		 * @param string $fields какие поля выбрать
		 * @param string $prefix префикс таблицы
		 * @return array массив объектов
		 * @see where, limit, order
		 */
		function listingU($where=null,$order='',$limit=0,$start=0,$fields='*',$prefix=PREFIX){
			$table=$this->table;
			$limit=($limit)?"LIMIT $start,$limit":'';
			return $this->qAll("SELECT $fields FROM `".$prefix."$table` ".$this->where($where)." ".$this->order($order)." ".$limit);
    	}
		/**
		 * Формирование списка с ключом равным значению поля `id` строки.
		 * @param bool|array|string $where условие отбора
		 * @param string $order сортировка
		 * @param int $limit сколько записей выбрать
		 * @param int $start с какой записи начать
		 * @param string $fields какие поля выбрать
		 * @param string $prefix префикс таблицы
		 * @return array массив объектов
		 * @see where, limit, order
		 */
		function listingID($where=null,$order='',$limit=0,$start=0,$fields='*',$prefix=PREFIX){
			$table=$this->table;
			$limit=($limit)?"LIMIT $start,$limit":'';
			$this->query("SELECT $fields FROM `".$prefix."$table` ".$this->where($where)." ".$this->order($order)." ".$limit);
			while ($d=$this->fetch($this->query_id)){
				$arr[$d->id]=$d;
			}
			return $arr;
    	}
		/**
		 * Удаление записей <red>осторожно</red>.
		 * @param bool|array|string $where условия отбора для удаления
		 * @param int $limit лимит удаляемых записей <red>осторожно - "0" подразумевает удаление всех отобранных записей</red>
		 * @param string $prefix префикс таблицы
		 * @return int число обработанных записей
		 * @see where, limit
		 */
		function delete($where,$limit=1,$prefix=PREFIX){
			$table=$this->table;
			$limit=($limit)?"LIMIT $limit":'';
			$this->query("DELETE FROM `".$prefix."$table` ".$this->where($where)." $limit");
			return $this->affected_rows();
    	}
		/**
		 * Обновление записей.
		 * @param array $arr массив данных, где ключ - поле, значение - значение поля
		 * @param bool|array|string  $where уловия отбора
		 * @param string $prefix префикс таблицы
		 * @return int число обработанных записей
		 * @see makeSQL, where, limit
		 */
		function update($arr,$where=array(),$prefix=PREFIX){
			$table=$this->table;

			$this->query("UPDATE `".$prefix."$table` SET ".$this->makeSQL($arr)." ".$this->where($where)."");
			return  $this->affected_rows();
    	}
		/**
		 * Добавление записи.
		 * @param array $arr массив данных, где ключ - поле, значение - значение поля
		 * @param string $prefix префикс таблицы
		 * @param bool $delayed отложенное добавление
		 * @return int число обработанных записей
		 * @see makeSQL
		 */
		function insert($arr,$prefix=PREFIX,$delayed=false){
			$table=$this->table;

			$this->query("INSERT ".(($delayed)?'DELAYED ':'')."INTO `".$prefix."$table` SET ".$this->makeSQL($arr));
			return $this->insert_id();
    	}
		/**
		 * Добавление или обновление записи.
		 * @param $ins данные для добавления
		 * @param $upd тоже что и $ins, только без ключей
		 * @param string $prefix префикс таблицы
		 * @return int число обработанных записей
		 * @see makeSQL
		 */
		function insupd($ins,$upd,$prefix=PREFIX){
			$table=$this->table;

			$arrIns=$this->makeSQL($ins,',',true);
			$arrUpd=$this->makeSQL($upd);
			$this->query("INSERT INTO `".$prefix."$table` ({$arrIns[0]}) VALUES ({$arrIns[1]}) ON DUPLICATE KEY UPDATE $arrUpd");
			return $this->insert_id();
    	}
	/**
	* @}
	* @name Операции со структурой таблицы
	* @{
	*/
		/**
		 * Ищет таблицу в базе данных.
		 * @param $name имя искомой таблицы
		 * @param string $prefix префикс
		 * @return bool true - найдено | false - не найдено
		 */
		function tableSeek($name,$prefix=PREFIX){
			$row=$this->qrow("SHOW TABLES LIKE '".$prefix."$name'");
			return ($row[0])?true:false;
		}
		/**
		 * Создать таблицу.
		 * Создается таблица, на основании переданных параметров.
		 * @param $tbl таблица
		 * @param $tblOpt параметры таблицы, например кодировка.
		 * @param $field массив полей таблицы
		 * @param array $fieldOpt свойства полей таблицы
		 * @param string $prefix префикс
		 * @code
		 * $tbl='cat';
		 * $field=$fieldOpt=$tableOpt=array();
		 * $field['id']=array('int(11)','NOT NULL','auto_increment'); # идентификатор - системный
		 * $field['visible']=array('tinyint(1)','NOT NULL',''); # видимость новости 1-на сайте 0-скрыта
		 * $field['cpu']=array('varchar(255)','NOT NULL',''); # ЧПУ
		 *
		 * $field['header']=array('varchar(255)','NOT NULL',''); # заголовок категории
		 * $field['sub']=array('int(11)','NOT NULL',''); # id родительской категории
		 * $field['ncch_sub']=array('varchar(255)','NOT NULL',''); # название ролдительской категории
		 * $field['keyw']=array('text','NOT NULL',''); # ключевики страницы
		 * $field['descr']=array('text','NOT NULL',''); # description страницы
		 * $field['title']=array('varchar(255)','NOT NULL',''); # title страницы
		 *
		 * $fieldOpt[]='PRIMARY KEY (`id`)';
		 * $tableOpt='ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 PACK_KEYS=0';
		 * $c_db->tableInstall($tbl,$tableOpt,$field,$fieldOpt);
		 * @endcode
		 */
		function tableMake($tbl,$tblOpt,$field,$fieldOpt=array(),$prefix=PREFIX){
			$rezF=array();
			foreach($field as $k=>$v){
				$rezF[]='`'.$k.'` '.implode(' ',$v);
			}
			$rezF=array_merge($rezF,$fieldOpt);
			$rez="CREATE TABLE `".$prefix."$tbl` (".implode(',',$rezF).")".$tblOpt;
			$this->query($rez);
		}
		/**
		 * Обновить поля в таблице.
		 * @param $tbl таблица
		 * @param $field поля
		 * @param string $prefix префикс
		 */
		function tableChgFields($tbl,$field,$prefix=PREFIX){
			if(count($field)>0){
				$tbl='`'.$prefix.$tbl.'`';
				$fBaseTmp=$this->qAll("SHOW FIELDS FROM $tbl");
				foreach($fBaseTmp as $v){
					$k=$v->Field;
					if(!array_key_exists($k,$field)) $this->query("ALTER TABLE $tbl DROP `".$k."`");
					else $fBase[$k]=$v;
				}
				unset($fBaseTmp);
				foreach($field as $k=>$v){
					$q="ALTER TABLE $tbl ";
					$q.=(array_key_exists($k,$fBase))?"MODIFY":"ADD";
					$q.=" `".$k."` ".implode(' ',$field[$k]);
					$this->query($q);
				}
			}
		}
		/**
		 * Установка таблицы.
		 * Проверяется налиичие таблицы, если нет - создается, если есть - обновляется.
		 * @param $tblтаблица
		 * @param $tblOpt свойства таблицы
		 * @param $field поля
		 * @param array $fieldOpt свойства полей
		 * @param string $prefix префикс
		 */
		function tableInstall($tbl,$tblOpt,$field,$fieldOpt=array(),$prefix=PREFIX){
			if(!$this->tableSeek($tbl,$prefix)){$this->tableMake($tbl,$tblOpt,$field,$fieldOpt,$prefix);}
			else{$this->tableChgFields($tbl,$field,$prefix);}
		}
	/**
	* @}
	* @name Методы для шифрования
	* @{
	*/
		/**
		 * Шифрует строку.
		 * @param  $str шифруемая строка
		 * @param null $iv IV для шифрования
		 * @return array массив из зашифрованной строки и IV строки
		 */
		private function cryptEn($str,$iv=null){
			global $c_tools;
			$td=mcrypt_module_open('rijndael-256','','ofb','');
			if(!$iv)$iv=$c_tools->genPass(mcrypt_enc_get_iv_size($td),1,1,1);
			$hash='=-----------------;';
			mcrypt_generic_init($td,$hash,$iv);
			$str=mcrypt_generic($td,$str);
			mcrypt_generic_deinit($td);
			return array($str,$iv);
		}
		/**
		 * Расшифровывает строку.
		 * @param  $str зашифрованная строка
		 * @param  $iv IV для шифрования
		 * @return string
		 */
		private function cryptDe($str,$iv){
			$td=mcrypt_module_open('rijndael-256','','ofb','');
			$hash='-----------------';
			mcrypt_generic_init($td,$hash,$iv);
			$str=mdecrypt_generic($td,$str);
			mcrypt_generic_deinit($td);
			return $str;
		}
		/**
		 * Полноценная расшифровка записи.
		 * Именно эту функцию необходимо использовать для расшифровки строки. Т.к. именно она в будущем будет решать вопросы сложного шифрования
		 * @param $str строка
		 * @param $iv IV для шифрования
		 * @return string расшифрованная строка
		 */
		public function fulldecrypt($str,$iv){
			$a=base64_decode($str);
			$a=$this->cryptDe($a,$iv);
			return stripslashes($a);
		}
}