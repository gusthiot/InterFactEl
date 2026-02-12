
//let mpYear = $('#mp-year').val();
//let mpMonth = $('#mp-month').val();
let plateforme = $('#plate').val();


/** Liste */

$("#list-tab").on("click", function() {
    window.location.href = "tarifs.php?plateforme="+plateforme;
});

$(document).on("click", ".all", function() {
    const tab = $(this).attr('id').split("-");
    const run = $(this).data("run");
    const version = $(this).data("version");
    window.location.href = "controller/download.php?type=alltarifs&plate="+plateforme+"&year="+tab[1]+"&month="+tab[2]+"&version="+version+"&run="+run;
});

$(document).on("click", ".etiquette", function() {
    const tab = $(this).attr('id').split("-");
    $.post("controller/getLabel.php", {plate: plateforme, right: "tarifs", year: tab[1], month: tab[2]}, function (data) {
        $('#label-'+tab[1]+'-'+tab[2]).html(data);
    });
});

$(document).on("click", "#save-label", function() {
    const tab = $(this).parent().parent().attr('id').split("-");
    const txt = $('#label-area').val();
    $.post("controller/saveLabel.php", {txt: txt, right: "tarifs", plate: plateforme, year: tab[1], month: tab[2]}, function () {
        window.location.href = "tarifs.php?plateforme="+plateforme;
    });
});


/** Espace */

let files = {};
let type = "";
let choices = "";
let first = 0;
let readPos = 0;

function reset() {
    files = {};
    $('#tarifs-files').html("");
    $('#tarifs-select').html("");
    $('#tarifs-correct').addClass('desactived-tile');
    $('#tarifs-load').addClass('desactived-tile');
    $('#tarifs-save').addClass('desactived-tile');
    $('#tarifs-check').addClass('desactived-tile');
    $('#tarifs-cancel').addClass('desactived-tile');
}

function displayFiles() {
    let filesList = '<div id="files">';
    [mandatoryCsvs, mandatoryPdfs, optionalPdfs].forEach(function(dict) {
        Object.keys(dict).forEach(function(key) {
            filesList += '<div id="' + key + '" class="file">' + dict[key].name + "</div>";
        });
    });
    filesList += '</div>'
    $('#tarifs-files').html(filesList);
    $('#tarifs-save').removeClass('desactived-tile');
    $('#tarifs-check').removeClass('desactived-tile');
}


function displayDates() {
    if(Object.keys(choices).length > 0) {
        let html = '<div class="over-tarifs over-dates">';
        html += '<svg id="dates-center" class="icon icon-selectable left" aria-hidden="true">' +
                    '<use xlink:href="#disc"></use>' +
                '</svg>';
        if(first > 0) {
            html += '<svg id="dates-up" class="icon icon-selectable" aria-hidden="true">' +
                        '<use xlink:href="#chevrons-up"></use>' +
                    '</svg>';
        }
        html += '<svg id="dates-remove" class="icon icon-selectable right" aria-hidden="true">' +
                    '<use xlink:href="#x"></use>' +
                '</svg>';
        html += '<table id="' + type + '-dates" class="dates-tarifs table table-boxed">';
        let pos = 0;
        Object.keys(choices).forEach(function(key) {
            if(pos >= first && pos < (first + 6)) {
                const choice = choices[key];
                html += '<tr data-key="' + key +'"><td>' + choice[0] + '</td><td>' + choice[1] + '</td></tr>';
            }
            pos++;
        });
        html += '</table>';
        if((first + 6) < Object.keys(choices).length) {
            html += '<svg id="dates-down" class="icon icon-selectable" aria-hidden="true">' +
                        '<use xlink:href="#chevrons-down"></use>' +
                    '</svg>';
        }
        html += '</div>';
        $('#tarifs-select').html(html);
    }
    else {
        $('#tarifs-select').html("Pas de données dans la période autorisée");
    }
}


$(document).on("click", "#dates-remove", function() {
    $('#tarifs-select').html("");
});

$(document).on("click", "#dates-down", function() {
    if(first < Object.keys(choices).length-6) {
        first += 3;
    }
    else {
        first = Object.keys(choices).length-6;
    }
    displayDates();
});

$(document).on("click", "#dates-up", function() {
    if(first > 3) {
        first -= 3;
    }
    else {
        first = 0;
    }
    displayDates();
});

$(document).on("click", "#dates-center", function() {
    if(type == "read") {
        if(readPos > 3) {
            first = readPos - 3;
        }
        else {
            first = 0;
        }
    }
    else {
        if(Object.keys(choices).length > 6) {
            first = Object.keys(choices).length - 6;
        }
        else {
            first = 0;
        }
    }
    displayDates();
});


/** Left */

$("#tarifs-read").on("click", function() {
    reset();
    $.post("controller/getReadDates.php", {plate: plateforme}, function (data) {
        type = "read";
        first = 0;
        const dataParsed = JSON.parse(data);
        choices = dataParsed[0];
        readPos = parseInt(dataParsed[1]);
        if(readPos > 3) {
            first = readPos - 3;
        }
        displayDates();
        $('#tarifs-cancel').removeClass('desactived-tile');
    });
});

$(document).on("click", "#read-dates tr", function() {
    const key = $(this).data('key');
    $.post("controller/openTarifs.php", {plate: plateforme, type: key.split("-")[0], date: key.split("-")[1]}, function (data) {
        files = JSON.parse(data);
        extract();
        $('#tarifs-select').html("");
        displayFiles();
    });
});

async function blobToBase64(blob) {
  return new Promise((resolve, _) => {
    const reader = new FileReader();
    reader.onloadend = () => resolve(reader.result.split(',')[1]);
    reader.readAsDataURL(blob);
  });
}

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
            $('#tarifs-cancel').removeClass('desactived-tile');
        }
    });
});


/** Right */

$("#tarifs-load").on("click", function() {
    loadDates("load");
});

$("#tarifs-remove").on("click", function() {
    reset();
    loadDates("remove");
    $('#tarifs-cancel').removeClass('desactived-tile');
});

function loadDates(typeLoad) {
    $.post("controller/getLoadDates.php", {plate: plateforme, type: typeLoad}, function (data) {
        type = "load";
        first = 0;
        choices = JSON.parse(data);
        if(Object.keys(choices).length > 6) {
            first = Object.keys(choices).length - 6;
        }
        displayDates();
    });
}

$(document).on("click", "#load-dates tr", function() {
    const key = $(this).data('key');
    const type = key.split("-")[0];
    const date = key.split("-")[1];
    if(type == "remove") {
        window.location.href = "controller/suppressTarifs.php?plate="+plateforme+"&date="+date;
    }
    else {
        if(type == "replace") {
            if(confirm("Voulez-vous sauvegarder les paramètres existants ?")) {
                window.location.href = "controller/download.php?type=zip-tarifs&date="+date+"&plate="+plateforme;
                applyTarifs(date);
            }
            else {
                applyTarifs(date);
            }
        }
        else {
            applyTarifs(date);
        }
    }
});

function applyTarifs(date) {
    let categprix = [["Id-ClasseClient", "Id_Categorie", "Prix unitaire"]];
    Object.keys(ids["classeclient"]).forEach(function(ccKey) {
        const ccLine = contents["classeclient"][ids["classeclient"][ccKey]];
        const idBase = ccLine[8];
        Object.keys(ids["categorie"]).forEach(function(caKey) {
            const idBaseCateg = idBase+"_"+caKey;
            const bcLine = contents["basecateg"][ids["basecateg"][idBaseCateg]];
            categprix.push([ccKey, caKey, bcLine[2]]);
        });
    });
    files["categprix.csv"] = btoa(Papa.unparse(categprix, {delimiter: ";", skipEmptyLines: true}));
    const enc_files = JSON.stringify(files);
    $.post("controller/applyTarifs.php", {plate: plateforme, date: date, files: enc_files}, function (data) {
        $('#message').html(data);
        window.location.href = "tarifs.php?plateforme="+plateforme;
    });
}


/** Bottom */

$("#tarifs-cancel").on("click", function() {
    reset();
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
    $('#message').html("alles gut");
    $('#tarifs-correct').removeClass('desactived-tile');
    $('#tarifs-load').removeClass('desactived-tile');
});

function runCheck(res) {
    if(res != "") {
        $('#message').html(res);
        return true;
    }
    return false;
}

$(document).on("click", "#tarifs-save", function() {
    const enc_files = JSON.stringify(files);
    $.post("controller/saveTarifs.php", {plate: plateforme, files: enc_files}, function (data) {
        window.location.href = "controller/download.php?type=js-tarifs&name="+data+"&plate="+plateforme;
        $('#tarifs-save').addClass('desactived-tile');
    });
});


/** files manipulation */

let contents = {};
let ids = {};

const mandatoryCsvs = { "paramfact": {
                            name: "Paramètres SAP",
                            columns: 4,
                            labels: ["code_int", "code_ext", "devise", "modes"]
                        },
                        "articlesap": {
                            name: "Articles SAP",
                            columns: 8,
                            uniqueId: [0],
                            tests: [
                                {type: "num", col: 2, neg: false, zero: false, int: true}
                            ]
                        },
                        "overhead": {
                            name: "Taux Overhead",
                            columns: 3,
                            uniqueId: [0],
                            tests: [
                                {type: "num", col: 1, neg: false, zero: true, int: false},
                                {type: "ref", col: 2, origin: "articlesap", zero: false}
                            ]
                        },
                        "base": {
                            name: "Liste Tarifs Base",
                            columns: 2,
                            uniqueId: [0]
                        },
                        "classeclient": {
                            name: "Classes Client",
                            columns: 9,
                            uniqueId: [0],
                            tests: [
                                {type: "in", col: 3, array: ["INT", "EXT"]},
                                {type: "in", col: 4, array: ["BONUS", "RABAIS"]},
                                {type: "in", col: 5, array: ["BONUS", "RABAIS"]},
                                {type: "in", col: 6, array: ["OUI", "NON"]},
                                {type: "ref", col: 7, origin: "overhead", zero: true},
                                {type: "ref", col: 8, origin: "base", zero: false}
                            ]
                        },
                        "plateforme": {
                            name: "Données Plateforme",
                            columns: 3,
                            labels: ["Id-Plateforme", "Code_P", "CF", "Fonds", "Admin", "Abrev-Plateforme", "Intitulé-Plateforme", "Grille-Plateforme"]
                        },
                        "partenaire": {
                            name: "Partenaires Plateforme",
                            columns: 3,
                            uniqueId: [0, 1],
                            tests: [
                                {type: "plateforme", col: 0},
                                {type: "ref", col: 2, origin: "classeclient", zero: false}
                            ]
                        },
                        "classeprestation": {
                            name: "Classes Prestations",
                            columns: 9,
                            uniqueId: [0],
                            tests: [
                                {type: "ref", col: 1, origin: "articlesap", zero: false},
                                {type: "in", col: 2, array: ["OUI", "NON"]},
                                {type: "in", col: 3, array: ["OUI", "NON"]},
                                {type: "in", col: 4, array: ["OUI", "NON"]},
                                {type: "in", col: 5, array: ["OUI", "NON"]}
                            ]
                        },
                        "categorie": {
                            name: "Catégories",
                            columns: 8,
                            uniqueId: [0],
                            tests: [
                                {type: "plateforme", col: 5},
                                {type: "num", col: 4, neg: false, zero: true, int: true, max: 8},
                                {type: "ref", col: 6, origin: "classeprestation", zero: false, special: true},
                                {type: "in", col: 7, array: ["K1", "K2", "K3", "K4", "K5", "K6", "K7"]}
                            ]
                        },
                        "groupe": {
                            name: "Groupes",
                            columns: 9,
                            uniqueId: [0],
                            tests: [
                                {type: "in", col: 1, array: ["OUI", "NON"]},
                                {type: "ref", col: 2, origin: "categorie", zero: true},
                                {type: "ref", col: 3, origin: "categorie", zero: true},
                                {type: "ref", col: 4, origin: "categorie", zero: true},
                                {type: "ref", col: 5, origin: "categorie", zero: true},
                                {type: "ref", col: 6, origin: "categorie", zero: true},
                                {type: "ref", col: 7, origin: "categorie", zero: true},
                                {type: "ref", col: 8, origin: "categorie", zero: true}
                            ]
                        },
                        "coeffprestation": {
                            name: "Coefficients Prestations",
                            columns: 3,
                            uniqueId: [0, 1],
                            shouldExist: ["classeclient", "classeprestation"],
                            tests: [
                                {type: "ref", col: 0, origin: "classeclient", zero: false},
                                {type: "ref", col: 1, origin: "classeprestation", zero: false}
                            ]
                        },
                        "basecateg": {
                            name: "Tarifs Base",
                            columns: 3,
                            uniqueId: [0, 1],
                            shouldExist: ["base", "categorie"],
                            tests: [
                                {type: "ref", col: 0, origin: "base", zero: false},
                                {type: "ref", col: 1, origin: "categorie", zero: false},
                                {type: "num", col: 2, neg: false, zero: true, int: false}
                            ]
                        },
                    };
const optionalCsvs = ["categprix"];
const mandatoryPdfs = {"logo": {
                            name: "Logo PDF"
                        }
                    };
const optionalPdfs = {"grille": {
                            name: "Grille PDF"
                        }
                    };

function extract() {
    Object.keys(mandatoryCsvs).forEach(function(filename) {
        if(Object.keys(files).includes(filename + ".csv")) {
            contents[filename] = Papa.parse(atob(files[filename + ".csv"]), {delimiter: ";", skipEmptyLines: true}).data;
        }
    });
}

function check() {
    let missing = [];
    Object.keys(mandatoryCsvs).forEach(function(mandatory) {
        if(!Object.keys(files).includes(mandatory + ".csv")) {
            missing.push(mandatory + ".csv");
        }
    });
    Object.keys(mandatoryPdfs).forEach(function(mandatory) {
        if(!Object.keys(files).includes(mandatory + ".pdf")) {
            missing.push(mandatory + ".pdf");
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
    Object.keys(files).forEach(function(fn) {
        const filename = fn.split(".")[0];
        if(!Object.keys(mandatoryCsvs).includes(filename) && !Object.keys(mandatoryPdfs).includes(filename)
            && !optionalCsvs.includes(filename) && !Object.keys(optionalPdfs).includes(filename)) {
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
        $('#'+filename).removeClass('orange-tile');
        $('#'+filename).removeClass('red-tile');
        $('#'+filename).removeClass('green-tile');
        const number = mandatoryCsvs[filename].columns;
        let i = 1;
        contents[filename].forEach( function(line) {
            if(number != line.length) {
                result += "la ligne " + i + " du fichier " + filename + ".csv contient " + line.length + " colonnes au lieu de " + number + "<br />";
                $('#'+filename).addClass('orange-tile');
            }
            i++;
        });
    });
    if(result != "") {
        return result;
    }
    Object.keys(contents).forEach(function(filename) {
        let resFile = "";
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
                        resFile += "l'id " + id + " du fichier " + filename + ".csv n'est pas unique<br />";
                    }
                    else {
                        arrayIds[id] = i-1;
                    }
                }
                if(mandatoryCsvs[filename].labels) {
                    if(filename == "plateforme") {
                        if(line[0] == mandatoryCsvs[filename].labels[0]) {
                            plateforme_id = line[2];
                        }
                        if(line[0] == mandatoryCsvs[filename].labels[7]) {
                            has_grille = line[2];
                        }
                    }
                    if(Object.keys(arrayIds).includes(line[0])) {
                        resFile += "l'étiquette " + line[col] + " du fichier " + filename + ".csv n'est pas unique<br />";
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
                                    resFile += "la ligne " + i + " du fichier " + filename + ".csv contient " + line[test.col] + " au lieu de " + test.array.toString() + "<br />";
                                }
                                break;
                            case "ref":
                                if(!(((Object.keys(ids[test.origin])).includes(line[test.col])) || (test.zero && (line[test.col] == 0)))) {
                                    resFile += "l'id " + line[test.col] + " de la ligne " + i + " du fichier " + filename + ".csv n'existe pas dans les ids de  " + test.origin + "<br />";
                                }
                                if(filename == "categorie") {
                                    const idPrest = line[test.col];
                                    const contentLine = contents[test.origin][ids[test.origin][idPrest]];
                                    if(contentLine[2] != "NON") {
                                        resFile += "le flag coef_prest de l'id classe prestation '" + idPrest + "' devrait être à NON <br />";
                                    }
                                }
                                break;
                            case "num":
                                const pref = "la valeur de la colonne " + test.col + " (" + line[test.col] + ") de la ligne " + i + " du fichier " + filename + ".csv";
                                if(Number.isNaN(Number(line[test.col]))) {
                                    resFile += pref + " doit être un nombre<br />";
                                }
                                if(test.int && !Number.isInteger(Number(line[test.col]))) {
                                    resFile += pref + " doit être un entier<br />";
                                }
                                if(!test.neg && (line[test.col] < 0)) {
                                    resFile += pref + " ne peut être négative<br />";
                                }
                                if(!test.zero && (line[test.col] == 0)) {
                                    resFile += pref + " ne peut être nulle<br />";
                                }
                                if(test.max && (line[test.col] > test.max)) {
                                    resFile += pref + " ne peut être plus grand que " + test.max + " <br />";
                                }
                                break;
                            case "plateforme":
                                if(line[test.col] != plateforme_id) {
                                    resFile += "l'id " + line[test.col] + " de la ligne " + i + " du fichier " + filename + ".csv n'existe pas dans les ids de plateforme.csv <br />";
                                }
                        }
                    });
                    if((filename == "basecateg") && (line[2] > 0)) {
                        const prestLine = ids["categorie"][line[1]];
                        const nd = contents["categorie"][prestLine][4];
                        if((Math.floor(Math.log10(line[2])) + 1) > (9 - nd)) {
                            resFile += "le prix unitaire " + line[2] + " de la ligne " + i + " du fichier " + filename + ".csv contient trop de décimales <br />";
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
            if(filename == "plateforme") {
                if(plateforme_id != plateforme) {
                    resFile += "l'id plateforme du fichier " + filename + ".csv ne correspond pas à la plateforme de travail <br />";
                }
                if(has_grille == "OUI" && !Object.keys(files).includes("grille.pdf")) {
                    resFile += "il manque la grille de tarifs mentiennée dans le fichier " + filename + ".csv <br />";
                }
            }
            if(mandatoryCsvs[filename].labels) {
                mandatoryCsvs[filename].labels.forEach(function(label) {
                    if(!Object.keys(arrayIds).includes(label)) {
                        resFile += "l'étiquette " + label + " est manquante dans le fichier " + filename + " <br />";
                    }
                });
            }
            if(mandatoryCsvs[filename].shouldExist) {
                Object.keys(ids[mandatoryCsvs[filename].shouldExist[0]]).forEach(function(id0) {
                    Object.keys(ids[mandatoryCsvs[filename].shouldExist[1]]).forEach(function(id1) {
                        const id = id0 + "_" + id1;
                        if(filename == "coeffprestation") {
                            const contentLine = contents["classeprestation"][ids["classeprestation"][id1]];
                            if(contentLine[2] == "NON") {
                                return;
                            }
                        }
                        if(!(Object.keys(arrayIds).includes(id))) {
                            resFile += "Le couple id classe prestation '" + id0 + "' et id classe client '" + id1 + "' n'existe pas dans " + filename + " <br />";
                        }
                    });
                });
            }
            if(mandatoryCsvs[filename].uniqueId) {
                ids[filename] = arrayIds;
            }
        }
        if(resFile != "") {
            result += resFile;
            $('#'+filename).addClass('orange-tile');
        }
        else {
            $('#'+filename).addClass('green-tile');
        }
    });
    return result;
}
