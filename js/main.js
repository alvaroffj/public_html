var reportes = [];
reportes[0] = "auditoria";
reportes[1] = "alarma";
reportes[2] = "recorrido";
reportes[3] = "velocidad";
var reporteSel = -1;
var $resize;

function setReporte(n) {
    reporteSel = n;
    
    $.ajax({
        url: "?sec=reporte&ssec="+reportes[n]+"&ajax",
        type: 'get',
        beforeSend: function() {
        },
        complete: function(data) {
            if(reporteSel == 2) $map_canvas.hide();
            else $map_canvas.show();
            $("#bar", $lateralLeft).html(data.responseText);
            clearInterval(monitoreo);
            hideAllDevice();
            desactivaTodos();
        }
    });
}

function setSec() {
    $url = $.url();
    $sec = $url.fsegment(1);
    switch($sec) {
        case "reporte":
            $ssec = $url.fsegment(2);
            for(var i=0; i<reportes.length; i++) {
                if(reportes[i]==$ssec) {
                    break;
                }
            }
            setReporte(i);
            break;
        default:
            showDevices();
            break;
        case "monitoreo":
            showDevices();
            break;
    }
}

$(document).ready(function(){
    $resize = $("#resize");
    $resize.draggable({
        axis: 'x',
        stop: function(event, ui) {
            $lateralLeft.width(event.pageX-12);
            if($reporte && reporteSel && reporteSel == 2) {
                $reporte.css({
                    "margin-left":($lateralLeft.width()+20+$lateralLeft.position().left)+"px",
                    "margin-right":(5-($lateralRight.position().left-$(window).width()))+"px"
                });
            }
        }
    });
});