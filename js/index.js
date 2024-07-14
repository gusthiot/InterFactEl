
$('#download-prefa').on('click', function () {
    window.location.href = "controller/download.php?type=prefa";
} );

$('#download-config').on('click', function () {
    window.location.href = "controller/download.php?type=config";
} );

$('#zip-config').on('change', function () {
    const file = $(this).val();
    if(file.indexOf('.zip') > -1) {
        $('#form-config').submit();
        $('#message').text('');
    }
    else {
        $('#message').html('<div class="alert alert-danger alert-dismissible fade show" role="alert">'+
                                'Vous devez uploader une archive zip !'+
                                '<button type="button" class="close" data-dismiss="alert" aria-label="Close">'+
                                    '<span aria-hidden="true">&times;</span>'+
                                '</button>'+
                            '</div>');
    }
} );

$('.facturation').on('click', function () {
    window.location.href = "plateforme.php?plateforme="+$(this).find('#plate-fact').val();
} );

$('.tarifs').on('click', function () {
    window.location.href = "tarifs.php?plateforme="+$(this).find('#plate-tarifs').val();
} );

$(document).on("change", ".zip-simu", function () {
    const file = $(this).val();
    if(file.indexOf('.zip') > -1) {
        $(this).closest("form").submit();
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
