	<?
	require_once('config.php');

	$instMods=$c_rq->get('instMods','getpar');
	$instCore=$c_rq->get('instCore','getpar');
	if(count($instMods)>0||count($instCore)>0){
		if(count($instMods)>0){
			foreach($instMods as $v){
				if(in_array($v,$defMod)){
					$file=PATH.'/module/official/'.$v.'/controller/install.php';
					if(file_exists($file)){
						include_once($file);
						print 'installed '.$v;
					}
				}
			}
		}
		if(count($instCore)>0){
			foreach($instCore as $v){
				if(method_exists(${$v},'install')){
					${$v}->install();
					print 'installed '.$v;
				}
			}
		}
	//	$c_auth->install();
	}else{?>
		<form method="POST">
			<h1>Выберите системные установки</h1>
			<?
				$rez=array();
				$rez['c_auth']='<div style="display:inline-block;width:350px;color:red;"><input name="instCore[]" type="checkbox" value="c_auth"><div style="display:inline-block;width:75px;">c_auth </div> <span style="color:#ccc;font-size:10px;">(Авторизация)<span></div>';
				$rez['c_mod']='<div style="display:inline-block;width:350px;color:red;"><input name="instCore[]" type="checkbox" value="c_log"><div style="display:inline-block;width:75px;">c_log </div> <span style="color:#ccc;font-size:10px;">(Логирование)<span></div>';
				ksort($rez);
			?>
			<?=implode('',$rez)?>
			<h1>Выберите модули для установки</h1>
			<?
				$rez=array();
				$defMod=array_unique(array_values($defMod));
				foreach($defMod as $k=>$v){
					$fileInstall=PATH.'/module/official/'.$v.'/controller/install.php';
					$file=PATH.'/module/official/'.$v.'/config.php';
					if(file_exists($fileInstall)){
						include_once($file);
						$rez[$v]='<div style="display:inline-block;width:350px;"><input name="instMods[]" type="checkbox" value="'.$v.'"><div style="display:inline-block;width:75px;">'.constant($cB.'TYPE').'</div> <span style="color:#ccc;font-size:10px;">('.constant($cB.'NAME').')<span></div>';
					}
				}
				ksort($rez);
			?>
			<?=implode('',$rez)?>
			<hr/><input type="submit" value="Установить" />
		</form>
	<? }
