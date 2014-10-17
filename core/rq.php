<?
global $c_rq;
$c_rq=new c_rq();
/**
 * Отвечает за обработку входящих переменных
 * Предохраняет от большинства атак, связанных с вводом нестандартных символов или тегов в неположенном месте.
 */
class c_rq{
	public $regexp='';
	/**
	 * Конструктор.
	 * Выполняется подключение регулярных выражений и шаблонов, находящееся в файле rq_exp.
	 */
	function __construct(){
        include_once(PATH.'/core/rq_exp.php');
    }
	/**
	 * Создание регулярного выражения на основе имени шаблона
	 *
	 * @param string $str имя шаблона
	 * @return array|string регулярное выражение на выходе
	 */
	function makeReg($str=''){
        if(isset($this->regexp[$str])){
			$str=explode(',',$str);
			$str=array_unique($str);

			$regexp=$this->regexp;
			$rez=array();
			foreach($str as $v){
				if($v){
					$rl=@$regexp[$v];
					if(is_string($rl)) $rez[]=$rl;
					elseif(is_array($rl)) $rez=array_merge($rez,$rl);
				}
			}
			$rez=array_unique($rez);
			$rez="![^".implode($rez)."]!u";
		}elseif(isset($this->tplexp[$str])) $rez=$this->tplexp[$str];
        else $rez='';
		return $rez;
	}
	/**
	 * Получить содержимое переменной
	 * @param  $key имя ключа удаленного запроса
	 * @param string $type шаблон
	 * @param null $data если это не удаленный запрос, то обработывается черз данную строку
     * @param bool $encode
     * @return string обработанная переменная
	 */
    function get($key,$type='',&$data=null,$encode=false){
        if(is_string($key)){$keyType='string'; $key=array($key);}
        elseif(is_array($key)){$keyType='array';}
		elseif($data) $key=array('0');
        else return 'wrong type';
        foreach($key as $kv){
			if(!$data) $rqobj=@$_REQUEST[$kv];
			else $rqobj=$data;
            $exp=$this->makeReg($type);
            if(is_array($rqobj)){
                foreach($rqobj as $k=>$v){
                    if($encode){
                        $rqobj[$k]=preg_replace($exp,'',mb_convert_encoding($v,'utf8',$encode));
                    }else{
                        $rqobj[$k]=preg_replace($exp,'',$v);
                    }
                }
            }elseif(is_string($rqobj)){
                if($encode){
                    $rqobj=preg_replace($exp,'',mb_convert_encoding($rqobj,'utf8',$encode));
                    }else{
                        $rqobj=preg_replace($exp,'',$rqobj);
                    }
            }
            $rez[]=$rqobj;
        }
        if($keyType=='string'){$rez=$rez[0];}
		return $rez;
	}
	/**
	 * Проверка удаленного запроса на соответствие
	 * @param  $key ключ удаленного запроса
	 * @param string $type шаблон
	 * @return string <ul>
	 * 		<li> matched - переменная соответствует шаблону </li>
	 * 		<li> nomatched - переменная не соответствует шаблону </li>
	 * 		<li> undefined - переменная не найдена </li>
	 * </ul>
	 */
	function check($key,$type=''){
        $rez='matched';
        if(isset($_REQUEST[$key])){
            if($type){
                $exp=$this->makeReg($type);
                $rqobj=$_REQUEST[$key];
                if(is_array($rqobj)){
                    foreach($rqobj as $k=>$v){
                        if(preg_match($exp,$v)){$rez='nomatch';break;}
                    }
                }elseif(is_string($rqobj)){
                    if(preg_match($exp,$rqobj)) $rez='nomatch';
                }else $rez='nomatch';
            }
        }else $rez='undefined';
        return $rez;
	}
	/**
	 * Обрабатывает несколько переменных, добавляя их в указанный массив
	 * @param  $array массив, в который будут добавлены результаты
	 * @param array $fields данные в виде массива, где <ul>
	 * 		<li>Название ключа в массиве и ключа удаленного запроса (если нет третьего параметра)</li>
	 * 		<li>Шаблон</li>
	 * 		<li>(опционально) Название ключа удаленного запроса</li>
	 * </ul>
	 * @param bool $required_msg префикс ошибки<ul>
	 * <li>указан - при отсутсвии перменной прекращает обработку пременных - возвращает [префикс][название ключа]
	 * @param bool $empty обрабатывать пустые значение или нет
     * @param bool $encode
     * @return bool|string ошибку или false
	 */
    function getR(&$array,$fields=array(),$required_msg=false,$empty=false,$encode=false){
		foreach($fields as $v){
			$expl=explode('|',$v);
			if(count($expl)==2){
				list($requestTmp,$expTmp)=$expl;
				$fieldTmp=$requestTmp;
			}elseif(count($expl)==3){
				list($fieldTmp,$requestTmp,$expTmp)=$expl;					
			}else return false;
            $data=null;
			$rezTmp=$this->get($requestTmp,$expTmp,$data,$encode);
			if((!$rezTmp&&$rezTmp!=='0')&&$required_msg) return $required_msg.'_'.$requestTmp;
			elseif($rezTmp||$empty)$array[$fieldTmp]=$rezTmp;
		}
		return false;
	}
}
