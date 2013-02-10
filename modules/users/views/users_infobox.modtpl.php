<div class="moduserLoginBox">
	<?php echo users::getCurrent(); ?>
	<br />
	<a href="<?php echo $this->url('moduser:logout')?>"><?php echo langManager::msg('loginbox logout',null,'moduser') ?></a>
</div>