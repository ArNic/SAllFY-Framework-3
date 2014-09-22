<?php
class m_user_unlog_v{
    static function login($err=false){
		global $c_rq;
	    $baseLink=M_USER_LINK_USER;
	    $errC='M_USER_ERROR_'.(($err)?$err:'');
	    $errN=(defined($errC))?constant($errC):'';
        if($errN){?><div class="ui-make-alert"><?=$errN?></div><?}?>
		<? if($err){
				$obj=array();
				$c_rq->getR($obj,array('nick|name'));
				$obj=(object)$obj;
		} ?>
        <form method="POST" class="control_form" action="<?=$baseLink?>/login.html">
			<input type="hidden" name="data" value="login" />
			<table style="margin-left:30px;">
				<tr><td>Логин</td><td><input type="text" class="input_text" name="login" value="<?=@$obj->nick?>"></td></tr>
				<tr><td>Пароль</td><td><input type="password" class="input_text" name="passw"></td></tr>
				<tr><td colspan="2" align="center"><p class="line"><input type="submit" value="Войти" class="input_button crystal" /></td></tr>
			</table>
        </form>
    <?}
	static function register($array,$err=false){
		global $c_rq;
		if($err){
			$array=array();
			$c_rq->getR($array,array(
					'login|name',
					'email|email',
					'nick|sentence'
				),false,true);
		}
		$array=(object)$array;
		#$baseLink=M_USER_LINK_USER;
		$errN=tplBase::showError($err,'M_USER_ERROR_');?>

			<div class="profile_block">
				<form method="POST">
					<h2>Регистрация</h2>
					<input type="hidden" name="data" />
					<div>
						<label>Логин</label>
						<input type="text" name="login" value="<?=((isset($array->id)||$err)?($array->login):'')?>"/>
					</div>
					<div>
						<label>Ник на сайте</label>
						<input type="text" name="nick" value="<?=((isset($array->id)||$err)?($array->nick):'')?>"/>
					</div>
					<div>
						<label>Пароль:</label>
						<input type="password" name="passw">
					</div>
					<div>
						<label>E-mail:</label>
						<input type="text" name="email" value="<?=((isset($array->id)||$err)?($array->email):'')?>"/>
					</div>
					<div>
						<label>Текст на картинке</label>
						<img src="/capt/index.php"/>
						<input type="text" class="input_text" name="captcha_code" style="width:150px">
					</div>
					<p>
						<button class="submit btn_1" type="submit"><span>Отправить</span></button>
					</p>
				</form>
			</div>
	<?}
	static function activate($mail,$err=false){
		$baseLink=M_USER_LINK_USER;
		$errN=tplBase::showError($err,'M_USER_ERROR_');
		if($errN){?><div class="ui-make-alert"><?=($errN)?></div><?}?>
			<div class="profile_block">
				<form method="POST">
					<h2>Активация</h2>
					<div>
						<label>Код</label>
						<input type="text" name="code">
					</div>
					<p>
						<button class="submit btn_1" type="submit"><span>Отправить</span></button>
					</p>
				</form>
			</div>
	<?}
	static function restore(){?>
			<div class="profile_block">
				<form method="POST">
					<h2>Восстановление пароля</h2>
					<input type="hidden" name="data" />
					<div>
						<label>Ваша почта</label>
						<input type="text" name="email">
					</div>
					<p>
						<button class="submit btn_1" type="submit"><span>Восстановить</span></button>
					</p>
				</form>
			</div>
	<?}
	static function restoreS(){?><p>На электронный ящик отправлено письмо с новым паролем.</p><?}
}