<?php
include_once 'controlador/CPrincipal.php';

$cp = new CPrincipal();
?>
<? if($cp->showLayout) include $cp->getLayout();?>