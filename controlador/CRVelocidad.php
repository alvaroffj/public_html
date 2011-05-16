<?php
require_once 'modelo/DeviceGroupMP.php';
require_once 'modelo/DeviceMP.php';
require_once 'modelo/EventDataMP.php';

class CRVelocidad {
    protected $cp;
    protected $dgMP;
    protected $deMP;

    function  __construct($cp) {
        $this->cp = $cp;
        $this->dgMP = new DeviceGroupMP();
        $this->deMP = new DeviceMP();
        $this->edMP = new EventDataMP();
        $this->ope = array(array("id"=>0, "nom"=>"Mayor a"), array("id"=>1, "nom"=>"Menor a"));
        $this->ope[0] = (object)$this->ope[0];
        $this->ope[1] = (object)$this->ope[1];
        $this->setGet();
        $this->setOp();
    }

    function getLayout() {
        return $this->layout;
    }

    function setGet() {
        if(isset($_GET["get"])) {
            $this->cp->cp->showLayout = false;
            $this->get = mysql_escape_string($_GET["get"]);
            $attr = array("accountID");
            switch($this->get) {
//                case 'mapaAni':
//                    include 'vista/ralarma_mapa.phtml';
//                    break;
                case 'mapa':
                    include 'vista/ralarma_mapa.phtml';
                    $info = array("par"=>0, "lat"=>$_GET["lat"], "lon"=>$_GET["lon"]);
                    echo "<div id='info' style='display:none;'>".json_encode($info)."</div>";
                    break;
                case 'reporte':
                    $ini = strtotime($_POST["fecha_ini"]." ".$_POST["hrs_ini"].":".$_POST["min_ini"].":00");
                    $fin = strtotime($_POST["fecha_fin"]." ".$_POST["hrs_fin"].":".$_POST["min_fin"].":00");
//                    echo "vel: ".$_POST["operador"];
                    $rep = null;
                    if($_POST["id_device"] == "0") {
                        $gr = $this->dgMP->find($_POST["id_grupo"], $attr);
                        if($gr->accountID == $this->cp->getSession()->get("accountID")) {
                            $de = $this->deMP->fetchByGrupo($_POST["id_grupo"]);
                            $dev = array();
                            $license = array();
                            foreach($de as $d) {
                                $dev[] = $d->deviceID;
                                $license[$d->deviceID] = $d->licensePlate;
                                $nombre[$d->deviceID] = $d->displayName;
                            }
                            $rep = $this->edMP->velocidadByDevice($ini, $fin, $dev, $_POST["operador"], $_POST["vel"]);
                        }
                    } else {
                        $dev = $this->deMP->find($_POST["id_device"], array("accountID", "licensePlate", "displayName"));
                        $license[$_POST["id_device"]] = $dev->licensePlate;
                        $nombre[$_POST["id_device"]] = $dev->displayName;
                        if($dev->accountID == $this->cp->getSession()->get("accountID")) {
                            $rep = $this->edMP->velocidadByDevice($ini, $fin, array($_POST["id_device"]), $_POST["operador"], $_POST["vel"]);
                        }
                    }
//                    if($rep!=null) {
//                        $txt = "<h2>Reporte</h2>
//                        <table border='0' cellspacing='0' cellpadding='0' width='100%' class='tablarojo' id='reporte'>
//                            <thead>
//                                <tr>
//                                    <th align='center' width='100'>Veh&iacute;culo</th>
//                                    <th align='center' width='100'>Patente</th>
//                                    <th align='center' width='150'>Fecha</th>
//                                    <th align='center' width='100'>Velocidad (Km/h)</th>
//                                    <th align='center'>Ubicaci&oacute;n</th>
//                                </tr>
//                            </thead>
//                            <tbody>";
//                        foreach($rep as $r) {
//                            $txt .= "<tr>";
//                            $txt .= "<td align='center'>".$nombre[$r->deviceID]."</td>";
//                            $txt .= "<td align='center'>".$license[$r->deviceID]."</td>";
//                            $txt .= "<td align='center'>".$r->fecha."</td>";
//                            $txt .= "<td align='center'>".round($r->speedKPH)."</td>";
//                            $txt .= "<td align='center'><span style='display:none;' class='info'>".json_encode($info)."</span><a class='pop' title='<b>".$license[$r->deviceID]."</b><br /><b>Fecha</b>:".$r->fecha."<br /><b>Velocidad</b>:".round($r->speedKPH)." (Km/h)<br />' href='?sec=reporte&ssec=auditoria&get=mapa&lat=".$r->latitude."&lon=".$r->longitude."'>Ver mapa</a></td>";
//                            $txt .= "</tr>";
//                        }
//                        $txt .= "</tbody></table>";
//
//                        echo $txt;
//                    }
                    if($rep != null) {
                        foreach ($rep as $r) {
                            $out[] = array(
                                "licensePlate"=>$license[$r->deviceID],
                                "displayName"=>$nombre[$r->deviceID],
                                "fecha"=>$r->fecha,
                                "latitude"=>$r->latitude,
                                "longitude"=>$r->longitude,
                                "encendido"=>$r->encendido,
                                "velocidad"=>round($r->speedKPH)
                            );
                        }
                        echo json_encode($out);
                    }
                    break;
            }
        }
    }

    function setOp() {
        if (isset($_GET["op"])) {
        } else {
            $this->layout = "vista/reporte_base.phtml";
            $this->grupos = $this->dgMP->fetchByCuenta($this->cp->getSession()->get("accountID"));
            $this->min = range(0,59,15);
            $this->hrs = range(0,23,1);
        }
    }

    function getGrupoName($id) {
        $n = count($this->grupos);
        $i = 0;
        while($i<$n && $this->grupos[$i]->groupID != $id) { $i++; }
        return $this->grupos[$i]->displayName;
    }
}
?>

