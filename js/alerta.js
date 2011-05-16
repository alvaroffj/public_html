var valAnt;

function desactivaMenor(sel) {
    var hora = sel[sel.selectedIndex].value;
    var name = sel.name.split("_");
    var dest = name[0]+"_fin_"+name[2];
    console.log(dest);
    var op = $("#"+dest).children();
    var n = op.length;
    for(var i=0; i<n; i++) {
        op[i].disabled = false;
    }
    for(var i=0; i<hora; i++) {
        op[i].disabled = true;
    }
}

function setNext(sel) {
    var idSel = sel[sel.selectedIndex].value;
    var ope, par;
    switch(sel.id) {
        case 'Tipo':
            switch(idSel) {
                case "1":
                    par = Array(Array("2", "Tiempo"));
                    ope = Array(Array("1", "Mayor a"), Array("2", "Menor a"));
                    setSelect("Parametro", par);
                    setSelect("Operador", ope);
                    setValor($("#Parametro")[0]);
                    break;
                case "2":
                    par = Array(Array("1", "Velocidad"));
                    ope = Array(Array("1", "Mayor a"), Array("2", "Menor a"));
                    setSelect("Parametro", par);
                    setSelect("Operador", ope);
                    setValor($("#Parametro")[0]);
                    break;
                case "3":
                    par = Array(Array("3", "Geozona"), Array("4", "Geofrontera"), Array("5", "Punto de interes"));
                    ope = Array(Array("4", "Entra a"), Array("5", "Sale de"));
                    setSelect("Parametro", par);
                    setSelect("Operador", ope);
                    setValor($("#Parametro")[0]);
                    break;
            }
            break;
        case "Parametro":
            switch(idSel) {
                case "3":
                    ope = Array(Array("4", "Entra a"), Array("5", "Sale de"));
                    setSelect("Operador", ope);
                    break;
                case "4":
                    ope = Array(Array("6", "Cruza"));
                    setSelect("Operador", ope);
                    break;
                case "5":
                    ope = Array(Array("4", "Entra a"), Array("5", "Sale de"));
                    setSelect("Operador", ope);
                    break;
            }
            break;
    }
}

function setSelect(idSel, par) {
    var n = par.length;
    var op;
    for(var i=0; i<n; i++) {
        if(i==0)
            op += "<OPTION VALUE='"+par[i][0]+"' SELECTED>"+par[i][1]+"</OPTION>";
        else
            op += "<OPTION VALUE='"+par[i][0]+"'>"+par[i][1]+"</OPTION>";
    }

    $("#"+idSel).html(op);
}

function setValor(sel) {
    var valAct = sel[sel.selectedIndex].value;
    $("#val_"+valAnt).attr("style", "display:none;");
    $("#val_"+valAct).attr("style", "display:inline;");
    valAnt = valAct;
    setNext(sel);
}

function getDevices(sel, idDest){
    var idGr = sel[sel.selectedIndex].value;
    $.ajax({
        url: "?sec=configuracion&ssec=alarma&get=devByGrupo&id_grupo="+idGr,
        type: 'get',
        dataType: 'json',
        beforeSend: function() {

        },
        complete: function(data) {
            var res = $.parseJSON(data.responseText);
            var nRes = res.length;
            var i;
            var op = "<OPTION VALUE='0'>Todos</OPTION>";
            for(i=0; i<nRes; i++) {
                op += "<OPTION VALUE='"+res[i].deviceID+"'>" + res[i].displayName + "</OPTION>";
            }
            $("#"+idDest).html(op);
        }
    });
}

$(document).ready(function() {
    valAnt = 1;
});