<?php
include_once 'controlador/CPrincipal.php';
//lala
$cp = new CPrincipal();
?>
<? if($cp->showLayout) include $cp->getLayout(); ?>
