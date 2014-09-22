<?php

global $c_u_tools;
$c_u_tools=new c_u_tools();
class c_u_tools{

	/* add */
    function cpMenuAdd($group,$name,$link='#',$img='',$target=''){
        $this->cpMenuGrAdd($group,'{'.$group.'}');
        global $cpMenu;
        $cpMenu[$group]->childs[]=(object)array('name'=>$name,'link'=>$link,'img'=>$img,'target'=>$target,'active'=>false);
    }
    function cpMenuGrAdd($key,$name,$img='acces-denied-sign',$link='#'){
        global $cpMenu;
        if(count($cpMenu)==0) $cpMenu=array();
        if(!isset($cpMenu[$key])){
            $cpMenu[$key]=(object)array();
            $cpMenu[$key]->name=$name;
            $cpMenu[$key]->img=$img;
            $cpMenu[$key]->link=$link;
            $cpMenu[$key]->childs=array();
            $cpMenu[$key]->active=false;
        }
    }

	/* active */
    function cpMenuActive($find){
        global $cpMenu;
        $this->cpMenuDeactive();
        $this->cpMenuActiveStack($find,$cpMenu);
    }
    function cpMenuActiveStack($find,&$stack){
        foreach ($stack as $k=>$v) {
            if (tvar($v->childs)&&count($v->childs)>0){
                $return=$this->cpMenuActiveStack($find, $stack[$k]->childs);
                if($return){
                    $stack[$k]->active=true;
                    return true;
                }
            }elseif($v->link==$find){
                $stack[$k]->active=true;
                return true;
            }
        }
    }

	/* deactive */
    function cpMenuDeactive(){
        global $cpMenu;
        $this->cpMenuDeactiveStack($cpMenu);
    }
    function cpMenuDeactiveStack(&$stack){
        foreach ($stack as $k=>$v){
			$stack[$k]->active=false;
            if (tvar($v->childs)&&count($stack[$k]->childs)>0){
                $this->cpMenuDeactiveStack($stack[$k]->childs);
            }
        }
    }
}