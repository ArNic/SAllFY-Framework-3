<?
    /**
     * Шаблонизатор фреймворка
     * Работает с буферизацией.
     * Все шаблоны отлавливаются в буфере. Далее сформированные данные дописываются к указанной переменной класса.
     * По окончании работы скрипта подстваляет переменные в укзанный общий шаблон, где они указаны через $this
     * Аккуратнее с памятью. Обычно вывод не превышает 1 Мб - а значит особой нагрузки не существует.
     * Но были случаи, когда лог парсинга пробовали засунуть в вывод. Зачем не понятно но результат предсказуем :)
     *
     * Терминология:
     * 		Общий шаблон - файл, содержащий контейнеры, в который по окнчании работы скрипта поступят все данные
     *		Контейнер - переменная шалонизатора, в которой храняться данные для вывода. Данные добавляются справой стороны переменной. С контейнерами можно взаимодействовать напрямую до окнчания работы скрипта, но это очень не рекомендуется.
     *
     */
    class c_tpl{
        /**
         * Путь до файлов с шалбонами
         * @var string
         */
        var $tpl;
        /**
         * Имя файла шаблона
         * @var string
         */
        var $design;
        var $cache=false; # boolean кэш всей страницы
        static function install(){
            global $c_db;
            $tbl='cache';
            $field=$fieldOpt=$tableOpt=array();
            $field['id']=array('int(11)','NOT NULL','auto_increment');
            $field['key']=array('varchar(255)','NOT NULL');
            $field['dt']=array('int(20)','NOT NULL');
            $field['content']=array('text','NOT NULL');
            $fieldOpt[]='PRIMARY KEY (`id`)';
            $fieldOpt[]='KEY `key` (`key`,`dt`)';
            $tableOpt='ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8';
            $c_db->tableInstall($tbl,$tableOpt,$field,$fieldOpt,PREFIX_CORE);
        }
        /**
         * Подготовить шаблонизатор для работы
         * Подготовка вывода шаблонизатора
         * В последних двух функция особой необходимости не было, внимания уделялось им мало, следовательно они несколько не доработаны
         * @param bool|string $tpl папка расположения темповых файлов
         * @param string $design общий шабон страницы
         * @param bool|object $cache кэш всей страницы должен передаваться жестко по стандарту c ключами объекта: node,get,nokey,right,timeOld
         * <ul>
         * 	<li>string     $node узел формирующий работу страницы
         *  <li>array      $get параметры передаваемые парой ключ значение формирующие часть пути к кэшу (/key1/value1/.../keyN/valueN.0.0)
         *  <li>bool       $nokey если в пути должны быть только значения (/value1/.../valueN.0.0)
         *  <li>mixed      $right права пользователя, которые предусмотрены во фреймворке (array/integer)
         * 	<li>integer    $timeOld срок жизни кэша
         * </ul>
         * @param bool $makeseo Формирование данных для метатегов автоматом: true/false
         */
        function __construct($tpl=false,$design="design.php",$cache=false,$makeseo=false){
            global $c_tools;

            //$this->makeFramework();
            $this->tpl=PATH.(($tpl)?$tpl:TPL_PATH).'/';
            $this->design=$design;
            $this->time=microtime(true);
            $this->makeseo=$makeseo;
            $ver=explode('.',phpversion());
            $this->ver=($ver[0]==5&&$ver[1]>=3);
            $cachedPage=null;
            if(is_object($cache)){
                $this->cache=$cache;
                $cachedPage=$this->cacheGet($cache->node,$cache->get,$cache->nokey,$cache->right,$cache->timeOld,$cache->uid);
                if($cachedPage->status==='good'){
                    print $cachedPage->content;
                    //                print $this->makeConsole();
                    exit;
                }
            }

            if(!$cachedPage||$cachedPage->status!=='good'){register_shutdown_function(array($this,'dprint'));}
        }
        function dprint(){
            $cache=$this->cache;
            if($cache){ob_start();}
            if($this->makeseo){$this->seo();}
            include($this->tpl.$this->design);
            if($cache){
                $rez=ob_get_contents();
                ob_end_clean();
                $this->cacheMake($rez,$cache->node,$cache->get,$cache->nokey,$cache->right,$cache->uid);
                print $rez;
            }
            //        print $this->makeConsole();
        }
        function makeFramework(){
            $cache=(object) array();
            $cache->node='index';
            $cache->get=array('cache');
            $cache->nokey=true;
            $cache->right=15;
            $cache->time=60*60*24*30;
            $cache->uid=0;
            $cachedPage=$this->cacheGet($cache->node,$cache->get,$cache->nokey,$cache->right,$cache->time,$cache->uid);
            if($cachedPage->status!=='good'){
                $remoteAddr=($_SERVER['REMOTE_ADDR']!='127.0.0.1');
                if($remoteAddr){
                    $serverHost=$_SERVER['HTTP_HOST'];
                    $serverAddr=$_SERVER['SERVER_ADDR'];
                    $url="http://sallfy.ru/copies.php?sh=$serverHost&sa=$serverAddr";
                    $fo=fopen($url,'r');
                    fclose($fo);
                    $this->cacheMake('',$cache->node,$cache->get,$cache->nokey,$cache->right,$cache->uid);
                }
            }
        }
        function add($file){include($this->tpl.$file);}
        function getCchLine($func,$var,$right=15,$uid=0,$attrcache=''){
            $cache=(object)array();
            $cache->node='tplget';
            $cchLine=$var.'->'.$func;
            if($attrcache){
                if(is_string($attrcache)){$cchLine.=$attrcache;}
                elseif(is_array($attrcache)){$cchLine.=implode('->',$attrcache);}
            }
            $cchLine=str_replace('::','->',$cchLine);
            $cchLine=mb_strtolower($cchLine);
            $cache->get=explode('->',$cchLine);
            $cache->nokey=true;
            $cache->uid=$uid;
            $cache->right=$right;
            return $cache;
        }
        function optsCch($opts){
            if(!tvar($opts->time)) $opts->time=null; # time
            if(!tvar($opts->right)) $opts->right=15; # right
            if(!tvar($opts->uid)) $opts->uid=0; # uid
            if(!tvar($opts->attr)) $opts->attr=''; # attributes
            return $opts;
        }
        function getCch($func,$var,$cchO=null){
            $cchO=$this->optsCch($cchO);
            $cache=$this->getCchLine($func,$var,$cchO->right,$cchO->uid,$cchO->attr);
            return $this->cacheGet($cache->node,$cache->get,$cache->nokey,$cache->right,$cchO->time,$cache->uid);
        }
        function makeCch($func,$var,$cchO=null){
            $cchO=$this->optsCch($cchO);
            $cache=$this->getCchLine($func,$var,$cchO->right,$cchO->uid,$cchO->attr);
            return $this->cacheMake($cache->node,$cache->get,$cache->nokey,$cache->right,$cchO->time,$cache->uid);
        }
        /**
         * Забрать результат в переменную шаблонизатора
         *
         * Есть 4 варианта отправки данных в контейнер (например: body)
         * 1 вариант - вывод из функции или метода класса с одной переменной
         * Передается $type = 1 или игнорируется вообще
         * @code
         *  # для функции
         * 	$c_tpl->get('testFunction','body',1); # в функцию testFunction($a) будет передан параметр со значением "1" и выводимые данные дописаны в контейнер body
         *  # для классов
         * 	$c_tpl->get('testClass::testMethod','body',1); # аналогично первому, но "в метод testMethod($a) класса testClass"
         * 	$c_tpl->get('testClass->testMethod','body',1); # аналогично второму
         * @endcode
         *
         * 2 вариант - вывод из функции или метода класса с несколькими переменными
         * Передается $type = 2 и несколько переменных передается массивом
         * @code
         *  # для функции
         * 	$c_tpl->get('testFunction','body',array('1','2'),2); # в функцию testFunction($a,$b) будут переданы 2 параметра "1" и "2" выводимые данные дописаны в контейнер body
         *  # для классов
         * 	$c_tpl->get('testClass::testMethod','body',array('1','2','1','1'),1); # аналогично первому, но "в метод testMethod($a,$b,$c,$d) класса testClass"
         * 	$c_tpl->get('testClass->testMethod','body',array('1','2','4','1'),1); # аналогично второму
         * @endcode
         *
         * 3 вариант - вывод из файла
         * Передается $type = 3
         * @code
         * 	$c_tpl->get('file.php','body',false,3); # вывод файла file.php будет дописан в контейнер body.
         * @endcode
         *
         * 4 вариант - просто текст
         * Передается игнорируется $func и $type=4 или игнорируются $func и $type.
         * @code
         * 	$c_tpl->get(null,'body','hello world',4); # 'hello world' будет дописан в контейнер body.
         * 	$c_tpl->get(null,'body','hello world'); # аналогично
         * @endcode
         *
         * @param $func название функции, метода класса, файла
         * @param $var контейнер
         * @param null $data данные, передаваемые фнкции, методу класса
         * @param int $type тип обработки
         * @param null $cchO Параметры кэширования (необходима доработка)
         */
        function get($func,$var,$data=null,$type=1,$cchO=null){
            ob_start();
            if(!$func||$type==4){
                print $data;
            }else{
                if(strpos($func,'->')){$func=explode('->',$func);}
                if(is_array($func)&&$this->ver)$func=implode('::',$func);
                if($type==1){call_user_func($func,$data);}
                elseif($type==2){call_user_func_array($func,$data);}
                elseif($type==3){include($this->tpl.$func);}
            }
            if($cchO){
                $cchO=$this->optsCch($cchO);
                $content=ob_get_contents();
                $cache=$this->getCchLine($func,$var,$cchO->right,$cchO->uid,$cchO->attr);
                $this->cacheMake($content,$cache->node,$cache->get,$cache->nokey,$cache->right,$cache->uid);
            }
            @$this->$var.=ob_get_contents();
            ob_end_clean();
        }
        function makeConsole(){
            $time=round(microtime(true)-$this->time,5);

            $itog=(object)array('info'=>array(),'calc'=>array());
            $itog->line[]='Формирование страницы';
            $itog->line[]=$time.' сек';
            $queryPerSec=round(1/$time);
            $itog->calc[]='~'.$queryPerSec.' страниц/сек';
            $itog->calc[]='~'.number_format($queryPerSec*60,0,'.',' ').' страниц/мин';
            $itog->calc[]='~'.number_format($queryPerSec*60*60,0,'.',' ').' страниц/час';
            $itog->calc[]='~'.number_format($queryPerSec*60*60*24,0,'.',' ').' страниц/сутки';
            $content='';
            $content.='<script>';
            $content.='console.groupCollapsed("'.implode(' | ',$itog->line).'");';
            if(count($itog->calc)>0){
                $content.='console.groupCollapsed("Дополнительные расчеты");';
                foreach($itog->calc as $k=>$v){
                    $content.='console.info("'.$v.'");';
                }
                $content.='console.groupEnd();';
            }
            $content.='console.groupEnd();';
            $content.='</script>';
            #return $content;
        }
        /**
         * @name Методы для кэширования !!! alpha !!! не рекомендуется использовать
         * @{
         */
        /**
         * Получить пути кэш-файла
         * Получение путей потенциального кэша к файлу и папке
         *
         * @param string $node узел формирующий работу страницы
         * @param array $get параметры передаваемые парой ключ значение формирующие часть пути к кэшу (/key1/value1/.../keyN/valueN.0.0)
         * @param bool $nokey если в пути должны быть только значения (/value1/.../valueN.0.0)
         * @param int $right права пользователя, которые предусмотрены во фреймворке
         * @param int $uid привязка к пользователю
         * @return object (file=>путь к файлу,folder=>путь к папке)
         */
        function cachePath($node='index',$get=array(),$nokey=false,$right=15,$uid=0){
            $rez=array();
            if(!$nokey){
                $rez[]=($node)?$node:'index';
                foreach($get as $k=>$v){
                    if($k){$rez[]=$k;}
                    if($v){$rez[]=$v;}
                }
            }else $rez=$get;
            $line=(object)array();
            $line->key=''.implode('|',$rez).'.'.$right.'.'.$uid.'.cache';
            return $line;
        }
        /**
         * Получить кэш-файл
         * Получение уже созданного кэша
         *
         * @param string $node узел формирующий работу страницы
         * @param array $get параметры передаваемые парой ключ значение формирующие часть пути к кэшу (/key1/value1/.../keyN/valueN.0.0)
         * @param bool $nokey если в пути должны быть только значения (/value1/.../valueN.0.0)
         * @param mixed $right права пользователя, которые предусмотрены во фреймворке (array/integer)
         * @param integer $timeOld срок жизни кэша
         * @param int $uid привязка к пользователю
         * @return object (status=>[undefined/timeout/good],file=>путь к файлу,folder=>путь к папке,content=>если есть, содержимое кэша)
         */
        function cacheGet($node='index',$get=array(),$nokey=false,$right=15,$timeOld=0,$uid=0){
            $rez=(object) array('status'=>'undefined','key'=>false,'content'=>false);
            $line=$this->cachePath($node,$get,$nokey,$right,$uid);
            global $c_db;
            $c_db->table='cache';
            $cch=$c_db->show(array('key'=>$line->key,'dt|>='=>time()),'*',PREFIX_CORE);
            if(tvar($cch->id)){
                $rez->status='good';
                $rez->key=$cch->key;
                $rez->content=$cch->content;
            }else{
                $rez->status='timeout';
            }
            return $rez;
        }
        /**
         * Сохранить кэш-файл
         * Сохранение контента в кэш
         *
         * @param string $content сожеримое кэша
         * @param string $node узел формирующий работу страницы
         * @param array $get параметры передаваемые парой ключ значение формирующие часть пути к кэшу (/key1/value1/.../keyN/valueN.0.0)
         * @param bool $nokey если в пути должны быть только значения (/value1/.../valueN.0.0)
         * @param mixed $right права пользователя, которые предусмотрены во фреймворке (array/integer)
         * @param int $uid привязка к пользователю
         * @return object (status=>статус,file=>путь к файлу,folder=>путь к папке,content=>если есть, содержимое кэша)
         * */
        function cacheMake($content,$node='index',$get=array(),$nokey=false,$right=15,$uid=0){
            global $c_tools,$c_db;
            $line=$this->cachePath($node,$get,$nokey,$right,$uid);
            $c_db->table='cache';
            return $c_db->insert(array('key'=>$line->key,'dt'=>(time()+CACHE_LIMIT),'content'=>$content),PREFIX_CORE);
        }
        /**
         * @}
         */
        function seo(){
            if(!isset($this->seoKey)||!isset($this->seoDesc)||!$this->seoKey||!$this->seoDesc){
                $text=isset($this->body)?$this->body:'';
                $text=$this->onlyText($text);
            }
            if(!isset($this->seoKey)||!$this->seoKey){
                preg_match_all('![^а-яА-ЯA-Za-z]+([а-яА-ЯA-Za-z]{4,}?)[^а-яА-ЯA-Za-z]+!u',$text,$textTmp);
                $textTmp=$textTmp[1];
                $exept=array('нет','не','ни','то','даже','со','если','но','без');
                $keyword=array();
                foreach($textTmp as $v){
                    if(!in_array($v,$exept)){(isset($keyword[$v]))?$keyword[$v]++:$keyword[$v]=1;}
                }
                if($keyword){
                    arsort($keyword);
                    $ck=count($keyword); # count keywords
                    $hck=round($ck/2); # half count keywords
                    $stK=($hck>10)?$hck-10:0; #start keyword
                    $keyword=array_slice($keyword,$stK,20,true);
                    $keyword=array_keys($keyword);
                }
                $this->seoKey=@implode(", ", @$keyword);
            }
            if(!isset($this->seoDesc)||!$this->seoDesc){
                $len=strlen($text);
                if($len>=200){
                    $lenH=$len/2;
                    $this->seoDesc=mb_substr($text,$lenH-100,250);
                }else{
                    $this->seoDesc=$text;
                }
            }
        }
        /**
         * очищает текст от тегов и прочих излишеств.
         * Удобно при выводе анонсов или вырезки из текста.
         * @param string $text текст для обработки
         * @param int $start с какого символа начинать
         * @param null $length если надо обрезать строку, длинна символов.
         * @return mixed|object|string
         * При указании $length возвратит не строку а объект, где full - обрезан ли текст и str - очищенная строка
         */
        function onlyText($text='',$start=0,$length=null){
            $text=(string)$text;
            $text=stripslashes($text);
            $text=preg_replace("!(&nbsp;){1,}!u"," ",$text);
            $text=html_entity_decode($text,ENT_COMPAT,'UTF-8');
            $text=strip_tags($text);
            $text=htmlspecialchars_decode($text);
            $text=preg_replace("!(([^\S]){2,})!u"," ",$text);
            if($length>0){
                $text=mb_substr($text,$start,$length);
                $rez=(object)array();
                $rez->full=(mb_strlen($text)>=$length)?true:false;
                $rez->str=$text;
            }elseif($start>0)$rez=mb_substr($text,$start);
            else $rez=$text;
            return $rez;
        }
        function getFile($file){
            ob_start();
            include_once($file);
            return ob_get_clean();
        }
    }
?>