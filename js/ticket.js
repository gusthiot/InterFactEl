const getDir = "&plate="+$('#plate').val()+"&year="+$('#year').val()+"&month="+$('#month').val()+"&version="+$('#version').val()+"&run="+$('#run').val();

$('.pdf').on('click', function () {
    window.location.href = "controller/download.php?type=ticketpdf&nom="+$(this).text()+getDir;
} );

$('.csv').on('click', function () {
    window.location.href = "controller/download.php?type=ticketcsv&nom="+$(this).text()+getDir;
} );
