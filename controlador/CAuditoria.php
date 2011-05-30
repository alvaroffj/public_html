<?php
require_once 'modelo/DeviceGroupMP.php';
require_once 'modelo/DeviceMP.php';
require_once 'modelo/EventDataMP.php';

class CAuditoria {
    protected $cp;
    protected $dgMP;
    protected $deMP;

    function  __construct($cp) {
        $this->cp = $cp;
        $this->dgMP = new DeviceGroupMP();
        $this->deMP = new DeviceMP();
        $this->edMP = new EventDataMP();
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
                case 'mapaAni':
                    include 'vista/ralarma_mapa.phtml';
                    break;
                case 'mapa':
                    include 'vista/ralarma_mapa.phtml';
                    $info = array("par"=>0, "lat"=>$_GET["lat"], "lon"=>$_GET["lon"]);
                    echo "<div id='info' style='display:none;'>".json_encode($info)."</div>";
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
                            $rep = $this->edMP->auditoriaByDevice($ini, $fin, $dev);
                        }
                    } else {
                        $dev = $this->deMP->find($_GET["id_device"], array("accountID", "licensePlate", "displayName"));
                        $license[$_GET["id_device"]] = $dev->licensePlate;
                        $nombre[$_GET["id_device"]] = $dev->displayName;
                        if($dev->accountID == $this->cp->getSession()->get("accountID")) {
                            $rep = $this->edMP->auditoriaByDevice($ini, $fin, array($_GET["id_device"]));
                        }
                    }
                    if($rep != null) {
                        require_once 'Classes/PHPExcel.php';

                        $objPHPExcel = new PHPExcel();
                        $objPHPExcel->getProperties()->setCreator("GPSLine")
                                ->setTitle("Reporte de Auditoria " . $ini . " - " . $fin)
                                ->setSubject("Reporte de Auditoria " . $ini . " - " . $fin)
                                ->setDescription("Reporte de Auditoria " . $ini . " - " . $fin);

                        $objPHPExcel->setActiveSheetIndex(0);
                        $objPHPExcel->getActiveSheet()->setTitle('Auditoria');
//                        $objReader = PHPExcel_IOFactory::createReader('Excel5');
//                        $objPHPExcel = $objReader->load("plantilla.xls");

                        $objPHPExcel->getActiveSheet()
                                ->setCellValueByColumnAndRow(5, 2, 'Reporte de Auditoria')
                                ->setCellValueByColumnAndRow(5, 3, utf8_encode('Período de tiempo: ') . $fini . " / ".$ffin);
                        $columnas = array("Vehículo", "Patente", "Fecha", "Latitud", "Longitud", "Velocidad", "Km. Recorridos", "Encendido");
                        $nCol = count($columnas);
                        $rowIni = 7;
                        for($i=0; $i<$nCol; $i++) {
                            $objPHPExcel->getActiveSheet()
                                ->setCellValueByColumnAndRow($i+1, $rowIni-1, utf8_encode($columnas[$i]));
                        }
                        $km = 0;
                        $i = 0;
                        foreach ($rep as $r) {
                            $km += $r->odometerKM;
                            $km = round($km,1);
                            $objPHPExcel->getActiveSheet()
                                ->setCellValueByColumnAndRow(1, $rowIni+$i, $nombre[$r->deviceID])
                                ->setCellValueByColumnAndRow(2, $rowIni+$i, $license[$r->deviceID])
                                ->setCellValueByColumnAndRow(3, $rowIni+$i, $r->fecha)
                                ->setCellValueByColumnAndRow(4, $rowIni+$i, $r->latitude)
                                ->setCellValueByColumnAndRow(5, $rowIni+$i, $r->longitude)
                                ->setCellValueByColumnAndRow(6, $rowIni+$i, round($r->speedKPH))
                                ->setCellValueByColumnAndRow(7, $rowIni+$i, $km)
                                ->setCellValueByColumnAndRow(8, $rowIni+$i, ($r->encendido==1)?"Si":"No");
                            $i++;
                        }
                        header('Content-Type: application/vnd.ms-excel');
                        header('Content-Disposition: attachment;filename="reporte_auditoria_'.$ini.'_'.$fin.'.xls"');
                        header('Cache-Control: max-age=0');
                        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
                        $objWriter->save('php://output');
//                        echo "lala<br>";
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
                            $rep = $this->edMP->auditoriaByDevice($ini, $fin, $dev);
                        }
                    } else {
                        $dev = $this->deMP->find($_POST["id_device"], array("accountID", "licensePlate", "displayName"));
                        $license[$_POST["id_device"]] = $dev->licensePlate;
                        $nombre[$_POST["id_device"]] = $dev->displayName;
                        if($dev->accountID == $this->cp->getSession()->get("accountID")) {
                            $rep = $this->edMP->auditoriaByDevice($ini, $fin, array($_POST["id_device"]));
                        }
                    }
//                    if($rep!=null) {
//                        $ani = ($_POST["id_device"] != "0")?"<div id='local-nav'><a href='?sec=reporte&ssec=auditoria&get=mapaAni' id='aniRep'><img src='img/play.png' border=0/>Animar Reporte</a></div>":"";
//                        $txt = "Auditor&iacute;a $ani
//                        <table border='0' cellspacing='0' cellpadding='0' width='100%' class='tablarojo' id='reporte'>
//                            <thead>
//                                <tr>";
//                        $txt .= "<th align='center' width='100'>Veh&iacute;culo</th>";
//                        $txt .= "<th align='center' width='100'>Patente</th>";
//                        $txt .= "<th align='center' width='150'>Fecha</th>";
//                        $txt .= "<th align='center' width='100'>Velocidad</th>";
//                        $txt .= "<th align='center' width='100'>Distancia</th>";
//                        $txt .= "<th align='center'>Ubicaci&oacute;n</th>";
//                        $txt .= "<th align='center'>Latitud</th>";
//                        $txt .= "<th align='center'>Longitud</th>";
//                        $txt .= "</tr>
//                            </thead>
//                            <tbody style='overflow:auto;'>";
//                        $km = 0;
//                        foreach($rep as $r) {
//                            $km += $r->odometerKM;
//                            $info = array(
//                                "licensePlate"=>$license[$r->deviceID],
//                                "displayName"=>$nombre[$r->deviceID],
//                                "fecha"=>$r->fecha,
//                                "velocidad"=>round($r->speedKPH),
//                                "distancia"=>round($km, 1),
//                                "lat"=>$r->latitude,
//                                "lon"=>$r->longitude
//                            );
//
//                            $txt .= "<tr>";
//                            $txt .= "<td align='center'>".$nombre[$r->deviceID]."</td>";
//                            $txt .= "<td align='center'>".$license[$r->deviceID]."</td>";
//                            $txt .= "<td align='center'>".$r->fecha."</td>";
//                            $txt .= "<td align='center'>".round($r->speedKPH)."</td>";
//                            $txt .= "<td align='center'>".$km."</td>";
//                            $txt .= "<td align='center'><span style='display:none;' class='info'>".json_encode($info)."</span><a class='pop' title='<b>Veh&iacute;culo</b>: ".$nombre[$r->deviceID]."<br /><b>Patente</b>: ".$license[$r->deviceID]."</b><br /><b>Fecha</b>:".$r->fecha."<br /><b>Velocidad</b>:".round($r->speedKPH)." (Km/h)<br /><b>Distancia</b>:".$km." (Km)' href='?sec=reporte&ssec=auditoria&get=mapa&lat=".$r->latitude."&lon=".$r->longitude."'>Ver mapa</a></td>";
//                            $txt .= "<td align='center'>".$r->latitude."</td>";
//                            $txt .= "<td align='center'>".$r->longitude."</td>";
//                            $txt .= "</tr>";
//                        }
//
//                        $txt .= "</tbody></table>";
//
//                        echo $txt;
//                    }
                    if($rep != null) {
                        $km = 0;
                        $i = 0;
                        foreach ($rep as $r) {
                            $km += $r->odometerKM;
                            if($i==0) {
                                $out[] = array(
                                    "licensePlate"=>$license[$r->deviceID],
                                    "displayName"=>$nombre[$r->deviceID],
                                    "fecha"=>$r->fecha,
                                    "velocidad"=>round($r->speedKPH),
                                    "distancia"=>round($km, 1),
                                    "latitude"=>$r->latitude,
                                    "longitude"=>$r->longitude,
                                    "encendido"=>$r->encendido
                                );
                                $i++;
                            } else {
//                                echo $out[$i-1]["latitude"]." | ".$r->latitude."\n";
                                if($out[$i-1]["latitude"] != $r->latitude && $out[$i-1]["longitude"]!=$r->longitude) {
                                    $out[] = array(
                                        "licensePlate"=>$license[$r->deviceID],
                                        "displayName"=>$nombre[$r->deviceID],
                                        "fecha"=>$r->fecha,
                                        "velocidad"=>round($r->speedKPH),
                                        "distancia"=>round($km, 1),
                                        "latitude"=>$r->latitude,
                                        "longitude"=>$r->longitude,
                                        "encendido"=>$r->encendido
                                    );
                                    $i++;
                                }
                            }
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