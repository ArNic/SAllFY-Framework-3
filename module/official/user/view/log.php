<?php
class m_user_log_v{
    static function profile($array=array(),$err=false){
	    $errC='M_USER_ERROR_'.(($err)?$err:'');
	    $errN=(defined($errC))?constant($errC):'';
        if($errN){ ?>
            <div class="ui-make-alert" ><?=$errN?></div>
        <?}?>
        <form method="POST" class="control_form">
	        <input type="hidden" name="data" />
	        <fieldset><legend>Смена пароля</legend><table>
		        <tr><td>Новый пароль: </td><td width="90%"><input style="width:150px" name="passw" type="password" class="input_text" value="" /></td></tr>
                <tr><td colspan="2" class="subscription">если хотите сменить пароль заполните данной поле</td></tr>
	        </table></fieldset>
			<table>
				<tr><td valign="top">Текст на картинке<span style="color: red; font-size: 16px;"><b> *</b></span></td><td width="88%"><input type="text" class="input_text" name="captcha_code" style="width:150px"></td></tr>
                <tr><td></td><td> <img src="/capt/index.php"/></td></tr>
                <tr><td colspan="2" class="subscription"><span style="color:red;font-size:18px"><b>*</b></span> - поля обязательные для заполнения</td></tr>
	            <tr><td colspan="2" align="center"><input type="submit" value="Отправить" class="input_button crystal" /></td></tr>
	        </table></form>
	    <?}
}