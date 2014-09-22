<?php

global $m_user_admin_m;
$m_user_admin_m=new m_user_admin_m();
class m_user_admin_m{
    function listing($pg=0,$limit=20){
        global $c_db;
        $c_db->table='user';
        $arr=$c_db->listing(null,$limit,$pg*$limit);
        foreach($arr->rez as $k=>$v){
            $c_db->table='users';
            $vT=$c_db->show(array('id'=>$v->id),'*',PREFIX_CORE);
            $c_db->table='auth';
            $vTs=$c_db->show(array('id'=>$v->id),'*',PREFIX_CORE);
            $arr->rez[$k]=(object)array_merge((array)$vTs,(array)$vT,(array)$v);
            if(!$arr->rez[$k]->status)$arr->rez[$k]->status='user';
        }
        return $arr;
    }
	function save($id=false){
		global $c_db,$c_rq;
		$insF=$updF=array();
		$insS=$updS=array();
		$err=$c_rq->getR($insF,array('login|name','email|email'),'err_save');
		if($err)return $err;
		
		$err=$c_rq->getR($insF,array('pass|pass'),(!$id)?'err_save':'');
		if($err)return $err;

		foreach($_POST['group'] as $v){
			$insS['group'][]=$c_rq->get('-','name',$v);
		}
		$insFS['status']=(in_array('admin',$insS['group']))?'admin':'';
		$insS['group']=implode(',',$insS['group']);

		if(isset($insF['pass'])){
			$insF['passMd5']=md5($insF['pass']);
			unset($insF['pass']);
		}
		
		$updF=$insF;
		$updFS=$insFS;
		$updS=$insS;
		if($id){
			$insF['id']=$insFS['id']=$insS['id']=$id;
			$c_db->table='auth';
			$c_db->insupd($insF,$updF,PREFIX_CORE);
			$c_db->table='users';
			$c_db->insupd($insFS,$updFS,PREFIX_CORE);
			$c_db->table='user';
			$c_db->insupd($insS,$updS,PREFIX);
		}else{
			$c_db->table='auth';
			$insF['activate']='yes';
			$id=$c_db->insert($insF,PREFIX_CORE);
			if($id){
				$insFS['id']=$insS['id']=$id;
				$c_db->table='users';
				$c_db->insupd($insFS,$updFS,PREFIX_CORE);
				$c_db->table='user';
				$c_db->insupd($insS,$updS,PREFIX);
			}
		}
	}
	function del($id){
		global $c_db;
		$c_db->table='auth';
		$c_db->delete(array('id'=>$id),1,PREFIX_CORE);
		$c_db->table='user';
		$c_db->delete(array('id'=>$id),1,PREFIX_CORE);
		$c_db->table='user';
		$c_db->delete(array('id'=>$id));
	}
	function element($id){
		global $c_db;
		$array=array();
		$array['id']=$id;
		$c_db->table='auth';
		$auth=$c_db->show($array,'*',PREFIX_CORE);
		$c_db->table='user';
		$user=$c_db->show($array,'*',PREFIX);
		return (object)array_merge((array)$auth,(array)$user);
	}
}