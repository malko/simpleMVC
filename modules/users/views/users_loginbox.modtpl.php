<div class="moduserLoginBox">
	<form action="<?php echo $this->url('moduser:login').($this->userInfoBox_redirectDisaptch ?'/'.$this->userInfoBox_redirectDisaptch:'' )?>" method="post" class="moduserLoginForm">
		<div class="formInput"><label for="login"><?php echo langManager::msg('loginform login',null,'moduser')?></label><input type="text" name="login" id="login" /></div>
		<div class="formInput"><label for="password"><?php echo langManager::msg('loginform password',null,'moduser')?></label><input type="password" name="password" id="password" /></div>
		<div class="formAction"><button type="submit"><?php echo langManager::msg('loginform submit',null,'moduser')?></button></div>
	</form>
</div>