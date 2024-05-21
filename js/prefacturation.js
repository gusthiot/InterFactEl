const getDir = "plate="+$('#plate').val()+"&year="+$('#year').val()+"&month="+$('#month').val()+"&version="+$('#version').val()+"&run="+$('#run').val();
const postDir = {plate: $('#plate').val(), year: $('#year').val(), month: $('#month').val(), version: $('#version').val(), run: $('#run').val()};

$('#label').on('click', function () {
    $.post("controller/getLabel.php", postDir, function (data) {
        $('#content').html(data);
    });
} );

$('#dl_prefa').on('click', function () {
    window.location.href = "controller/download.php?type=prefa";
} );

$('#info').on('click', function () {  
    $.post("controller/getInfos.php", postDir, function (data) {
        $('#content').html(data);
    });
} );

$('#bills').on('click', function () {
    $.post("controller/displaySap.php", postDir, function (data) {
        $('#content').html(data);
    });
} );

$(document).on("click", "#getSap", function() {
    window.location.href = "controller/download.php?type=sap&"+getDir;
} );

$(document).on("click", "#saveLabel", function() {
    const txt = $('#labelArea').val();
    $.post("controller/saveLabel.php", Object.assign({}, postDir, {txt: txt}), function () {
        window.location.href = "plateforme.php?plateforme="+$('#plate').val();
    });
} );

$('#ticket').on('click', function () {
    window.open("ticket.php?"+getDir);
} );

$('#changes').on('click', function () {
    $.post("controller/getModifs.php", postDir, function (data) {
        $('#content').html(data);
    });
} );

$('#invalidate').on('click', function () {
    $.post("controller/invalidate.php", postDir, function () {
        window.location.href = "plateforme.php?plateforme="+$('#plate').val();
    });
} );

$('#bilans').on('click', function () {
    window.location.href = "controller/download.php?type=bilans&"+getDir;
} );

$('#annexes').on('click', function () {
    window.location.href = "controller/download.php?type=annexes&"+getDir;
} );

$('#all').on('click', function () {
    window.location.href = "controller/download.php?type=all&"+getDir;
} );

$('#send').on('click', function () {
    $.post("controller/selectBills.php", Object.assign({}, postDir, {type: "sendBills"}), function (data) {
        $('#content').html(data);
    });
} );

$(document).on("click", "#getModif", function() {
    window.location.href = "controller/download.php?type=modif&"+getDir+"&pre=Modif-factures";
} );

$(document).on("click", "#getJournal", function() {
    window.location.href = "controller/download.php?type=modif&"+getDir+"&pre=Journal-corrections";
} );

$(document).on("click", "#getClient", function() {
    window.location.href = "controller/download.php?type=modif&"+getDir+"&pre=Clients-modifs";
} );

$(document).on("click", "#sendBills", function() {
    sending("Envoi dans SAP");
} );

$(document).on("click", "#resendBills", function() {
    sending("Renvoi dans SAP");
} );

function sending(type) {
    let bills = [];
    $.each($("input[name='bills']:checked"), function(){
        bills.push($(this).val());
    });
    $(".lockable").prop('disabled', true);
    $('#message').html('<div>Veuillez patienter, cela peut prendre plusieurs minutes...</div><div class="loader"></div>');
    $.post("controller/sendBills.php", Object.assign({}, postDir, {bills: bills, type: type}), function () {
        window.location.href = "plateforme.php?plateforme="+$('#plate').val();
    });
}

let all = false;
$(document).on("click", "#allBills", function() {
    if(all) {
        $('#allBills').text("Tout sélectionner");
        $.each($("input[name='bills']"), function(){
            $(this).prop('checked', false);
        });
        all = false;
    }
    else {
        $('#allBills').text("Tout désélectionner");
        $.each($("input[name='bills']"), function(){
            $(this).prop('checked', true);
        });
        all = true;
    }
});

$('#finalize').on('click', function () {
    $.post("controller/finalize.php", postDir, function () {
        window.location.href = "plateforme.php?plateforme="+$('#plate').val();
    });
} );

$('#resend').on('click', function () {
    if (confirm($(this).data('msg')) == true) {
        $.post("controller/selectBills.php", Object.assign({}, postDir, {type: "resendBills"}), function (data) {
            $('#content').html(data);
        });
        
    } 
} );
