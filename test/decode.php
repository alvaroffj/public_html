<?php
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

$obj = new stdClass();
$obj->hex = $hex;
$obj->length = strlen($hex);
$obj->head = substr($hex, 0, 2);
$obj->imei = substr($hex, 2, 10);
$obj->pVer = substr($hex, 12, 1);
$obj->dataType = substr($hex, 13, 1);
$obj->dataLength = substr($hex, 14, 4);
$obj->date = substr($hex, 18, 6);
$obj->time = substr($hex, 24, 6);
$obj->latitude = substr($hex, 30, 8);
$obj->longitude = substr($hex, 38, 9);
$obj->locating = substr($hex, 47, 1);
$obj->speed = substr($hex, 48, 2);
$obj->direction = substr($hex, 50, 2);
$obj->fuelLevelH = substr($hex, 52, 2);
$obj->status = substr($hex, 54, 8);
$obj->mileage = substr($hex, 62, 8);
$obj->fuelLevelL = substr($hex, 70, 2);
$obj->SN = substr($hex, 72, 2);

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
