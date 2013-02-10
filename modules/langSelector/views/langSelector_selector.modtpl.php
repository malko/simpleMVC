<div class="langSelector"><?php
		foreach( $tmp = $this->availableLanguages as $l=>$url){
			echo '
			<a href="'.$url.'" class="langchoice '.$l.($l===$this->currentLanguage?' selected':'').'">'.$l.'</a>';
		}
	?>
</div>