var $alarma;
var $mensaje = true;

function playSound() {
    $("#alarmaSound").jPlayer("play");
}

function stopSound() {
    $("#alarmaSound").jPlayer("stop");
}

function getAlarma(flag, hrs) {
    var urlAl;
//    if($mensaje) {
//        $.jGrowl("<span id='txt' style='display:block;'><b>Problema de transmisi&oacuten</b></span><span id='txt'>Estimado cliente, nuestro proveedor del servicio de Internet Movil esta teniendo problemas con la red de datos, por lo que los dispositivos estan teniendo dificultades para reportar su posici&oacute;n, apenas esto sea solucionado, los equipos enviaran todos los datos pendientes.<br />Agradecemos su comprensi&oacute;n.</span>", {
//            
//            life: 3000,
//            sticky: true,
//            close:  function(e,m) {
//                var msg = $("<li>"+m+"</li>");
//                if($alarma.length > 0) {
//                    msg.prependTo($alarma).hide().slideDown().fadeIn();
//                }
//                unsetGravedad(gravedad);
//            }
//        });
//        $mensaje = false;
//    }
    if(hrs == 0) {
        urlAl = "?sec=monitoreo&get=alarma";
    } else {
        urlAl = "?sec=monitoreo&get=alarma&hrs="+hrs;
    }
    $.ajax({
        url: urlAl,
        type: 'get',
        dataType: 'json',
        beforeSend: function() {

        },
        complete: function(data) {
            var al = $.parseJSON(data.responseText);
            var i = 0;
            var n = al.length;
            var aux;
            var gravedad = 10;
            var nueva = new Array();
            if(!flag) $alarma.html("");
            if(n>0 && flag) $.titleBlink("Alarma activada!",{repeat: 10,delay: 400});
            for(i=n-1; i>=0; i--) {
                aux = al[i];
                nueva[i] = $("<li><b><a onmousedown=\"javascript:setActive('"+aux.deviceID+"'); return false\">"+aux.displayName+"</a></b> ("+aux.fecha+")<br /><span id='txt' style='display:block;'><b>"+aux.NOM_ALERTA+"</b></span><span id='txt'>"+aux.txt+"</span></li>");
                gravedad = (aux.NIVEL_REGLA < gravedad)?aux.NIVEL_REGLA:gravedad;
                if(flag) {
                    $.jGrowl("<b><a onmousedown=\"javascript:setActive('"+aux.deviceID+"'); return false\">"+aux.displayName+"</a></b> ("+aux.fecha+")<br /><span id='txt' style='display:block;'><b>"+aux.NOM_ALERTA+"</b></span><span id='txt'>"+aux.txt+"</span>", {
                        header: 'Alarma: ',
                        life: 3000,
                        sticky: (gravedad=="0")?true:false,
                        close:  function(e,m) {
                            var msg = $("<li>"+m+"</li>");
                            if($alarma.length > 0) {
                                msg.prependTo($alarma).hide().slideDown().fadeIn();
                            }
                            unsetGravedad(gravedad);
                        }
                    });
                } else {
                    if($alarma.length > 0) {
                        nueva[i].prependTo($alarma).hide().slideDown().fadeIn();
                    } else {
                        $.jGrowl("<b>"+aux.displayName+"</b> ("+aux.fecha+")<br /><span id='txt' style='display:block;'><b>"+aux.NOM_ALERTA+"</b></span><span id='txt'>"+aux.txt+"</span>", {
                            header: 'Alarma: ',
                            sticky: (gravedad=="0")?true:false,
                            close:  function(e,m) {
                                unsetGravedad(gravedad);
                            }
                        });
                    }
                }
            }
            if(flag) {
                setGravedad(gravedad, aux);
            }
        }
    });
}

function unsetGravedad(gravedad) {
    switch(gravedad) {
        case "0":
            // console.log("Fin Panico!");
            stopSound();
            break;
    }
    act_dev.removeClass("nivel_"+gravedad);
}

function setGravedad(gravedad, dev) {
    switch(gravedad) {
        case "0":
            // console.log("Panico!");
            playSound();
            setActive(dev.deviceID);
            break;
    }
    act_dev.addClass("nivel_"+gravedad);
}

$(document).ready(function() {
    $alarma = $("#alarma");
    if($alarma.length > 0) getAlarma(false, 12);
    else getAlarma(true, 0);
    setInterval("getAlarma(true, 0)", 30000);
    $("#alarmaSound").jPlayer({
        ready: function() {
            $(this).jPlayer("setMedia", {
                mp3: "alarma.mp3"
            });
        },
        ended: function() {
            $(this).jPlayer("play");
        },
        swfPath: "/js"
    });
});