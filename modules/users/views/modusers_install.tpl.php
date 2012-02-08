<h1>Module users installation</h1>

<style>
.moduleAdminForm .formInput{
	margin:1em 0;
}
.moduleAdminForm .formInput label{
	zoom:1;
	line-height:1.25em;
	display:inline-block;
	*display:inline;
	vertical-align: top;
	width:18em;
}
</style>
<form action="<?php echo $this->url('modusers:install2') ?>" class="moduleAdminForm" method="post">
<?php echo $this->token('moduserInstall2'); ?>
	<div class="formInput"><label for="">Database Connection string: </label> <input name="dbConnection" type="text" value="<?php echo defined('DB_CONNECTION')?DB_CONNECTION:''; ?>" size="40" /></div>
	<div class="formInput"><label for="">user module administrator login: </label> <input name="login" type="text" value="userAdmin" /></div>
	<div class="formInput"><label for="">user module administrator password: </label> <input name="password" type="password" value="" /></div>
	<div class="formInput"><label for="">user module administrator email: </label> <input name="email" type="text" value="" /></div>
	<div style="text-align:right"><input type="submit"></div>
</form>