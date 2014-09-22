<?
if(!isset($c_db))$c_err[3]['c_db']=true;
if(!isset($c_auth))$c_err[3]['c_auth']=true;
if(!isset($c_rq))$c_err[3]['c_rq']=true;
if(!isset($c_tools))$c_err[3]['c_tools']=true;
if(!defined('PATH'))$c_err[200]['PATH']=true;
if(!defined('UPLOADS_PATH'))$c_err[200]['UPLOADS_PATH']=true;

if(count($c_err)) $c_tools->fw_error('m_source');

define('M_USER_TYPE','user');
define('M_USER_LOCATION','/');
define('M_USER_LINK_USER','/'.M_USER_TYPE);
define('M_USER_LINK_ADMIN','/'.M_USER_TYPE.'/admin');

define('M_USER_ERROR_NOACTIVATE','Логин не активирован');
define('M_USER_ERROR_LOGINFAILED','Неверный логин или пароль');
define('M_USER_ERROR_CAPTCHA','Введите правильно код на рисунке');
define('M_USER_ERROR_PHONE_10_12','неправильно введен номер телефона');
define('M_USER_ERROR_EMAIL','Введите свою почту');
define('M_USER_ERROR_SHORTPASS','Пароль должен быть не менее 4х символов');
define('M_USER_ERROR_NOMAIL','Не удалось отправить письмо');
define('M_USER_ERROR_CODE','Неверный код');
define('M_USER_ERROR_NICK','Введите свой псевдоним (ник)');

define('M_USER_NAME','Пользователи');

global $m_user_array;
$m_user_array['group']=array();
$m_user_array['group']['admin']='Админстратор';
$m_user_array['groupImg']=array();
$m_user_array['groupImg']['admin']='/files/images/toolicons2/wrench-screwdriver.png';

?>