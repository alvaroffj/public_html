<?php
$timestamp = $_GET["timestamp"];
echo date("H:i", $timestamp)."<br>";
$h = "11-19";
$h = explode("-", $h);
$tED = date("H", $timestamp);
if($h[0]<=$tED && $h[1]>$tED) {
    echo "in<br>";
} else echo "out<br>";

?>
