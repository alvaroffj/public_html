<?php

include "GP6000.php";
$hex = $_GET["hex"];
function hextostr($x) { 
    $s=''; 
    foreach(explode("\n",trim(chunk_split($x,2))) as $h) $s.=chr(hexdec($h)); 
    return($s); 
} 
function hexToAscii($hex, $delimeter=" ") {
	//Remove delimiters from hex string
    $hex = str_replace($delimeter, "", $hex);
    $strLength = strlen($hex);
    $returnVal = '';

    for($i=0; $i<$strLength; $i += 2) {
        $dec_val = hexdec(substr($hex, $i, 2));
        $returnVal .= chr($dec_val);
    }
    return $returnVal;
}

$obj = new GP6000($hex);

//echo hexdec("0x".$obj->speed);
//locating
//1001 de derecha a izq
//1: locating
//0: S
//0: W
//1: 1

echo "<pre>";
print_r($obj);
echo "</pre>";
?>
