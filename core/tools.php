<?php
/**
 * Проверка переменной на существование, в случае успеха возвращает переменную, в случае отсутствия - алтернативное значение
 * Осторожно с передачей больших объемов информации в $alt. Есть риск большого потребления ресурсов сервера.
 * @param  $var переменная <b>Внимание! Функция работает со ссылкой.</b>
 * @param bool $alt альтернативное значение, если переменной не существует
 * @return bool
 */
function tvar(&$var,$alt=null){
    return (isset($var))?$var:$alt;
}
/**
 * Содержит допонительные инструменты - не распределенные по другим классам
 *  */
global $c_tools;
$c_tools=new c_tools();
class c_tools{
	function getCiArSt($city=0,$area=0,$street=0){
		global $c_db;
		$rez=(object)array();
		$c_db->table='st_city';
		$rez->city=$c_db->show(array('city_id'=>$city),'*',PREFIX_CORE);
		$c_db->table='st_area';
		$rez->area=$c_db->show(array('area_id'=>$area),'*',PREFIX_CORE);
		$c_db->table='st_street';
		$rez->street=$c_db->show(array('street_id'=>$street),'*',PREFIX_CORE);
		return $rez;
	}
	/**
	 * Проверка на наличие необходимых библиотек, переменных, констант
	 * Вслучае отсутсвия нужных библиотек проблему записывает в файл и прекращает работу скрипта
	 * @param  $name имя процесса
	 * @param bool $class необходимые классы ядра (начинаются с {c_})
	 * @param bool $constant необходимые константы
	 * @param bool $extension необходимые расширения apache
	 * @return void
	 */
	function fw_error($name,$class=false,$constant=false,$extension=false){
		global $c_err;
		if($class){
			$class=explode('|',$class);
			foreach($class as $v){
				if(!in_array($v,get_declared_classes())){
					$core=strpos($v,'c_');
					$c_err[(($core===0)?3:999999)][$v]=true;
				}
			}
		}
		if($constant){
			$constant=explode('|',$constant);
			foreach($constant as $v){
				if(!defined($v)) $c_err[200][$v]=true;
			}
		}
		if($extension){
			$extension=explode('|',$extension);
			foreach($extension as $v){
				if(!extension_loaded($v)) $c_err[2][$v]=true;
			}
		}
		if(count($c_err)>0){
			$fileput=array();
			$fileput[]=$name.' | '.$_SERVER['SCRIPT_FILENAME'];
			$fileput[]='--------------------------------';
			$fileput[]=print_r($c_err,true);
			$fileput[]='--------------------------------';
			$fileput[]='';
			file_put_contents(FW_ERROR_LOG.'_'.date('Y-m-d').'.log',implode("\n",$fileput),FILE_APPEND);
            print 'fw_error';
			exit();
		}
	}
	function noTreeList($table,$ff,$fs,$zero,$prefix){
		global $c_db;
		$c_db->table=$table;
		$rez=$c_db->listingU(null,null,0,0,$ff.','.$fs,$prefix);
		$rez[0]->id=0;
		$rez[0]->title=$zero;
		return $rez;
	}
	/**
	 * Создание папки (и подпапок) с правами 0777
	 * @param  $path путь до папки
	 * @param bool $infold массив с именами вложенных папок
	 * @return void
	 */
	function makefolder($path,$infold=false){
		if(!file_exists($path))	mkdir($path,0777,true);
		if($infold){
			foreach($infold as $v){
				if(!file_exists($path."/".$v)) mkdir($path."/".$v,0777,true);
			}
		}
	}
	/**
	 * Формирует деревовидный массив подчииненности записей
	 *
	 * @param  $table таблица из которой будет формироваться массив
	 * @param int $sub корневая запись дерева
	 * @param bool $order сортировка
	 * @param string $fields какие поля необходимы - пока бесполезна (не доработана)
	 * @param string $prefix префикс таблицы
	 * @param array $where условия отбора (до следующей версии здесь)
	 * @param int $limit лимит вложений (до следующей версии здесь)
	 * @param int $current текущий уровень вложения (до следующей версии здесь)
	 * @return array
	 */
	function treeFull($table,$sub=0,$order=false,$fields='`id`,`title`',$prefix=PREFIX,$where=array(),$limit=0,$current=0){
        global $c_db;
        $rez=$rezTmp=array();
		if($limit>0&&$limit<$current)return $rez;
		$c_db->table=$table;
		$whereS=$where;
        $whereS['sub']=$sub;
        $childs=$c_db->listingU($whereS,$order,0,0,$fields,$prefix);
        if($sub==0)$rez[0]=(object)array('id'=>0,'title'=>'Нет');
        foreach($childs as $v){
	    if($v->id==$sub)break;
            $rezTmp=$v;
			$childsS=$this->treeFull($table,$v->id,$order,$fields,$prefix,$where,$limit,$current+1);
            if(count($childsS)>0) $rezTmp->childs=$childsS;
            if($sub==0) $rez[0]->childs[]=$rezTmp;
            else $rez[]=$rezTmp;
        }
        return $rez;
    }
	/**
	 * Получить подчиненные элементы категории.
	 * Формирует объект массивов разделенный на 3 части:
	 * <ul>
	 * <li>fav - особые ссылки (родительской категории текущей категории и root категории)
	 * <li>childs - подчиненные категориии
	 * <li>contents - подчиненные записи
	 * @param  $grp данные группы <ul><li> данные группы:<ul>
                    <li>строка - наименование таблицы</li>
                    <li>объект:
                        <ul>
                            <li>table - таблица</li>
                            <li>fields - поля</li>
                            <li>order - сортировка</li>
                            <li>where - условия отбора</li>
                            <li> prefix - префикс</li>
                        </ul>
                    </li></ul></li></ul>
	 * @param  $cnt данные контента <ul><li> данные группы:<ul>
                    <li>строка - наименование таблицы</li>
                    <li>объект:
                        <ul>
                            <li>table - таблица</li>
                            <li>fields - поля</li>
                            <li>order - сортировка</li>
                            <li>where - условия отбора</li>
                            <li> prefix - префикс</li>
                        </ul>
                    </li></ul></li></ul>
	 * @param int $sub идентификатор группы
	 * @param int $pg страница
	 * @param int $limit лимит
	 * @param bool $rootIgnore - игнорировать флаг корня (если в бд есть такое поле и оно установлено как true - то на этом поиск родителей останавливается) )
	 * @return object
	 */
    function treeGet($grp,$cnt,$sub=0,$pg=0,$limit=20,$rootIgnore=false){
	    global $c_db;
	    $parents=$fav=$childs=$contents=array();
	    if(is_string($grp))$grp=(object)array('table'=>$grp);
	    if($grp->table){
		    if(!tvar($grp->fields))$grp->fields='*';
		    if(!tvar($grp->order))$grp->order='';
		    if(!tvar($grp->where))$grp->where='';
		    if(!tvar($grp->prefix))$grp->prefix=PREFIX;
		    if(is_string($cnt))(object)array('table'=>$cnt);
		    if(tvar($cnt->table)){
			    if(!tvar($cnt->fields))$cnt->fields='*';
			    if(!tvar($cnt->order))$cnt->order='';
			    if(!tvar($cnt->where))$cnt->where='';
			    if(!tvar($cnt->prefix))$cnt->prefix=PREFIX;
			    $c_db->table=$cnt->table;
			    $cnt->where['sub']=$sub;
			    if($pg==='all'){$limit=1000000;$pg=0;}
			    $contents=$c_db->listing($cnt->where,$limit,$pg*$limit,$cnt->order,$cnt->fields,false,$cnt->prefix);
		    }else $contents=false;

		    $c_db->table=$grp->table;
		    $grp->where['sub']=$sub;
		    $childs=$c_db->listingU($grp->where,$grp->order,0,0,$grp->fields,$grp->prefix);


		    $iTmp=0;
		    $c_db->table=$grp->table;
		    while($sub!=0&&$iTmp<40){
			    $where=array('id'=>$sub);
			    $parent=$c_db->show($where,$grp->fields,$grp->prefix);
				if(isset($parent->id)){
					$parents[]=$parent;
					$sub=$parent->sub;
					if($sub==$parent->id||(!$rootIgnore&&tvar($parent->root))) break;
					$iTmp++;
				}else break;
		    }

		    if(count($parents)>1) @$fav[0]=(object)array('favtitle'=>'.','id'=>0);
		    if(count($parents)>0){
			    if(isset($parents[1])) @$fav[1]=$parents[1];
			    else @$fav[1]->id=0;
			    @$fav[1]->favtitle='..';
		    }
	    }
		return (object)array('parents'=>$parents,'fav'=>$fav,'childs'=>$childs,'contents'=>$contents);
    }
	/**
	 * Получить id подчиненных категорий и записей
	 * @param $table таблица групп
	 * @param int $sub родитель
	 * @param bool $tableS таблица записей
	 * @param bool $whereS условия отбора записей
	 * @param int $pg номер страницы
	 * @param bool $order сортировка
	 * @param string $prefix префикс
	 * @return object
	 */
    function childId($table,$sub=0,$tableS=false,$whereS=false,$pg=0,$order=false,$prefix=PREFIX){
        global $c_db;
        $childs=$contents=array();
		$whereS['sub']=$where['sub']=$sub;
        if($tableS){
            $c_db->table=$tableS;
            if($pg!='all')$contents=$c_db->listing($whereS,20,$pg*20,$order);
            else $contents=$c_db->listingU($whereS,false,0,0,'`id`,`sub`',$prefix);
		}
		$c_db->table=$table;
		$childs=$c_db->listingU($where,false,0,0,'`id`,`sub`',$prefix);
        return (object)array('childs'=>$childs,'contents'=>$contents);
    }
	/**
	 * Распознавание кодировки строки (спасибо Чечеткину Дмитрию)
	 * Смысл алгоритма довольно-таки прост, он основывается на следующих наблюдениях:
	 * 	<ul>
	 * 	<ol>Соотношение гласных к согласным примерно одинаково для всех текстов одного и того же языка (для русского - 1 к 3)
	 *	<ol>В неправильно закодированном тексте это соотношение нарушается
	 *	<ol>В неправильно закодированном тексте уменьшается количество национальных символов
	 *	</ul>
	 *
	 * таким образом были выведены два критерия, по которым можно было оценивать успешность подбора кодировки:
	 *	<ul>
	 *	<ol> количество национальных символов в тексте должно приближаться к количеству символов в тексте
	 *	<ol> соотношение национальных гласных к согласным должно приближаться к частотным характеристикам языка
	 *	</ul>
	 *	в результате реализации была получена функция
	 *
	 * @param  $string строка в неизвестной кодировке
	 * @param int $pattern_size если строка больше этого размера, то определение кодировки будет производиться по шаблону из $pattern_size символов, взятых из середины переданной строки. Это сделано для увеличения производительности на больших текстах.
	 * @return string
	 */
	function detect_encoding($string, $pattern_size = 50){
		$list = array('cp1251', 'utf-8', 'ascii', '855', 'KOI8R', 'ISO-IR-111', 'CP866', 'KOI8U');
		$c = strlen($string);
		if ($c > $pattern_size){
			$string = substr($string, floor(($c - $pattern_size) /2), $pattern_size);
			$c = $pattern_size;
		}

		$reg1 = '/(\xE0|\xE5|\xE8|\xEE|\xF3|\xFB|\xFD|\xFE|\xFF)/i';
		$reg2 = '/(\xE1|\xE2|\xE3|\xE4|\xE6|\xE7|\xE9|\xEA|\xEB|\xEC|\xED|\xEF|\xF0|\xF1|\xF2|\xF4|\xF5|\xF6|\xF7|\xF8|\xF9|\xFA|\xFC)/i';

		$mk = 10000;
		$enc = 'ascii';
		foreach ($list as $item){
			$sample1 = @iconv($item, 'cp1251', $string);
			$gl = @preg_match_all($reg1, $sample1, $arr);
			$sl = @preg_match_all($reg2, $sample1, $arr);
			if (!$gl || !$sl) continue;
			$k = abs(3 - ($sl / $gl));
			$k += $c - $gl - $sl;
			if($k < $mk){
				$enc = $item;
				$mk = $k;
			}
		}
		return $enc;
	}
	/**
	 * Траслит строки для URL, предназначен, для формирвоания траслита русской строки, если таковая имеется
	 * @param  $str строка
	 * @return mixed|string транслитная строка.
	 */
    function translitURL($str){
        if(preg_match('/[^A-Za-z0-9_\-]/', $str)){
            $tr = array(
                "А" => "a", "Б" => "b", "В" => "v", "Г" => "g",
                "Д" => "d", "Е" => "e", "Ж" => "j", "З" => "z", "И" => "i",
                "Й" => "y", "К" => "k", "Л" => "l", "М" => "m", "Н" => "n",
                "О" => "o", "П" => "p", "Р" => "r", "С" => "s", "Т" => "t",
                "У" => "u", "Ф" => "f", "Х" => "h", "Ц" => "ts", "Ч" => "ch",
                "Ш" => "sh", "Щ" => "sch", "Ъ" => "", "Ы" => "yi", "Ь" => "",
                "Э" => "e", "Ю" => "yu", "Я" => "ya", "а" => "a", "б" => "b",
                "в" => "v", "г" => "g", "д" => "d", "е" => "e", "ж" => "j",
                "з" => "z", "и" => "i", "й" => "y", "к" => "k", "л" => "l",
                "м" => "m", "н" => "n", "о" => "o", "п" => "p", "р" => "r",
                "с" => "s", "т" => "t", "у" => "u", "ф" => "f", "х" => "h",
                "ц" => "ts", "ч" => "ch", "ш" => "sh", "щ" => "sch", "ъ" => "y",
                "ы" => "yi", "ь" => "", "э" => "e", "ю" => "yu", "я" => "ya",
                " " => "_", "." => "", "/" => "_"
            );
            $str=strtr($str,$tr);
            $str=preg_replace('/[^A-Za-z0-9_\-]/', '', $str);
        }
        return $str;
    }
	/**
	 * Транслитерация текста по стандарту Почты РФ
	 * @param  string $str строка
	 * @return string
	 */
    function translitText($str){
        $tr = array(
            "А"=>"A","Б"=>"B","В"=>"V","Г"=>"G",
            "Д"=>"D","Е"=>"E","Ж"=>"J","З"=>"Z","И"=>"I",
            "Й"=>"Y","К"=>"K","Л"=>"L","М"=>"M","Н"=>"N",
            "О"=>"O","П"=>"P","Р"=>"R","С"=>"S","Т"=>"T",
            "У"=>"U","Ф"=>"F","Х"=>"H","Ц"=>"TS","Ч"=>"CH",
            "Ш"=>"SH","Щ"=>"SCH","Ъ"=>"","Ы"=>"YI","Ь"=>"",
            "Э"=>"E","Ю"=>"YU","Я"=>"YA","а"=>"a","б"=>"b",
            "в"=>"v","г"=>"g","д"=>"d","е"=>"e","ж"=>"j",
            "з"=>"z","и"=>"i","й"=>"y","к"=>"k","л"=>"l",
            "м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r",
            "с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"h",
            "ц"=>"ts","ч"=>"ch","ш"=>"sh","щ"=>"sch","ъ"=>"y",
            "ы"=>"yi","ь"=>"","э"=>"e","ю"=>"yu","я"=>"ya"
        );
        return strtr($str,$tr);
    }
	/**
	 * Возвращает заголовок для формы добаления/редактирования элемента
	 * @param  $text строка типа элемента редактирувется в родительном падеже
	 * @param int $id идентификатор для определения что происходит (0 - добавляение / 1 - редактирование)
	 * @param string $title название элемена, который будет расположен в кавычках
	 * @return string
	 */
    function formHead($text,$id=0,$title=''){
        return (($id>0)?'Редактирование':'Добавление').' '.$text.(($id>0)?' &ldquo;'.$title.'&rdquo;':'');
    }
	/**
	 * Возвращает сгенерированный пароль
	 * @param int $number - длинна пароля
	 * @param int $caseU - число заглавных букв англ. алфавита
	 * @param int $caseL - число строчных букв англ. алфавита
	 * @param int $nums - число цифр
	 * @param array $other - другие символы
	 * @return string
	 */
    function genPass($number=10,$caseU=1,$caseL=1,$nums=1,$other=array()){
        $array=$arrCaseL=$arrCaseU=$arrNums=array();
        if($caseL) $arrCaseL=array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','r','s','t','u','v','x','y','z');
        if($caseU) $arrCaseU=array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','R','S','T','U','V','X','Y','Z');
        if($nums) $arrNums=array('1','2','3','4','5','6','7','8','9','0');
        $array=array_merge($arrCaseL,$arrCaseU,$arrNums,$other);
        $pass="";
        for($i=0;$i<$number;$i++){
            $index=rand(0,count($array)-1);
            $pass.=$array[$index];
        }
        return $pass;
    }
	/**
	 * Добавление в очередь хлебных крошек ещё одного элемента (см. $breadCump)
	 * @param bool $text текст крошки
	 * @param bool $href ссылка
	 * @param bool $alt альтернативный текст (например если это последняя крошка или ссылка не выводится)
	 * @param bool $root корневая запись
	 * @return void
	 */
    function bcAdd($text=false,$href=false,$alt=false,$root=false){
        global $breadCump;
        if($text){
			$obj=(object)array();
		    if($href) $obj->href=$href;
		    if($alt)$obj->alt=$alt;
            $obj->txt=$text;
			$obj->root=$root;
            $breadCump[]=$obj;
        }
    }
	/**
	 * Добавляет в хлебные крошки ряд, где каждая запись это шаг к корню
	 * @param $table таблица
	 * @param int $sub
	 * @param string $sh
	 * @param string $fh
	 * @param string $prefix
	 * @param bool $rootIgnore - игнорировать флаг корня (если в бд есть такое поле и оно установлено как true - то на этом поиск родителей останавливается) )
	 */
    function bcTree($table,$sub=0,$sh='',$fh='',$prefix=PREFIX,$rootIgnore=false){
        if($sub>0){
	        $grp=$cnt=(object)array();
	        $grp->table=$table;
	        $grp->prefix=$cnt->prefix=$prefix;

            $obj=$this->treeGet($grp,false,$sub,0,20,$rootIgnore);
            $obj=$obj->parents;
            $obj=array_reverse($obj);
            foreach($obj as $v){
                $this->bcAdd($v->title,$sh.$v->id.$fh,false,(!$rootIgnore&&tvar($v->root)));
            }
        }
    }
	/**
	 * Отправка почты основывается на настройках фреймворка
	 * @param  $to кому
	 * @param  $subj тема
	 * @param  $text текст
	 * @param bool $smtp отправлять через SMTP или функцией mail
	 * @return bool
	 */
    public function send_mail($to, $subj, $text, $smtp=MAIL_SMTP_SEND){ // функция отправки писем
        if($smtp){
	    require_once(PATH.'/module/custom/mail.php');
            global $mailClass;
            //$mailClass->throwExceptions(true);
            $mailClass->smtpConnect(MAIL_USER,MAIL_PASS,MAIL_SMTP,MAIL_SMTP_PORT);
            $mailClass->setUser(MAIL_ADDR,$_SERVER['HTTP_HOST']);
            $mailClass->setSubject($subj);
            $mailClass->setBody($text);
            $mailClass->addTo($to,' ');
            $send=$mailClass->send();
            $mailClass->smtpClose();
        }else{
	    require_once(PATH.'/module/custom/sendmail.php');	
            $mulmail=new multipartmail($to, MAIL_ADDR, $subj );
	    $mulmail->addmessage($text);
	    $send=$mulmail->sendmail();
        }
        return $send;
    }
	/**
	 * Подготовить сообщение на основе шаблона, хранящегося в /files/msg/*.msg
	 * @param  $msg имя шаблона
	 * @param  $arr массив данных для замены шаблона, если шаблон %_a_%, то ключ должен быть _a_
	 * @return mixed array('заголовок','текст')
	 */
    function getMessage($msg,$arr){
        $line=PATH.'/files/msg/'.$msg.'.msg';
        if(!file_exists($line))$line=PATH.'/files/msg/nomessg.msg';
        if(file_exists($line)){
            $i=0; $rez=array('','');
            $fo=fopen($line,'rb');
            while(!feof($fo)){
                $text=fgets($fo, 4096);
                if($i==0)$rez[0]=$text;
                elseif($i>1)$rez[1].=$text;
                $i++;
            }
            fclose($fo);
            $rez=preg_replace('/%_(.*?)_%/ue', '$arr["$1"]', $rez);
            return $rez;
        } return array('error message','error message');
    }
	 /**
	  * Функция, формирующая список пар id=title родительской и подчиненной таблицы
	  *
	  * @param string $tableIn родительская таблица, для которой будет готовиться отбор
	  * @param string $tableOut подчиненная таблица, которая будет источником информации
	  * @param array $where условия фильтрации
	  * @param string $field связанное поле родителя
	  * @param string $id идентификатор подчиненной таблицы (является связью для $field)
	  * @param string $title выводимая часть подчиненной таблицы (является парой к $id)
	  * @param string $zeroText название нулевого раздела
	  * @param string $prefixIn префикс родительской таблицы
	  * @param string $prefixOut префикс подчиненной таблицы
	  * @param string $nokey sprintf строка в случае отсутствия ID
	  *
	  * @return array массив из подготовленной пары [id]=title
	 */
    function filterSatCat($tableIn,$tableOut,$where=array(),$field='sub',$id='id',$title='title',$zeroText='основной раздел',$prefixIn=PREFIX,$prefixOut=PREFIX,$nokey='уд. %s/$1'){
	    global $c_db;
	    $c_db->table=$tableIn;
		$whTmp=(isset($where[$field]))?$where[$field]:'';
		unset($where[$field]);
		$tmp=$c_db->listingU($where,null,0,0,"GROUP_CONCAT(DISTINCT `$field`) as `$field`",$prefixIn);
		if($tmp[0]->$field||$whTmp){
			if(!$tmp[0]->$field&&isset($whTmp))$tmp[0]->$field=$whTmp;
			$tmpA=$tmp=explode(',',$tmp[0]->$field);
			if(in_array(0,$tmp)){$rez[0]=$zeroText;}
			$c_db->table=$tableOut;
			$tmp=$c_db->listingU(array($id=>$tmp),"+`$id`",0,0,"`$id`,`$title`",$prefixOut);
			foreach($tmp as $v){$rez[$v->$id]=$v->$title;}
			foreach($tmpA as $v){if(!in_array($v,$tmp)){$tmp[$v]=sprintf($nokey,$v);}}
		}else{$rez=array();}
	    return $rez;
    }
	/**
	 * Создание тайтла на основе хлебных крошек.
	 * @param string $delimer разделитель крошек
	 * @return string конечная строка
	 */
	function bcMakeTitle($delimer=' > '){
		global $breadCump;
		$fin=&$breadCump[count($breadCump)-1];
		$fst=&$breadCump[0];
		if(count($breadCump)>2)$sec=&$breadCump[1];
		$rez[]=$this->bcTitleorAlt($fst);
		if(isset($sec)&&$sec)$rez[]=$this->bcTitleorAlt($sec);
		if(count($breadCump)>3) $rez[]='...';
		$rez[]=$this->bcTitleorAlt($fin);
		return implode($delimer,$rez);
	}
	/**
	 * Возвращает текст хлебной крошки альтенативный (если есть) или основной
	 * @param $obj хлебная крошка
	 * @return void
	 */
	function bcTitleorAlt(&$obj){
		return (isset($obj->alt)&&$obj->alt)?$obj->alt:$obj->txt;
	}
	/**
	 * Рекурсивное удаление папки и содержимого <b>Осторожно! Возможна потеря данных</b>
	 * @param  $path путь к директории
	 * @return void
	 */
	function removeDir($path){
		if(file_exists($path) && is_dir($path)){
			$dirHandle = opendir($path);
			while(false !== ($file=readdir($dirHandle))){
				if($file!='.'&&$file!='..'){
					$tmpPath=$path.'/'.$file;
					chmod($tmpPath,0777);
					if(is_dir($tmpPath))RemoveDir($tmpPath);
					else if(file_exists($tmpPath)) unlink($tmpPath);
				}
			}
			closedir($dirHandle);
			if(file_exists($path))rmdir($path);
		}
	}
	/**
	 * Добавление в массив uid равный id пользователя если не админ
	 * Написано для сокращения часто используемого условия в where
	 * @param $array ссылка на нужный массив
	 * @param bool $user прописывать пользователя: true - добавить, игнорируя базовые условия
	 */
	function accessUser(&$array,$user=false){
		global $c_auth;
		if($user==true||$c_auth->data->status!=='admin') $array['uid']=$c_auth->data->id;
	}
	/**
	 * Определение текста ошибки
	 * В каждом модуле содержится ряд словарей ошибок, к которым можно обращаться и выводить данные
	 * Эта функция помагает найти нужную ошибку и вывести её, а в случае если не найдет, записать в специальный файл и вывести дежурный текст.
	 * @static
	 * @param bool|string $err ошибка (например name)
	 * @param $conPrefix Префикс константы (например NEWS_ERROR_)
	 * @param null $exclude Исключения, при которых выводить ошибку не надо. Такие ошибки игнорируются и возвращается пустота.
	 * @return mixed|string
	 */
    static function errorMsg($err=false,$conPrefix,$exclude=null){
        if($exclude){
            if(is_string($exclude)) $exclude=explode('|',$exclude);
            elseif(!is_array($exclude)) $exclude=array();
        }else $exclude=array();
        if($err&&!in_array($err,$exclude)){
            $errC=($err)?$conPrefix.$err:'';
            if(defined($errC)) $errN=constant($errC);
            else{
                $errN='Ошибка ['.$err.']';
                file_put_contents('constant_error.log',$err."\n",FILE_APPEND);
            }
        }else $errN='';
        return $errN;
    }
}
class formGenerator{
	private $readyFields=array();
	function addField($title='',$name='',$type='',$value='', $attr='',$default=''){
		if(in_array($type,array('checkbox','file','hidden','password','radio','reset','submit','text'))) $this->typeInput($title,$name,$type,$value,$attr);
		elseif($type=='select') $this->typeSelect($title,$name,$value,$default,$attr);
		elseif($type=='textarea') $this->typeTextarea($title,$name,$value,$attr);
	}
	function typeInput($title='',$name='',$type='',$value='',$attr=''){
		$rez=null;
		$attrs=array();
		if($title) $attrs[]="title='$title'";
		if($type) $attrs[]="type='$type'";
		if($name) $attrs[]="name='$name'";
		if($value) $attrs[]="value='$value'";
		if($attr) $attrs[]=$attr;
		$rez->field="<input ".implode(' ',$attrs)." />";
		$rez->title=$title;
		$this->readyFields[]=$rez;
	}
	function typeSelect($title='',$name='',$value=array(),$default='',$attr=''){
		$rez=null;
		$attrs=array();
		if($title) $attrs[]="title='$title'";
		if($name) $attrs[]="name='$name'";
		if($attr) $attrs[]=$attr;
		$rez->field="<select ".implode(' ',$attrs)." />";
			foreach($value as $v){
				if($v->field==$default) $v->attr.=' selected=selected';
				$rez->field.=$this->typeOption($v->title,$v->field,$v->attr);
			}
		$rez->field.="</select>";
		$rez->title=$title;
		$this->readyFields[]=$rez;
	}
	function typeOption($title='',$value='',$attr=''){
		$rez=null;
		$attrs=array();
		if($value) $attrs[]="value='$value'";
		if($attr) $attrs[]=$attr;
		return "<option ".implode(' ',$attrs).">$title</option>";
	}
	function typeTextarea($title='',$name='',$value='',$attr=''){
		$rez=null;
		$attrs=array();
		if($title) $attrs[]="title='$title'";
		if($name) $attrs[]="name='$name'";
		if($value) $attrs[]="value='$value'";
		if($attr) $attrs[]=$attr;
		$rez->field="<texarea ".implode(' ',$attrs).">$value</textarea>";
		$rez->title=$title;
		$this->readyFields[]=$rez;
	}
	function make(){
		$rez=$this->readyFields;
		$this->readyFields=array();
		return $rez;
	}
}