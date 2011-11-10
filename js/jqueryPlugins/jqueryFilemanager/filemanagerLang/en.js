/**
* language pack for filemanager
*/
jQuery(function($){
	$.fn.filemanager.langMsgs = {
		dialogBox:{
			titleError: 'Error',
			titleConfirm: 'Confirm',
			btCancel: 'Cancel',
			btDelete: 'Delete'
		},
		infoBox:{
			title: 'Infos',
			tabs: ['General','Permissions','Actions','About'],
			btUpload: 'upload file to server',
			credits: '<p>jquery.filemanager.js plugin originally created by<br /><a href="http://agence-modedemploi.com" target="_blank">modedemploi</a></p>'+
				'<p>upload managed by <a href="http://valums.com/ajax-upload/" target="_blank">AjaxUpload</a> Licensed under the MIT license Copyright (c) 2008 Andris Valums</p>'+
				'<p>icons are part of <a href="http://www.famfamfam.com/lab/icons/silk/" target="_blank">Silk icon set</a> and distributed under Creative Commons Attribution 2.5 License</p>'+
				'<p>this work was inspired (only the original css remain) from <a href="http://abeautifulsite.net/notebook.php?article=58" target="_blank">jqueryFileTree plugins</a> </p>',

			fileFullpath:'File path',
			fileSize: 'File size',
			fileDelete: 'Delete file',
			dirFullpath: 'Directory path',
			dirSize: 'Directory contains',
			dirDelete: 'Delete directory',
			mtime: 'Last modification time',
			owner:'Owner',
			group:'Group',
			perms: 'Permissions',
			newdirLabel: 'Create a new directory',
			newdirButton: 'Create directory'
		},
		loading: 'Loading ...',
		accessDenied: "Access denied.",
		badConnectorConfig:'Connector config error<br />(probably nonexistent directory).',
		postWaitPrevResult: "Please wait for previous request to process.",
		infosMissing: "Some required informations are missing.",
		confirmFileDelete: "Are you sure you want to delete file:<br />$path ?",
		confirmDirDelete: "Are you sure you want to delete directory:<br />$path ?",
		dirAlreadyExists: "Directory already exists.",
		fileAlreadyExists: "File already exists.",
		badFileType: "Unauthorized file type.",
		directoryNotWritable: "Directory isn't writable",
		browse:'browse'
	};
});