
Reveal.initialize();

function changeClient(sel) {
    Reveal.slide(sel.value, 0);
}

$('#selector').on('change', function () {
    Reveal.slide(this.value, 0);
} );

Reveal.on('slidechanged', (event) => {
    $('#selector option[value="' + event.indexh + '"]').prop('selected', true);
});


$( document ).ready(function() {
    let getDir = "";
    if($('#unique').val()) {
        getDir = "&unique="+$('#unique').val();
    }
    else {
        getDir = "&plate="+$('#plate').val()+"&year="+$('#year').val()+"&month="+$('#month').val()+"&version="+$('#version').val()+"&run="+$('#run').val();
    }
    let click = false;
    $('.pdf').on('click', function () {
        click = true;
        window.location.href = "controller/download.php?type=ticketpdf&nom="+$(this).text()+getDir;
    } );

    $('.csv').on('click', function () {
        click = true;
        window.location.href = "controller/download.php?type=ticketcsv&nom="+$(this).text()+getDir;
    } );

    function deleteDir() {
        if($('#unique').val() && !click) {
            window.location.href = "controller/deleteTicket.php?unique="+$('#unique').val(); // firefox
            $.post("controller/deleteTicket.php", {unique: $('#unique').val()}, function () { // chromium
                window.location.href = "index.php";
            });
        }
        else {
            click = false;
        }
    }

    $(window).on("unload", deleteDir); // firefox
    $(window).on('beforeunload', deleteDir); // chromium

});
