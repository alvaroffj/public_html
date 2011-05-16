function addUserGrupo() {
    var grupoID = $("#grupo option:selected").val();
    var userID = $("#userID").val();
    if(grupoID>0) {
        $.ajax({
            url: "?sec=configuracion&ssec=usuario&do=add_grupo",
            type: 'post',
            dataType: 'json',
            data: {
                id_grupo: grupoID
                , id_usuario: userID
            },
            beforeSend: function() {

            },
            complete: function(data) {
                var res = $.parseJSON(data.responseText);
                if(res.error == 0) {
                    var fila = "<div class=\"elemento medium-input\" id=\""+res.idUsGr+"\"><a href=\"?sec=vehiculo&op=mod_grupo&id="+res.groupID+"\">"+res.displayName+"</a><a onClick=\"delUserGrupo("+res.idUsGr+"); return false;\" style=\"cursor:pointer; position: absolute; right: 0;\"><img src=\"img/delete.png\" border=\"0\" title=\"Quitar grupo\" alt=\"Quitar grupo\"/></a></div>";
                    $(fila).hide().appendTo("#grupos").fadeIn();
                    $(".notification").html("<div>El grupo fue agregado correctamente</div>");
                    $(".notification").attr("class", "notification success png_bg");
                } else {
                    $(".notification").html("<div>El grupo NO pudo ser agregado, intentelo de nuevo</div>");
                    $(".notification").attr("class", "notification error png_bg");
                }
            }
        });
    }
}

function delUserGrupo(id) {
    $.ajax({
            url: "?sec=configuracion&ssec=usuario&do=del_grupo",
            type: 'post',
            dataType: 'json',
            data: {
                idUsGr: id
            },
            beforeSend: function() {

            },
            complete: function(data) {
                var res = $.parseJSON(data.responseText);
                if(res.error == 0) {
                    $("#"+id, "#grupos").fadeOut();
                } else {
                    $(".notification").html("<div>El grupo NO pudo ser quitado, intentelo de nuevo</div>");
                    $(".notification").attr("class", "notification error png_bg");
                }
            }
        });
}

$(document).ready(function(){
    var validator = $("#formu").bind("invalid-form.validate",
        function() {
            $(".notification").html("<div>Debe completar todos lo campos requeridos</div>");
            $(".notification").attr("class", "notification error png_bg");
        }).validate({
        errorPlacement: function(error, element) {
        },
        submitHandler: function(form) {
            form.submit();
        },
        success: function(label) {
        }
    });
});