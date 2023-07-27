
$('#historique').on('click', function () {
    alert("nécessite la mise en place du log");
} );

$('#launch').on('click', function () {
    alert("launch");
} );

$('#export').on('click', function () {
    alert("export");
} );

$('.run').on('click', function () {
    window.location.href = "prefacturation.php?"+$(this).val();
} );