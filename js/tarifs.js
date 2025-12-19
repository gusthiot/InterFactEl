
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
    reset();
    type = "read";
    setDates();
});

$("#tarifs-control").on("click", function() {
    type = "control";
    setDates();
});

function setDates() {
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
    if(runCheck(check())) {
        return;
    }
    if(runCheck(reCheck())) {
        return;
    }
    if(runCheck(checkColumns(true))) {
        return;
    }
    $('#tarifs-files').html("alles gut");
    $('#tarifs-correct').css('display', 'inline-block');
    $('#tarifs-load').css('display', 'inline-block');
});

function runCheck(res) {
    if(res != "") {
        $('#tarifs-files').html(res);
        return true;
    }
    return false;
}

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

let files = {};
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
        extract();
        if(runCheck(checkColumns(false))) {
            files = {};
        }
        else {
            displayFiles();
        }
    });
});

$(document).on("change", "#tarifs-month", function() {
    $('#tarifs-open').html('<button type="button" class="btn but-line lockable">Ouvrir</button>');
});

function reset() {
    files = {};
    $('#tarifs-files').html("");
    $('#tarifs-select').html("");
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
        extract();
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

/************************ */

let contents = {};
let ids = {};

const mandatoryCsvs = { "paramfact.csv": {
                            columns: 4,
                            labels: ["code_int", "code_ext", "devise", "modes"]
                        },
                        "articlesap.csv": {
                            columns: 8,
                            uniqueId: [0],
                            tests: [
                                {type: "num", col: 2, neg: false, zero: false, int: true}
                            ]
                        },
                        "overhead.csv": {
                            columns: 3,
                            uniqueId: [0],
                            tests: [
                                {type: "num", col: 1, neg: false, zero: true, int: false},
                                {type: "ref", col: 2, origin: "articlesap.csv", zero: false}
                            ]
                        },
                        "base.csv": {
                            columns: 2,
                            uniqueId: [0]
                        },
                        "classeclient.csv": {
                            columns: 9,
                            uniqueId: [0],
                            tests: [
                                {type: "in", col: 3, array: ["INT", "EXT"]},
                                {type: "in", col: 4, array: ["BONUS", "RABAIS"]},
                                {type: "in", col: 5, array: ["BONUS", "RABAIS"]},
                                {type: "in", col: 6, array: ["OUI", "NON"]},
                                {type: "ref", col: 7, origin: "overhead.csv", zero: true},
                                {type: "ref", col: 8, origin: "base.csv", zero: false}
                            ]
                        },
                        "plateforme.csv": {
                            columns: 3,
                            labels: ["Id-Plateforme", "Code_P", "CF", "Fonds", "Admin", "Abrev-Plateforme", "Intitulé-Plateforme", "Grille-Plateforme"]
                        },
                        "partenaire.csv": {
                            columns: 3,
                            uniqueId: [0, 1],
                            tests: [
                                {type: "plateforme", col: 0},
                                {type: "ref", col: 2, origin: "classeclient.csv", zero: false}
                            ]
                        },
                        "classeprestation.csv": {
                            columns: 9,
                            uniqueId: [0],
                            tests: [
                                {type: "ref", col: 1, origin: "articlesap.csv", zero: false},
                                {type: "in", col: 2, array: ["OUI", "NON"]},
                                {type: "in", col: 3, array: ["OUI", "NON"]},
                                {type: "in", col: 4, array: ["OUI", "NON"]},
                                {type: "in", col: 5, array: ["OUI", "NON"]}
                            ]
                        },
                        "categorie.csv": {
                            columns: 8,
                            uniqueId: [0],
                            tests: [
                                {type: "plateforme", col: 5},
                                {type: "num", col: 4, neg: false, zero: true, int: true, max: 8},
                                {type: "ref", col: 6, origin: "classeprestation.csv", zero: false, special: true},
                                {type: "in", col: 7, array: ["K1", "K2", "K3", "K4", "K5", "K6", "K7"]}
                            ]
                        },
                        "groupe.csv": {
                            columns: 9,
                            uniqueId: [0],
                            tests: [
                                {type: "in", col: 1, array: ["OUI", "NON"]},
                                {type: "ref", col: 2, origin: "categorie.csv", zero: true},
                                {type: "ref", col: 3, origin: "categorie.csv", zero: true},
                                {type: "ref", col: 4, origin: "categorie.csv", zero: true},
                                {type: "ref", col: 5, origin: "categorie.csv", zero: true},
                                {type: "ref", col: 6, origin: "categorie.csv", zero: true},
                                {type: "ref", col: 7, origin: "categorie.csv", zero: true},
                                {type: "ref", col: 8, origin: "categorie.csv", zero: true}
                            ]
                        },
                        "coeffprestation.csv": {
                            columns: 3,
                            uniqueId: [0, 1],
                            shouldExist: ["classeclient.csv", "classeprestation.csv"],
                            tests: [
                                {type: "ref", col: 0, origin: "classeclient.csv", zero: false},
                                {type: "ref", col: 1, origin: "classeprestation.csv", zero: false}
                            ]
                        },
                        "basecateg.csv": {
                            columns: 3,
                            uniqueId: [0, 1],
                            shouldExist: ["base.csv", "categorie.csv"],
                            tests: [
                                {type: "ref", col: 0, origin: "base.csv", zero: false},
                                {type: "ref", col: 1, origin: "categorie.csv", zero: false},
                                {type: "num", col: 2, neg: false, zero: true, int: false}
                            ]
                        },
                    };
const optionalCsvs = ["categprix.csv"];
const mandatoryPdfs = ["logo.pdf"];
const optionalPdfs = ["grille.pdf"];

function extract() {
    Object.keys(mandatoryCsvs).forEach(function(filename) {
        if(Object.keys(files).includes(filename)) {
            contents[filename] = Papa.parse(atob(files[filename]), {delimiter: ";", skipEmptyLines: true}).data;
        }
    });
}

function check() {
    let missing = [];
    Object.keys(mandatoryCsvs).forEach(function(mandatory) {
        if(!Object.keys(files).includes(mandatory)) {
            missing.push(mandatory);
        }
    });
    mandatoryPdfs.forEach(function(mandatory) {
        if(!Object.keys(files).includes(mandatory)) {
            missing.push(mandatory);
        }
    });
    if(missing.length > 0) {
        let list = "";
        missing.forEach( function(miss) {
            list += miss+" ";
        });
        return 'il manque " '+ list +'" dans les paramètres';
    }
    return "";
}

function reCheck() {
    let polluting = [];
    Object.keys(files).forEach(function(filename) {
        if(!Object.keys(mandatoryCsvs).includes(filename) && !mandatoryPdfs.includes(filename)
            && !optionalCsvs.includes(filename) && !optionalPdfs.includes(filename)) {
            polluting.push(filename);
        }
    });
    if(polluting.length > 0) {
        let list = "";
        polluting.forEach( function(pollute) {
            list += pollute+" ";
        });
        return '" ' + list +'" est de trop les paramètres';
    }
    return "";
}

let plateforme_id = "";
let has_grille = "";
function checkColumns(complete) {
    let result = "";
    Object.keys(mandatoryCsvs).forEach(function(filename) {
        const number = mandatoryCsvs[filename].columns;
        let i = 1;
        contents[filename].forEach( function(line) {
            if(number != line.length) {
                result += "la ligne " + i + " du fichier " + filename + " contient " + line.length + " colonnes au lieu de " + number + "<br />";
            }
            i++;
        });
    });
    if(result != "") {
        return result;
    }
    Object.keys(contents).forEach(function(filename) {
        let header = true;
        let arrayIds = {};
        let i = 1;
        if(mandatoryCsvs[filename].labels) {
            header = false;
        }
        contents[filename].forEach(function(line) {
            if(!header) {
                if(mandatoryCsvs[filename].uniqueId) {
                    let id = "";
                    mandatoryCsvs[filename].uniqueId.forEach(function(col) {
                        if(id != "") {
                            id += "_";
                        }
                        id += line[col];
                    });
                    if(Object.keys(arrayIds).includes(id)) {
                        result += "l'id " + id + " du fichier " + filename + " n'est pas unique<br />";
                    }
                    else {
                        arrayIds[id] = i-1;
                    }
                }
                if(mandatoryCsvs[filename].labels) {
                    if(filename == "plateforme.csv") {
                        if(line[0] == mandatoryCsvs[filename].labels[0]) {
                            plateforme_id = line[2];
                        }
                        if(line[0] == mandatoryCsvs[filename].labels[7]) {
                            has_grille = line[2];
                        }
                    }
                    if(Object.keys(arrayIds).includes(line[0])) {
                        result += "l'étiquette " + line[col] + " du fichier " + filename + " n'est pas unique<br />";
                    }
                    else {
                        arrayIds[line[0]] = i-1;
                    }
                }
                if(complete && mandatoryCsvs[filename].tests) {
                    mandatoryCsvs[filename].tests.forEach(function(test) {
                        switch(test.type) {
                            case "in":
                                if(!test.array.includes(line[test.col])) {
                                    result += "la ligne " + i + " du fichier " + filename + " contient " + line[test.col] + " au lieu de " + test.array.toString() + "<br />";
                                }
                                break;
                            case "ref":
                                if(!(((Object.keys(ids[test.origin])).includes(line[test.col])) || (test.zero && (line[test.col] == 0)))) {
                                    result += "l'id " + line[test.col] + " de la ligne " + i + " du fichier " + filename + " n'existe pas dans les ids de  " + test.origin + "<br />";
                                }
                                if(filename == "categorie.csv") {
                                    const idPrest = line[test.col];
                                    const contentLine = contents[test.origin][ids[test.origin][idPrest]];
                                    if(contentLine[2] != "NON") {
                                        result += "le flag coef_prest de l'id classe prestation '" + idPrest + "' devrait être à NON <br />";
                                    }
                                }
                                break;
                            case "num":
                                const pref = "la valeur de la colonne " + test.col + " (" + line[test.col] + ") de la ligne " + i + " du fichier " + filename;
                                if(Number.isNaN(Number(line[test.col]))) {
                                    result += pref + " doit être un nombre<br />";
                                }
                                if(test.int && !Number.isInteger(Number(line[test.col]))) {
                                    result += pref + " doit être un entier<br />";
                                }
                                if(!test.neg && (line[test.col] < 0)) {
                                    result += pref + " ne peut être négative<br />";
                                }
                                if(!test.zero && (line[test.col] == 0)) {
                                    result += pref + " ne peut être nulle<br />";
                                }
                                if(test.max && (line[test.col] > test.max)) {
                                    result += pref + " ne peut être plus grand que " + test.max + " <br />";
                                }
                                break;
                            case "plateforme":
                                if(line[test.col] != plateforme_id) {
                                    result += "l'id " + line[test.col] + " de la ligne " + i + " du fichier " + filename + " n'existe pas dans les ids de plateforme.csv <br />";
                                }
                        }
                    });
                    if((filename == "basecateg.csv") && (line[2] > 0)) {
                        const prestLine = ids["categorie.csv"][line[1]];
                        const nd = contents["categorie.csv"][prestLine][4];
                        if((Math.floor(Math.log10(line[2])) + 1) > (9 - nd)) {
                            result += "le prix unitaire " + line[2] + " de la ligne " + i + " du fichier " + filename + " contient trop de décimales <br />";
                        }
                    }
                }
            }
            else {
                header = false;
            }
            i++;
        });
        if(complete) {
            if(filename == "plateforme.csv") {
                if(plateforme_id != plateforme) {
                    result += "l'id plateforme du fichier " + filename + " ne correspond pas à la plateforme de travail <br />";
                }
                if(has_grille == "OUI" && !Object.keys(files).includes("grille.pdf")) {
                    result += "il manque la grille de tarifs mentiennée dans le fichier " + filename + " <br />";
                }
            }
            if(mandatoryCsvs[filename].labels) {
                mandatoryCsvs[filename].labels.forEach(function(label) {
                    if(!Object.keys(arrayIds).includes(label)) {
                        result += "l'étiquette " + label + " est manquante dans le fichier " + filename + " <br />";
                    }
                });
            }
            if(mandatoryCsvs[filename].shouldExist) {
                Object.keys(ids[mandatoryCsvs[filename].shouldExist[0]]).forEach(function(id0) {
                    Object.keys(ids[mandatoryCsvs[filename].shouldExist[1]]).forEach(function(id1) {
                        const id = id0 + "_" + id1;
                        if(filename == "coeffprestation.csv") {
                            const contentLine = contents["classeprestation.csv"][ids["classeprestation.csv"][id1]];
                            if(contentLine[2] == "NON") {
                                return;
                            }
                        }
                        if(!(Object.keys(arrayIds).includes(id))) {
                            result += "Le couple id classe prestation '" + id0 + "' et id classe client '" + id1 + "' n'existe pas dans " + filename + " <br />";
                        }
                    });
                });
            }
            if(mandatoryCsvs[filename].uniqueId) {
                ids[filename] = arrayIds;
            }
        }
    });
    return result;
}
