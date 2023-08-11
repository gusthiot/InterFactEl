
$('#download').on('click', function () {
    window.location.href = "controller/download.php?type=config";
} );

$('#upload').on('click', function () {
    const file = $('#zip_file').val();
    if(file.indexOf('.zip') > -1) {
        $('#upform').submit();
        $('#message').text('');
    }
    else {
        $('#message').text('Vous devez uploader une archive zip !');
    }
} );

$('.plateforme').on('click', function () {
    window.location.href = "plateforme.php?plateforme="+$(this).val();
} );