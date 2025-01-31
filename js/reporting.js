let report = "";
let toIndex = false;

$('#concatenation').on('click', function () {
    $('.tile').removeClass('selected-tile');
    $('#concatenation').addClass('selected-tile');
    report = "concatenation";
    $('#report-content').html("");
    $.post("controller/selectPeriod.php", {plate: $('#plate').val()}, function (data) {
        $('#period').html(data);
    });
} );

$('#montants').on('click', function () {
/*
    
    $.post("controller/generateJsonStructure.php", {plate: $('#plate').val()}, function (data) {
        $('#period').html("");
        $('#bilans').html("");
        $('#report-content').html(data);
    });
    
} );

*/    
    $('.tile').removeClass('selected-tile');
    $('#montants').addClass('selected-tile');
    $('#report-content').html("");
    report = "montants";
    $.post("controller/selectPeriod.php", {plate: $('#plate').val()}, function (data) {
        $('#period').html(data);
    });
} );

$(document).on("change", "#from", function() {
    const from = $(this).find(":selected").val();
    const to = $('#last').find(":selected").val();
    let blank = true;
    let start = '<label for="to">A</label><select id="to" class="custom-select lockable">';
    let next = "";
    $(this.children).each(function()
    {
        if(parseInt($(this).val()) >= parseInt(from)) {
            next += '<option value="'+$(this).val()+'"';
            if(to && (to == $(this).val())) {
                blank = false;
                next += ' selected ';
            }
            next += '>'+$(this).text()+'</option>';
        }
    });
    if(blank) {
        start += '<option disabled selected></option>';
        $('#generate').html('');
    }
    const last = start + next + '</select>'; 
    $('#last').html(last);
} );

$(document).on("change", "#to", function() {
    $('#generate').html('<button type="button" class="btn but-line lockable">Générer</button>');
} );

$(document).on("click", "#generate", function() {
    $('#message').html('<div>Veuillez patienter, cela peut prendre plusieurs minutes...</div><div class="loader"></div>');
    $(".lockable").prop('disabled', true);
    if(report == "concatenation") {
        window.location.href = "controller/generateConcatenation.php?plate="+$('#plate').val()+"&from="+$('#from').val()+"&to="+$('#to').val();
    }
    else if(report == "montants") {
        $.post("controller/generateBilan.php", {plate: $('#plate').val(), from: $('#from').val(), to: $('#to').val(), unique: $('#unique').val()}, function (data) {
            $('#period').html("");
            $('#message').html("");
            $('#report-content').html(data);
            $('#report-tiles').html('<button type="button" id="reinit" class="btn but-line">Retour au menu principal</button>');
        });
    }
    else {    
        $(".lockable").prop('disabled', false);
        $('#message').html('<div class="alert alert-danger alert-dismissible fade show" role="alert">'+
            'Pas de générateur correspondant'+
            '<button type="button" class="close" data-dismiss="alert" aria-label="Close">'+
                '<span aria-hidden="true">&times;</span>'+
            '</button>'+
        '</div>');
    }
} );

$(document).on("click", "#reinit", function() {
    deleteDir();
});

$(document).on("click", ".reinit", function() {        
    toIndex = true;
});

$('#download-generated').on('click', function () {
    window.location.href = "controller/download.php?type=generated";
} );


$(document).on("click", ".sort-text", function() {
    sortTable(this, "text");
});

$(document).on("click", ".sort-number", function() {
    sortTable(this, "number");
});

$(document).on("click", ".get-report", function() {
    const reportId = $(this).attr('id').replace("-dl", "");
    window.location.href = "controller/download.php?type=report&unique="+$('#unique').val()+"&name="+reportId;
});

function sortTable(th, type) {
    const columnIndex = $(th).parent().children().index($(th));
    const tabId = $(th).closest('table').attr('id');
    const table = document.getElementById(tabId);
    let dir = "asc";
    if($(th).hasClass('asc')) {
        $(th).removeClass('asc');
        dir = "desc";
    }
    else {
        $(th).addClass('asc');
    }
    var rows = Array.prototype.slice.call(table.querySelectorAll("tbody > tr"));

    if(dir == "asc") {
        if(type == "number") {
            rows.sort(function(rowA, rowB) {
                return getNum(rowA,columnIndex) - getNum(rowB,columnIndex);
            });
        }
        else {
            rows.sort(function(rowA, rowB) {
                return getTxt(rowA,columnIndex) < getTxt(rowB, columnIndex) ? -1 : 1;
            });
        }
        dir = "desc";
    }
    else {
        if(type == "number") {
            rows.sort(function(rowA, rowB) {
                return getNum(rowB,columnIndex) - getNum(rowA,columnIndex);
            });
        }
        else {
            rows.sort(function(rowA, rowB) {
                return getTxt(rowB,columnIndex) < getTxt(rowA, columnIndex) ? -1 : 1;
            });
        }
        dir = "asc";
    }

    rows.forEach(function(row) {
        table.querySelector("tbody").appendChild(row);
    });
}

function getTxt(row, column) {
    return row.cells[column].textContent.toLowerCase();
}

function getNum(row, column) {
    return Number.parseFloat(row.cells[column].textContent.replaceAll("'", ""));
}

function deleteDir() {
    if(toIndex) {
        window.location.href = "controller/deleteReport.php?unique="+$('#unique').val(); // firefox
        $.post("controller/deleteReport.php", {unique: $('#unique').val()}, function () {
            window.location.href = "index.php";
        });
    }
    else {
        window.location.href = "controller/deleteReport.php?unique="+$('#unique').val()+"&plate="+$('#plate').val(); // firefox
        $.post("controller/deleteReport.php", {unique: $('#unique').val()}, function () {
            window.location.href = "reporting.php?plateforme="+$('#plate').val();
        });
    }
}

$(window).on("unload", deleteDir); // firefox
$(window).on('beforeunload', deleteDir); // chromium
