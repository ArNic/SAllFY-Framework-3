<?php

$c_tools->fw_error('c_mod','c_db|c_rq|c_tools');
global $c_auth;
$c_auth=new c_auth($c_db);
/**
 * Класс, отвечающий за авторизацию. Определение прав. Если на ресурсе нет необходимости авторизоваться можно данный класс выключить. Но тогда, надо обратить внмание на модули в которых проверяется права и статус авторизации пользователя.
 */
class c_auth{
	/**
	 * Имя таблицы отвечающей за данные авторизации.
	 * @var string
	 */
    public $table='auth';
	/**
	 * Cтатус авторизации. Определяется при подключении бибилиотеки.
	 * true - авторизован, false - не авторизован.
	 * @var bool
	 */
	public $exist;
	/**
	 * Данные о пользователе, хранящиеся в БД в виде объекта. Добавляются после авторизации. Рекомендуется брать именно отсюда не используя сессию.
	 * @var object
	 */
	public $data;
	/**
	 * Числовые массивы прав. Определяется хранимым статусом в бд. Для удобства можно использовать следующие записи
	 * @var object
	 */
	public $rights;
	/**
	 * Строковый статус. Аналогичен статусу в бд. Например "admin".
	 * @var string
	 */
	public $status;
	/**
	 * Массив, выбранный из $rights для взаимодействия с правами.
	 * @var array
	 */
	public $statusArr;
	/**
	 * Конструктор класса. Сначала предопределяются переменные exist и rights. Далее вызывается authenticate для авторизации. Далее, если авторизован данные из таблицы core_users добавляются к data. Или формируются права.
	 */
	public function __construct(){ // конструктор
		$this->data=(object)array();
		$this->exist=false;
			
		$this->rights=(object)array();
		$this->rights->unlog=array(1,3,5,7,9,11,13,15);
		$this->rights->log=array(2,3,6,7,10,11,14,15);
		$this->rights->moder=array(4,5,6,7,12,13,14,15);
		$this->rights->admin=array(8,9,10,11,12,13,14,15);

		$this->authenticate();
        if($this->exist)$this->addData('users','core_');
        else $this->rightsMake();
	}
	/**
	 * Установка модуля.
	 * Требуется для работы модуля. Вызвать перед работой или использовать файл install.php
	 */
	public function install(){
        global $c_db;
        $tbl=$this->table;
        $field=$fieldOpt=$tableOpt=array();
        $field['id']=array('int(11)','NOT NULL','auto_increment');
        $field['ip']=array('varchar(20)','NOT NULL');
        $field['login']=array('varchar(100)','NOT NULL');
		$field['email']=array('varchar(255)','NOT NULL');
        $field['pass']=array('varchar(40)','NOT NULL');
        $field['passMd5']=array('varchar(40)','NOT NULL');
        $field['activate']=array('varchar(35)','NOT NULL');
        $field['date']=array('datetime','NOT NULL');
        $fieldOpt[]='PRIMARY KEY (`id`)';
        $tableOpt='ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8';
        $c_db->tableInstall($tbl,$tableOpt,$field,$fieldOpt,PREFIX_CORE);

        $tbl='users';
        $field=$fieldOpt=$tableOpt=array();

        # общие системные поля
        $field['id']=array('int(11)','NOT NULL','auto_increment');
		$field['status']=array('varchar(20)','NOT NULL',' default \'\'');
		$field['reasonBan']=array('text','NOT NULL');
		$field['IVC']=array('text','NOT NULL');

        # индивидуальные поля
        $field['nick']=array('varchar(255)','NOT NULL');

        $fieldOpt[]='PRIMARY KEY (`id`)';
        $tableOpt='ENGINE=MyISAM DEFAULT CHARSET=utf8';
        $c_db->tableInstall($tbl,$tableOpt,$field,$fieldOpt,PREFIX_CORE);

        $tbl='iplist';
        $field=$fieldOpt=$tableOpt=array();
        $field['id']=array('varchar(15)','NOT NULL');
        $field['ip']=array('varchar(20)','NOT NULL');
        $field['count']=array('tinyint(4)','NOT NULL');
        $field['time']=array('int(11)','NOT NULL');
        $fieldOpt[]='PRIMARY KEY (`id`)';
        $tableOpt='ENGINE=MyISAM DEFAULT CHARSET=utf8';
        $c_db->tableInstall($tbl,$tableOpt,$field,$fieldOpt,PREFIX_CORE);
	}
	/**
	 * Добавление данных о пользователе из указанной таблицы.
	 * Данные пользователя дополняются данными указанной таблицы. Строка в таблице ищется по id пользователя.
	 * @see data
	 * @param string $table таблица, откуда будут добавляться данные
	 * @param string $prefix префикс таблицы
	 */
	public function addData($table='user',$prefix=PREFIX){
        if($this->exist){
            global $c_db;
            $c_db->table=$table;
            $arr=$c_db->show(array('id'=>$this->data->id),'*',$prefix);
            $this->data=(object)array_merge((array)$this->data,(array)$arr);
        }
        $this->rightsMake();
    }
	/**
	 * Определение прав пользователя.
	 * Исходя из статуса пользователя и статуса авторизации в БД, пользователю присваивается строковый статус и числовой массив статуса
	 * @see status, statusArr
	 */
	public function rightsMake(){
	    if(!$this->exist) $statusS='unlog';
	    elseif($this->data->status=='moder')$statusS='moder';
	    elseif($this->data->status=='admin')$statusS='admin';
	    else $statusS='log';
	    $this->data->status=$statusS;
		$this->statusArr=$this->rights->$statusS;
    }
	/**
	 * Возврат статуса авторизации. Нужен удобного вывода информации пользователю.
	 * @return string
	 */
	public function login(){
		if(isset($_SESSION['user_state'])){
			switch($_SESSION['user_state']){
				case 'noactivate': return 'NOACTIVATE';
				case 'loginfailed': return 'LOGINFAILED';
				case 'auth': return ($this->exist)?'OK':'LOGOUT';
				default: return 'NOLOGIN';
			}
		}
    }
	/**
	 * Уничтожить данные авторизации. Или по другому "разлогиниться"
	 */
	public function logout(){
        $this->exist = false;
        $this->set_cookie(0, 0, 0);
        session_destroy();
    }
	/**
	 * Функция ограничения числа проверок e-mail адресов с одного ip-адреса.
	 * Если количество превышает 10 проверок - активация email игнорируется.
	 * @return bool true - IP превысил лимит, false - проверка возможна
	 */
	public function test_ip(){ //
        global $c_db;
        $ip=$_SERVER['REMOTE_ADDR'];
        $c_db->table='iplist';
        $rtrn=$c_db->show(array('ip'=>$ip),'*',PREFIX_CORE);
        $t=time();
        if(isset($rtrn->time))$c_db->query('INSERT INTO `'.PREFIX_CORE.'iplist` (`ip`,`count`,`time`) VALUES("'.$ip.'",1,'.$t.')');
        else if($rtrn->count<10) $c_db->query('UPDATE `'.PREFIX_CORE.'iplist` SET `count`=`count`+1 WHERE `ip`="'.$ip.'" LIMIT 1');
        else if($t-$rtrn->time<1) return true; // запретить проверку email
        else $c_db->query('UPDATE `'.PREFIX_CORE.'iplist` SET `time`='.$t.' WHERE `ip`="'.$ip.'" LIMIT 1');
        return false; // разрешить проверку
    }
	/**
	 * Функция авторизации пользователя.
	 * Вначале пробует автризоваться при помощи сессии.
	 * Далее при помощи кукисов.
	 * Далее, если фданные поступают из формы (должен быть скрытый html-тег data с параметром name) при помощи внешних переменных login и pass.
	 * В случае успешной авторизации - проводятся стандартные операции идентификации пользователя, устанавливаются кукисы.
	 */
	private function authenticate(){
        global $c_db,$c_rq;
        if(@$_SESSION['user_state']=='auth'&&@$_SESSION['data']->login&&@$_SESSION['data']->passMd5){
            $c_db->table=$this->table;
            $sessTmp=&$_SESSION['data'];
            $rtrn=$c_db->show(array('login'=>$sessTmp->login,'passMd5'=>$sessTmp->passMd5,'activate'=>'yes'),'*',PREFIX_CORE);
        }
        if(!isset($rtrn->login)&&@$_COOKIE['c_login']&&@$_COOKIE['c_passw']){
            $c_db->table=$this->table;
            $rtrn=$c_db->show(array('login'=>$_COOKIE['c_login'],'passMd5'=>$_COOKIE['c_passw'],'activate'=>'yes'),'*',PREFIX_CORE);
        }
        if(isset($rtrn->login)) return $this->set_authenticated($rtrn);
        $data=$c_rq->get('data','name');
        $login=$c_rq->get('login','name');
        $passw=$c_rq->get('passw','pass');
        if($data=='login'&&$login&&$passw){
            $c_db->table=$this->table;
            $rtrn=$c_db->show(array('login'=>$login),'*',PREFIX_CORE);
            if(!isset($rtrn->login)) $_SESSION['user_state']='loginfailed';
            elseif($rtrn->activate!='yes')$_SESSION['user_state']='noactivate';
            else{
                $passw=md5($passw);
                if($rtrn->passMd5!=$passw)$_SESSION['user_state']='loginfailed';
                else{
                    $this->set_authenticated($rtrn);
					$this->set_cookie($login,$passw);
					$c_db->table=$this->table;
                    $c_db->update(array('ip'=>$_SERVER['REMOTE_ADDR']),array('id'=>$rtrn->login),PREFIX_CORE);
                }
            }
        }
    }
	/**
	 * Функция индентифиакции пользователя. Выполняет запись в сессию и передает начальные данные в data.
	 * @see data
	 * @param $arr начальный массив данных пользователя
	 */
	private function set_authenticated($arr){
        $_SESSION['user_state']='auth';
        $_SESSION['data']=$this->data=$arr;
        $this->exist=true;
    }
	/**
	 * Установка логина и md5 пароля в кукисов.
	 * @param $login
	 * @param $pwd
	 */
	private function set_cookie($login,$pwd){ // функция устанваливает куки
        setcookie('c_login', $login, AUTHEXPIRE, '/', AUTHHOST, false, true);
        setcookie('c_passw', $pwd, AUTHEXPIRE, '/', AUTHHOST, false, true);
    }
} ?>