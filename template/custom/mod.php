<?php
class tplMod{
	/************************* Вывод ************************/
		function viewChilds(&$baseLink,&$array,$head='Разделы'){
			if(count($array)>0){ ?>
				<?=($head)?'<div class="mod-view-childs-head">'.$head.'</div>':''?>
			    <div class="mod-view-childs-body">
					<? foreach($array as $k=>$v){ ?>
						<a href="<?=$baseLink?>/<?=$v->id?>.html"><?=stripslashes($v->title)?></a>
					<? } ?>
			    </div>
			<? }
		}
		function ccsClass($type='',$cls='',$postfix=''){
			if($type)$rez[]='sallfy-mod-n_'.$type;
			$rez='sallfy-mod'.(($cls)?'-'.$cls:'').(($postfix)?'-'.$postfix:'');
		}
		function viewContents(&$baseLink,&$cnt,&$sub,$type,$head=true,$var=0,$fields='title,pre,img,date,comm,city'){
			if(is_bool($head)) $head=($head===true)?'Содержание':'';
			elseif(is_string($head)){}
			elseif(!is_string($head))$head='';
			$fields=explode(',',$fields);

			$ifTitle=in_array('title',$fields);
			$ifImg=(in_array('img',$fields));
			$ifPre=in_array('pre',$fields);
			$ifDate=in_array('date',$fields);
			$ifComm=in_array('comm',$fields);
			$ifCity=in_array('city',$fields);

			if(count($cnt->rez)>0){?>
				<?=($cnt->found>0||$head)?'<div class="mod-view-contents-head">'.$head.'</div>':''?>
				<div class="mod-view-contents-body">
				<? foreach($cnt->rez as $k=>$v){
					$text=stripslashes($v->pretext);
					$title=stripslashes($v->title);
					$href=$baseLink.'/'.$sub.'/'.$v->id.'.html';

					$ifImg=($ifImg&&$v->preview);
					?>
						<div class="block mod-lp mod-n_<?=$type?> <?=($k%2)?'even':'ogg'?> <?=$type?>">
							<? if($ifTitle){?> <h2 class="pre-head"><a href="<?=$href?>"><?=$title?></a></h2><?}?>
							<? if($ifImg){ tplMod::elImg('preview',$title,$type,$v->id,$href,false,'preview'); } ?>
							<? if($ifPre){?><div class="text"><?=$text?></div><?}?>
							<? if($var==1&&($ifDate||$ifComm)){ ?>
									<div class="block-bottom-left">
										<? if($ifDate&&isset($v->date)){ tplBase::makeDateStr($v->date); } ?>
										<? if($ifComm&&isset($v->comments)){?><?=($v->comments)?'|| комментариев:'.$v->comments:''?><?}?>
									</div>
								<? if($ifCity&&isset($v->ncch_city)){?><div class="block-bottom-right"><?=$v->ncch_city?></div><?}?>
							<? } ?>
						</div>
					<? } ?>
				</div>
				<?if($cnt->parts>1)tplBase::listPg($cnt->parts,$cnt->part,$baseLink.'/'.$sub.'.html','?');
			}
		}
		function elImg($name,$title,$type,$id,$href=false,$imgC='',$hrefC=''){
			$imgC=(is_string($imgC)&&$imgC)?'class="'.$imgC.'" ':'';
			$hrefC=(is_string($hrefC)&&$hrefC)?'class="'.$hrefC.'" ':'';
			$src=str_split($id);
			$src=UPLOADS_PATH.'/img/'.$type.'/'.implode('/',$src).'/'.$name.'.jpg'; ?>
			<a href="<?=$href?>" <?=$hrefC?>><img alt='<?=$title?>' title='<?=$title?>' src="<?=$src?>" <?=$imgC?>/></a>
		<?}
		function viewPage(&$array,$type){
			$title=stripslashes($array->title);
			$text=stripslashes($array->text);
			$src=str_split($array->id);
			$src=UPLOADS_PATH.'/img/'.$type.'/'.implode('/',$src).'/full.jpg';	?>
			<h1 class="pagetitle textshadow"><?=$title?></h1>
			<? if($array->preview){ ?><img alt='<?=$title?>' title='<?=$title?>' class="pagephoto" src="<?=$src?>" /><? } ?>
			<div><?=$text?></div>
		<?}
		function viewListingTitle($defname,$title,$part){ ?>
			<h1 class="pagetitle"><?=($title)?$title:$defname?><?=($part>1)?' - страница '.$part:''?></h1>
		<? }
	/************************* Управление ************************/
		function actionModerPanel($baseLink,$pars=false,$gap=false){
			if(is_object($pars)){
				if($gap)$baseLinkGaP=str_replace('listing','listing_gap',$baseLink);
				$panel=array();
				$obj=&$pars->filter;
				if($obj){
					$title=($obj->title)?$obj->title:'Перейти к фильтрам';
					$panel[]='<a href="'.$baseLink.'/index.html?filter=1">'.$title.'</a>';
					$panel[]='|';
				}
				$obj=&$pars->addGroup;
				if($obj){
					$title=($obj->title)?$obj->title:'Добавить группу';
					$panel[]='<a href="'.(($gap)?$baseLinkGaP:$baseLink).'/index.html?route=form&sub='.$obj->sub.'">'.$title.'</a>';
				}
				$obj=&$pars->addLine;
				if($obj){
					$title=($obj->title)?$obj->title:'Добавить запись';
					$panel[]='<a href="'.(($gap)?$baseLinkGaP:$baseLink).'/'.$obj->sub.'/index.html?route=form">'.$title.'</a>';
				}
				print implode(' ',$panel);
			}
		}
		function actionListingFav($baseLink,&$array){
			foreach($array as $k=>$v){ ?><tr>
				<td class="ac"><img src="/files/images/toolicons/folder.png" /></td>
				<td><a href="<?=($v->table_type)?(str_replace('/listing','/listing_gap',$baseLink)):$baseLink?>/<?=$v->id?>.html"><?=stripslashes($v->favtitle)?></a></td>
				<td class="ac"></td>
				<td></td>
			</tr><? }
		}
		function actionListingChilds($baseLink,&$array){
			foreach($array as $k=>$v){ ?><tr>
				<td class="ac"><img src="/files/images/toolicons/folder.png" /></td>
				<td><a href="<?=$baseLink?>/<?=$v->id?>.html"><?=stripslashes($v->title)?></a></td>
				<td class="ac"><?
					$linkButtons=$baseLink.'/'.$v->id.'.html?sub='.$v->sub.'&';
					tplBase::buttonForm($linkButtons).tplBase::buttonDel($linkButtons);
				?></td>
				<td class="ac"><a href="/news/<?=$v->sub?>.html" target="_blank">Смотреть</a></td>
			</tr><? }
		}
		function actionListingContents($baseLink,&$array){
			if($array->gap) $baseLink=str_replace('/listing','/listing_gap',$baseLink);
			foreach($array->rez as $k=>$v){
				$name=stripslashes($v->title);
				if($array->gap){
					$href=$baseLink.'/'.$v->id.'.html';
					$name='<a href="'.$href.'">'.$name.'</a>';
				}
				?>
				<tr>
					<td class="ac"><img src="/files/images/toolicons/page_white_text.png" /></td>
					<td><?=$name?></td>
					<td class="ac"><?
					$linkButtons=$baseLink.'/'.$v->sub.'/'.$v->id.'.html?';
					tplBase::buttonForm($linkButtons).tplBase::buttonDel($linkButtons);
					?></td>
					<td class="ac"><a href="/news/<?=$v->sub?>/<?=$v->id?>.html" target="_blank">Смотреть</a></td>
				</tr>
				<? }
		}
		function actionListing($baseLink,$sub,&$array,$gap=false){?>
			<table class="control_table">
				<thead><tr><th width="24px"></th><th>Название</th><th width="42px"></th><th width="40px"></th></tr></thead>
				<tbody><?
					tplMod::ActionListingFav($baseLink,$array->fav,$gap);
					tplMod::ActionListingChilds($baseLink,$array->childs,$gap);
					tplMod::ActionListingContents($baseLink,$array->contents);
					$cnt=&$array->contents;
				?></tbody>
			</table>
			<? if($cnt->parts>1)tplBase::listPg($cnt->parts,$cnt->part,$baseLink.'/'.$sub.'.html','?');
		}
	/************************* Прочее ************************/
}