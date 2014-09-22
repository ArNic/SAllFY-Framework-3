<?php
global $m_user_log_m;
$m_user_log_m=new m_user_log_m();
class m_user_log_m{
	public function profileF(){
		global $c_tools;
		$status=$this->profile();
		if($status!='OK') $c_tools->bcAdd('Личные настройки');
		return $status;
	}
    public function profile(){ // редактирование регистрационных данных
        global $c_auth,$c_rq,$c_db;
        $dt=$c_auth->data;
        if($dt->id){
            $request=(object)array();
            if($c_rq->check('data')=='undefined') return 'NOERROR';

            $request->name=$c_rq->get('name','companyName');
            $request->pass=$c_rq->get('passw','en,num');

            $request->capcha=$c_rq->get('captcha_code','en,num');
            if(!$request->capcha||!isset($_SESSION['SecretPass'])||($request->capcha!=$_SESSION['SecretPass'])) return 'CAPTCHA';

            $arraySGl=$arrayGl=$arrayLoc=array();
            if($request->pass&&strlen($request->pass)<=6) return 'SHORTPASS';

            if($request->pass) $arraySGl['passMd5']=md5($request->pass);

            if(count($arraySGl)>0){
                $c_db->table=$c_auth->table;
                $c_db->update($arraySGl,array('id'=>$dt->id),PREFIX_CORE);
            }
            if(count($arrayGl)>0){
                $c_db->table='users';
                $ins=$upd=$arrayGl;
                $ins['id']=$dt->id;
                $c_db->insupd($ins,$upd,PREFIX_CORE);
            }
            if(count($arrayLoc)>0){
                $c_db->table='user';
                $ins=$upd=$arrayLoc;
                $ins['id']=$dt->id;
                $c_db->insupd($ins,$upd);
            }
            return 'OK';
        }
    }
}