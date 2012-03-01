<?php
require_once 'modelo/DeviceGroupMP.php';
require_once 'modelo/SensorDeviceMP.php';
require_once 'modelo/DeviceMP.php';
require_once 'modelo/EventDataMP.php';

class CRUso {
    protected $cp;
    protected $dgMP;
    protected $deMP;

    function  __construct($cp) {
        $this->cp = $cp;
        $this->dgMP = new DeviceGroupMP();
        $this->deMP = new DeviceMP();
        $this->edMP = new EventDataMP();
        $this->sdMP = new SensorDeviceMP();
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
                                ->setTitle("Reporte de Horas de Uso " . $ini . " - " . $fin)
                                ->setSubject("Reporte de Horas de Uso " . $ini . " - " . $fin)
                                ->setDescription("Reporte de Horas de Uso " . $ini . " - " . $fin);

                        $objPHPExcel->setActiveSheetIndex(0);
                        $objPHPExcel->getActiveSheet()->setTitle('Auditoria');
                        $objReader = PHPExcel_IOFactory::createReader('Excel5');
                        $objPHPExcel = $objReader->load("plantilla.xls");

                        $objPHPExcel->getActiveSheet()
                                ->setCellValueByColumnAndRow(5, 2, 'Reporte de Horas de Uso')
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
                        header('Content-Disposition: attachment;filename="reporte_horas_uso_'.$ini.'_'.$fin.'.xls"');
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
                    if($_POST["id_device"] != "0") {
                        $dev = $this->deMP->find($_POST["id_device"], array("accountID", "licensePlate", "vehicleID", "displayName", "deviceID"));
                        $sen = $this->sdMP->findSensor($_POST["id_sensor"]);
                        
                        $rep = $this->getReporte($ini, $fin, $sen, $dev);
                        
                        echo $rep;
                    }

                    break;
            }
        }
    }
    
    function getReporte($ini, $fin, $sen, $dev) {
        $license[$dev->licensePlate] = $dev->licensePlate;
        $nombre[$dev->displayName] = $dev->displayName;
        $vehicle[$dev->vehicleID] = $dev->vehicleID;
        if($dev->accountID == $this->cp->getSession()->get("accountID")) {
            $rep = $this->edMP->fetchByDevice($ini, $fin, $dev->deviceID, array("deviceID", $sen->COLUMNA_SENSOR));
        }
        
//        echo "<pre>";
//        print_r($rep);
//        echo "</pre>";
        
        $data = array();
        $data[0] = new stdClass();
        $data[0]->nombre = "Tiempo de Uso";
        $data[0]->tipo = "line";
        $data[0]->eje = 0;
        $data[0]->tTotal = 1; //suma
        $data[0]->total = 0;
        $data[0]->deviceID = $dev->deviceID;
        $data[0]->licensePlate = $dev->licensePlate;
        $data[0]->displayName = $dev->displayName;
        $nRep = count($rep);
        $col = $sen->COLUMNA_SENSOR;
        $tiempoUso = 0;
        $tiempoTotal = 0;
        $ant = new stdClass();
        for($i=0; $i<$nRep; $i++) {
            $r = $rep[$i];
            $obj = new stdClass();
            $obj->value = $r->$col;
            $obj->ts = $r->timestamp;
            
            if($i>0) {
                $dif = $obj->ts - $ant->ts;
                $tiempoTotal += $dif;
                if($obj->value == 1 && $ant->value == 1) {
                    $tiempoUso += $dif;
                }
                $nDif = round($dif/60)-1;
                if($nDif>0) {
                    for($j=0; $j<$nDif; $j++) {
                        $aux = new stdClass();
                        $aux->ts = $ant->ts + ($j+1)*60;
                        $aux->value = $ant->value;
                        $aux->relleno = true;
                        $data[0]->data[] = $aux;
                        $tiempoTotal += ($j+1)*60;
                        if($obj->value == 1 && $aux->value == 1) {
                            $tiempoUso += ($j+1)*60;
                        }
                    }
                }
            }
            $data[0]->tiempoTotal = $tiempoTotal;
            $data[0]->tiempoUso = $tiempoUso;
            $data[0]->data[] = $obj;
            $ant = $obj;
        }
        
        $grafico = new stdClass();
        $grafico->titulo = "Horas de uso: ".$sen->NOM_SENSOR;
        $grafico->inicio = $rep[0]->timestamp*1000;
        $grafico->periodos = $rep[0]->DIAS;
        $grafico->intervalo = 60*1000;
        $grafico->titulo_x = "Fecha";
        $grafico->titulo_y[0] = "Estado (Encendido/Apagado)";
        $grafico->colores = array("'#AA4643'","'#89A54E'","'#ff9900'", "'#527daa'");
        $grafico->series = $data;
        $nSeries = count($grafico->series);
        echo count($grafico->series[0]->data);

        for($i=0; $i<$nSeries; $i++) {
            $senHead .= "<th align='center' width='100'>".$grafico->series[$i]->nombre."</th>";
        }
        
        if($rep!=null) {
            $txt = "<h2>Reporte</h2>";
            $txt .= $this->getGrafico($grafico, true, 2);
            $txt .= "<table border='0' cellspacing='0' cellpadding='0' width='100%' class='tablarojo' id='reporte'>
                <thead>
                    <tr>
                        <th align='center' width='100'>Veh&iacute;culo</th>
                        <th align='center' width='100'>Patente</th>
                        <th align='center' width='100'>Tiempo Total</th>
                        $senHead
                    </tr>
                </thead>
                <tbody>";
            $col = $sen->COLUMNA_SENSOR;
            for($j=0; $j<$nSeries; $j++) {
                $txt .= "<tr>";
                $txt .= "<td align='center'>".$grafico->series[$j]->displayName."</td>";
                $txt .= "<td align='center'>".$grafico->series[$j]->licensePlate."</td>";
                $txt .= "<td align='center'>".$grafico->series[$j]->tiempoTotal." ".$this->getTiempo($grafico->series[$j]->tiempoTotal)."</th>";
                $txt .= "<td align='center'>".$grafico->series[$j]->tiempoUso." ".$this->getTiempo($grafico->series[$j]->tiempoUso)."</th>";
                $txt .= "</tr>";
            }

            $txt .= "</tbody></table>";

            return $txt;
        }
    }
    
    function getTiempo($seg, $sep=":") {
        if($seg >= 0) {
            $hrsA = $seg/3600;
            $hrs = floor($hrsA);
            if($hrs == 0) $hrs = "00";
            else if($hrs<10) $hrs = "0".$hrs;

            $minA = ($hrsA-$hrs)*60;
            $min = floor($minA);
            if($min == 0) $min = "00";
            else if($min<10) $min = "0".$min;

            $sec = floor(($minA-$min)*60);
            if($sec == 0) $sec = "00";
            else if($sec<10) $sec = "0".$sec;

            return $hrs.$sep.$min.$sep.$sec;
        }
    }
    
    function getGrafico($grafico, $regular = true, $tipo=1, $id_grafico = "grafico_res") {
        $nSeries = count($grafico->series);
        
        $r = "[";
        for ($i = 0; $i < $nSeries; $i++) {
            $serie = $grafico->series[$i];
            if($i) {
                $r .= ", { 
                    name: '".$serie->nombre."', 
                    pointInterval: ".$grafico->intervalo.",
                    pointStart: $grafico->inicio,
                    yAxis: ".$serie->eje.",
                    step: true,
                    data: ([";
            } else {
                $r .= "{ 
                    name: '".$serie->nombre."', 
                    pointInterval: ".$grafico->intervalo.",
                    pointStart: $grafico->inicio,
                    yAxis: ".$serie->eje.",
                    step: true,
                    data: ([";
            }
            if($regular) {
                $nData = count($serie->data);
                for($j=0; $j<$nData; $j++) {
                    if($j==0) {
                        $r .= $serie->data[$j]->value;
                    } else {
                        $r .= ", ".$serie->data[$j]->value;
                    }
                }
            } else {
                $nData = count($serie->data);
                for($j=0; $j<$nData; $j++) {
                    if($j==0) {
                        $r .= "[".($serie->data[$j]->ts*1000).",".$serie->data[$j]->value."]";
                    } else {
                        $r .= ", [".($serie->data[$j]->ts*1000).",".$serie->data[$j]->value."]";
                    }
                }
            }
            $r .= "])}";
        }
        $r .= "]";
//        echo $r;
        $tgrafico = ($tipo == 1)?"Chart":"StockChart";
        
        $txt = "<div class='grafico' id='$id_grafico'></div>";
        $graf = " 
            chartRes = new Highcharts.$tgrafico({
                    chart: {
                        renderTo: '$id_grafico',
                        zoomType: 'x',
                        spacingRight: 20
                    },
                    title: {
                        text: '".$grafico->titulo."'
                    },
                    colors: [".implode(",", $grafico->colores)."],
                    subtitle: {
                        text: 'Click y arrastrar para acercar'
                    },
                    xAxis: {
                        type: 'datetime',
                        maxZoom: 3600*24*10,
                        title: {
                            text: '".$grafico->titulo_x."'
                        }
                    },
                    yAxis: [{
                            title: {
                                text: '".$grafico->titulo_y[0]."'
                            },
                            startOnTick: false,
                            showFirstLabel: false,
                            plotLines: [{
                                value: 0,
                                width: 2,
                                color: 'green'
                            }]
                        }";
            if(isset($grafico->titulo_y[1])) {
                $graf .= ", {title: {text: '".$grafico->titulo_y[1]."'}, opposite: true}";
            }
            $graf .= "],
                    tooltip: {
                        shared: true
                    },
                    series: $r
                });
        Highcharts.setOptions({
            global: {
                useUTC: false
            }
        });";
        return $txt."<script>".$graf."</script>";
    }

    function setOp() {
        if (isset($_GET["op"])) {
        } else {
            $this->layout = "vista/reporte_base.phtml";
            $this->sensores = $this->sdMP->fetchByTipo($this->cp->getSession()->get("accountID"), 1);
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