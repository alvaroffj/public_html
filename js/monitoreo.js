var geocoder;
var map;
var device = [];
var infoDevice = [];
var indexDevice = [];
var activeIndex = -1;
var preActive = -1;
var act_dev;
var punto = [];
var monitoreo;
var markerBuscador;

var puntoMarker;
var puntoNew;
var $puntoForm;
var $btn_pint;
var puntoFormValidator;

var $reporte;

var pin_dev = [];

//var pInMarker = new google.maps.MarkerImage('img/marker.png',
//    new google.maps.Size(16, 26),
//    new google.maps.Point(0,0),
//    new google.maps.Point(8,26)
//    );

var dev_stop = new google.maps.MarkerImage('img/car_stop.png',
    new google.maps.Size(32, 32),
    new google.maps.Point(0,0),
    new google.maps.Point(16,16)
);

var dev_run = new google.maps.MarkerImage('img/car_run.png',
    new google.maps.Size(32, 32),
    new google.maps.Point(0,0),
    new google.maps.Point(16,16)
);
//var img = new Image();
//img.src = "img/pin_auto.png";

var angle;
var canvas;

function plotcar(angle) {
    var cosa = Math.cos(angle);
    var sina = Math.sin(angle);
    canvas.clearRect(0,0,32,32);
    canvas.save();
    canvas.rotate(angle);
    canvas.translate(16*sina+16*cosa,16*cosa-16*sina);
    canvas.drawImage(img,-16,-16);
    canvas.restore();
}
function initialize() {
    geocoder = new google.maps.Geocoder();
    var latlng = new google.maps.LatLng(-33.446365427932115,-70.6538918134155);
    var myOptions = {
        zoom: 11,
        center: latlng,
        mapTypeId: google.maps.MapTypeId.ROADMAP,
        navigationControl: true,
        navigationControlOptions: {
            style: google.maps.NavigationControlStyle.ZOOM_PAN,
            position: google.maps.ControlPosition.TOP_RIGHT
        }
    };
    map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
    var menu = new contextMenu({map:map});
    menu.addItem('Crear punto de inter&eacute;s aqu&iacute;', function(map, latLng){
        creaPuntoInteres(latLng.lat(), latLng.lng());
    });
    menu.addItem('Acercar', function(map, latLng){
        map.setZoom( map.getZoom() + 1);
        map.panTo( latLng );
    });
    menu.addItem('Alejar', function(map, latLng){
        map.setZoom( map.getZoom() - 1 );
        map.panTo(latLng);
    });
    menu.addItem('Centrar aqui', function(map, latLng){
        map.panTo(latLng);
    });
}

function showGeozona() {
}

function showPInteres() {
    var i;
    if($btn_pint.hasClass("active")) {
        for(i=0; i<punto.length; i++) {
            punto[i].setMap(null);
        }
        $btn_pint.removeClass("active");
        $btn_pint.attr("title", "Mostrar puntos de interes");
    } else {
        $btn_pint.attr("title", "Ocultar puntos de interes");
        $btn_pint.addClass("active");
        if(punto.length > 0) {
//            console.log("muestra lo cargado");
            for(i=0; i<punto.length; i++) {
                punto[i].setMap(map);
            }
        } else {
//            console.log("carga desde la BD");
            $.ajax({
                url: "?sec=monitoreo&get=pinteres",
                type: 'get',
//                dataType: 'json',
                beforeSend: function() {

                },
                complete: function(data) {
                    res = $.parseJSON(data.responseText);
                    var nRes = res.length;
                    var pto;
                    for(i=0; i<nRes; i++) {
                        pto = new google.maps.LatLng(res[i].latitude, res[i].longitude);
//                        console.log(res[i]);
//                        console.log(res[i].latitude+", "+res[i].longitude);
                        punto[i] = new google.maps.Circle({
                            center: pto,
                            radius: res[i].radio*1,
                            strokeColor: '#ff0000',
                            strokeOpacity: 0.7,
                            strokeWeight: 2,
                            fillColor: '#ff0000',
                            fillOpacity: 0.3,
                            map: map,
                            tooltip: res[i].name,
                            type: "p_interes"
                        });
                        google.maps.event.addListener(punto[i], 'mouseover', function(e) {
                            showToolTip(this);
                        });
                        google.maps.event.addListener(punto[i], 'mouseout', function(e) {
                            hideToolTip();
                        });
                    }
                }
            });
        }
    }
}

//function ProjectionHelperOverlay(map) {
////    google.maps.OverlayView.call(this);
//    this.setMap(map);
//  }
//ProjectionHelperOverlay.prototype = new google.maps.OverlayView();
//ProjectionHelperOverlay.prototype.draw = function () {
//    if (!this.ready) {
//      this.ready = true;
//      google.maps.event.trigger(this, 'ready');
//    }
//};

//function showToolTip(e) {
//    ov = new ProjectionHelperOverlay(map);
//    google.maps.event.addListener(ov, 'ready', function() {
//        var pro = ov.getProjection();
//        var centro = e.getCenter();
//        var pto = pro.fromLatLngToDivPixel(centro);
//        console.log(pto);
//        tooltip.html("<p>"+e.title+"</p>");
//        var w = tooltip.width();
//        var x = pto.x - w/2;
//        var h = tooltip.height();
//        var y = pto.y - 4*h;
//        tooltip.css({
//           top: y+"px",
//           left: x+"px"
//        });
//        tooltip.fadeIn();
//    });
//}

function showToolTip(e) {
//    tooltip.stop();
//    console.log(e);
    tooltip.html("<p>"+e.tooltip+"</p>");
    var x = mouse.pageX;
    var y = mouse.pageY;
    tooltip.css({
       top: y+"px",
       left: x+"px"
    });
    tooltip.stop().fadeTo(500,1);
}

function hideToolTip() {
    tooltip.stop().fadeTo(500,0, function() {
        $(this).hide();
    });
}

function creaPuntoInteres(lat, lon) {
    desactivaTodos();
    if(!$btn_pint.hasClass("active")) showPInteres();
    var latlng = new google.maps.LatLng(lat, lon);
    $puntoForm.find("input[name='lat']").val(lat);
    $puntoForm.find("input[name='lon']").val(lon);
    $.ajax({
        url: "?sec=monitoreo&get=direccion&lat="+lat+"&lon="+lon,
        type: 'get',
        beforeSend: function() {

        },
        complete: function(data) {
            var r = $.parseJSON(data.responseText);
            $puntoForm.find("input[name='dir']").val(r.DIRECCION);
            $puntoForm.fadeIn();
            dibujaPunto(latlng);
        }
    });
}

function dibujaPunto(pto) {
    map.panTo(pto);
    if(map.getZoom()<15) {
        map.setZoom(15);
    }
    if(puntoMarker == null) {
        puntoMarker = new google.maps.Marker({
            map: map,
            position: pto,
            title: "Puedes arrastrame",
            draggable: true,
//            icon: pInMarker,
            animation: google.maps.Animation.DROP
        });
        puntoNew = new google.maps.Circle({
            center: pto,
            radius: 50,
            strokeColor: '#ff0000',
            strokeOpacity: 0.7,
            strokeWeight: 2,
            fillColor: '#ff0000',
            fillOpacity: 0.3,
            map: map
        });
        google.maps.event.addListener(puntoMarker, 'dragend', function(e) {
            creaPuntoInteres(e.latLng.lat(), e.latLng.lng());
        });
        google.maps.event.addListener(puntoMarker, 'dragstart', function(e) {
            puntoNew.setMap(null);
        })
    } else {
        puntoMarker.setPosition(pto);
        puntoNew.setCenter(pto);
        puntoNew.setMap(map);
        puntoNew.setRadius($puntoForm.find("input[name='rad']").val()*1);
    }
}

function delNewPunto() {
    if(!$("#cancelar", $puntoForm).hasClass("working")) {
        if(puntoNew!=null) {
            puntoNew.setMap(null);
            puntoNew = null;
        }

        if(puntoMarker!=null) {
            puntoMarker.setMap(null);
            puntoMarker = null;
        }

        $puntoForm.find("input[name='dir']").val("");
        $puntoForm.find("input[name='lat']").val("");
        $puntoForm.find("input[name='lon']").val("");
        $puntoForm.fadeOut();
        $puntoForm.find("#msg").html("");
    }
}

function savePunto(f) {
    var accion;
    if($puntoForm.find("#id").val()=="") {
        accion="add";
    } else accion="mod";
    
    $.ajax({
        url: "?sec=configuracion&ssec=puntointeres&do="+accion,
        type: 'post',
//        dataType: 'json',
        data: {
            nom: $("#nom", $puntoForm).val()
            , dir: $("#dir", $puntoForm).val()
            , lat: $("#lat", $puntoForm).val()
            , lon: $("#lon", $puntoForm).val()
            , accountID: $("#accountID", $puntoForm).val()
            , rad: $("#rad", $puntoForm).val()
            , estado: "1"
            , noSalto: ''
        },
        beforeSend: function() {
            $("#submit", $puntoForm).val("Guardando...").addClass("working");
            $("#cancelar", $puntoForm).addClass("working");
        },
        complete: function(data) {
            var res = $.parseJSON(data.responseText);
            console.log(res);
            $("#submit", $puntoForm).val("Guardar").removeClass("working");
            $("#cancelar", $puntoForm).removeClass("working");
            if(res.error == 0) {
                puntoNew.title = $("#nom", $puntoForm).val();
//                console.log(puntoNew);
                puntoMarker.setMap(null);
                puntoMarker = null;
                var des = setInterval(function() {
                    $puntoForm.fadeOut(700, function() {
                        f.reset();
                        $("#msg", $puntoForm).html("");
                        clearInterval(des);
                        punto[punto.length] = puntoNew;
                        puntoNew.setMap(null);
                        puntoNew = null;
                        punto[punto.length-1].setMap(map);
                    })
                }, 2000);
            }
            $("#msg", $puntoForm).slideUp().html(res.msg).slideDown();
        }
    });
}

function showDevices() {
    $.ajax({
        url: "?sec=monitoreo&get=device",
        type: 'get',
        dataType: 'json',
        beforeSend: function() {

        },
        complete: function(data) {
            res = $.parseJSON(data.responseText);
            var nRes = res.length;
            var i;
            for(i=0; i<device.length; i++) {
                device[i].setMap(null);
            }
            for(i=0; i<nRes; i++) {
                var dev = res[i];
                
                actualizaFila(dev);
                var myLatLng = new google.maps.LatLng(dev.latitude, dev.longitude);
                if(activeIndex == -1 && i==0) {
                    activeIndex = dev.deviceID;
//                    setActive(activeIndex);
                }
//                console.log(dev);
                
                var marker = new google.maps.Marker({
                    position: myLatLng,
                    map: map,
//                    icon: (dev.encendido=="1")?dev_run:dev_stop,
                    icon:getPinVehiculo(dev.vehicleID, dev.heading),
//                    title: dev.displayName,
                    tooltip: dev.displayName,
                    zIndex: i,
                    m_id: i
                });
                device[i] = marker;
                infoDevice[i] = dev;
                indexDevice[i] = dev.deviceID;
                if(activeIndex > 0 && dev.deviceID == activeIndex) {
                    map.panTo(myLatLng);
                    updateActive(dev);
//                    setActive(activeIndex);
                }
            }
            $(device).each(function(i, marker) {
                google.maps.event.addListener(marker, 'click', function(){
                    setActive(indexDevice[marker.m_id]);
                });
                google.maps.event.addListener(marker, 'mouseover', function(){
                    showToolTip(marker);
                });
                google.maps.event.addListener(marker, 'mouseout', function(){
                    hideToolTip();
                });
            });
        }
    });   
}

function actualizaFila(res) {
    $(".dev_"+res.deviceID).each(function() {
        var dev = $(this);
        var img;
        dev.find("#velocidad").html(Math.round(res.speedKPH*1)+" <span class='uni_med'>(Km/h)</span>");
//        if(dev.hasClass("active")) console.log("activo: "+dev);
        dev.find("#fecha").html("<b>Fecha: </b>"+res.fecha);
        if(res.encendido == "1") img = "<img src='img/car_run.png' width='14' alt='Encendido' title='Encendido'/>";
        else img = "<img src='img/car_stop.png' width='14' alt='Apagado' title='Apagado'/>";
        dev.find("#estado").html(img);
    });
}

function setActive(index) {
//    alert(index);
    var nDev = device.length;
    var i=0;
    while(i<nDev && indexDevice[i]!=index) {
        i++;
    }
    delNewPunto();
//    console.log("activeIndex: "+index);
//    console.log("preActive: "+preActive);
    if(index != preActive) {
        map.panTo(device[i].getPosition());
        updateActive(infoDevice[i]);
        activeIndex = index;
        $(".dev_"+index).each(function(i, elem){
            $(elem).addClass("active");
        });
        if(preActive>0) {
            $(".dev_"+preActive).each(function(i, elem){
                $(elem).removeClass("active");
            });
        }
        preActive = activeIndex;
    } else {
        desactivaTodos();
    }
}

function desactivaTodos() {
    if(preActive != -2) {
        console.log("desactiva todos");
        activeIndex = -2;
        act_dev.fadeOut();
        $(".dev_"+preActive).each(function(i, elem){
            $(elem).removeClass("active");
        });
        preActive = activeIndex;
    }
}

function updateActive(res) {
    $.ajax({
        url: "?sec=monitoreo&get=direccion&lat="+res.latitude+"&lon="+res.longitude,
        type: 'get',
        beforeSend: function() {

        },
        complete: function(data) {
            r = $.parseJSON(data.responseText);
            act_dev.find("#titulo").html("<b>Vehiculo: </b>"+res.displayName);
            act_dev.find("#patente").html("<b>Patente: </b>"+res.licensePlate);
            act_dev.find("#velocidad").html("<b>Velocidad: </b>"+Math.round(res.speedKPH*1)+" <span class='uni_med'>(Km/h)</span>");
            act_dev.find("#direccion").html("<b>Direcci&oacute;n: </b>"+r.DIRECCION);
            act_dev.find("#fecha").html("<b>Fecha: </b>"+res.fecha);
            var nSAux = sensor.length;
            for(var j=0; j<nSAux; j++) {
                act_dev.find("#S"+sensor[j].ID_SENSOR).hide();
            }
            if(devSen!=null) {
                var senAux = devSen["S"+res.deviceID];
                if(senAux) {
                    nSenAux = senAux.length;
                    for(var i=0; i<nSenAux; i++) {
                        switch(senAux[i].TIPO_SENSOR) {
                            case "1":
                                var enc = (res[senAux[i].COLUMNA_SENSOR]=="1")?"Encendido":"Apagado";
                                act_dev.find("#S"+senAux[i].ID_SENSOR).html("<b>"+senAux[i].NOM_SENSOR+": </b>"+enc).show();
                                break;
                            case "2":
                                act_dev.find("#S"+senAux[i].ID_SENSOR).html("<b>"+senAux[i].NOM_SENSOR+": </b>"+res[senAux[i].COLUMNA_SENSOR]+" ("+senAux[i].UNIDAD_SENSOR+")").show();
                                break;
                        }
                    }
                }
            }
            act_dev.fadeIn();
            _gaq.push(['_trackPageview', '/Ajax/GeoCode/inversa/'+r.fuente]);
        }
    });
}

function hideLateral(n) {
//    alert("hide");
    var w;
    if(n == 0) {
        w = $lateralLeft.width()+10;
        $lateralLeft.animate({'left':'-'+w+'px'}, 500, function() {
            if($reporte && reporteSel>-1  && !reportes[reporteSel].showMapa) {
                console.log("izq");
                $reporte.css({
                    "margin-left":($lateralLeft.width()+20+$lateralLeft.position().left)+"px",
                    "margin-right":(5-($lateralRight.position().left-$(window).width()))+"px"
                });
            }
        });
        $toggleLeft.html("&gt;<br>&gt;");
        $resize.hide();
    } else {
        w = $lateralRight.width()+10;
        $lateralRight.animate({'right':'-'+w+'px'}, 500, function() {
            if($reporte && reporteSel>-1 && !reportes[reporteSel].showMapa) {
                console.log("der");
                $reporte.css({
                    "margin-left":($lateralLeft.width()+20+$lateralLeft.position().left)+"px",
                    "margin-right":(5-($lateralRight.position().left-$(window).width()))+"px"
                });
            }
        });
        $toggleRight.html("&lt;<br>&lt;");
    }
//    $lateral.hide();
//    $main.css("padding-left", "10px");
}

function showLateral(n) {
    if(n == 0) {
        $lateralLeft.animate({'left':'0px'}, 500, function() {
            if($reporte && reporteSel>=0 && !reportes[reporteSel].showMapa) {
                console.log("modRep");
                $reporte.css({
                    "margin-left":($lateralLeft.width()+20+$lateralLeft.position().left)+"px",
                    "margin-right":(5-($lateralRight.position().left-$(window).width()))+"px"
                });
            } else console.log("NO modRep");
        });
        $toggleLeft.html("&lt;<br>&lt;");
        $resize.show();
    } else {
        $lateralRight.animate({'right':'0px'}, 500, function() {
            if($reporte && reporteSel>=0 && !reportes[reporteSel].showMapa) {
                console.log("modRep");
                $reporte.css({
                    "margin-left":($lateralLeft.width()+20+$lateralLeft.position().left)+"px",
                    "margin-right":(5-($lateralRight.position().left-$(window).width()))+"px"
                });
            } else console.log("NO modRep");
        });
        $toggleRight.html("&gt;<br>&gt;");
    }
}

function hideAllDevice() {
    var n = device.length;
    for(var i=0; i<n; i++) {
        device[i].setVisible(false);
    }
}

function showAllDevice() {
    var n = device.length;
    for(var i=0; i<n; i++) {
        device[i].setVisible(true);
    }
}

function showBuscador(e) {
    desactivaTodos();
    $btn_buscador.addClass("active");
    $buscador.fadeIn();
    if(markerBuscador) {
        markerBuscador.setMap(map);
    }
    e.preventDefault();
}

function hideBuscador(e) {
    $btn_buscador.removeClass("active");
    $buscador.fadeOut();
    if(markerBuscador) {
        markerBuscador.setMap(null);
    }
    e.preventDefault();
}

function getPinVehiculo(idV, gr) {
    var g;
    g = Math.round(gr/10)*10;
    return new google.maps.MarkerImage('img/'+pin_dev[idV]+'_'+g+'.png',
        new google.maps.Size(32, 32),
        new google.maps.Point(0,0),
        new google.maps.Point(16,16)
    );
}

function creaPinVehiculos() {
    $.ajax({
        url: "?sec=monitoreo&get=pin_vehiculo",
        type: 'get',
        beforeSend: function() {
            
        },
        complete: function(data) {
            var r = $.parseJSON(data.responseText);
            var nR = r.length;
            var base;
            for(var i=0; i<nR; i++) {
                base = r[i].vehicleImg.split(".");
                pin_dev[r[i].vehicleID] = base[0];
            }
            cargaSensor();
        }
    });
}

$(window).resize(function() {
    $map_canvas.height($(window).height()-40);
    $logAlarma.height($(window).height()-95);
    $listaDev.height($(window).height()-95);
    if(reporteSel>=0 && reportes[reporteSel].showMapa) {
        $reporte.height($(window).height()-100-$filtros.height());
    }
});

function disableSeleccion() {
    $lateralLeft.disableSelection();
    act_dev.disableSelection();
    $map_canvas.disableSelection();
}

$(document).ready(function(){
    $map_canvas = $("#map_canvas");
    $lateralLeft = $("#left-sidebar");
    $lateralRight = $("#right-sidebar");
    $main = $("#main-content");
    $main.css("padding-left", "0px");
    $toggleLeft = $("#toggle-left");
    $toggleRight = $("#toggle-right");
    $grupos = $($(".grupo")[0]);
    $reporte = $("#reporte");
    $filtros = $("#filtros");
    
    $logAlarma = $lateralRight.find("#scroll");
    $listaDev = $lateralLeft.find("#scroll");
    $puntoForm = $("#puntoForm");
    $btn_pint = $("#btn_pint");
    $buscador = $("#buscaDireccion");
    $btn_buscador = $("#btn_buscar");
    $btn_buscador.toggle(function(e){showBuscador(e)}, function(e){hideBuscador(e)});
    $("label", $buscador).inFieldLabels();
    initialize();
//    showDevices();
//    getAlarma();
    act_dev = $("#active_dev");
    act_dev.appendTo($map_canvas);
    act_dev.draggable({
        opacity: 0.8,
        snapMode: 'outer'
    });
    
    disableSeleccion();

    tooltip = $("#tooltip");
    tooltip.appendTo($map_canvas);
    
    
    $map_canvas.height($(window).height()-40);
    $logAlarma.height($(window).height()-95);
    $listaDev.height($(window).height()-95);
    
    $toggleLeft.toggle(function() {hideLateral(0)}, function() {showLateral(0)});
    $toggleRight.toggle(function() {hideLateral(1)}, function() {showLateral(1)});
    $grupos.bind("click", function(e) {
        var grupo = $(e.target).parent();
        var hijoId;
        if(grupo.parent().hasClass("grupo")) {
//            console.log("grupo");
            hijoId = 1;
//            grupo.find("ul.device").slideUp();
            var hijo = $(grupo.children()[hijoId]);
            var estado = $(grupo.children()[0]).children().children();
//            console.log(estado);
            if(hijo.hasClass("oculto")) {
                grupo.addClass("active");
                estado.attr("src", "img/collapse.png");
                hijo.removeClass("oculto");
                hijo.slideDown();
            } else {
                grupo.removeClass("active");
                estado.attr("src", "img/expand.png");
//                $(grupo.children()[0]).html("[ + ]");
                hijo.addClass("oculto");
                hijo.slideUp();
            }
//        }
        }
    });

    buscadorValidator = $buscador.bind("invalid-form.validate",
        function() {
//            $("#msg", $puntoForm).slideUp().html("Debes completar todos los campos").slideDown();
        }).validate({
        errorPlacement: function(error, element) {
        },
        submitHandler: function(form) {
            var address = form.s.value;
            if (geocoder) {
                geocoder.geocode( { 'address': address}, 
                function(results, status) {
                    if (status == google.maps.GeocoderStatus.OK) {
                        map.setCenter(results[0].geometry.location);
                        map.setZoom(16);
                        if(markerBuscador) {
                            markerBuscador.setPosition(results[0].geometry.location);
                        } else {
                            markerBuscador = new google.maps.Marker({
                                map: map, 
                                position: results[0].geometry.location
                            });
                        }
                    } else {
//                        alert("Geocode was not successful for the following reason: " + status);
                    }
                });
            }
            _gaq.push(['_trackPageview', '/Ajax/GeoCode/normal/GoogleMapsApi']);
        },
        success: function(label) {
        }
    });
    
    puntoFormValidator = $puntoForm.bind("invalid-form.validate",
        function() {
            $("#msg", $puntoForm).slideUp().html("Debes completar todos los campos").slideDown();
        }).validate({
        errorPlacement: function(error, element) {
        },
        submitHandler: function(form) {
            if(puntoNew!=null) {
                console.log("ok");
                if(!$("#submit", $puntoForm).hasClass("working")) {
                    savePunto(form);
                }
            }
        },
        success: function(label) {
        }
    });
    $("#rad", $puntoForm).change(function() {
        if(puntoNew!=null)
            puntoNew.setRadius($(this).val()*1);
    });
//    $(".logo").qtip({
//        content: "logo",
//        style: "dark"
//    });
    $(document).mousemove(function(e){mouse = e});
    setSec();
});
