let report = "";

$('.select-period').on('click', function () {
    $('.tile').removeClass('selected-tile');
    $($(this).attr('id')).addClass('selected-tile');
    report = $(this).attr('id');
    $('#report-content').html("");
    $.post("controller/selectPeriod.php", {plate: $('#plate').val(), type: report}, function (data) {
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
    else {
        $.post("controller/generateReports.php", {type: report, plate: $('#plate').val(), from: $('#from').val(), to: $('#to').val()}, function (data) {
            $('#period').html("");
            $('#message').html("");
            $('#report-content').html(data);
        });
    }
} );

$('#download-generated').on('click', function () {
    window.location.href = "controller/download.php?type=generated";
} );

$(document).on("click", ".sort-text", function() {
    sortTable(this, "text");
});

$(document).on("click", ".sort-number", function() {
    sortTable(this, "number");
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
