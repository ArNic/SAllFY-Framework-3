<?
class m_user_admin_v{
	static function listing($array){
		global $m_user_array;
		$baseLink=M_USER_LINK_ADMIN.'/';
		?>
		<div class="control_panel">
			<a href="<?=$baseLink.'index.html?route=form'?>">Добавить</a>
		</div>
		<table class="listingTable">
			<thead>
				<tr>
					<th>Логин</th>
					<th>E-mail</th>
                    <th>Группа</th>
                    <th></th>
				</tr>
			</thead>
			<tbody>
				<?foreach($array->rez as $k=>$v){
					$group=explode(',',$v->group); ?>
					<tr>
						<td align="center"><?=$v->login?></td>
						<td align="center"><?=$v->email?></td>
						<td align="center">
							<? foreach($m_user_array['groupImg'] as $grKey=>$grImg){
								$nameGr=$m_user_array['group'][$grKey];	?>
								<img title="<?=$nameGr?>" alt="<?=$nameGr?>" src="<?=$grImg?>" <?=in_array($grKey,$group)?'':'style="opacity:0.2"'?> />
							<? } ?>
						</td>
						<td align="center">
							<? tplBase::buttonForm($baseLink.$v->id.'.html?'); ?>
							<? tplBase::buttonDel($baseLink.$v->id.'.html?'); ?>
						</td>
					</tr>
				<?}?>
			</tbody>
		</table>
		<div style="text-align:center;">
		<? if($array->parts>1){tplBase::listPg($array->parts,$array->part,$baseLink.'index.html');} ?>
		</div>
	<?}
	static function form($err,$obj){
        global $c_rq,$m_user_array;
        if($err){
            $obj=array();
            $c_rq->getR($obj,array(
                    'login|name',
                    'email|email',
                    'status|name',
                    'pass|pass'
                ),false,true);
        }
		$group=(isset($obj->id))?explode(',',$obj->group):array();
        $obj=(object)$obj;
        $baseLink=M_USER_LINK_ADMIN.'/'.(isset($obj->id)?$obj->id:'index').'.html?route=send';?>
		<form action="<?=$baseLink?>" method="POST">
			<div>
				<label>Логин</label>
				<input type="text" name="login" value="<?=((isset($obj->id))?($obj->login):'')?>"/>
			</div>
			<div>
				<label>Пароль:</label>
				<input type="text" name="pass" type="text" value=""/>
			</div>
			<div>
				<label>Почта:</label>
				<input type="text" name="email" type="text" value="<?=((isset($obj->id))?($obj->email):'')?>"/>
			</div>
			<div>
				<label>Группа:</label>
				<? foreach($m_user_array['group'] as $k=>$v){ ?>
					<input type="checkbox" name="group[]" <?=((in_array($k,$group))?'checked="checked"':'')?> value="<?=$k?>" /> <?=$v?><br/>
				<? } ?>
			</div>
			<p><button class="submit btn_1" type="submit"><span>ОТПРАВИТЬ</span></button></p>
		</form>
	<?}
}
