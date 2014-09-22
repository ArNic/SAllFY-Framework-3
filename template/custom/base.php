<?php
class tplBase{
	/************************* Основное ************************/
		static function blSt($t,$c=false){?>
			<div class="block">
				<? if($t){ ?><div class="tit"><?=$t?></div><?}?>
				<? if($c){ ?><div class="content"><? } ?>
		<? }
		static function blFin($c=false){?>
				<?=(($c)?'</div>':'')?>
			</div>
		<?}
		static function block($t='',$c='',$img='',$class='',$id=''){
            if($img){?><div class="block-bk"><?}?>
                <div class="block <?=$class?>" <?=($id)?" id='$id'":''?> >
                    <? if($t){ ?><div class="tit"><?=$t?></div><? } ?>
                    <? if($c){ ?><div class="content"><?=$c?></div><? } ?>
                </div>
                <? if($img){ ?><img class="imgbk" src="<?=TPL_PATH.'/img/block-bg/'.$img.'.png'?>" /></div><? } ?>
        <? }
		static function blocks($array=array()){
			foreach($array->rez as $v){ $t=stripslashes($v->name); $c=stripslashes($v->text); ?>
				<div class="block">
				<? if($t){ ?><div class="tit"><?=$t?></div><? } ?>
				<? if($c){ ?><div class="content"><?=$c?></div><? } ?>
				</div>
			<?}
		}
        static function listPg($parts,$part,$link,$pattern='?'){
            print '<div class="listPg ac">';
            if($part-4>=1)print '<a href="'.$link.$pattern.'pg=1" class="pg">1</a> ';
            if($part-4>1) print '<span class="more">...</span> ';
            if($part-3>=1){print '<a href="'.$link.$pattern.'pg='.($part-3).'" class="pg">'.($part-3).'</a> ';}
            if($part-2>=1){print '<a href="'.$link.$pattern.'pg='.($part-2).'" class="pg">'.($part-2).'</a> ';}
            if($part-1>=1){print '<a href="'.$link.$pattern.'pg='.($part-1).'" class="pg">'.($part-1).'</a> ';}
            print '<span class="pg">'.$part.'</span> ';
            if($part+1<=$parts)print '<a href="'.$link.$pattern.'pg='.($part+1).'" class="pg">'.($part+1).'</a> ';
            if($part+2<=$parts)print '<a href="'.$link.$pattern.'pg='.($part+2).'" class="pg">'.($part+2).'</a> ';
            if($part+3<=$parts)print '<a href="'.$link.$pattern.'pg='.($part+3).'" class="pg">'.($part+3).'</a> ';
            if($part+4<$parts) print '<span class="more">...</span> ';
            if($part+4<=$parts)print '<a href="'.$link.$pattern.'pg='.($parts).'" class="pg">'.$parts.'</a>';
            print '</div>';
        }
        static function menu($type=0,$array=array(),$startInA='',$finishInA='',$classBl='',$idFirst='',$classLink='',$active=false){
            global $c_auth;
            foreach($array as $k=>$v){
                if(in_array($v->right,$c_auth->statusArr)){
                    $line='';
                    $line.=($type)?'<td ':'<li ';
                    $line.='class="';
                    if($k==0)$line.='first_menu ';
                    if($classBl) $line.=$classBl.' ';
                    if($active) $line.='menu_active';
                    $line.='" ';
                    if($k===0&&$idFirst) $line.='id="'.$idFirst.'" ';
                    $line.='><a href="'.$v->link.'" ';
                    if($classLink) $line.='class="'.$classLink.'" ';
                    $line.='>';
                    if($startInA) $line.=$startInA;
                    $line.=stripslashes($v->name);
                    if($finishInA) $line.=$finishInA;
                    print $line.'</a>'.(($type)?'</td>':'</li>');
                }
            }
        }
        static function breadCrumbs($array,$delimer=' &raquo; '){
            $cnt=count($array);
            foreach($array as $k=>$v){
                $end=((!tvar($v->href)||$cnt==$k+1)&&!tvar($v->link));
                $rez[]=((!$end)?'<a href="'.$v->href.'">':'').$v->txt.((!$end)?'</a>':'');
            }
            print implode($delimer,$rez);
        }
        static function treeListing($arr,$size=0,$string='',$tabulator='&nbsp;'){
            if($string){
                $str='';
                foreach($arr as $k=>$v){
                    $tab=str_repeat($tabulator,$size);
                    print sprintf($string,$tab,$v->id,$v->title);
                    if($v->childs) $str.=tplBase::treelisting($v->childs,$size+1,$string,$tabulator );
                }
                return $str;
            }
        }
    /*************************** Фильтр *************************/
        static function filterSelect($id,$link,$filter,$key=false,$array=false,$sname='',$fname=''){
            if(strlen($id)){
                $linkTmp=$link.'?'.implode('&',$filter);
                if(is_string($array)){$name=$array;$array=false;}
                elseif(0===(int)$id&&!isset($array[$id])) $name='основной раздел';
                elseif($array)$name=$array[$id];
                else $name=$id;
                $name=$sname.$name.$fname;
                ?>
                <div class="control_panelSelect">
                    <? if($array){?>
                        <div class="control_panelSelectText"><?=$name?>
                            <div class="selectDown">
                                <?foreach($array as $k=>$v){
                                    if($k!=$id){?><a href="<?=$linkTmp.'&filter_'.$key.'='.$k.'&pg=0'?>"><?=$v?></a><?}
                                }?>
                            </div>
                        </div>
                        <?if(count($array)>1){?>
                            <a class="control_panelSelectButtonDown">&nabla;</a>
                        <?}
                    }else{?>
                        <?=$name?>
                    <?} ?>
                    <a href="<?=$linkTmp?>" class="control_panelSelectButton">X</a>
                </div>
            <?}
        }
        static function filterLink($link,$id,$k,$array=false,$beforeLine='',$afterLine=''){
	        if(is_string($array))$array=array('',$array);
	        if(0===(int)$id&&!isset($array[$id])) $array[$id]='основной раздел';
	        if(!isset($array[$id])||$array[$id]!=''){
		        if($beforeLine)print $beforeLine;
				if($k!=$id) print '<a href="'.$link.'">';
				if($array) print ($array[$id])?$array[$id]:'уд['.$id.']';
				else print $id;
				if($k!=$id) print '</a>';
		        if($afterLine) print $afterLine;
	        }
        }
	    static function orderLink($link,$name='',$asc=0,$beforeLine='',$afterLine=''){
			if($beforeLine)print $beforeLine;
			print '<a href="'.$link.'">'.((!is_null($asc))?(($asc)?'&#9650;':'&#9660;'):'').$name.'</a>';
			if($afterLine) print $afterLine;
        }
    /*************************** Кнопки *************************/
        static function buttonForm($link){
            print '<a class="linkImgForm " href="'.$link.'route=form"><img src="/files/images/toolicons/application_form_edit.png" alt="редактировать" /></a> ';
        }
        static function buttonDel($link){
            print '<a class="linkImgDel" href="'.$link.'route=del" onclick="return confirm(\'Удалить?\');"><img src="/files/images/toolicons/bin_empty.png" alt="удалить"/></a> ';
        }
	/*************************** Блоки *************************/
        static function loginBox(){
            global $c_auth;
            if(!$c_auth->exist){?>
                <form action="/user/login.html" method="POST">
                    <table width="100%">
                        <tr><td>E-mail:</td><td><input type="text" class="input_text" name="mail"></td></tr>
                        <tr><td>Пароль:</td><td><input type="password" class="input_text" name="passw"></td></tr>
                        <tr><td colspan="2" align="center"><input type="submit" value="Войти"></td></tr>
                    </table>
                </form>
                <p style="font-size:10px;"><a href="/user/register.html">Зарегистрироваться</a><br/><a href="/user/restore.html">Восстановить пароль</a></p>
            <?}else{?>
                <a href='/user/logout.html'>Выйти</a>
            <?}
        }
	/************************ В теге Head **********************/
        static function jsInclude($file){ ?>
            <script type="text/javascript" src="<?=TPL_PATH?>/js/<?=$file?>.js"></script>
        <?}
		static function jsTinyMCE(){ ?>
			<script type="text/javascript" src="/files/js/tiny_mce/tiny_mce.js"></script>
			<script type="text/javascript">
				tinyMCE.init({
					language : "ru",
					mode : "exact",
					theme:"simple",
					elements:"tinymce1,tinymce2",
					force_br_newlines : true,
					force_p_newlines : false,
					forced_root_block : '', // Needed for 3.x
					convert_urls : false
				});
				tinyMCE.init({
					language : "ru",
					mode : "exact",
					theme:"advanced",
					elements:"tinymceA1,tinymceA2",
					force_br_newlines : true,
					force_p_newlines : true,
					plugins : "safari,spellchecker,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template",

					// Theme options
					theme_advanced_buttons1 : "save,newdocument,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist",
					theme_advanced_buttons2 : "outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,help,code,|,insertdate,inserttime,preview,|,forecolor,backcolor",
					theme_advanced_buttons3 : "hr,removeformat,visualaid,|,insertlayer,moveforward,movebackward,absolute,|,styleprops,spellchecker,|,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,template,blockquote,pagebreak",
					theme_advanced_buttons4 : "charmap,emotions,iespell,media,advhr,|,styleselect,formatselect,fontselect,fontsizeselect",
					theme_advanced_buttons5 : "tablecontrols,|,sub,sup,|,print,|,ltr,rtl,|,fullscreen",
					theme_advanced_toolbar_location : "top",
					theme_advanced_toolbar_align : "left",
					theme_advanced_statusbar_location : "bottom",
					theme_advanced_resizing : true,
					convert_urls : false
				});
			</script>
		<? }
		static function jsJQuery(){?>
            <script type="text/javascript" src="/files/js/jquery.js"></script>
        <?}
		static function jsJQUI(){?>
            <script type="text/javascript" src="/files/js/jquery.ui.js"></script>
        <?}
		static function cssInclude($file){?>
            <link rel="stylesheet" type="text/css" media="screen" href="<?=TPL_PATH?>/css/<?=$file?>.css" />
        <?}
    /*************************** Разработка *************************/
        static function formGenerator($fields=array(),$action='',$method='',$attr=''){
            $attrs=array();
            if($action) $attrs[]="action='$action'";
            if($method) $attrs[]="method='$method'";
            if($attr) $attrs[]=$attr;
            /* start TPL */
            $rez='<form class="form" '.implode(' ',$attrs).'><table>';
            foreach($fields as $v){$rez.='<tr><td>'.$v->title.'</td><td>'.$v->field.'</td></tr>';}
            $rez.='</table></form>';
            /* finish TPL */
            return $rez;
        }
        static function selectOpts($arr,$id,$string='',$return=false,$check='selected',$def=0){
            if($string){
                $str='';
                if(!$arr)$arr=array();
                foreach($arr as $k=>$v){
	                if(is_null($id))$status=($k==$def);
					elseif(is_array($id))$status=in_array($k,$id);
	                else $status=($k==$id);
	                $rtrnTmp=sprintf($string,$k,$v,($status)?"$check='$check'":'');
	                if($return) $str.=$rtrnTmp;
	                else print $rtrnTmp;
                }
                return $str;
            }
        }
        static function treeSelectOpts($arr,$id,$size=0,$string='',$tabulator='&nbsp;',$return=false){
            if($string){
                $str='';
                if(!$arr)$arr=array();
                foreach($arr as $v){
	                $tab=str_repeat($tabulator,$size);
	                $rtrnTmp=sprintf($string,$tab,$v->id,$v->title,($v->id==$id)?'selected="selected"':'');
	                if($return) $str.=$rtrnTmp;
	                else print $rtrnTmp;
		            if(isset($v->childs)&&$v->childs) $str.=tplBase::treeSelectOpts($v->childs,$id,$size+1,$string,$tabulator,$return);
                }
                return $str;
            }
        }
        static function treeSelectOptsGaP($arr,$id,$type,$size=0,$string='',$tabulator='&nbsp;',$return=false){
            if($string){
                $str='';
                if(!$arr)$arr=array();
                foreach($arr as $k=>$v){
	                $tab=str_repeat($tabulator,$size);
	                $rtrnTmp=sprintf($string,$tab,$v->id,$v->title,($v->id==$id&&$v->gap==$type)?'selected="selected"':'',(!$v->gap)?'GaP_Group':'GaP_Page',$v->gap);
	                if($return) $str.=$rtrnTmp;
	                else print $str;
		            if($v->childs) $str.=tplBase::treeSelectOptsGaP($v->childs,$id,$type,$size+1,$string,$tabulator,$return);
                }
                return $str;
            }
        }
		static function showError($err=false,$conPrefix,$exclude=null){
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
        static function makeDateStr($date){
            global $defMonth;
            $date=strtotime($date);
            $rez=@date("j",$date).' '.$defMonth[date("n",$date)][1];
            $rez.=' '.((@date("Y",$date)!=date("Y"))?@date("Y",$date).' года':'');
            print $rez;
        }
		static function makeDate($date,$time='H:i',$return=false){
			global $defMonth;
			$rez=@date("j",$date).' '.$defMonth[date("n",$date)][1];
			$rez.=' '.((@date("Y",$date)!=date("Y"))?@date("Y",$date).' года':'');
			if($time) $rez.=' '.date($time,$date);
			if($return) return $rez;
			else print $rez;
		}
}