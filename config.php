<?
mb_internal_encoding('utf-8');
session_start();
setlocale(LC_ALL,"ru_RU.UTF-8");

$path=str_replace("\\",'/',dirname(__FILE__));

# путь к корню сайта
define('PATH',$path);
define('SITE','Мой сайт');
define('AUTHHOST',$_SERVER['HTTP_HOST']);
define('AUTHEXPIRE',315360000);

# внешние файлы
define('UPLOADS_PATH','/upload');

# база данных
define('HOST','127.0.0.1');
define('USER','root');
define('PASS','');
define('DBNAME','agro');
define('PREFIX','mods_');
define('PREFIX_CORE','core_');

# параметры шаблона
define('TPL_PATH','/template/custom');
define('ACT_PATH','/action/custom');

# почта
define('MAIL_SMTP_SEND',false);
define('MAIL_USER', 'noreply@mysite.ru');
define('MAIL_PASS', '');
define('MAIL_ADDR', '');
define('MAIL_SMTP', '');
define('MAIL_SMTP_PORT', 25);

#авторизация
define('ACTIVATIONDAYS', 10);

#настройки кэша
define('CACHE_PATH', '/cache');
define('CACHE_LIMIT', 60);

define('SEO_KEY','');
define('SEO_DESC','');
define('SEO_TITLE','My New Site');

define('FW_ERROR_LOG','fw_error');

define('TMP_UPLOAD_LIFE',60*60);

global $defMonth;
$defMonth[1]=array('январь','января');
$defMonth[2]=array('февраль','февраля');
$defMonth[3]=array('март','марта');
$defMonth[4]=array('апрель','апреля');
$defMonth[5]=array('май','мая');
$defMonth[6]=array('июнь','июня');
$defMonth[7]=array('июль','июля');
$defMonth[8]=array('август','августа');
$defMonth[9]=array('сентябрь','сентября');
$defMonth[10]=array('октябрь','октября');
$defMonth[11]=array('ноябрь','ноября');
$defMonth[12]=array('декабрь','декабря');

global $c_err;
$core=array('rq','tools','db','auth','tpl','image','mod','search');
foreach($core as $v){require_once(PATH.'/core/'.$v.'.php');}

list($act,$type,$var,$idobj,$route,$sub,$idobjS)=$c_rq->get(array('act','type','var','idobj','route','sub','idobjS'),'getpar');
$filter=(bool)$c_rq->get('filter','num');
$pg=$c_rq->get('pg','num');

if($pg){
	$_SESSION['pg']=$pg;
	$_SESSION['act']=$act;
	$_SESSION['type']=$type;
	$_SESSION['var']=$var;
	$_SESSION['idobj']=$idobj;
	$pg--;
}elseif(@$_SESSION['act']==$act&&@$_SESSION['type']==$type&&@$_SESSION['var']==$var&&@$_SESSION['idobj']==$idobj){
	if(@$_SESSION['pg']&&(isset($_GET['pg'])&&$_GET['pg']!=0))$pg=@$_SESSION['pg']-1;
	else $pg=0;
}elseif(!in_array(array('form','send','del','show'),array($act,$type,$var,$idobj,$route))){
	unset($_SESSION['pg'],$_SESSION['act'],$_SESSION['type'],$_SESSION['var'],$_SESSION['idobj']);
}

global $defMod;
$defMod=array('opr','user');