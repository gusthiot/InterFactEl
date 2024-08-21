const getDir = "plate="+$('#plate').val()+"&year="+$('#year').val()+"&month="+$('#month').val()+"&version="+$('#version').val()+"&run="+$('#run').val();
const postDir = {plate: $('#plate').val(), year: $('#year').val(), month: $('#month').val(), version: $('#version').val(), run: $('#run').val()};

$('#download-prefa').on('click', function () {
    window.location.href = "controller/download.php?type=prefa";
} );

$('#open-label').on('click', function () {
    $.post("controller/getLabel.php", Object.assign({}, postDir, {right: "facturation"}), function (data) {
        $('#prefa-content').html(data);
    });
} );

$(document).on("click", "#save-label", function() {
    const txt = $('#label-area').val();
    $.post("controller/saveLabel.php", Object.assign({}, postDir, {txt: txt, right: "facturation"}), function () {
        window.location.href = "facturation.php?plateforme="+$('#plate').val();
    });
} );

$('#open-info').on('click', function () {  
    $.post("controller/getInfos.php", postDir, function (data) {
        $('#prefa-content').html(data);
    });
} );

$('#open-bills').on('click', function () {
    $.post("controller/displaySap.php", postDir, function (data) {
        $('#prefa-content').html(data);
    });
} );

$('#open-report').on('click', function () {
    $.post("controller/displayReport.php", postDir, function (data) {
        $('#prefa-content').html(data);
    });
} );

$(document).on("click", "#get-sap", function() {
    window.location.href = "controller/download.php?type=sap&"+getDir;
} );

$('#open-ticket').on('click', function () {
    window.open("ticket.php?"+getDir);
} );

$('#open-changes').on('click', function () {
    $.post("controller/getModifs.php", postDir, function (data) {
        $('#prefa-content').html(data);
    });
} );

$(document).on("click", "#get-modif", function() {
    window.location.href = "controller/download.php?type=modif&"+getDir+"&pre=Modif-factures";
} );

$(document).on("click", "#get-journal", function() {
    window.location.href = "controller/download.php?type=modif&"+getDir+"&pre=Journal-corrections";
} );

$(document).on("click", "#get-client", function() {
    window.location.href = "controller/download.php?type=modif&"+getDir+"&pre=Clients-modifs";
} );

$('#invalidate').on('click', function () {
    $.post("controller/invalidate.php", postDir, function () {
        window.location.href = "facturation.php?plateforme="+$('#plate').val();
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
    $.post("controller/selectBills.php", Object.assign({}, postDir, {type: "send-bills"}), function (data) {
        $('#prefa-content').html(data);
    });
} );

$('#finalize').on('click', function () {
    $.post("controller/finalize.php", postDir, function () {
        window.location.href = "facturation.php?plateforme="+$('#plate').val();
    });
} );

$('#resend').on('click', function () {
    if (confirm($(this).data('msg')) == true) {
        $.post("controller/selectBills.php", Object.assign({}, postDir, {type: "resend-bills"}), function (data) {
            $('#prefa-content').html(data);
        });
        
    } 
} );

$(document).on("click", "#send-bills", function() {
    sending("Envoi dans SAP");
} );

$(document).on("click", "#resend-bills", function() {
    sending("Renvoi dans SAP");
} );

function sending(type) {
    let bills = [];
    $.each($("input[name='bills']:checked"), function(){
        bills.push($(this).val());
    });
    $(".lockable").prop('disabled', true);
    $('#message').html('<div>Veuillez patienter, cela peut prendre plusieurs minutes...</div><div class="loader"></div>');
    $.post("controller/sendBills.php", Object.assign({}, postDir, {bills: bills, type: type, mode: $('#mode-switch').prop('checked')}), function () {
        window.location.href = "facturation.php?plateforme="+$('#plate').val();
    });
}

let all = false;
$(document).on("click", "#all-bills", function() {
    let num = 0;
    if(all) {
        $('#all-bills').text("Tout sélectionner");
        $.each($("input[name='bills']"), function(){
            $(this).prop('checked', false);
        });
        all = false;
    }
    else {
        $('#all-bills').text("Tout désélectionner");
        $.each($("input[name='bills']"), function(){
            $(this).prop('checked', true);
            num++;
        });
        all = true;
    }
    displayNum(num);
});

$(document).on("click", ".check-bill", function() {
    let num = 0;
    $.each($("input[name='bills']:checked"), function(){
        num++;
    });
    displayNum(num);
});

function displayNum(num) {
    if(num > 0 ) {
        if(num > 1) {
            $('#selected-factures').text(num+" factures sélectionnées");
        }
        else {
            $('#selected-factures').text("1 facture sélectionnée");
        }
    }
    else {
        $('#selected-factures').text("aucune facture sélectionnée");
    }
}
