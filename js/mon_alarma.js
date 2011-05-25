var $alarma;

function getAlarma(flag, hrs) {
    var urlAl;
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
            var nueva = new Array();
            if(!flag) $alarma.html("");
            if(n>0 && flag) $.titleBlink("Alarma activada!",{repeat: 10,delay: 400});
            for(i=n-1; i>=0; i--) {
                aux = al[i];
                nueva[i] = $("<li><b>"+aux.displayName+"</b> ("+aux.fecha+")<br /><span id='txt'>"+aux.txt+"</span></li>");
                if(flag) {
                    $.jGrowl("<b>"+aux.displayName+"</b> ("+aux.fecha+")<br /><span id='txt'>"+aux.txt+"</span>", {
                        header: 'Alarma: ',
                        life: 5000,
                        beforeClose: function(e,m) {
                            var msg = $("<li>"+m+"</li>");
                            if($alarma.length > 0) {
                                msg.prependTo($alarma).hide().slideDown().fadeIn();
                            }
                        }
                    });
                } else {
                    if($alarma.length > 0) {
                        nueva[i].prependTo($alarma).hide().slideDown().fadeIn();
                    } else {
                        $.jGrowl("<b>"+aux.displayName+"</b> ("+aux.fecha+")<br /><span id='txt'>"+aux.txt+"</span>", {header: 'Alarma: '});
                    }
                }
            }
        }
    });
}

$(document).ready(function(){
    $alarma = $("#alarma");
    if($alarma.length > 0) getAlarma(false, 1);
    else getAlarma(true, 0);
    setInterval("getAlarma(true, 0)", 30000);
});