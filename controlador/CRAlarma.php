<?php
require_once 'modelo/DeviceGroupMP.php';
require_once 'modelo/DeviceMP.php';
require_once 'modelo/EventDataMP.php';
require_once 'modelo/AlertaLogMP.php';
require_once 'modelo/PoligonoMP.php';
require_once 'modelo/PInteresMP.php';

class CRAlarma {
    protected $cp;
    protected $dgMP;
    protected $deMP;
    protected $poMP;
    protected $piMP;
    protected $alMP;

    function  __construct($cp) {
        $this->cp = $cp;
        $this->dgMP = new DeviceGroupMP();
        $this->deMP = new DeviceMP();
        $this->edMP = new EventDataMP();
        $this->alMP = new AlertaLogMP();
        $this->poMP = new PoligonoMP();
        $this->piMP = new PInteresMP();
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
                case 'mapa':
                    include 'vista/ralarma_mapa.phtml';
                    $info = array("par"=>$_GET["par"], "lat"=>$_GET["lat"], "lon"=>$_GET["lon"]);
                    echo "<div id='info' style='display:none;'>".json_encode($info)."</div>";
                    switch ($_GET["par"]) {
                        case "1": //vel
                            break;
                        case "2": //time
                            break;
                        case "3": //geoz
                            $this->poligono = $this->poMP->find($_GET["pol"]);
                            $this->puntos = $this->poMP->fetchPuntos($this->poligono->ID_POLIGONO);
                            echo "<div id='pol' style='display:none;'>";
                            echo json_encode($this->puntos);
                            echo "</div>";
                            break;
                        case "4": //geof
                            break;
                        case "5": //pint
                            $this->obj = $this->piMP->find($_GET["pol"]);
                            echo "<div id='pint' style='display:none;'>";
                            echo json_encode($this->obj);
                            echo "</div>";
                            break;
                    }
                    break;
                case 'descargar':
                    $ini = strtotime($_GET["fecha_ini"]." ".$_GET["hrs_ini"].":".$_GET["min_ini"].":00");
                    $fin = strtotime($_GET["fecha_fin"]." ".$_GET["hrs_fin"].":".$_GET["min_fin"].":00");
                    $fini = $_GET["fecha_ini"]." ".$_GET["hrs_ini"].":".$_GET["min_ini"].":00";
                    $ffin = $_GET["fecha_fin"]." ".$_GET["hrs_fin"].":".$_GET["min_fin"].":00";
                    $rep = null;
                    if($_GET["id_device"] == "0") {
                        $gr = $this->dgMP->find($_GET["id_grupo"], $attr);
                        if($gr->accountID == $this->cp->getSession()->get("accountID")) {
                            $de = $this->deMP->fetchByGrupo($_GET["id_grupo"]);
                            $dev = array();
                            $license = array();
                            foreach($de as $d) {
                                $dev[] = $d->deviceID;
                                $license[$d->deviceID] = $d->licensePlate;
                                $nombre[$d->deviceID] = $d->displayName;
                            }
                            $rep = $this->alMP->reporte($ini, $fin, $dev);
                        }
                    } else {
                        $dev = $this->deMP->find($_GET["id_device"], array("accountID", "licensePlate", "displayName"));
                        $license[$_GET["id_device"]] = $dev->licensePlate;
                        $nombre[$_GET["id_device"]] = $dev->displayName;
                        if($dev->accountID == $this->cp->getSession()->get("accountID")) {
                            $rep = $this->alMP->reporte($ini, $fin, array($_POST["id_device"]));
                        }
                    }
                    if($rep != null) {
                        require_once 'Classes/PHPExcel.php';

                        $objPHPExcel = new PHPExcel();
                        $objPHPExcel->getProperties()->setCreator("GPSLine")
                                ->setTitle("Reporte de Alarmas " . $ini . " - " . $fin)
                                ->setSubject("Reporte de Alarmas " . $ini . " - " . $fin)
                                ->setDescription("Reporte de Alarmas " . $ini . " - " . $fin);

                        $objPHPExcel->setActiveSheetIndex(0);
                        $objPHPExcel->getActiveSheet()->setTitle('Alarmas');
                        $objReader = PHPExcel_IOFactory::createReader('Excel5');
                        $objPHPExcel = $objReader->load("plantilla.xls");

                        $objPHPExcel->getActiveSheet()
                                ->setCellValueByColumnAndRow(5, 2, 'Reporte de Alarmas')
                                ->setCellValueByColumnAndRow(5, 3, utf8_encode('Período de tiempo: ') . $fini . " / ".$ffin);
                        $columnas = array("Fecha", "Vehículo", "Patente", "Latitud", "Longitud", "Velocidad", "Encendido", "Alarma", "Regla");
                        $nCol = count($columnas);
                        $rowIni = 7;
                        for($i=0; $i<$nCol; $i++) {
                            $objPHPExcel->getActiveSheet()
                                ->setCellValueByColumnAndRow($i+1, $rowIni-1, utf8_encode($columnas[$i]));
                        }
                        $km = 0;
                        $i = 0;
                        foreach ($rep as $r) {
                            $objPHPExcel->getActiveSheet()
                                ->setCellValueByColumnAndRow(1, $rowIni+$i, $r->fecha)
                                ->setCellValueByColumnAndRow(2, $rowIni+$i, $nombre[$r->deviceID])
                                ->setCellValueByColumnAndRow(3, $rowIni+$i, $license[$r->deviceID])
                                ->setCellValueByColumnAndRow(4, $rowIni+$i, $r->latitude)
                                ->setCellValueByColumnAndRow(5, $rowIni+$i, $r->longitude)
                                ->setCellValueByColumnAndRow(6, $rowIni+$i, round($r->speedKPH))
                                ->setCellValueByColumnAndRow(7, $rowIni+$i, ($r->encendido=="1")?"Si":"No")
                                ->setCellValueByColumnAndRow(8, $rowIni+$i, $r->NOM_ALERTA)
                                ->setCellValueByColumnAndRow(9, $rowIni+$i, utf8_encode(strip_tags($this->traduceRegla($r))));
                            $i++;
                        }
                        
                        header('Content-Type: application/vnd.ms-excel');
                        header('Content-Disposition: attachment;filename="reporte_alarma_'.$ini.'_'.$fin.'.xls"');
                        header('Cache-Control: max-age=0');
                        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
                        $objWriter->save('php://output');
                    }
                    break;
                case 'reporte':
                    $ini = strtotime($_POST["fecha_ini"]." ".$_POST["hrs_ini"].":".$_POST["min_ini"].":00");
                    $fin = strtotime($_POST["fecha_fin"]." ".$_POST["hrs_fin"].":".$_POST["min_fin"].":00");
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
                            $rep = $this->alMP->reporte($ini, $fin, $dev);
                        }
                    } else {
                        $dev = $this->deMP->find($_POST["id_device"], array("accountID", "licensePlate", "displayName"));
                        $license[$_POST["id_device"]] = $dev->licensePlate;
                        $nombre[$_POST["id_device"]] = $dev->displayName;
                        if($dev->accountID == $this->cp->getSession()->get("accountID")) {
                            $rep = $this->alMP->reporte($ini, $fin, array($_POST["id_device"]));
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
//                                    <th align='center' width='100'>Alarma</th>
//                                    <th align='center'>Regla</th>
//                                    <th align='center'>Ubicaci&oacute;n</th>
//                                </tr>
//                            </thead>
//                            <tbody>";
//
//                        foreach($rep as $r) {
//                            $til = $this->traduceRegla($r);
//                            $txt .= "<tr>";
//                            $txt .= "<td align='center'>".$nombre[$r->deviceID]."</td>";
//                            $txt .= "<td align='center'>".$license[$r->deviceID]."</td>";
//                            $txt .= "<td align='center'>".$r->fecha."</td>";
//                            $txt .= "<td align='center'>".$r->NOM_ALERTA."</td>";
//                            $txt .= "<td align='center'>".$til."</td>";
////                            $txt .= "<td align='center'><a onClick=\"showMapa('".$r->latitude."', '".$r->longitude."', ".$r->ID_PARAMETRO.", ".$r->ID_POLIGONO."); return false;\">Ver mapa</a></td>";
//                            $txt .= "<td align='center'><a class='pop' title='<b>".$r->NOM_ALERTA."</b>: <b>".$license[$r->deviceID]."</b> $til (".$r->fecha.")' href='?sec=reporte&ssec=alarma&get=mapa&lat=".$r->latitude."&lon=".$r->longitude."&par=".$r->ID_PARAMETRO."&pol=".$r->ID_POLIGONO."'>Ver mapa</a></td>";
//                            $txt .= "</tr>";
//                        }
//
//                        $txt .= "</tbody></table>";
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
                                "alarma"=>$r->NOM_ALERTA,
                                "regla"=>utf8_encode($this->traduceRegla($r)),
                                "velocidad"=>round($r->speedKPH)
                            );
                        }
                        echo json_encode($out);
                    }
                    break;
            }
        }
    }

    function traduceRegla($regla) {
        switch($regla->ID_PARAMETRO) {
            case 1: //velocidad
                switch($regla->ID_OPERADOR) {
                    case 1:
                        return "Velocidad (".$regla->speedKPH.") > ".$regla->VALOR_REGLA." (Km/h)";
                        break;
                    case 2:
                        return "Velocidad (".$regla->speedKPH.") < ".$regla->VALOR_REGLA." (Km/h)";
                        break;
                }
                break;
            case 2: //tiempo
                switch($regla->ID_OPERADOR) {
                    case 1:
                        return "Detención > ".$regla->VALOR_REGLA." (Min.)";
                        break;
                    case 2:
                        return "Detención > ".$regla->VALOR_REGLA." (Min.)";
                        break;
                }
                break;
            case 3: //geozona
                $pol = $this->poMP->find($regla->ID_POLIGONO, array("NOM_POLIGONO"));
                switch($regla->ID_OPERADOR) {
                    case 4:
                        return "Entró a la Geozona <b>".$pol->NOM_POLIGONO."</b>";
                        break;
                    case 5:
                        return "Salió de la Geozona <b>".$pol->NOM_POLIGONO."</b>";
                        break;
                }
                break;
            case 4: //geofrontera
                $pol = $this->poMP->find($regla->ID_POLIGONO, array("NOM_POLIGONO"));
                switch($regla->ID_OPERADOR) {
                    case 6:
                        return "Cruzó la Geofrontera <b>".$pol->NOM_POLIGONO."</b>";
                        break;
                }
                break;
            case 5: //punto de interes
                $pi = $this->piMP->find($regla->ID_POLIGONO, array("name"));
                switch($regla->ID_OPERADOR) {
                    case 4:
                        return "Entró al Punto de interés <b>".$pi->name."</b>";
                        break;
                    case 5:
                        return "Salió del Punto de interés <b>".$pi->name."</b>";
                        break;
                }
                break;
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