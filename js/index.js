
$('#download').on('click', function () {
    window.location.href = "controller/download.php?type=config";
} );

$('#zip_file').on('change', function () {
    const file = $('#zip_file').val();
    if(file.indexOf('.zip') > -1) {
        $('#upform').submit();
        $('#message').text('');
    }
    else {
        $('#message').text('Vous devez uploader une archive zip !');
    }
} );

$('.facturation').on('click', function () {
    window.location.href = "plateforme.php?plateforme="+$(this).find('#plateNum').val();
} );

$('.tarifs').on('click', function () {
    window.location.href = "tarifs.php?plateforme="+$(this).find('#plateNum').val();
} );