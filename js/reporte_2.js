var reporteMk = [];
//var $reporte;
var $filtros;
var mgMk;
var clMk;
var $dataReporte;
var poligono;

function cargaPol(pol, tPol) {
    ptos = Array();
    if(pol.length>0) {
        for(var i=0; i<pol.length; i++) {
            ptos[i] = new google.maps.LatLng(pol[i].LAT_PUNTO, pol[i].LON_PUNTO);
        }
        switch(tPol) {
            case '1':
                poligono = new google.maps.Polyline({
                    map: map,
                    strokeColor : '#ff0000',
                    strokeOpacity : 0.7,
                    strokeWeight : 4,
                    path : ptos
                });
                break;
            case '2':
                ptos[pol.length] = new google.maps.LatLng(pol[0].LAT_PUNTO, pol[0].LON_PUNTO);
                poligono = new google.maps.Polygon({
                    map: map,
                    path: ptos,
                    strokeColor: '#ff0000',
                    strokeWeight: 2,
                    strokeOpacity: 0.7,
                    fillColor: '#ff0000',
                    fillOpacity: 0.2
                });
                break;
        }
        var bounds = new google.maps.LatLngBounds();
        for (i=0;i<ptos.length;i++) {
            bounds.extend(ptos[i]);
        }
        map.fitBounds(bounds);
    }
}

function getDevices(sel, idDest){
    var idGr = sel[sel.selectedIndex].value;
    $.ajax({
        url: "?sec=reporte&get=devByGrupo&id_grupo="+idGr,
        type: 'get',
        dataType: 'json',
        beforeSend: function() {
        },
        complete: function(data) {
            var res = $.parseJSON(data.responseText);
            var nRes = res.length;
            var i;
            if(reporteSel!=0)
                var op = "<OPTION VALUE='0'>Todos</OPTION>";
            for(i=0; i<nRes; i++) {
                op += "<OPTION VALUE='"+res[i].deviceID+"'>" + res[i].displayName + "</OPTION>";
            }
            $("#"+idDest).html(op);
        }
    });
}

function reset() {
    if(clMk) clMk.clearMarkers();
    if(mgMk) mgMk.clearMarkers();
    if(poligono) poligono.setMap(null);
    if($reporte) $reporte.html("");
}

function showReporteJSON(reporte) {
//    $filtros.slideUp();
    switch(reporteSel) {
        case 0:
            $dataReporte = $("<table id='dataReporte' border='0' cellspacing='0' cellpadding='0' width='100%' class='tablarojo'><thead><tr><th>Fecha</th><th>Distancia</th><th>Velocidad</th></tr></thead></table>");
            break;
        case 1:
            $dataReporte = $("<table id='dataReporte' border='0' cellspacing='0' cellpadding='0' width='100%' class='tablarojo'><thead><tr><th>Fecha</th><th>Veh&iacute;culo</th><th>Patente</th><th>Alarma</th><th>Regla</th></tr></thead></table>");
            break;
        case 2:
            break;
        case 3:
            $dataReporte = $("<table id='dataReporte' border='0' cellspacing='0' cellpadding='0' width='100%' class='tablarojo'><thead><tr><th>Fecha</th><th>Veh&iacute;culo</th><th>Patente</th><th>Velocidad</th></tr></thead></table>");
            break;
    }
    $reporte.html($dataReporte);
    var n = reporte.length;
    var dev;
    var ptos = [];
    reporteMk = [];
    for(var i=0; i<n; i++) {
        dev = reporte[i];
        dev.i = i;
        var myLatLng = new google.maps.LatLng(dev.latitude, dev.longitude);
        ptos.push(myLatLng);
        var tp = "";
        switch(reporteSel) {
            case 0:
                tp = "<b>Veh&iacute;culo: </b>"+dev.displayName+"<br /><b>Patente: </b>"+dev.licensePlate+"<br /><b>Fecha: </b>"+dev.fecha+"<br /><b>Velocidad: </b>"+dev.velocidad+" (km/h)<br /><b>Recorrido: </b>"+dev.distancia+" (km)";
                break;
            case 1:
                tp = "<b>Veh&iacute;culo: </b>"+dev.displayName+"<br /><b>Patente: </b>"+dev.licensePlate+"<br /><b>Fecha: </b>"+dev.fecha+"<br /><b>Velocidad: </b>"+dev.velocidad+" (km/h)<br /><b>Alarma: </b>"+dev.alarma+"<br /><b>Regla: </b>"+dev.regla;
                break;
            case 2:
                break;
            case 3:
                tp = "<b>Veh&iacute;culo: </b>"+dev.displayName+"<br /><b>Patente: </b>"+dev.licensePlate+"<br /><b>Fecha: </b>"+dev.fecha+"<br /><b>Velocidad: </b>"+dev.velocidad+" (km/h)";
                break;
        }
        var marker = new google.maps.Marker({
            position: myLatLng,
            icon: (dev.encendido=="1")?dev_run:dev_stop,
            tooltip: tp,
            zIndex: i,
            m_id: i
        });
        $("#fila_"+reporteSel).tmpl(dev).appendTo($dataReporte);
        reporteMk.push(marker);
    }
    if(reporteSel == 0) {
        poligono = new google.maps.Polyline({
            map: map,
            strokeColor : '#333333',
            strokeOpacity : 0.5,
            strokeWeight : 5,
            path : ptos
        });
        mgMk = new MarkerManager(map);
        google.maps.event.addListener(mgMk, 'loaded', function(){
            mgMk.addMarkers(reporteMk, 3);
            mgMk.refresh();
        });
    } else {
        var mcOptions = {gridSize: 40, maxZoom: 15};
        clMk = new MarkerClusterer(map, reporteMk, mcOptions);
    }
    var bounds = new google.maps.LatLngBounds();
    for (i=0;i<ptos.length;i++) {
        bounds.extend(ptos[i]);
    }
    map.fitBounds(bounds);
    $(reporteMk).each(function(i, marker) {
        google.maps.event.addListener(marker, 'click', function(){
            map.panTo(marker.position);
//            setActive(indexDevice[marker.m_id]);
//            console.log(marker);
        });
        google.maps.event.addListener(marker, 'mouseover', function(){
            showToolTip(marker);
        });
        google.maps.event.addListener(marker, 'mouseout', function(){
            hideToolTip();
        });
    });
    $("tr").live("click mouseover mouseout", function(e){
        switch(e.type) {
            case "click":
                console.log($(this)[0].id);
                map.panTo(reporteMk[$(this)[0].id].position);
                break;
            case "mouseover":
                $(this).toggleClass("over");
                break;
            case "mouseout":
                $(this).toggleClass("over");
                break;
        }
        return false;
    });
}

function showReporteTXT(txt) {
    $reporte.html(txt);
    $reporte.css({
        "margin-left":$lateralLeft.width()+20+"px",
        "margin-right":(5-($lateralRight.position().left-$(window).width()))+"px"
    });
}

$(document).ready(function() {
    reset();
    $reporte = $("#reporte");
    $filtros = $("#filtros");
    if(reporteSel!=2)
        $reporte.height($(window).height()-100-$filtros.height());
    console.log($filtros.height());
    console.log($reporte.height());
    fecha = new Date();
    $form = $("#formu");
    $("#fecha_ini").datepicker({
        maxDate: fecha,
        'dateFormat': 'yy-mm-dd'
    });
    $("#fecha_fin").datepicker({
        maxDate: fecha,
        'dateFormat': 'yy-mm-dd'
    });
    var validator = $form.bind("invalid-form.validate",
        function() {
//            $(".notification").html("<div>Debe completar todos lo campos requeridos</div>");
//            $(".notification").attr("class", "notification error png_bg");
        }).validate({
        errorPlacement: function(error, element) {
        },
        submitHandler: function(form) {
            if(!$("#submit").hasClass("working") && $("#Grupo option:selected", $form).val()!='0') {
                $.ajax({
                    url: form.action,
                    type: 'post',
                    context: form,
                    data: {
                        id_grupo: $("#Grupo option:selected", $form).val()
                        , id_device: $("#Vehiculo option:selected", $form).val()
                        , fecha_ini: $("#fecha_ini", $form).val()
                        , hrs_ini: $("#hrs_ini", $form).val()
                        , min_ini: $("#min_ini", $form).val()
                        , fecha_fin: $("#fecha_fin", $form).val()
                        , hrs_fin: $("#hrs_fin", $form).val()
                        , min_fin: $("#min_fin", $form).val()
                        , vel: $("#vel", $form).val()
                        , operador: $("#Operador", $form).val()
                    },
                    beforeSend: function() {
                        $("#submit").addClass("working");
                        if(reporteSel != 2) {
                            $reporte.html("<p align='center'>Generando reporte...</p>");
                        } else {
                            $reporte.appendTo($main);
                            $reporte.html("<h2>Reporte</h2><p align='center'>Generando reporte...</p>");
                        }
                    },
                    complete: function(data) {
                        $("#submit").removeClass("working");
                        reset();
                        if(reporteSel !=2) {
                            var reporte = $.parseJSON(data.responseText);
                            showReporteJSON(reporte);
                        } else {
                            showReporteTXT(data.responseText);
                        }
                    }
                });
            }
            return false;
        },
        success: function(label) {
        }
    });
});