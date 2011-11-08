<?php
class Bd {
    var $conex;
    var $pass = "";
    var $user = "";
    var $server = "";
    var $bd = "";
//mobilia: BEP2vFqFRhaaDSEr
//starclutch: LsD94GL76juntYAh
//maestra: 6EQyhzvnqR5LKwnX
//telascheito: VVrR8sPwEWZdGLUt
//demo: Lbbpd7GSZ7NZwJ7G
//melon: 2SmVCNxv5Tzdsf4e
//rapel: SHdwsFwSFR8DwYWs
//easton: xBh94FsKRvrTT7wj
//tbd: h6CFcGsmpqRMTRnu
//transmatic: 5d4xjdsKbM2A7HzX
//elcon: qcrpqUPZrEQFrycs
//first: qR4Xbe7wHu7sTqYA
//ingebolt: 5KTVcxSS2MeZzZ93
    
    function __construct($user=null, $pass=null, $server=null, $bd=null) {
        if($user != null) {
            $this->user = $user;
            $this->pass = $pass;
            $this->server = $server;
            $this->bd = $bd;
        } else {
            $this->user = $_SESSION["cueNom"];
            $this->pass = $_SESSION["cuePass"];
            $this->server = $_SESSION["cueBDIP"];
            $this->bd = $_SESSION["cueBD"];
        }
//        echo "<pre>";
//        print_r($this);
//        echo "</pre>";
        $this->conecta();
    }

    function conecta() {
        $this->conex = mysql_connect($this->server, $this->user, $this->pass) or die("En este momento no podemos procesar su peticion, intentelo más tarde ".$this->server." | ".$this->user);
//        echo "conectado<br>";
        mysql_select_db($this->bd);
    }

    function get_conex() {
        return $this->conex;
    }

    function sql($sql) {
        $res = mysql_query($sql, $this->get_conex()) or ($res = false);
        return $res;
    }

    function limpia($s) {
        return mysql_real_escape_string($s);
    }

    function code($s) {
//        return utf8_encode($s);
        return $s;
    }

    function decode($s) {
//        return utf8_decode($s);
        return $s;
    }

    function getArrayFull($res) {
        $r = array();
        $i = 0;
        while($row=mysql_fetch_row($res)) {
                $r[$i] = $row;
                $i++;
        }
        return $r;
    }
}
?>