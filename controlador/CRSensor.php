<?php
require_once 'modelo/DeviceGroupMP.php';
require_once 'modelo/SensorDeviceMP.php';
require_once 'modelo/DeviceMP.php';
require_once 'modelo/EventDataMP.php';

class CRSensor {
    protected $cp;
    protected $dgMP;
    protected $deMP;

    function  __construct($cp) {
        $this->cp = $cp;
        $this->dgMP = new DeviceGroupMP();
        $this->deMP = new DeviceMP();
        $this->edMP = new EventDataMP();
        $this->sdMP = new SensorDeviceMP();
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
                            $rep = $this->edMP->velocidadByDevice($ini, $fin, $dev, $_GET["operador"], $_GET["vel"]);
                        }
                    } else {
                        $dev = $this->deMP->find($_GET["id_device"], array("accountID", "licensePlate", "displayName"));
                        $license[$_GET["id_device"]] = $dev->licensePlate;
                        $nombre[$_GET["id_device"]] = $dev->displayName;
                        if($dev->accountID == $this->cp->getSession()->get("accountID")) {
                            $rep = $this->edMP->velocidadByDevice($ini, $fin, array($_GET["id_device"]), $_GET["operador"], $_GET["vel"]);
                        }
                    }
                    if($rep != null) {
                        require_once 'Classes/PHPExcel.php';

                        $objPHPExcel = new PHPExcel();
                        $objPHPExcel->getProperties()->setCreator("GPSLine")
                                ->setTitle("Reporte de Velocidad " . $ini . " - " . $fin)
                                ->setSubject("Reporte de Velocidad " . $ini . " - " . $fin)
                                ->setDescription("Reporte de Velocidad " . $ini . " - " . $fin);

                        $objPHPExcel->setActiveSheetIndex(0);
                        $objPHPExcel->getActiveSheet()->setTitle('Velocidad');
                        $objReader = PHPExcel_IOFactory::createReader('Excel5');
                        $objPHPExcel = $objReader->load("plantilla.xls");

                        $objPHPExcel->getActiveSheet()
                                ->setCellValueByColumnAndRow(5, 2, 'Reporte de Velocidad')
                                ->setCellValueByColumnAndRow(5, 3, utf8_encode('Período de tiempo: ') . $fini . " / ".$ffin);
                        $columnas = array("Fecha", "Vehículo", "Patente", "Latitud", "Longitud", "Velocidad");
                        $nCol = count($columnas);
                        $rowIni = 7;
                        for($i=0; $i<$nCol; $i++) {
                            $objPHPExcel->getActiveSheet()
                                ->setCellValueByColumnAndRow($i+1, $rowIni-1, utf8_encode($columnas[$i]));
                        }
                        $i = 0;
                        foreach ($rep as $r) {
                            $objPHPExcel->getActiveSheet()
                                ->setCellValueByColumnAndRow(1, $rowIni+$i, $r->fecha)
                                ->setCellValueByColumnAndRow(2, $rowIni+$i, $nombre[$r->deviceID])
                                ->setCellValueByColumnAndRow(3, $rowIni+$i, $license[$r->deviceID])
                                ->setCellValueByColumnAndRow(4, $rowIni+$i, $r->latitude)
                                ->setCellValueByColumnAndRow(5, $rowIni+$i, $r->longitude)
                                ->setCellValueByColumnAndRow(6, $rowIni+$i, round($r->speedKPH));
                            $i++;
                        }
                        header('Content-Type: application/vnd.ms-excel');
                        header('Content-Disposition: attachment;filename="reporte_velocidad_'.$ini.'_'.$fin.'.xls"');
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
                        
                        switch ($sen->TIPO_REPORTE) {
                            case 2: //combustible
                                $rep = $this->reporteCombustible($ini, $fin, $sen, $dev);
                                break;
                            case 3: //temperatura
                                $rep = $this->reporteTemperatura($ini, $fin, $sen, $dev);
                                break;
                        }
                        echo $rep;
                    }
                    break;
            }
        }
    }
    
    function reporteTemperatura($ini, $fin, $sen, $dev) {
        $license[$_POST["id_device"]] = $dev->licensePlate;
        $nombre[$_POST["id_device"]] = $dev->displayName;
        $vehicle[$_POST["id_device"]] = $dev->vehicleID;
        if($dev->accountID == $this->cp->getSession()->get("accountID")) {
            $rep = $this->edMP->fetchByDevice($ini, $fin, $_POST["id_device"], array("deviceID", $sen->COLUMNA_SENSOR));
        }
        
        $data = array();
        $data[0] = new stdClass();
        $data[0]->nombre = "Temperatura";
        $data[0]->tipo = "spline";
        $data[0]->eje = 0;
        $data[0]->tTotal = 2; //promedio
        $data[0]->total = 0;
        $nRep = count($rep);
        $col = $sen->COLUMNA_SENSOR;
        $difAcum = 0;
        for($i=0; $i<$nRep; $i++) {
            $r = $rep[$i];
            $obj = new stdClass();
            $obj->value = $r->$col;
            $obj->ts = $r->timestamp;
            if($ant->value!=$obj->value) {
                $data[0]->filtro[] = $obj;
            }
            if($i==$nRep-1) {
                $data[0]->filtro[] = $obj;
            }
            if($i>0) {
                $dif = $obj->ts - $ant->ts;
                $dif = round($dif/60)-1;
                $difAcum += $dif;
                if($dif>0) {
                    for($j=0; $j<$dif; $j++) {
                        $aux = new stdClass();
                        $aux->ts = $ant->ts + ($j+1)*60;
                        $aux->value = $ant->value;
                        $data[0]->data[] = $aux;
                    }
                }
            }
            $data[0]->data[] = $obj;
            $ant = $obj;
        }
        $grafico = new stdClass();
        $grafico->titulo = "Sensor: ".$sen->NOM_SENSOR;
        $grafico->inicio = $rep[0]->timestamp*1000;
        $grafico->periodos = $rep[0]->DIAS;
        $grafico->intervalo = 60*1000;
        $grafico->titulo_x = "Fecha";
        $grafico->titulo_y[0] = "Temperatura (".$sen->UNIDAD_SENSOR.")";
        $grafico->colores = array("'#AA4643'","'#89A54E'","'#ff9900'", "'#527daa'");
        $grafico->series = $data;
        $nSeries = count($grafico->series);
        $nData = count($grafico->series[0]->filtro);

        for($i=0; $i<$nSeries; $i++) {
            $senHead .= "<th align='center' width='100'>".$grafico->series[$i]->nombre."</th>";
            for($j=0;$j<$nData; $j++) {
                switch($grafico->series[$i]->tTotal) {
                    case 1: //suma
                        $grafico->series[$i]->total += $grafico->series[$i]->filtro[$j];
                        break;
                    case 2: //promedio
                        $grafico->series[$i]->total += $grafico->series[$i]->filtro[$j]->value/$nData;
                        break;
                }
            }
        }

        if($rep!=null) {
            $txt = "<h2>Reporte</h2>";
            $txt .= $this->getGrafico($grafico, false, 2);
            $txt .= "<table border='0' cellspacing='0' cellpadding='0' width='100%' class='tablarojo' id='reporte'>
                <thead>
                    <tr>
                        <th align='center' width='100'>Fecha</th>
                        <th align='center' width='100'>Veh&iacute;culo</th>
                        <th align='center' width='100'>Patente</th>
                        $senHead
                    </tr>
                </thead>
                <tbody>";
            $col = $sen->COLUMNA_SENSOR;
            for($j=0; $j<$nSeries; $j++) {
                for($i=0; $i<$nData; $i++) {
                    $txt .= "<tr>";
                    $txt .= "<td align='center'>".date("d-m-Y H:i:s", $grafico->series[$j]->filtro[$i]->ts)."</td>";
                    $txt .= "<td align='center'>".$nombre[$r->deviceID]."</td>";
                    $txt .= "<td align='center'>".$license[$r->deviceID]."</td>";
                    $txt .= "<td align='center'>".$grafico->series[$j]->filtro[$i]->value."</th>";
                    $txt .= "</tr>";
                }
            }

            $txt .= "<tr class='total'>";
            $txt .= "<td align='center'>Resumen</td>";
            $txt .= "<td align='center'>".$nombre[$r->deviceID]."</td>";
            $txt .= "<td align='center'>".$license[$r->deviceID]."</td>";
            for($j=0; $j<$nSeries; $j++) {
                $txt .= "<td align='center'>".round($grafico->series[$j]->total)."</th>";
            }
            $txt .= "</tr>";

            $txt .= "</tbody></table>";

            return $txt;
        }
    }
    
    function reporteCombustible($ini, $fin, $sen, $dev) {
        $license[$_POST["id_device"]] = $dev->licensePlate;
        $nombre[$_POST["id_device"]] = $dev->displayName;
        $vehicle[$_POST["id_device"]] = $dev->vehicleID;
        if($dev->accountID == $this->cp->getSession()->get("accountID")) {
            $rep = $this->edMP->fetchByDevice($ini, $fin, $_POST["id_device"], array("deviceID", $sen->COLUMNA_SENSOR));
            $km = $this->edMP->distanciaByDevice($ini, $fin, array($_POST["id_device"]));
        }
        $data = array();
        $data[0] = new stdClass();
        $data[1] = new stdClass();
        $data[2] = new stdClass();
        $data[3] = new stdClass();
        $data[0]->nombre = "Consumo";
        $data[0]->tipo = "column";
        $data[0]->eje = 0;
        $data[0]->tTotal = 1; //suma
        $data[0]->total = 0;
        $data[1]->nombre = "Carga";
        $data[1]->tipo = "column";
        $data[1]->eje = 0;
        $data[1]->tTotal = 1;
        $data[1]->total = 0;
        $data[2]->nombre = "Estanque";
        $data[2]->tipo = "area";
        $data[2]->eje = 0;
        $data[2]->tTotal = 2; //promedio
        $data[2]->total = 0;
        $data[3]->nombre = "Km Recorridos";
        $data[3]->tipo = "line";
        $data[3]->eje = 1;
        $data[3]->tTotal = 1;
        $data[3]->total = 0;
        $nRep = count($rep);
        $col = $sen->COLUMNA_SENSOR;
        
        $data2 = array();
        $data2[0] = new stdClass();
        $data2[0]->nombre = "Estanque";
        $data2[0]->tipo = "line";
        $data2[0]->eje = 0;
        $data2[0]->tTotal = 1; //suma
        $data2[0]->total = 0;
        
        for($i=0; $i<$rep[0]->DIAS; $i++) {
            $data[0]->data[$i]->value = 0;
            $data[1]->data[$i]->value = 0;
            $data[2]->data[$i]->value = 0;
            $data[3]->data[$i]->value = 0;
        }
        
//        for($i=0; $i<$nRep; $i++) {
//            $data2[0]->data[$i]->value = 0;
//        }

        $difAcum = 0;
        for($i=0; $i<$nRep; $i++) {
            $r = $rep[$i];
            if($i==0) {
                $ant = new stdClass();
                $ant->value = $r->$col;
            }
            $obj = new stdClass();
            $obj->value = $r->$col;
            $obj->ts = $r->timestamp;
//            echo $ant->value." | ".$obj->value."<br>";
            if($ant->value > $obj->value) { //gasto
                $data[0]->data[$r->INDICE]->value += $ant->value - $obj->value;
            } elseif($ant->value < $obj->value) { //cargo
                $data[1]->data[$r->INDICE]->value += $obj->value - $ant->value;
            } else {
//                                $data[2]->data[$i] = $act;
            }
//            echo "nData: ".count($data2[0]->data)."<br>";
            if($i>0) {
                $dif = $obj->ts - $ant->ts;
                $dif = round($dif/60)-1;
                $difAcum += $dif;
                if($dif>0) {
//                    echo $i.": ".$dif."<br>";
                    for($j=0; $j<$dif; $j++) {
//                        echo "--------".$j."<br>";
                        $aux = new stdClass();
                        $aux->ts = $ant->ts + ($j+1)*60;
                        $aux->value = $ant->value;
                        $data2[0]->data[] = $aux;
                    }
                }
            }
            $ant = $obj;
            $data2[0]->data[] = $obj;
        }

        for($i=0; $i<$rep[0]->DIAS; $i++) {
            $data[3]->data[$km[$i]->INDICE]->value = round($km[$i]->distancia);
            if($i==0) {
                $data[2]->data[$i]->value = $rep[0]->$col + $data[1]->data[$i]->value - $data[0]->data[$i]->value;
            } else {
                $data[2]->data[$i]->value = $data[2]->data[$i-1]->value + $data[1]->data[$i]->value - $data[0]->data[$i]->value;
            }
        }

        $grafico = new stdClass();
        $grafico->titulo = "Sensor: ".$sen->NOM_SENSOR." (Resumen)";
        $grafico->inicio = $rep[0]->timestamp*1000;
        $grafico->periodos = $rep[0]->DIAS;
        $grafico->intervalo = 24*60*60*1000;
        $grafico->titulo_x = "Fecha";
        $grafico->titulo_y[0] = "Litros";
        $grafico->titulo_y[1] = "Kilometros";
        $grafico->colores = array("'#AA4643'","'#89A54E'","'#ff9900'", "'#527daa'");
        $grafico->series = $data;
        
        $grafico2 = new stdClass();
        $grafico2->titulo = "Sensor: ".$sen->NOM_SENSOR." (Detalle)";
        $grafico2->inicio = $rep[0]->timestamp*1000;
        $grafico2->periodos = $rep[0]->DIAS;
        $grafico2->intervalo = 60*1000;
        $grafico2->titulo_x = "Fecha";
        $grafico2->titulo_y[0] = "Litros";
        $grafico2->colores = array("'#AA4643'","'#89A54E'","'#ff9900'", "'#527daa'");
        $grafico2->series = $data2;
        
//        echo "<pre>";
//        print_r($grafico);
//        echo "</pre>";
        
//        echo "<pre>";
//        print_r($grafico2);
//        echo "</pre>";
        
        $nSeries = count($grafico->series);
        for($i=0; $i<$nSeries; $i++) {
            $senHead .= "<th align='center' width='100'>".$grafico->series[$i]->nombre."</th>";
            for($j=0;$j<$grafico->periodos; $j++) {
                switch($grafico->series[$i]->tTotal) {
                    case 1: //suma
                        $grafico->series[$i]->total += $grafico->series[$i]->data[$j]->value;
                        break;
                    case 2: //promedio
                        $grafico->series[$i]->total += $grafico->series[$i]->data[$j]->value/$grafico->periodos;
                        break;
                }
            }
        }

        if($rep!=null) {
            $txt = "<h2>Reporte $difAcum</h2>";
            $txt .= $this->getGrafico($grafico2, false, 2, "graf_detalle");
            $txt .= $this->getGrafico($grafico, true, 1, "graf_resumen");
            $txt .= "<table border='0' cellspacing='0' cellpadding='0' width='100%' class='tablarojo' id='reporte'>
                <thead>
                    <tr>
                        <th align='center' width='100'>Fecha</th>
                        <th align='center' width='100'>Veh&iacute;culo</th>
                        <th align='center' width='100'>Patente</th>
                        $senHead
                    </tr>
                </thead>
                <tbody>";
            $col = $sen->COLUMNA_SENSOR;
            $inicio = $grafico->inicio/1000;
            $intervalo = $grafico->intervalo/1000;
            for($i=0; $i<$grafico->periodos; $i++) {
                $txt .= "<tr>";
                $txt .= "<td align='center'>".date("d-m-Y", $inicio + $i*$intervalo)."</td>";
                $txt .= "<td align='center'>".$nombre[$r->deviceID]."</td>";
                $txt .= "<td align='center'>".$license[$r->deviceID]."</td>";
                for($j=0; $j<$nSeries; $j++) {
                    $txt .= "<td align='center'>".$grafico->series[$j]->data[$i]->value."</th>";
                }
                $txt .= "</tr>";
            }

            $txt .= "<tr class='total'>";
            $txt .= "<td align='center'>Resumen</td>";
            $txt .= "<td align='center'>".$nombre[$r->deviceID]."</td>";
            $txt .= "<td align='center'>".$license[$r->deviceID]."</td>";
            for($j=0; $j<$nSeries; $j++) {
                $txt .= "<td align='center'>".round($grafico->series[$j]->total)."</th>";
            }
            $txt .= "</tr>";

            $txt .= "</tbody></table>";

            return $txt;
        }
    }
    
    function getGrafico($grafico, $regular = true, $tipo=1, $id_grafico = "grafico_res") {
        $nSeries = count($grafico->series);
        
        $r = "[";
        for ($i = 0; $i < $nSeries; $i++) {
            $serie = $grafico->series[$i];
            if($i) {
                $r .= ", { 
                    type: '".$serie->tipo."',
                    name: '".$serie->nombre."', 
                    pointInterval: ".$grafico->intervalo.",
                    pointStart: $grafico->inicio,
                    yAxis: ".$serie->eje.",
                    data: ([";
            } else {
                $r .= "{ 
                    type: '".$serie->tipo."',
                    name: '".$serie->nombre."', 
                    pointInterval: ".$grafico->intervalo.",
                    pointStart: $grafico->inicio,
                    yAxis: ".$serie->eje.",
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
            $this->sensores = $this->sdMP->fetchToReporte($this->cp->getSession()->get("accountID"));
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