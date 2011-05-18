<?php
require_once 'modelo/DeviceGroupMP.php';
require_once 'modelo/DeviceMP.php';
require_once 'modelo/EventDataMP.php';

class CRDistancia {
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
//                case 'mapaAni':
//                    include 'vista/ralarma_mapa.phtml';
//                    break;
//                case 'mapa':
//                    include 'vista/ralarma_mapa.phtml';
//                    $info = array("par"=>0, "lat"=>$_GET["lat"], "lon"=>$_GET["lon"]);
//                    echo "<div id='info' style='display:none;'>".json_encode($info)."</div>";
//                    break;
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
                            $rep = $this->edMP->distanciaByDevice($ini, $fin, $dev);
                        }
                    } else {
                        $devAux = $this->deMP->find($_POST["id_device"], array("deviceID", "accountID", "licensePlate", "displayName"));
                        $license[$_POST["id_device"]] = $devAux->licensePlate;
                        $nombre[$_POST["id_device"]] = $devAux->displayName;
                        if($devAux->accountID == $this->cp->getSession()->get("accountID")) {
                            $rep = $this->edMP->distanciaByDevice($ini, $fin, array($_POST["id_device"]));
                        }
                        $dev[0] = $devAux->deviceID;
                    }
                    if($rep!=null) {
                        $txt = "<h2>Reporte</h2>";
                        $txt .= $this->getGrafico($rep, $nombre, $license, $dev, $_POST["fecha_ini"]);
                        $txt .= "<table border='0' cellspacing='0' cellpadding='0' width='100%' class='tablarojo' id='reporte'>
                            <thead>
                                <tr>
                                    <th align='center' width='100'>Fecha</th>
                                    <th align='center' width='100'>Veh&iacute;culo</th>
                                    <th align='center' width='100'>Patente</th>
                                    <th align='center' width='100'>Distancia (Km)</th>
                                </tr>
                            </thead>
                            <tbody>";
                        foreach($rep as $r) {
                            $txt .= "<tr>";
                            $txt .= "<td align='center'>".$r->fecha."</td>";
                            $txt .= "<td align='center'>".$nombre[$r->deviceID]."</td>";
                            $txt .= "<td align='center'>".$license[$r->deviceID]."</td>";
                            $txt .= "<td align='center'>".round($r->distancia, 1)."</td>";
                            $txt .= "</tr>";
                        }

                        $txt .= "</tbody></table>";

                        echo $txt;
                    }
                    break;
            }
        }
    }
    
    function getGrafico($log, $nom, $pat, $dev, $ini) {
        $nDev = count($dev);
        for($j=0; $j<$nDev; $j++) {
            for ($i = 0; $i < $log[0]->DIAS + 1; $i++) {
                $data[$dev[$j]][] = 0;
            }
        }
        
        foreach ($log as $l) {
            $data[$l->deviceID][$l->INDICE] = round($l->distancia, 1);
        }
        $ini = explode("-", $ini);
        $r = "[";
        for ($i = 0; $i < $nDev; $i++) {
            if($i) {
                $r .= ", { 
                    name: '".$nom[$dev[$i]]."', 
                    pointInterval: 24 * 3600 * 1000,
                    pointStart: Date.UTC(".$ini[0].", ".($ini[1]-1).", ".$ini[2]."),
                    data: [";
            } else {
                $r .= "{ 
                    name: '".$nom[$dev[$i]]."', 
                    pointInterval: 24 * 3600 * 1000,
                    pointStart: Date.UTC(".$ini[0].", ".($ini[1]-1).", ".$ini[2]."),
                    data: [";
            }
            for($j=0; $j<$log[0]->DIAS + 1; $j++) {
                if($j) {
                    $r .= ", ".$data[$dev[$i]][$j];
                } else {
                    $r .= $data[$dev[$i]][$j];
                }
            }
            $r .= "]}";
        }
        $r .= "]";
        
        $txt = "<div class='grafico' id='grafico_res'></div>
        <script>
            chartRes = new Highcharts.Chart({
                    chart: {
                        renderTo: 'grafico_res',
                        zoomType: 'x',
                        spacingRight: 20
                    },
                    title: {
                        text: 'Kilometros recorridos'
                    },
                    subtitle: {
                        text: 'Click y arrastrar para acercar'
                    },
                    xAxis: {
                        type: 'datetime',
                        maxZoom: 10 * 24 * 3600000, // fourteen days
                        title: {
                            text: 'Fecha'
                        }
                    },
                    yAxis: {
                        title: {
                            text: 'Km. Recorridos'
                        },
                        min: 0.6,
                        startOnTick: false,
                        showFirstLabel: false
                    },
                    tooltip: {
                        shared: true
                    },
                    plotOptions: {
                        line: {
                            dataLabels: {
                               enabled: true
                            },
                            enableMouseTracking: false
                         },
                        area: {
                            fillColor: {
                                linearGradient: [0, 0, 0, 300],
                                stops: [
                                [0, 'rgba(2,0,0,2)'],
                                [1, 'rgba(2,0,0,0)']
                                ]
                            },
                            lineWidth: 1,
                            marker: {
                                enabled: false,
                                states: {
                                    hover: {
                                        enabled: true,
                                        radius: 5
                                    }
                                }
                            },
                            shadow: false,
                            states: {
                                hover: {
                                    lineWidth: 1
                                }
                            }
                        }
                    },

                    series: $r
                });
        </script>";
        return $txt;
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

