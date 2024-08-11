
$('#open-historique').on('click', function () {
    $('#plate-content').hide();
    $('#buttons').hide();
    $('#historique-div').show();
    $.post("controller/getLogfile.php", {plate: $('#plate').val()}, function (data) {
        $('#display').html(data);
    });
} );

$('#close-historique').on('click', function () {
    $('#plate-content').show();
    $('#buttons').show();
    $('#historique-div').hide();
    $('#display').html("");
} );

$('#download-prefa').on('click', function () {
    window.location.href = "controller/download.php?type=prefa";
} );

$('.open-run').on('click', function () {
    window.location.href = "run.php?plateforme="+$('#plate').val()+"&"+$(this).val();
} );

$('#destroy').on('click', function () {
    window.location.href = "controller/destroy.php?plate="+$('#plate').val();
} );

$(document).on("change", ".zip-file", function () {
    const id = $(this).attr('id');
    $('#type').val(id);
    const file = $(this).val();
    if(file.indexOf('.zip') > -1) {
        $('#form-fact').submit();
        $('#message').html('<div>Veuillez patienter, cela peut prendre plusieurs minutes...</div><div class="loader"></div>');
        $(".lockable").prop('disabled', true);
    }
    else {
        $('#message').html('<div class="alert alert-danger alert-dismissible fade show" role="alert">'+
                                'Vous devez uploader une archive zip !'+
                                '<button type="button" class="close" data-dismiss="alert" aria-label="Close">'+
                                    '<span aria-hidden="true">&times;</span>'+
                                '</button>'+
                            '</div>');
    }
});
