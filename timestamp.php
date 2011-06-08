<?php
if(isset($_GET["timestamp"])) {
    $timestamp = $_GET["timestamp"];
    echo date("Y-m-d H:i:s", $timestamp)."<br>";
} else {
    $timestamp = strtotime($_GET["fecha"]);
    echo $timestamp."<br>";
}
?>
