
let mpYear = $('#mp-year').val();
let mpMonth = $('#mp-month').val();
let plateforme = $('#plate').val();

/*
$(document).on("change", "#zip-tarifs", function () {
    const file = $('#zip-tarifs').val();
    if(file.indexOf('.zip') > -1) {
        $('#form-tarifs').submit();
        $('#message').text('');
    }
    else {
        $('#message').html('<div class="alert alert-danger alert-dismissible fade show" role="alert">'+
                                'Vous devez uploader une archive zip !'+
                                '<button type="button" class="close" data-dismiss="alert" aria-label="Close">'+
                                    '<span aria-hidden="true">&times;</span>'+
                                '</button>'+
                            '</div>');
    }
} );

$(document).on("change", "#zip-correct", function () {
    const file = $('#zip-correct').val();
    if(file.indexOf('.zip') > -1) {
        $('#form-correct').submit();
        $('#message').text('');
    }
    else {
        $('#message').html('<div class="alert alert-danger alert-dismissible fade show" role="alert">'+
                                'Vous devez uploader une archive zip !'+
                                '<button type="button" class="close" data-dismiss="alert" aria-label="Close">'+
                                    '<span aria-hidden="true">&times;</span>'+
                                '</button>'+
                            '</div>');
    }
} );

$(document).on("click", ".export", function() {
    const tab = $(this).attr('id').split("-");
    window.location.href = "controller/download.php?type=tarifs&plate="+plateforme+"&year="+tab[1]+"&month="+tab[2];
} );
*/
$(document).on("click", ".all", function() {
    const tab = $(this).attr('id').split("-");
    const run = $(this).data("run");
    const version = $(this).data("version");
    window.location.href = "controller/download.php?type=alltarifs&plate="+plateforme+"&year="+tab[1]+"&month="+tab[2]+"&version="+version+"&run="+run;
} );

$(document).on("click", ".suppress", function() {
    const tab = $(this).attr('id').split("-");
    window.location.href = "controller/suppressTarifs.php?plate="+plateforme+"&year="+tab[1]+"&month="+tab[2];
} );

$(document).on("click", ".etiquette", function() {
    const tab = $(this).attr('id').split("-");
    $.post("controller/getLabel.php", {plate: plateforme, right: "tarifs", year: tab[1], month: tab[2]}, function (data) {
        $('#label-'+tab[1]+'-'+tab[2]).html(data);
    });
} );

$(document).on("click", "#save-label", function() {
    const tab = $(this).parent().parent().attr('id').split("-");
    const txt = $('#label-area').val();
    $.post("controller/saveLabel.php", {txt: txt, right: "tarifs", plate: plateforme, year: tab[1], month: tab[2]}, function () {
        window.location.href = "tarifs.php?plateforme="+plateforme;
    });
} );

let type = "";
$("#tarifs-read").on("click", function() {
    type = "read";
    setDates();
});

$("#tarifs-control").on("click", function() {
    type = "control";
    setDates();
});

function setDates() {
    reset();
    $.post("controller/getTarifsDates.php", {plate: plateforme, type: type}, function (data) {
        $('#tarifs-select').html(data);
    });
}

$("#tarifs-load").on("click", function() {
    type = "load";
    $('#tarifs-select').html('<input name="month-picker" id="month-picker" class="date-picker"/>'+
                            '<div id="tarifs-apply"><button type="button" class="btn but-line lockable">Appliquer</button></div>');
    $('#month-picker').datepicker({
        dateFormat: "mm yy",
        changeMonth: true,
        changeYear: true,
        showButtonPanel: true,
        minDate: new Date(mpYear, mpMonth),
        maxDate: '+5Y',
        onClose: function(e){
            var month = $("#ui-datepicker-div .ui-datepicker-month :selected").val();
            var year = $("#ui-datepicker-div .ui-datepicker-year :selected").val();
            $(this).datepicker("setDate",new Date(year,month));
        }
    })
    .datepicker("setDate",new Date(mpYear,mpMonth));
});

$("#tarifs-check").on("click", function() {
    $('#tarifs-files').html("alles gut");
    $('#tarifs-correct').css('display', 'inline-block');
    $('#tarifs-load').css('display', 'inline-block');
});

$("#tarifs-correct").on("click", function() {
        type = "correct";
        if(confirm($('#msg').val())) {
            applyTarifs(mpMonth+" "+mpYear);
        }
});

async function blobToBase64(blob) {
  return new Promise((resolve, _) => {
    const reader = new FileReader();
    reader.onloadend = () => resolve(reader.result.split(',')[1]);
    reader.readAsDataURL(blob);
  });
}

let files = [];
$("#tarifs-import").on("change", function(e) {
    reset();
    JSZip.loadAsync(e.target.files[0]).then(function(zip) {
        const promises = Object.keys(zip.files).map(function (fileName) {
            const file = zip.files[fileName];
            return file.async("blob").then(function (blob) {
                return blobToBase64(blob).then(function (result) {
                    return [
                        fileName,
                        result
                    ];
                });
            });
        });
        return Promise.all(promises);
    }).then(function (results) {
        let json = " {";
        first = 1;
        results.forEach(function(result) {
            if(first == 1) {
                first = 0;
            }
            else {
                json += ",";
            }
            json += '"'+result[0]+'":"'+result[1]+'"';
        });
        json += "}";
        files = JSON.parse(json);
        displayFiles();
    });
});

$(document).on("change", "#tarifs-month", function() {
    $('#tarifs-open').html('<button type="button" class="btn but-line lockable">Ouvrir</button>');
});

function reset() {
    files = {};
    $('#tarifs-files').html("");
    $('#tarifs-correct').css('display', 'none');
    $('#tarifs-load').css('display', 'none');
    $('#tarifs-save').css('display', 'none');
}

function displayFiles() {
    let filesList = "";
    Object.keys(files).forEach(function(key) {
        filesList += key + "<br />";
    });
    $('#tarifs-files').html(filesList);
    $('#tarifs-save').css('display', 'inline-block');
    $('#tarifs-check').css('display', 'inline-block');
}

$(document).on("click", "#tarifs-open", function() {
    $.post("controller/openTarifs.php", {plate: plateforme, type: type, date: $('#tarifs-dates').val()}, function (data) {
        files = JSON.parse(data);
        $('#tarifs-select').html("");
        displayFiles();
    });
});

$(document).on("click", "#tarifs-apply", function() {
        applyTarifs($('#month-picker').val());
        $('#tarifs-select').html("");
});

function applyTarifs(date) {
    const enc_files = JSON.stringify(files);
    $.post("controller/applyTarifs.php", {plate: plateforme, type: type, date: date, files: enc_files}, function (data) {
        $('#tarifs-files').html(data);
    });
}

$(document).on("click", "#tarifs-save", function() {
    const enc_files = JSON.stringify(files);
    $.post("controller/saveTarifs.php", {plate: plateforme, files: enc_files}, function (data) {
        window.location.href = "controller/download.php?type=js-tarifs&name="+data+"&plate="+plateforme;
        $('#tarifs-save').css('display', 'none');
    });
});
