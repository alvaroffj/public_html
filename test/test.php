<?php
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
function hextostr($x) { 
    $s=''; 
    foreach(explode("\n",trim(chunk_split($x,2))) as $h) $s.=chr(hexdec($h)); 
    return($s); 
} 

$handle = fopen('php://stdin','r');  
$input = fread($handle, 4000);  
fclose($handle);  
$inputAscii = hexToAscii($input);
$fp = fopen("/home/mobilia/public_html/test/test.log", 'a');
fwrite($fp, "HEX: ".$input."\n");
fwrite($fp, "ASCII: ".$inputAscii."\n");
fwrite($fp, "----------------------------------------\n");
fclose($fp);
  
echo "Input: {$input}"; 

?>
