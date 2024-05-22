
$('#historique').on('click', function () {
    $('#arbo').hide();
    $('#buttons').hide();
    $('#histo').show();
    $.post("controller/getLogfile.php", {plate: $('#plate').val()}, function (data) {
        $('#display').html(data);
    });
} );

$('#close-histo').on('click', function () {
    $('#arbo').show();
    $('#buttons').show();
    $('#histo').hide();
    $('#display').html("");
} );

$('#dl_prefa').on('click', function () {
    window.location.href = "controller/download.php?type=prefa";
} );

$('.run').on('click', function () {
    window.location.href = "prefacturation.php?plateforme="+$('#plate').val()+"&"+$(this).val();
} );

$('#destroy').on('click', function () {
    window.location.href = "controller/destroy.php?plate="+$('#plate').val();
} );

$('.erase').on('click', function () {
    window.location.href = "controller/erase.php?plate="+$('#plate').val()+"&"+$(this).val();
} );

$(document).on("change", ".zip_file", function () {
    const id = $(this).attr('id');
    $('#type').val(id);
    const file = $(this).val();
    if(file.indexOf('.zip') > -1) {
        $('#factform').submit();
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

$('#tarifs').on('click', function () {
    window.location.href = "tarifs.php?plateforme="+$('#plate').val();
} );
