<?php
$this->renderScript("models_list.tpl.php");
?>
<script>
$(function(){
  $('table').on("click",'.duplicateButton',function(e){
    e.preventDefault();
    var url = $(this).attr('href')
      , newName = prompt('Please notify a new form\'s name or leave empty for auto increment');
    if(newName.match(/^[a-zA-Z0-9_-]{3,255}$/)){
      url = url + "/" + newName;
      window.location.href = url;
    }else if(!newName){
      window.location.href = url;
    }else
      return false;
  });
});
</script>
