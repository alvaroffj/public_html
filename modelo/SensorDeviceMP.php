<?php
require_once 'Bd.php';

class SensorDeviceMP {
    protected $_dbTable = "SENSOR_DEVICE";
    protected $_id = "ID_SENSOR_ACCOUNT";
    protected $_bd;

    function __construct() {
        $this->_bd = new Bd();
    }

    public function fetchByDevice($idDevice, $idCuenta) {
        $idDevice = $this->_bd->limpia($idDevice);
        $idCuenta = $this->_bd->limpia($idCuenta);
        
        $sql = "SELECT SD.*, S.NOM_SENSOR, S.TIPO_SENSOR 
                FROM $this->_dbTable AS SD 
                    INNER JOIN SENSOR_ACCOUNT AS SA
                    INNER JOIN SENSOR AS S 
                ON 
                    SD.DEVICEID = $idDevice 
                    AND SD.ID_SENSOR = S.ID_SENSOR 
                    AND SA.ID_SENSOR = S.ID_SENSOR
                    AND SA.ESTADO_SENSOR_ACCOUNT = 1";
        
//        echo $sql."<br>";
        $res = $this->_bd->sql($sql);
        $arr = array();
        while($row = mysql_fetch_object($res)) {
            $arr[] = $row;
        }
        return $arr;
    }
    
    function fetchByDevices($dev) {
        $dev = implode(",",$dev);
        
        $sql = "SELECT SD.DEVICEID, SD.ID_SENSOR, SD.COLUMNA_SENSOR, S.NOM_SENSOR, S.TIPO_SENSOR, S.UNIDAD_SENSOR 
                FROM $this->_dbTable AS SD 
                    INNER JOIN SENSOR_ACCOUNT AS SA
                    INNER JOIN SENSOR AS S 
                ON 
                    SD.DEVICEID IN ( $dev )
                    AND SD.ID_SENSOR = S.ID_SENSOR 
                    AND SA.ID_SENSOR = S.ID_SENSOR
                    AND SA.ESTADO_SENSOR_ACCOUNT = 1";
        
//        echo $sql."<br>";
        $res = $this->_bd->sql($sql);
        $arr = array();
        while($row = mysql_fetch_object($res)) {
            $arr[] = $row;
        }
        return $arr;
    }
    
    function fetchByAccount($idCuenta) {
        $idCuenta = $this->_bd->limpia($idCuenta);
        
        $sql = "SELECT SA.*, S.NOM_SENSOR, S.TIPO_SENSOR 
                FROM SENSOR_ACCOUNT AS SA
                    INNER JOIN SENSOR AS S 
                ON 
                    SA.ACCOUNTID = $idCuenta 
                    AND SA.ID_SENSOR = S.ID_SENSOR
                    AND SA.ESTADO_SENSOR_ACCOUNT = 1";
        
//        echo $sql."<br>";
        $res = $this->_bd->sql($sql);
        $arr = array();
        while($row = mysql_fetch_object($res)) {
            $arr[] = $row;
        }
        return $arr;
    }
}
?>


