<?php
$g = $_GET["g"];
$nombre_archivo = "http://mobilia.gpsline.cl/pin_auto.png";
header('Content-type: image/png');

// Cargar
$origen = imagecreatefrompng($nombre_archivo);
//echo "1<br>";
// Rotar
$rotar = imagerotate($origen, $g, 100);
//echo "2<br>";
// Imprimir
//imagesavealpha($rotar, TRUE);
//echo "3<br>";
imagepng($rotar);
//echo "4<br>";
?>
