<?php
global $m_user_unlog_m;
$m_user_unlog_m=new m_user_unlog_m();
class m_user_unlog_m{
	function login(){
		global $c_auth,$c_tools;
		$status=$c_auth->login();
		if(!in_array($status,array('OK','LOGOUT'))) $c_tools->bcAdd('Авторизация');
		return $status;
	}
	public function register(){
		global $c_auth;
		if(!$c_auth->exist){
			global $c_rq,$c_db,$c_tools;
			$request=(object)array();
			if($c_rq->check('data')=='undefined') return array($request,'NOERROR');

			$request->password=$c_rq->get('passw','pass');
			if(!$request->password||strlen($request->password)<5) return array($request,'SHORTPASS');

			$request->email=$c_rq->get('email','email');
			$testmail=$this->test_mail($request->email);
			if($testmail>0){
				if($testmail==2) return array($request,'EMAIL_DUBL');
				else return array($request,'EMAIL');
			}

			$request->login=$c_rq->get('login','name');
			if(!$request->login||strlen($request->login)<4) return array($request,'LOGIN');
			else{
				$c_db->table=$c_auth->table;
				$obj=$c_db->show(array('login'=>$request->login),'*',PREFIX_CORE);
				if(isset($obj->id)) return array($request,'LOGIN_DUBL');
			}
            #----------------------------------
			$request->capcha=$c_rq->get('captcha_code','en,num');
			if(!$request->capcha||!isset($_SESSION['SecretPass'])||($request->capcha!=$_SESSION['SecretPass'])) return array($request,'CAPTCHA');

			# работа с таблицей авторизации
			$c_db->table=$c_auth->table;
			$array=array();
			$array['login']=$request->login;
			$array['email']=$request->email;
			$array['ip']=$_SERVER['REMOTE_ADDR'];
			#$pass=$c_tools->genPass(10,0,0,1);
			$array['passMd5']=md5($request->password);
			$array['activate']='yes';
			$array['date']=gmdate('Y-m-d H-i-s');
			$c_db->table=$c_auth->table;
			$c_db->insert($array,PREFIX_CORE);
        #--------добавление ника пользователя
            $tvar=$c_db->show(array('email'=>$array['email']),'*',PREFIX_CORE);
            $c_db->table='users';
            $nick['id']=$tvar->id;
            $nick['status']='user';
            $c_db->insert($nick,PREFIX_CORE);
        #---------------------------
                $text=array(
                    'SITE'=>SITE,
                    'FORM'=>'http://'.$_SERVER['HTTP_HOST'].'/user/login.html',
                    'LOGIN'=>$request->email,
                    'PWD'=>$request->password
                );
                $text=$c_tools->getMessage('register',$text);
                $c_tools->send_mail($request->email,$text[0],$text[1]);
			return array($request,'OK');
		}else return 'REG';
	}
	function restore(){
        global $c_db,$c_rq,$c_tools,$c_auth;
        if($c_rq->check('data')=='undefined') return 'NOERROR';
        $mail=$c_rq->get('email','email');
        if($mail){
            $c_db->table='auth';
            $where=array();
            $where['email']=$mail;
            $rtrn=$c_db->show($where,'*',PREFIX_CORE);
            if(isset($rtrn->id)){
                unset($where);
                $where=array();
                $pass=$c_tools->genPass();
                $array['passMd5']=md5($pass);
                $where['id']=$rtrn->id;
                $c_db->update($array,$where,PREFIX_CORE);
                $text=array(
                    'SITE'=>SITE,
                    'FORM'=>'http://'.$_SERVER['HTTP_HOST'].'/user/login.html',
                    'LOGIN'=>$rtrn->login,
                    'PWD'=>$pass
                );
                $text=$c_tools->getMessage('restore',$text);
                $c_tools->send_mail($mail,$text[0],$text[1]);
            }else return 'NO_EMAIL';
        }
        return 'OK';
	}
    public function test_mail($m){ // функция проверки наличия email в базе и его соответствия стандартам
        global $c_db,$c_auth;
        if($c_auth->test_ip()) return 3;
        if(!preg_match('/^[-_\.a-zA-Z0-9]+@[-_\.a-zA-Z0-9]+\.[a-zA-Z]+$/',$m)) return 1;
        $c_db->table=$c_auth->table;
        $rtrn=$c_db->show(array('email'=>$m),'*',PREFIX_CORE);
        return (isset($rtrn->id))?2:0;
	}
}