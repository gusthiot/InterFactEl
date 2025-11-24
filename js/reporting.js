
let hidden = true;
let plateforme = $('#plate').val();

$(".icon-parameters").on("click", function() {
    if(hidden) {
        hidden = false;
        $('#report-inputs').show();
    }
    else {
        hidden = true;
        $('#report-inputs').hide();
    }
});

$("input[name='encoding']").on("change", function () {
    $.post("controller/changeReportParameter.php", {plate: plateforme, type: "encoding", value: $("input[name='encoding']:checked").val()}, function () {
    });
 });

$("input[name='separator']").on("change", function () {
    $.post("controller/changeReportParameter.php", {plate: plateforme, type: "separator", value: $("input[name='separator']:checked").val()}, function () {
    });
 });

let report = "";

$('.select-period').on('click', function () {
    if($('#disabled').val() == "") {
        $('.tile').removeClass('selected-tile');
        $($(this).attr('id')).addClass('selected-tile');
        report = $(this).attr('id');
        const title = $('.title', this).text();
        $.post("controller/selectPeriod.php", {plate: plateforme, type: report}, function (data) {
            $('#report-tiles').hide();
            $('#report-period').html(data);
            $('#report-period').show();
            $('#report-title').html('<div id="date-title">'+title+'</div>');
            $('#report-title').show();
            $('#back').show();
        });
    }
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


$(document).on("click", "#back", function() {
    $('#report-period').hide();
    $('#report-title').hide();
    $('#report-content').hide();
    $('#back').hide();
    $('#report-tiles').show();
});

$(document).on("click", "#generate", function() {
    $('#message').html('<div>Veuillez patienter, cela peut prendre plusieurs minutes...</div><div class="loader"></div>');
    $(".lockable").prop('disabled', true);
    $('#back').hide();
    if(["t1", "t2", "t3f", "t3s"].includes(report)) {
        window.location.href = "controller/generateConcatenation.php?plate="+plateforme+"&from="+$('#from').val()+"&to="+$('#to').val()+"&type="+report;
    }
    else {
        $.post("controller/generateReport.php", {type: report, plate: plateforme, from: $('#from').val(), to: $('#to').val()}, function (data) {
            $('#report-period').hide();
            $('#report-content').show();
            $('#message').html("");
            $('#back').show();
            $('#report-content').html(data);
        });
    }
} );

$('#download-generated').on('click', function () {
    window.location.href = "controller/download.php?type=generated";
} );

$(document).on("click", ".sort-text", function() {
    if($(this).text().includes("Operator") && ($(this).closest('table').attr('id') == "par-staff-date-table")) {
        sortTable2(this, "text", 2, "asc");
        if($(this).parent().children().index($(this)) == 0) {
            sortTable2(this, "text", 1, "desc");
        }
        else {
            sortTable2(this, "text", 0, "desc");
        }
        sortTable(this, "text");
    }
    else {
        sortTable(this, "text");
    }
});

$(document).on("click", ".sort-number", function() {
    sortTable(this, "number");
});

function sortTable(th, type) {
    const columnIndex = $(th).parent().children().index($(th));
    let dir = "asc";
    if($(th).hasClass('asc')) {
        $(th).removeClass('asc');
        dir = "desc";
    }
    else {
        $(th).addClass('asc');
    }
    sortTable2(th, type, columnIndex, dir);
}

function sortTable2(th, type, columnIndex, dir) {
    const tabId = $(th).closest('table').attr('id');
    const table = document.getElementById(tabId);
    var rows = Array.prototype.slice.call(table.querySelectorAll("tbody > tr"));

    if(dir == "asc") {
        if(type == "number") {
            rows.sort(function(rowA, rowB) {
                return getNum(rowA, columnIndex) - getNum(rowB, columnIndex);
            });
        }
        else {
            rows.sort(function(rowA, rowB) {
                return getTxt(rowA, columnIndex) < getTxt(rowB, columnIndex) ? -1 : 1;
            });
        }
        dir = "desc";
    }
    else {
        if(type == "number") {
            rows.sort(function(rowA, rowB) {
                return getNum(rowB, columnIndex) - getNum(rowA, columnIndex);
            });
        }
        else {
            rows.sort(function(rowA, rowB) {
                return getTxt(rowB, columnIndex) < getTxt(rowA, columnIndex) ? -1 : 1;
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
