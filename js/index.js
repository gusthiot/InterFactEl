import * as customTableur from "./custom-tableur.js";

const paramtext = JSON.parse($('#paramtext').val());
const messages = JSON.parse($('#messages').val());
const configs = JSON.parse($('#config').val());
const contents = JSON.parse($('#contents').val());

const tableur = new customTableur.CustomTableur(messages, configs, paramtext, closeTable);

function zipError() {
    $('#message').html('<div class="alert alert-danger alert-dismissible fade show" role="alert">'+
                            'Vous devez uploader une archive zip !'+
                            '<button type="button" class="close" data-dismiss="alert" aria-label="Close">'+
                                '<span aria-hidden="true">&times;</span>'+
                            '</button>'+
                        '</div>');
}

$('#download-generated').on('click', function () {
    window.location.href = "controller/download.php?type=generated";
});

$('.download-config').on('click', function () {
    window.location.href = "controller/download.php?type=config";
});

$('#zip-config').on('change', function () {
    const file = $(this).val();
    if(file.indexOf('.zip') > -1) {
        $('#form-config').submit();
        $('#message').text('');
    }
    else {
    }
});

$('.facturation').on('click', function () {
    window.location.href = "facturation.php?plateforme="+$(this).find('#plate-fact').val();
});

$('.tarifs').on('click', function () {
    window.location.href = "tarifs.php?plateforme="+$(this).find('#plate-tarifs').val();
});

$('.reporting').on('click', function () {
    window.location.href = "reporting.php?plateforme="+$(this).find('#plate-report').val();
});

$(document).on("change", ".zip-simu", function () {
    const file = $(this).val();
    if(file.indexOf('.zip') > -1) {
        $(this).closest("form").submit();
        $('#message').html('<div>Veuillez patienter, cela peut prendre plusieurs minutes...</div><div class="loader"></div>');
        $(".lockable").prop('disabled', true);
    }
    else {
        zipError();
    }
});

$('#zip-view').on('change', function () {
    const file = $(this).val();
    if(file.indexOf('.zip') > -1) {
        $('#form-view').submit();
        $('#message').text('');
    }
    else {
        zipError();
    }
});

$('#modal-save').on('click', function () {
    let content = [];
    const num = $('#msg-num').val();
    for(let i=0;i<num;i++) {
        if(!$('#del-'+i).is(':checked')) {
            let display = 0;
            if($('#dis-'+i).is(':checked')) {
                display = 1;
            }
            content.push([display, $('#msg-'+i).val()]);
        }
    }
    if($('#msg-new').val() != "") {
        content.push([1, $('#msg-new').val()]);
    }
    $.post("controller/saveMessages.php", {content: content}, function () {
        window.location.href = "index.php";
    });
});

$('.manage-files').on('click', function () {
    if($('#supervision-files').css("display") == "flex") {
        $('#supervision-files').css("display", "none");
    }
    else {
        $('#supervision-files').css("display", "flex");
    }
});

function closeTable() {
    $('#index-canevas').css("display", "block");
    $('#supervision-manage').html("");
}

$(document).on("click", ".csv", function() {
    $('#index-canevas').css("display", "none");
    const filename = $(this).attr('id');
    tableur.init(filename, "csv");
    $('#supervision-manage').html(tableur.header());
    //tableur.init(filename, "csv", contents);
    //$('#supervision-manage').html(tableur.unidimTableur());
});
