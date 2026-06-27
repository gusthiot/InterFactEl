
const plateforme = $('#plate').val();
const messages = JSON.parse($('#messages').val());
const paramtext = JSON.parse($('#paramtext').val());
const m0 = $('#m0').val();
const m0Status = $('#status').val();

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
let date = "";
let choices = "";
let first = 0;
let readPos = 0;

function reset() {
    files = {};
    contents = {};
    ids = {};
    sessionStorage.removeItem("files");
    sessionStorage.removeItem("contents");
    sessionStorage.removeItem("ids");
    $('#tarifs-files').html("");
    $('#tarifs-select').html("");
    $('#message').html("");
    $('#tarifs-load').addClass('desactived-tile');
    $('#tarifs-save').addClass('desactived-tile');
    $('#tarifs-check').addClass('desactived-tile');
    $('#tarifs-cancel').addClass('desactived-tile');
    $("#tarifs-read").removeClass('selected-tile');
    $("#tarifs-remove").removeClass('selected-tile');
    $("#tarifs-load").removeClass('selected-tile');
}

function displayFiles() {
    let filesList = '';
    [mandatoryCsvs].forEach(function(dict) {
        Object.keys(dict).forEach(function(key) {
            filesList += '<div id="' + key + '" class="file tile csv">' + dict[key].name + "</div>";
        });
    });
    [mandatoryPdfs, optionalPdfs].forEach(function(dict) {
        Object.keys(dict).forEach(function(key) {
            filesList += '<div id="' + key + '" class="file tile pdf">' + dict[key].name + "</div>";
        });
    });
    $('#tarifs-files').html(filesList);
    $('#tarifs-save').removeClass('desactived-tile');
    $('#tarifs-check').removeClass('desactived-tile');
}


function displayDates() {
    if(Object.keys(choices).length > 0) {
        let html = '<div>';
        html += '<svg id="dates-center" class="icon icon-selectable date-left" aria-hidden="true">' +
                    '<use xlink:href="#disc"></use>' +
                '</svg>';
        if(first > 0) {
            html += '<svg id="dates-up" class="icon icon-selectable" aria-hidden="true">' +
                        '<use xlink:href="#chevrons-up"></use>' +
                    '</svg>';
        }
        html += '<svg id="dates-remove" class="icon icon-selectable date-right" aria-hidden="true">' +
                    '<use xlink:href="#x"></use>' +
                '</svg>';
        html += '<table id="' + type + '-dates" class="dates-tarifs table table-boxed">';
        let pos = 0;
        Object.keys(choices).forEach(function(key) {
            if(pos >= first && pos < (first + 6)) {
                const choice = choices[key];
                const date = choice[0];
                const label = choice[1];
                let clickable = "clickable";
                let trClickable = "tr-clickable";
                if(choice[2] == 0) {
                    clickable = "faded";
                    trClickable = "";
                }
                let diode = "";
                if(choice[3] == 1) {
                    diode = '<svg class="icon" aria-hidden="true">' +
                                '<use xlink:href="#skip-forward"></use>' +
                            '</svg> ';
                }
                let base = "";
                if(choice[4] == 1) {
                    base = '<svg class="icon" aria-hidden="true">' +
                                '<use xlink:href="#database"></use>' +
                            '</svg> ';
                }
                let warning = "";
                if(choice[5] != "") {
                    warning = '<button aria-hidden="true" type="button" class="btn-invisible popover-warning" data-toggle="popover" data-trigger="focus"' +
                                        'data-content="' + choice[5] + '">' +
                                    '<svg class="icon icon-selectable red" aria-hidden="true">' +
                                        '<use xlink:href="#alert-triangle"></use>' +
                                    '</svg>' +
                                '</button>';
                }

                html += '<tr class="' + trClickable + '"><td>' + warning + '</td><td class="' + clickable + ' borded" data-key="' + key +'">' + diode + base + date + '</td><td class="' + clickable + ' borded" data-key="' + key +'">' + label + '</td><td></td></tr>';
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
        $('.popover-warning').popover();
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
        if(readPos > 5) {
            first = readPos - 5;
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

let tmpContent = "";
$(document).on("click", ".csv", function() {
    $('#tarifs-files').html("");
    const id = $(this).attr('id');
    tmpContent = contents[id];
    const parameters = mandatoryCsvs[id];
    let html = '<div id="param-header"><svg id="param-info" data-id="' + id + '" class="icon icon-selectable date-left" aria-hidden="true">' +
                        '<use xlink:href="#info"></use>' +
                    '</svg>';
    html += '<svg id="manage-remove" class="icon icon-selectable date-right" aria-hidden="true">' +
                    '<use xlink:href="#x"></use>' +
                '</svg></div>';
    html += '<div id="param-table"><table class="table">';
    if(parameters.bidim) {
        const dim0 = contents[parameters.columns[0].origin];
        const dim1 = contents[parameters.columns[1].origin];
        html += '<tr><th></th><th></th><th colspan="' + (dim1.length-1) + '">' + paramtext["table-"+id+"-"+1] + '</th></tr>';
        html += '<tr><th></th><th></th>';
        let num = 0;
        for(const key1 in dim1) {
            if(num > 0) {
                html += '<td class="border-around-black">' + dim1[key1][0] + '</td>';
            }
            num++;
        };
        html += '</tr>';
        num = 0
        for(const key0 in dim0) {
            if(num > 0) {
                html += '<tr>';
                if(num == 1) {
                    html += '<th rowspan="' + (dim0.length-1) + '" class="vert-th">' + paramtext["table-"+id+"-"+0] + '</th>';
                }
                html += '<td class="border-around-black">' + dim0[key0][0] + '</td>';
                let num1 = 0;
                for(const key1 in dim1) {
                    if(num1 > 0) {
                        let value = "";
                        for(const line of tmpContent) {
                            if((line[0] == dim0[key0][0]) && (line[1] == dim1[key1][0])) {
                                value = line[2];
                                break;
                            }
                        };
                        html += '<td class="border-around">' + number(value, parameters.columns[2]) + '</td>';
                    }
                    num1++;
                };
                html += '</tr>';
            }
            num++;
        };
    }
    else {
        html += '<tr>';
        for (let i = 0; i < parameters.numcol; i++) {
            html += '<th>' + paramtext["table-"+id+"-"+i] + '</th>';
        }
        html += '</tr>';
        let num = 0;
        tmpContent.forEach(function(line) {
            html += '<tr>';
            if(["paramfact", "plateforme"].includes(id) ||(num > 0)) {
                let num1 = 0;
                line.forEach(function(cell) {
                    const paramCol = parameters.columns[num1];
                    html += '<td class="border-around">';
                    switch(paramCol.type) {
                        case "num":
                            html += number(cell, paramCol);
                            break;
                        case "txt":
                            html += text(cell);
                            break;
                        case "alphanum":
                            html += alphanum(cell);
                            break;
                        case "menu":
                            html += menu(cell, paramCol);
                            break;
                        case "ref":
                            html += ref(cell, paramCol);
                            break;
                        case "line":
                            html += num;
                            break;
                        default:
                            html += cell;
                    }
                    html += '</td>';
                    num1++;
                });
            }
            html += '</tr>';
            num++;
        });
    }
    html += '</table></div>';
    $('#tarifs-manage').html(html);
});

$(document).on("click", "#param-info", function() {
    const id = $(this).data('id');
    $('#csv-modal-title').html("Informations concernant le fichier " + id + ".csv");
    $('#csv-modal-body').html(messages[id + "00"]);
    $('#info-modal').addClass("show");
    $('#info-modal').css("display", "block");
});

$(document).on("click", ".modal-ok", function() {
    $('#info-modal').removeClass("show");
    $('#info-modal').css("display", "none");
});

$(document).on("click", "#manage-remove", function() {
    $('#tarifs-manage').html("");
    displayFiles();
});

/** Left */

$("#tarifs-read").on("click", function() {
    reset();
    $.post("controller/getReadDates.php", {plate: plateforme, m0: m0, status: m0Status}, function (data) {
        type = "read";
        first = 0;
        const dataParsed = JSON.parse(data);
        choices = dataParsed[0];
        readPos = parseInt(dataParsed[1]);
        if(readPos > 5) {
            first = readPos - 5;
        }
        displayDates();
        $('#tarifs-cancel').removeClass('desactived-tile');
        $("#tarifs-read").addClass('selected-tile');
    });
});

$(document).on("click", "#read-dates .clickable", function() {
    const key = $(this).data('key');
    $.post("controller/openTarifs.php", {plate: plateforme, type: key.split("-")[0], date: key.split("-")[1]}, function (data) {
        files = JSON.parse(data);
        sessionStorage.setItem("files", data);
        extract();
        sessionStorage.setItem("contents", JSON.stringify(contents));
        $('#tarifs-select').html("");
        $("#tarifs-read").removeClass('selected-tile');
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

        if(runCheck(checkMandatory()) || runCheck(checkAuthorized()) || runCheck(checkColumnsNumbers()) || runCheck(checkPlateFact(false))) {
            files = {};
            contents = {};
        }
        else {
            sessionStorage.setItem("files", JSON.stringify(json));
            sessionStorage.setItem("contents", JSON.stringify(contents));
            displayFiles();
            $('#tarifs-cancel').removeClass('desactived-tile');
        }
    });
});


/** Right */

$("#tarifs-load").on("click", function() {
    $.post("controller/getLoadDates.php", {plate: plateforme, m0: m0, status: m0Status}, function (data) {
        type = "load";
        first = 0;
        choices = JSON.parse(data);
        if(Object.keys(choices).length > 6) {
            first = Object.keys(choices).length - 6;
        }
        displayDates();
        $("#tarifs-load").addClass('selected-tile');
    });
});

$("#tarifs-remove").on("click", function() {
    reset();
    $.post("controller/getRemoveDates.php", {plate: plateforme, m0: m0, status: m0Status}, function (data) {
        type = "remove";
        first = 0;
        choices = JSON.parse(data);
        if(Object.keys(choices).length > 6) {
            first = Object.keys(choices).length - 6;
        }
        displayDates();
        $('#tarifs-cancel').removeClass('desactived-tile');
        $("#tarifs-remove").addClass('selected-tile');
    });
});

$(document).on("click", "#remove-dates .clickable", function() {
    const key = $(this).data('key');
    date = key.split("-")[1];
    $('#save-modal').addClass("show");
    $('#save-modal').css("display", "block");
});

$(document).on("click", "#load-dates .clickable", function() {
    const key = $(this).data('key');
    type = key.split("-")[0];
    date = key.split("-")[1];
    if(type == "replace") {
        $('#save-modal').addClass("show");
        $('#save-modal').css("display", "block");
    }
    else {
        applyTarifs(date);
    }
});

$(document).on("click", "#modal-no", function() {
    $('#save-modal').removeClass("show");
    $('#save-modal').css("display", "none");
    if(type == "replace") {
        applyTarifs(date);
    }
    if(type == "remove") {
        removeTarifs(date);
    }
});

$(document).on("click", "#modal-yes", function() {
    $('#save-modal').removeClass("show");
    $('#save-modal').css("display", "none");
    if(type == "replace") {
        window.location.href = "controller/download.php?type=zip-tarifs&date="+date+"&plate="+plateforme;
        setTimeout(() => {  applyTarifs(date); }, 2000);
    }
    if(type == "remove") {
        window.location.href = "controller/download.php?type=zip-tarifs&date="+date+"&plate="+plateforme;
        setTimeout(() => {  removeTarifs(date); }, 2000);
    }
});

$(document).on("click", "#close-modal", function() {
    $('#save-modal').removeClass("show");
    $('#save-modal').css("display", "none");
});

function removeTarifs(date) {
    $.post("controller/suppressTarifs.php", {plate: plateforme, date: date}, function (data) {
        if(data == "ok" || data.includes("not empty")) {
            reset();
            window.location.href = "tarifs.php?plateforme="+plateforme;
        }
        else {
            $('#message').html(data);
        }
    });

}

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
        if(data == "ok") {
            reset();
            window.location.href = "tarifs.php?plateforme="+plateforme;
        }
        else {
            $('#message').html(data);
        }
    });
}


/** Bottom */

$("#tarifs-cancel").on("click", function() {
    reset();
});

$("#tarifs-check").on("click", function() {

    if(runCheck(checkMandatory()) || runCheck(checkAuthorized()) || runCheck(checkColumnsNumbers()) || runCheck(checkPlateFact(true))) {
        return;
    }
    if(runCheck(checkColumns())) {
        return;
    }
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
let checks = {};

let mandatoryCsvs = {};

$.getJSON( "./parameters.json", function( data ) {
    mandatoryCsvs = data;
});

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

function checkMandatory() {
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

function checkAuthorized() {
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
        let num = 0;
        polluting.forEach( function(pollute) {
            if(num > 0) {
                list += ", ";
            }
            list += pollute+".csv";
            num++;
        });
        let verbe = "est";
        if(num > 1) {
            verbe = "sont";
        }
        return '" ' + list +'" ' + verbe + ' de trop dans le dossier importé';
    }
    return "";
}

function checkColumnsNumbers() {
    let result = "";
    Object.keys(mandatoryCsvs).forEach(function(filename) {
        $('#'+filename).removeClass('red-file');
        $('#'+filename).removeClass('green-file');
        const number = mandatoryCsvs[filename].numcol;
        let i = 1;
        contents[filename].forEach( function(line) {
            if(number != line.length) {
                result += "la ligne " + i + " du fichier " + filename + ".csv contient " + line.length + " colonnes au lieu de " + number + "<br />";
                $('#'+filename).addClass('red-file');
            }
            i++;
        });
    });
    return result;
}

let arrayIds = {};

function checkPlateFact(verify) {
    let result = "";
    const names = ["paramfact", "plateforme"];
    names.forEach(function(filename) {
        arrayIds = {};
        let i = 1;
        contents[filename].forEach(function(line) {
            if(!Object.keys(arrayIds).includes(line[0])) {
                arrayIds[line[0]] = i-1;
            }
            else {
                result += "le label '" + line[0] + "' est présent plus d'une fois dans  " + filename + ".csv <br />";
            }
            if(filename == "plateforme") {
                if(line[0] == mandatoryCsvs[filename].labels[0]) {
                    if(line[2] != plateforme) {
                        if(verify) {
                            result += messages["plateforme01"] + " <br />";
                        }
                        else {
                            result +=  "L’étiquette [Id-Plateforme] dans plateforme.csv ne correspond pas à la plateforme de travail <br />";
                        }
                    }
                }
                if(line[0] == mandatoryCsvs[filename].labels[7]) {
                    if(!["OUI", "NON"].includes(line[2])) {
                        if(verify) {
                            result += messages["plateforme02"] + " <br />";
                        }
                        else {
                            result += "L’étiquette [Grille-Plateforme] dans plateforme.csv ne peut prendre comme valeur que OUI ou NON <br />";
                        }
                    }
                    if(line[2] == "OUI" && !Object.keys(files).includes("grille.pdf")) {
                        result += "il manque la grille de tarifs mentionnée dans le fichier " + filename + ".csv <br />";
                    }
                }
            }
            i++;
        });
        if(Object.keys(arrayIds).length != mandatoryCsvs[filename].labels.length) {
            result += "le fichier " + filename + " doit contenir " + mandatoryCsvs[filename].labels.length + " étiquettes <br />";
        }
        mandatoryCsvs[filename].labels.forEach(function(label) {
            if(!Object.keys(arrayIds).includes(label)) {
                result += "le fichier " + filename + " doit contenir l'étiquette : '" + label + "' <br />";
            }
        });
    });
    return result;
}

function checkColumns() {
    let result = "";
    Object.keys(contents).forEach(function(filename) {
        if("paramfact" == filename) {
            checks[filename] = "green-file";
            $('#'+filename).addClass('green-file');
            return;
        }
        if(result != "") {
            return;
        }

        if(mandatoryCsvs[filename].tests) {
            const columns = mandatoryCsvs[filename].columns;
            mandatoryCsvs[filename].tests.forEach(function(test) {
                let header = true;
                let i = 1;
                let resTest = "";
                let column = "";
                contents[filename].forEach(function(line) {
                    if(header) {
                        header = false;
                        if(test.type == "unique") {
                            arrayIds = {};
                            test.id.forEach(function(col) {
                                if(column != "") {
                                    column += " | ";
                                }
                                column += line[col];
                            });
                        }
                        else {
                            column = line[test.col];
                        }
                    }
                    else {
                        let error = switchTest(columns, test, line, i, column);
                        if(error != "") {
                            if(resTest == "") {
                                resTest += messages[filename + test.msg] + "<br />";
                                resTest += "Fichier : " + filename + ".csv<br />";
                                resTest += "Colonne : '" + column + "'<br />";
                            }
                            resTest += "Erreur ligne " + i + " : '" + error + "'<br />";
                        }
                    }
                    i++;
                });
                if((test.type == "unique") && !(test.noindex)) {
                    ids[filename] = arrayIds;
                }
                if(test.type == "should") {
                    Object.keys(ids[test.id[0]]).forEach(function(id0) {
                        Object.keys(ids[test.id[1]]).forEach(function(id1) {
                            if(filename == "coeffprestation") {
                                const prestLine = contents["classeprestation"][ids["classeprestation"][id1]];
                                if(prestLine[2] != "OUI") {
                                    return;
                                }
                            }
                            const id = id0 + "_" + id1;
                            if(!(Object.keys(arrayIds).includes(id))) {
                                if(resTest == "") {
                                    resTest += messages[filename + test.msg] + "<br />";
                                    resTest += "Fichier : " + filename + ".csv<br />";
                                    resTest += "Colonne : '" + column + "'<br />";
                                }
                                resTest += "Le couple '" + id1 + "' et '" + id0 + "' n'existe pas <br />";
                            }
                        });
                    });
                }
                result += resTest;
            });
        }

        if(result != "") {
            checks[filename] = "red-file";
            $('#'+filename).addClass('red-file');
            return result;
        }

        checks[filename] = "green-file";
        $('#'+filename).addClass('green-file');

    });
    sessionStorage.setItem("ids", JSON.stringify(ids));
    sessionStorage.setItem("checks", JSON.stringify(checks));
    return result;
}

function switchTest(columns, test, line, i, column) {
    switch(test.type) {
        case "in":
            if(columns[test.col].list) {
                if(!columns[test.col].list.includes(line[test.col])) {
                    return line[test.col];
                }
            }
            else {
                if(!Object.keys(columns[test.col].map).includes(line[test.col])) {
                    return line[test.col];
                }
            }
            break;
        case "ref":
            if(!(((Object.keys(ids[columns[test.col].origin])).includes(line[test.col])) || (columns[test.col].zero && (line[test.col] == 0)))) {
                return line[test.col];
            }
            break;
        case "ext":
            const idExt = line[test.col];
            const extLine = contents[test.extName][ids[test.extName][idExt]];
            if(extLine[test.extCol] != test.extValue) {
                return line[test.col];
            }
            break;
        case "num":
            if(line[test.col] == "") {
                return line[test.col];
            }
            if(Number.isNaN(Number(line[test.col]))) {
                return line[test.col];
            }
            if(columns[test.col].int && !Number.isInteger(Number(line[test.col]))) {
                return line[test.col];
            }
            if((line[test.col] < 0)) {
                return line[test.col];
            }
            if(!columns[test.col].zero && (line[test.col] == 0)) {
                return line[test.col];
            }
            if(columns[test.col].max && (line[test.col] > columns[test.col].max)) {
                return line[test.col];
            }
            if(test.special) {
                const catLine = contents["categorie"][ids["categorie"][line[1]]];
                if((Math.floor(Math.log10(line[test.col])) + 1) > (9 - catLine[4])) {
                    return line[test.col];
                }
            }
            break;
            break;
        case "unique":
            let id = "";
            test.id.forEach(function(col) {
                if(id != "") {
                    id += "_";
                }
                id += line[col];
            });
            if(Object.keys(arrayIds).includes(id)) {
                return id;
            }
            else {
                arrayIds[id] = i-1;
            }
            break;
        case "itemk":
            if(line[test.col] > 0) {
                const idCat = line[test.col];
                const cateLine = contents["categorie"][ids["categorie"][idCat]];
                if(cateLine[6] != column) {
                    return idCat;
                }
            }
    }
    return "";
}

/** Inputs */

function number(value, params) {
    let ret = '<input type="number" size="6" value="' + value + '" ';
    if(params.max) {
        ret += ' max="' + params.max + '" ';
    }
    if(params.int) {
        ret += ' step="1" ';
    }
    if(params.zero) {
        ret += ' min="0" ';
    }
    else {
        ret += ' min="1" ';
    }
    ret += ' >';
    return ret;
}

function text(value) {
    return '<input type="text" value="' + value + '">';
}

function alphanum(value) {
    return '<input type="text" value="' + value + '" pattern="[A-Za-z]{3}" >';
}

function menu(value, params) {
    let ret = '<select>';
    if(params.list) {
        params.list.forEach(function(el) {
            ret += '<option value="' + el + '"';
            if(value == el) {
                ret += ' selected ';
            }
            ret += '>' + el + '</option>';
        });
    }
    else {
        Object.keys(params.map).forEach(function(key) {
            ret += '<option value="' + key + '"';
            if(value == key) {
                ret += ' selected ';
            }
            ret += '>' + params.map[key] + '</option>';
        });
    }
    ret += '</select>';
    return ret;
}

function ref(value, params) {
    const ref = contents[params.origin];
    let ret = '<select>';
    if(params.zero) {
        ret += '<option value="0"';
        if(value == "0") {
            ret += ' selected ';
        }
        ret += '>0 - Aucun</option>';
    }
    let num = 0;
    for(const key in ref) {
        if(num > 0) {
            if(params.col && (params.value != ref[key][params.col])) {
                continue;
            }
            ret += '<option value="' + ref[key][0] + '"';
            if(value == ref[key][0]) {
                ret += ' selected ';
            }
            ret += '>' + ref[key][0];
            if(params.intitule) {
                ret += " - " + ref[key][params.intitule];
            }
            if(params.plus) {
                ret += params.plus;
            }
            ret += '</option>';
        }
        num++;
    };
    ret += '</select>';
    return ret;
}

/** Start */

if(sessionStorage.getItem("files")) {
    files = JSON.parse(sessionStorage.getItem("files"));
    contents = JSON.parse(sessionStorage.getItem("contents"));
    displayFiles();
    $('#tarifs-cancel').removeClass('desactived-tile');
}
if(sessionStorage.getItem("ids") && sessionStorage.getItem("checks")) {
    ids = JSON.parse(sessionStorage.getItem("ids"));
    checks = JSON.parse(sessionStorage.getItem("checks"));
    let result = true;
    Object.keys(checks).forEach(function(filename) {
        $('#'+filename).addClass(checks[filename]);
        if(checks[filename] != "green-file") {
            result = false;
        }
    });
    if(result) {
        $('#tarifs-load').removeClass('desactived-tile');
    }
}
