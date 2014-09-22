<?php
$c_tools->fw_error('c_mod','c_db|c_rq|c_image|c_tools','PREFIX');
global $c_mod_gap;
$c_mod_gap=new c_mod_gap();
/**
 * Работа со сложной иерархей хранимой информации GaP.
 * GaP (Group and Pages). В такой иерархии родителями могут быть не только группы, но и сами записи.
 */
class c_mod_gap{
	/**
	 * Проверяет, является ли родителем текущей страницы - запись
	 * Определяет исходя из значения переменной $type
	 * Ориентируется на окончание "_gap". Если запись содержит "_gap" - то это страница
	 * @param bool $typeTmp если надо указать другую переменную для идентификации
	 * @return int 1 - страница, 0 - группа
	 */
	function is_page($typeTmp=false){
		if(!$typeTmp){
			global $type;
			$typeTmp=$type;
		}
		return (int)(strpos($typeTmp,'_gap')!==false);
	}
	/**
	 * Формирование ссылки исходя из родителя страницы и переданной ссылки
	 * @param $link ссылка c $type='listing'
	 * @param null $sub_type указать желаемый тип, если не надо определять стандартным методом
	 * @return string
	 */
	function href($link,$sub_type=null){
		$href='';
		$ispage=(is_null($sub_type))?$this->is_page():$sub_type;
		if(!$ispage) $href.=$link;
		else $href.=str_replace('listing','listing_gap',$link);
		return $href;
	}
	/**
	 * Формирование подчиненного дерева, указанного родителя
	 * @param $table таблица
	 * @param null $where условия
	 * @param int $group id родителя
	 * @param int $type тип родителя: 0 - группа, 1 - страница
	 * @param int $pg номер страницы
	 * @param int $limit число записей
	 * @param string $fields поля
	 * @param string $order сортировка
	 * @param string $prefix префикс
	 * @return object возвращает объект с деревом подчиненных элементов, разеденных на: горячие переходы (fav), группы (childs), записи (contents):
	 */
	function listingToolTree($table,$where=null,$group=0,$type=0,$pg=0,$limit=20,$fields='*',$order='+`id`',$prefix=PREFIX){
		if(is_array($order))list($order,$orderGr)=$order;
		else $orderGr='+id';

		$grp=$cnt='';
		$grp->table=$table.'_group';
		$grp->order=$orderGr;
		$cnt->fields=$fields;
		$cnt->table=$table;
		$cnt->where=$where;
		$cnt->order=$order;
		$grp->prefix=$cnt->prefix=$prefix;
		$obj=$this->treeGet($grp,$cnt,$group,$type,$pg,$limit);
		$obj->type='tree';
		return $obj;
	}
	/**
	 * Вывод списка с автоопределением в каком виде выводить: с фильрацией или в виде дерева
	 * @param $table таблица
	 * @param null $where условия
	 * @param int $group id родителя
	 * @param int $type тип родителя: 0 - группа, 1 - страница
	 * @param int $pg номер страницы
	 * @param int $limit число записей
	 * @param bool $filter фильтр
	 * @param string $fields поля
	 * @param string $order сортировка
	 * @param string $prefix префикс
	 * @return array|object
	 */
	function listing($table,$where=null,$group=0,$type=0,$pg=0,$limit=20,$filter=false,$fields='*',$order='+`id`',$prefix=PREFIX){
		global $c_mod;
	    $pg=(int)$pg;
	    if($c_mod->testArr($filter)) $obj=$c_mod->listingToolFilter($table,$where,$group,$pg,$limit,$filter,$fields,$order,$prefix);
	    else $obj=$this->listingToolTree($table,$where,$group,$type,$pg,$limit,$filter,$fields,$order,$prefix);
	    return $obj;
	}
	/**
	 * Получить подиненное дерево родителя
	 * @param $grp string|object объект или строка с параметрами таблицы групп (fields,order,where,prefix,table)
	 * @param $cnt string|object объект или строка с параметрами таблицы записей  (fields,order,where,prefix,table)
	 * @param int $sub id родителя
	 * @param int $type тип родителя: 0 - группа, 1 - страница
	 * @param int $pg номер страницы
	 * @param int $limit число записей
	 * @return object возвращает объект с деревом подчиненных элементов, разеденных на: горячие переходы (fav), группы (childs), записи (contents):
	 */
	function treeGet($grp,$cnt,$sub=0,$type=0,$pg=0,$limit=20){
		global $c_db;
		$parents=$fav=$childs=$contents=array();
		$c_db->query('/* ------------------------- */');
		if(is_string($grp))$grp=(object)array('table'=>$grp);
		if($grp->table){
			if(!$grp->fields)$grp->fields='*';
			if(!$grp->order)$grp->order='';
			if(!$grp->where)$grp->where='';
			if(!$grp->prefix)$grp->prefix=PREFIX;
			if(is_string($cnt))$cnt->table=$cnt;
			if($cnt->table){
				if(!$cnt->fields)$cnt->fields='*';
				if(!$cnt->order)$cnt->order='';
				if(!$cnt->where)$cnt->where='';
				if(!$cnt->prefix)$cnt->prefix=PREFIX;
				$c_db->table=$cnt->table;
				$cnt->where['sub']=$sub;
				$cnt->where['sub_type']=$type;
				if($pg==='all'){$limit=1000000;$pg=0;}
				$contents=$c_db->listing($cnt->where,$limit,$pg*$limit,$cnt->order,$cnt->fields,false,$cnt->prefix);
			}else $contents=false;
			$contents->gap=1;

			$c_db->table=$grp->table;
			$grp->where['sub']=$sub;
			$grp->where['sub_type']=$type;
			$childs=$c_db->listingU($grp->where,'',0,0,$grp->fields,$grp->prefix);

			if($grp->table&&$cnt->table){
				$iTmp=0;
				$typeTmp=$type;
				while($sub!=0&&$iTmp<40){
					$grpTmp=($typeTmp==0)?$grp:$cnt;
					$c_db->table=$grpTmp->table;
					$where=array('id'=>$sub);
					$parent=$c_db->show($where,$grpTmp->fields,$grpTmp->prefix);
					$parent->table_type=$typeTmp;
					$parents[]=$parent;
					$sub=$parent->sub;
					$typeTmp=$parent->sub_type;
					$iTmp++;
				}
				if(count($parents)>1) $fav[0]=(object)array('favtitle'=>'.','id'=>0);
				if(count($parents)>0){
					if(isset($parents[1])) $fav[1]=$parents[1];
					else $fav[1]->id=0;
					$fav[1]->favtitle='..';
				}
			}
			$c_db->query('/* ------------------------- */');
		}
		return (object)array('parents'=>$parents,'fav'=>$fav,'childs'=>$childs,'contents'=>$contents);
	}
	/**
	 * Получить рекурсивно id всех подчиненных записей
	 * Очень удобен для рекурсивного удаления группы
	 * @param $table таблица
	 * @param sub $id id родителя
	 * @param int $type тип родителя: 0 - группа, 1 - страница
	 * @param string $prefix префикс
	 * @return object возвращает объект массивом ключей подчиненных элементов, разеденных на: группы (childs), записи (contents):
	 */
	function childsAll($table,$id,$type=0,$prefix=PREFIX){
	    $rez=(object)array('childs'=>array(),'contents'=>array());
	    $obj=$this->childId($table.'_group',$id,$type,$table,null,'all',false,$prefix);

	    $fch=$rez->childs=$obj->childs;
	    $fco=$rez->contents=$obj->contents;
	    if(count($fch)>0){
	        foreach($fch as $k=>$v){
	            $tmpObj=$this->childsAll($table,$v->id,0,$prefix);
	            $rez->childs=array_merge($rez->childs,$tmpObj->childs);
	            $rez->contents=array_merge($rez->contents,$tmpObj->contents);
	        }
	    }
	    if(count($fco)>0){
	        foreach($fco as $k=>$v){
	            $tmpObj=$this->childsAll($table,$v->id,1,$prefix);
	            $rez->childs=array_merge($rez->childs,$tmpObj->childs);
	            $rez->contents=array_merge($rez->contents,$tmpObj->contents);
	        }
	    }
	    return $rez;
	}
	/**
	 * Получить рекурсивно id всех подчиненных записей
	 * @param $table таблица групп
	 * @param int $sub id родителя
	 * @param int $type тип родителя: 0 - группа, 1 - страница
	 * @param bool $tableS таблица записей
	 * @param bool $whereS условия для записей
	 * @param int $pg страница
	 * @param bool $order сортировка
	 * @param string $prefix префикс
	 * @return object возвращает объект массивом ключей подчиненных элементов, разеденных на: группы (childs), записи (contents):
	 */
	function childId($table,$sub=0,$type=0,$tableS=false,$whereS=false,$pg=0,$order=false,$prefix=PREFIX){
		global $c_db;
		$childs=$contents=array();
		$whereS['sub']=$where['sub']=$sub;
		$whereS['sub_type']=$where['sub_type']=$type;
		if($tableS){
			$c_db->table=$tableS;
			if($pg!='all') $contents=$c_db->listing($whereS,20,$pg*20,$order);
			else $contents=$c_db->listingU($whereS,false,0,0,'`id`,`sub`',$prefix);
		}
		$c_db->table=$table;
		$childs=$c_db->listingU($where,false,0,0,'`id`,`sub`',$prefix);
		return (object)array('childs'=>$childs,'contents'=>$contents);
	}
	/**
	 * Формирование GaP хлебных крошек
	 * Для того чтобы самому не делать пробег для поиска родителей - можно вызвать эту функцию
	 * Таблица группы определяется автомматически добавлением "_group" постфикса названия
	 * @param $table таблица
	 * @param int $sub id родителя
	 * @param bool|int $type тип родителя: 0 - группа, 1 - страница
	 * @param string $sh начало ссылки
	 * @param string $fh конец ссылки
	 * @param string $prefix префикс таблиц
	 */
	function bcTree($table,$sub=0,$type=false,$sh='',$fh='',$prefix=PREFIX){
		global $c_tools;
	    if($sub>0){
		    if($type===false)$type=$this->is_page();
		    $grp=$cnt='';
		    $grp->table=$table.'_group';
		    $cnt->table=$table;
		    $grp->fields=$cnt->fields='`id`,`title`,`sub`,`sub_type`';
		    $grp->prefix=$cnt->prefix=$prefix;
		    $obj=$this->treeGet($grp,$cnt,$sub,$type,0,20);
		    $obj=$obj->parents;
		    $obj[0]->sub_type=$type;
		    $obj=array_reverse($obj);
		    foreach($obj as $k=>$v){
			    if($v->table_type){
				    $shTmp=str_replace('listing','listing_gap',$sh).$v->id;
			    }else $shTmp=$sh.$v->id;
			    $c_tools->bcAdd($v->title,$shTmp.$fh);
		    }
	    }
	}
	function treeFull_childs($table,$sub,$sub_type,$type,$order,$fields,$prefix){
		global $c_db;
		$rez=array();
		$c_db->table=$table.((!$type)?'_group':'');
		$where['sub']=$sub;
		$where['sub_type']=$sub_type;
		$childs=$c_db->listingU($where,'',0,0,$fields,$prefix);
		if($sub==0&&$type==0)$rez[0]=(object)array('id'=>0,'title'=>'Нет','sub'=>0,'sub_type'=>0);
		foreach($childs as $v){
			$rezTmp=$v;
			$rezTmp->gap=$type;
			$childsS=$this->treeFull($table,$v->id,$type,$order,$fields,$prefix);
			if(count($childsS)>0) $rezTmp->childs=$childsS;
			if($sub==0) $rez[0]->childs[]=$rezTmp;
			else $rez[]=$rezTmp;
		}
		return $rez;
	}
	function treeFull($table,$sub=0,$type=0,$order=false,$fields='`id`,`title`,`sub`,`sub_type`',$prefix=PREFIX){
		$rezG=$this->treeFull_childs($table,$sub,$type,0,$order,$fields,$prefix);
		$rezP=$this->treeFull_childs($table,$sub,$type,1,$order,$fields,$prefix);
		$rez=array_merge($rezG,$rezP);
		return $rez;
	}
	function rqSub(&$array=null){
		global $c_rq;
		$group=$c_rq->get('group','getpar');
		$group=explode('_',$group);
		if($array){
			$array['sub']=$group[0];
			$array['sub_type']=$group[1];
		}else return $group;
	}
}

global $c_mod;
$c_mod=new c_mod();
/**
 * Работа с простой структурой иерархии
 * В такой структуре родителями являются только группы
 */
class c_mod{
	/**
	 * Пробует вычислить имя папки модуля, на соновании указанного пути (часто аналогичен названию модуля)
	 * @param  $path путь для разбора
	 * @return bool|mixed название папки или false
	 */
	function folderName($path){
		$match=preg_match('![\\\\/]?module[\\\\/]?.*?[\\\\/](.*?)[\\\\/]!',$path,$array);
		if($match&&$array[1]) return str_replace("\\","/",$array[1]);
		else return false;
	}
	/**
	 * Пробует вычислить чистый путь до папки модуля, на основании указанного пути
	 * @param  $path путь для разбора
	 * @return bool|mixed путь или false
	 */
	function folderPath($path){
		$match=preg_match('!(.*?module[\\\\/]?.*?[\\\\/].*?)[\\\\/].!',$path,$array);
		if($match&&$array[1]) return str_replace("\\","/",$array[1]);
		else return false;
	}
	/**
	 * Проверка значения - является ли строкой и есть ли содержимое
	 * @param  $str проверяемое значение
	 * @return bool
	 */
	function testStr($str){ return (is_string($str)&&strlen($str)>0); }
	/**
	 * Проверка значения - является ли массивом и есть ли содержимое
	 * @param  $arr проверяемое значение
	 * @return bool
	 */
	function testArr($arr){ return (is_array($arr)&&count($arr)>0); }
	/**
	 * Проверка значения - является ли строкой/массивом и есть ли содержимое
	 * @param  $line проверяемое значение
	 * @return bool
	 */
	function testSA($line){ return ($this->testStr($line)||$this->testArr($line)); }
	/**
	 * Показать значение указанной таблицы
	 * @param  $table таблица
	 * @param array $where условие отбора
	 * @param string $prefix префикс таблицы
	 * @return an|array|object пустой массив или результат
	 */
	function show($table,$where=array(),$prefix=PREFIX){
        global $c_db;
        $c_db->table=$table;
        $obj=$c_db->show($where,'*',$prefix);
        return ($obj)?$obj:array();
    }
	/**
	 * Запись или обновление данных в таблицу
	 * @param  $table таблица
	 * @param bool $id id записи
	 * @param array $array массив обновляемых данных
	 * @param array $where условия отбора
	 * @param string $prefix префикс таблицы
	 * @return bool|int|string id записи или название ошибки
	 */
	function save($table,$id=false,$array=array(),$where=array(),$prefix=PREFIX){
        global $c_db;
        $c_db->table=$table;
        if($this->testSA($where)||$id){
			if($id)$where['id']=$id;
			$c_db->update($array,$where,$prefix);
	        if($c_db->error()) return 'err_update';
        }else{
	        $id=$c_db->insert($array,$prefix);
	        return (!$id)?'err_insert':$id;
        }
        return $id;
    }
	/**
	 * Обработка загруженного изображения
	 * Значительным отличием от обычной пакетной обработки - формирование адреса пакета на основании id
	 * Используемый метод изменения изображения resize
	 * @param  $id id записи
	 * @param  $folder папка, куда будет записана картинка
	 * @param  $imgN ключ удаленного запроса с картинкой
	 * @param array $imgA массив размеров
	 * @return bool|string ошибка или либо err_no
	 */
	function uploadImg($id,$folder,$imgN,$imgA=array()){
        global $c_image,$c_tools;
        if($imgN&&!$imgN['error']&&$id){
            $id=str_split((string)$id);
            $uploadPath=PATH.UPLOADS_PATH.'/'.$folder.'/'.implode('/',$id);
            $c_tools->makefolder($uploadPath);
            foreach($imgA as $k=>$v){
                $resize=$c_image->resize($imgN['tmp_name'],$uploadPath.'/'.$k.'.jpg',$v->w,$v->h);
                if($resize) return $resize;
            }
	        return 'err_no';
        }elseif(!$imgN['error']) return 'err_no';
	    else return $imgN['error'];
    }
	/**
	 * Загрузка изображения | алогритм thumbnail | с фоном и пропорциональным подгоном под размеры
	 * @param  $id идентифиактор записи
	 * @param  $folder папка, куда будет записана картинка
	 * @param  $imgN ключ удаленного запроса с картинкой
	 * @param array $imgA массив размеров
	 * @param bool $thP отступ от края картинки
	 * @param bool $thC цвет фона
	 * @return bool|string ошибка или либо err_no
	 */
	function uploadImgA($id,$folder,$imgN,$imgA=array(),$thP=false,$thC=false){
        global $c_image,$c_tools;
        $imgNerr=$c_image->error($imgN);
        if(!$imgNerr&&$id){
            $id=str_split((string)$id);
            $uploadPath='/'.$folder.'/'.implode('/',$id);
            $c_tools->makefolder($uploadPath);
        	$resize=$c_image->upload($uploadPath,$imgN,$imgA,'','',$thP,$thC);
        	if($resize) return $resize;
	        return 'err_no';
        }elseif(!$imgNerr) return 'err_no';
		else return !$imgNerr;
    }
	/**
	 * Загрузка изображения | алогритм resizeCrop | с обрезкой выступающих краев
	 * @param  $id идентифиактор записи
	 * @param  $folder папка, куда будет записана картинка
	 * @param  $imgN ключ удаленного запроса с картинкой
	 * @param array $imgA - массив размеров
	 * @return bool|string
	 */
	function uploadImgB($id,$folder,$imgN,$imgA=array()){
        global $c_image,$c_tools;
        $imgNerr=$c_image->error($imgN);
        if(!$imgNerr&&$id){
            $id=str_split((string)$id);
            $uploadPath='/'.$folder.'/'.implode('/',$id);
            $c_tools->makefolder($uploadPath);
        	$resize=$c_image->uploadCrop($uploadPath,$imgN,$imgA,'','');
        	if($resize) return $resize;
	        return 'err_no';
        }elseif(!$imgNerr) return 'err_no';
		else return !$imgNerr;
    }
	/**
	 * Формирование изображения на основе локального файла
	 * Удобен при перегенерации изображений
	 * @param  $id идентификатор записи
	 * @param  $folder папка, куда будет записана картинка
	 * @param  $imgN название файла с указанием полного пути
	 * @param array $imgA массив размеров
	 * @return bool|string
	 */
	function regenImg($id,$folder,$imgN,$imgA=array()){
        global $c_image,$c_tools;
        $imgN=PATH.$imgN;
        if(file_exists($imgN)){
            $id=str_split((string)$id);
            $uploadPath=PATH.UPLOADS_PATH.'/'.$folder.'/'.implode('/',$id);
            $c_tools->makefolder($uploadPath);
            foreach($imgA as $k=>$v){
                $resize=$c_image->resize($imgN,$uploadPath.'/'.$k.'.jpg',$v->w,$v->h);
                if($resize) return $resize;
            }
        }
    }
	/**
	 * Удаление изображения
	 * @param  $id идентификатор записи
	 * @param  $folder папка, куда будет записана картинка
	 * @param array $imgA массив размеров
	 * @return void
	 */
	function deleteImg($id,$folder,$imgA=array()){
        if(is_string($id)&&strlen($id)>0){
            $id=str_split($id);
            $uploadPath=PATH.UPLOADS_PATH.'/'.$folder.'/'.implode('/',$id);
            foreach($imgA as $k=>$v){
                $filename=$uploadPath.'/'.$k.'.jpg';
                if(file_exists($filename))unlink($filename);
            }
        }elseif(is_array($id)&&count($id)>0){
           foreach($id as $v){
               $this->deleteImg($v,$folder,$imgA);
           }
        }
    }
	/**
	 * Удаление записи
	 * @param  $table таблица
	 * @param null|array|string $where условия отбора
	 * @param int $limit лимит удаляемых файлов (редко, но нужно)
	 * @param string $prefix префикс таблицы
	 * @return string false или err_where_empty
	 */
	function delete($table,$where,$limit=1,$prefix=PREFIX){
        global $c_db;
	if(!$this->testSA($where))  return 'err_where_empty';
	if($limit==0) return 'err_limit_is_zero';
	$whereFree=true;
	foreach($where as $v){
		if((is_array($v)&&count($v)>0)&&(is_string($v) && $v!=='')){
			$whereFree=false;
			break;
		}
	}
	if($whereFree) return 'err_where_empty';
        $c_db->table=$table;
	$c_db->delete($where,$limit,$prefix);
    }
	/**
	 * Список страниц в двух вариантах tree и filter
	 * @param  $table таблица
	 * @param null|array|string $where условия отбора
	 * @param int $group группировка
	 * @param int $pg страница
	 * @param int $limit лимит на странице
	 * @param bool $filter фильтры
	 * @param string $fields выводимые поля
	 * @param string $order сортировка
	 * @param string $prefix префикс таблицы
	 * @return array|object объект
	 */
	function listing($table,$where=array(),$group=0,$pg=0,$limit=20,$filter=false,$fields='*',$order='+`id`',$prefix=PREFIX){
        global $c_tools,$c_db;
		if(!$this->testArr($filter)){
			if(is_array($order)) list($order,$orderGr)=$order;
			else $orderGr='+id';
		}
        $pg=(int)$pg;
        if($this->testArr($filter)){
            $c_db->table=$table;
            $ov=$this->order($order);
            $fv=$this->filter($filter);
            $where=($where)?array_merge_recursive($fv->where,$where):$fv->where;
            $obj=$c_db->listing($where,$limit,$pg*$limit,$ov->order,$fields,false,$prefix);
            $obj->where=$where;
	        $obj->order=$ov->order;
	        $obj->orderLine=$ov->orderLine;
            $obj->filter=$fv->filter;
            $obj->filterLine=$fv->filterLine;
            $obj->filter['filter']=1;
            $obj->type='filter';
        }else{
	        $grp=(object)array();
			$cnt=(object)array();
			$grp->order=$orderGr;
	        $grp->table=$table.'_group';

			$ov=$this->order($order);
			$cnt->order=$ov->order;
	        $cnt->fields=$fields;
	        $cnt->table=$table;
	        $cnt->where=$where;
	        $grp->prefix=$cnt->prefix=$prefix;
	        $obj=$c_tools->treeGet($grp,$cnt,$group,$pg,$limit);
	        $obj->type='tree';
			$obj->order=$ov->order;
			$obj->orderLine=$ov->orderLine;
        }
        return $obj;
    }
	/**
	 * Выводит таблицу с фильтрацией
	 * @param $table таблица
	 * @param null $where условия отбора
	 * @param int $group группировка
	 * @param int $pg страница
	 * @param int $limit лимит на странице
	 * @param bool $filter фильтры
	 * @param string $fields выводимые поля
	 * @param string|array $order сортировка
	 * @param string $prefix префикс таблицы
	 * @return array|object объект
	 */
	function listingToolFilter($table,$where=null,$group=0,$pg=0,$limit=20,$filter=false,$fields='*',$order=array('id'=>1),$prefix=PREFIX){
		global $c_db;
		$c_db->table=$table;
		$fv=$this->filter($filter);
		$ov=$this->order($order);
		$where=(is_array($where))?array_merge($where,$fv->where):$fv->where;
		$obj=$c_db->listing($where,$limit,$pg*$limit,$ov->order,$fields,false,$prefix);
		$obj->where=$where;
		$obj->filter=$fv->filter;
		$obj->order=$ov->order;
		$obj->filterLine=$fv->filterLine;
		$obj->orderLine=$ov->orderLine;
		$obj->filter['filter']=1;
		$obj->type='filter';
		return $obj;
	}
	/**
	 * Выводит рекурсивно все подчиненные элемены группы
	 * @param  $table таблица
	 * @param  $id идентификатор текущей группы
	 * @param string $prefix префикс таблицы
	 * @return object
	 */
	function childsAll($table,$id,$prefix=PREFIX){
        global $c_tools;
        $rez=(object)array('childs'=>array(),'contents'=>array());
        $obj=$c_tools->childId($table.'_group',$id,$table,false,'all',false,$prefix);
        $fe=$rez->childs=$obj->childs;
        $rez->contents=$obj->contents;
        if(count($fe)>0){
            foreach($fe as $v){
                $tmpObj=$this->childsAll($table,$v->id,$prefix);
                $rez->childs=array_merge($rez->childs,$tmpObj->childs);
                $rez->contents=array_merge($rez->contents,$tmpObj->contents);
            }
        }
        return $rez;
    }
	/**
	 * Возвращает массив id из массива объектов
	 * @param array $array массив объектов
	 * @return void
	 */
	function makeIdArr($array=array()){
		$rez=array();
        foreach($array as $v){ $rez[]=$v->id; }
        return $rez;
    }
	/**
	 * Сбросить приоритет всех записей
	 *
	 * @param  $table таблица
	 * @param array $where условия отбора
	 * @param string $prefix префикс таблицы
	 * @return void
	 */
	function priorityReset($table,$where=array('priority'=>'0'),$prefix=PREFIX){
        global $c_db;
		$c_db->query("UPDATE `{$prefix}{$table}` SET `priority`=`id` ".$c_db->where($where));
    }
	/**
	 * Обменяться приоритетами
	 * @param  $table таблица
	 * @param  $first рабочая запись
	 * @param  $second запись донор
	 * @param string $prefix префикс таблицы
	 * @return void
	 */
	function priorityChange($table,$first,$second,$prefix=PREFIX){
        global $c_db;
        $c_db->table=$table;
        $first=$c_db->show(array('id'=>$first),'`priority`,`id`',$prefix);
        $second=$c_db->show(array('id'=>$second),'`priority`,`id`',$prefix);
		if($first&&$second){
			$c_db->update(array('priority'=>$first->priority),array('id'=>$second->id),$prefix);
			$c_db->update(array('priority'=>$second->priority),array('id'=>$first->id),$prefix);
		}
    }
	/**
	 * обработка фильтрации
	 * @param bool $fields массив массивов, где элемент - array(ключ,шаблон)
	 * @return object
	 */
	function filter($fields=false){
        global $c_rq;
        $eq=array('__max_'=>'<','__min_'=>'>','__maxe'=>'<=','__mine'=>'>=');

        $rez=(object)array('filter'=>array(),'where'=>array());
        $rez->filterLine['full']=array();
        $rez->filterLine['full']['filter']='filter=1';
        foreach($fields as $k=>$v){
            $kk=$v[0];
            $tmp=$c_rq->get($kk,(isset($v[1]))?$v[1]:'listNum');
            if(is_array($tmp)) $tmp=implode(',',$tmp);
            $rez->filter[$kk]=$tmp;
            if($tmp!==NULL&&strlen($tmp)){
                $rez->filterLine['full'][$kk]=$kk.'='.$tmp;
                if(strpos(',',$tmp)!==false)$line=explode(',',$tmp);
                else $line=$tmp;
                $rez->where[$k]=$line;
            }
            foreach($eq as $kkk=>$vvv){
	            $filterNameTmp=$kk.$kkk;
	            if(tvar($_REQUEST[$filterNameTmp])){
		            $tmpLine=$c_rq->get($filterNameTmp,(isset($v[1]))?$v[1]:'listNum');
		            if($tmpLine){
			            $exKey=$k.'|'.$vvv;
			            $rez->filter[$filterNameTmp]=$tmpLine;
			            $rez->where[$exKey]=$tmpLine;
			            $rez->filterLine['full'][$kk.$kkk]=$kk.$kkk.'='.$tmpLine;
		            }
	            }
            }
        }
        foreach($rez->filter as $k=>$v){
            $rez->filterLine[$k]=$rez->filterLine['full'];
            unset($rez->filterLine[$k][$k]);
        }
        return $rez;
    }
	/**
	 * Обработка сортировки
	 * @param bool|array $fields массив массивов, где элемент - array(ключ,шаблон)
	 * @return object
	 */
	function order($fields=null){
	    global $c_rq;
		if(!is_array($fields)||!$fields) $fields=array();

	    $rez=(object)array('order'=>array());
	    $rez->orderLine['full']=array();
	    $rez->orderLine['full']['order']='order=1';
	    foreach($fields as $k=>$v){
            if(!is_array($v)){
                $rez->order[$k]=$v;
            }else{
                $kk=$v[0];
                $tmp=$c_rq->get($kk,(isset($v[1]))?$v[1]:'listNum');
                if(is_array($tmp)) $tmp=implode(',',$tmp);
                if($tmp!==NULL&&strlen($tmp)){
                    $rez->order[$k]=$tmp;
                    $rez->orderLine['full'][$kk]=$kk.'='.$tmp;
                }
            }
	    }
	    foreach($rez->order as $k=>$v){
	        $rez->orderLine[$k]=$rez->orderLine['full'];
	        unset($rez->orderLine[$k][$k]);
	    }
	    return $rez;
	}
	/**
	 * Записывает/получает предыдущую страницу (HTTP_REFERER)
	 * @param int $action 0 - записать / 1 - получить
	 * @return string
	 */
	function lastPage($action=1){ # 0 - set / 1 - get
		$sn='http://'.$_SERVER['SERVER_NAME'];
		if($action===0){
			$ref=$_SERVER['HTTP_REFERER'];
			$sp=strpos($ref,$sn);
			if($sp===0) $_SESSION['lastPage']=$ref;
		}else{
			$lp=$_SESSION['lastPage'];
			if($lp) return $lp;
			else return $sn.'/index.html';
		}
	}
}
