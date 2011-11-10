/**
* language pack for filemanager
*/
jQuery(function($){
	$.fn.filemanager.langMsgs= {
		dialogBox:{
			titleError: 'Erreur',
			titleConfirm: 'Confirmation',
			btCancel: 'Annuler',
			btDelete: 'Effacer'
		},
		infoBox:{
			title: 'Infos',
			tabs: ['Général','Permissions','Actions','A propos'],
			btUpload: 'Ajouter un fichier sur le serveur',
			credits: '<p>jquery.filemanager.js plugin originalement créer par <br /><a href="http://agence-modedemploi.com" target="_blank">modedemploi</a></p>'+
				'<p>upload gérer par <a href="http://valums.com/ajax-upload/" target="_blank">AjaxUpload</a> sous licence MIT &copy; 2008 Andris Valums</p>'+
				'<p>Les icones sont extraite de <a href="http://www.famfamfam.com/lab/icons/silk/" target="_blank">Silk icon set</a> distribué sous licence Creative Commons Attribution 2.5</p>'+
				'<p>Ce travail à été inspiré (seule la css originale à été conservé) par <a href="http://abeautifulsite.net/notebook.php?article=58" target="_blank">jqueryFileTree plugins</a> sous licence Creative Commons Attribution 3.0 &copy; Cory S.N. LaViska </p>',

			fileFullpath:'Chemin du fichier',
			fileSize: 'Taille du fichier',
			fileDelete: 'Supprimer le fichier',
			dirFullpath: 'Chemin du répertoire',
			dirSize: 'Contenu du répertoire',
			dirDelete: 'Supprimer le répertoire',
			mtime: 'Date de dernière modification',
			owner:'Propriétaire',
			group:'Groupe',
			perms: 'Permissions',
			newdirLabel: 'Créer un nouveau répertoire',
			newdirButton: 'Créer le répertoire'
		},
		loading: 'Chargement en cours ...',
		accessDenied: "Accès refusé.",
		badConnectorConfig:'Erreur de configuration du connecteur<br />( repertoire probablement inexistant ).',
		postWaitPrevResult: "Attendez la réponse de la requete précedente.",
		infosMissing: "Des informations requises sont manquantes.",
		confirmFileDelete: "Etes vous sur de vouloir effacer le fichier :<br />$path ?",
		confirmDirDelete: "Etes vous sur de vouloir effacer le répertoire :<br />$path ?",
		fileAlreadyExists: "Le fichier existe déja.",
		badFileType: "Type de fichier non authorisé.",
		dirAlreadyExists: "Le répertoire existe déja.",
		directoryNotWritable: "Le répertoire n'est pas accessible en écriture.",
		browse:'parcourir'
	};
});