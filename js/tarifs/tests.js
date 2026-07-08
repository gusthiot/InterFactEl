const plateforme = $('#plate').val();
export const messages = JSON.parse($('#messages').val());

export const mandatoryCsvs = JSON.parse($('#parameters').val());

export const optionalCsvs = ["categprix"];
export const mandatoryPdfs = {"logo": {
                            name: "Logo PDF"
                        }
                    };
export const optionalPdfs = {"grille": {
                            name: "Grille PDF"
                        }
                    };

export function checkMandatory(contents, pdfs) {
    let missing = [];
    Object.keys(mandatoryCsvs).forEach(function(mandatory) {
        if(!Object.keys(contents).includes(mandatory)) {
            missing.push(mandatory + ".csv");
        }
    });
    Object.keys(mandatoryPdfs).forEach(function(mandatory) {
        if(!Object.keys(pdfs).includes(mandatory)) {
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

export function checkAuthorized(contents, pdfs, optCsvs, optPdfs) {
    let polluting = [];
    Object.keys(contents).forEach(function(filename) {
        if(!Object.keys(mandatoryCsvs).includes(filename)) {
            polluting.push(filename+".csv");
        }
    });
    Object.keys(pdfs).forEach(function(filename) {
        if(!Object.keys(mandatoryPdfs).includes(filename)) {
            polluting.push(filename+".pdf");
        }
    });
    Object.keys(optCsvs).forEach(function(filename) {
        if(!optionalCsvs.includes(filename)) {
            polluting.push(filename+".csv");
        }
    });
    Object.keys(optPdfs).forEach(function(filename) {
        if(!Object.keys(optionalPdfs).includes(filename)) {
            polluting.push(filename+".pdf");
        }
    });
    if(polluting.length > 0) {
        let list = "";
        let num = 0;
        polluting.forEach( function(pollute) {
            if(num > 0) {
                list += ", ";
            }
            list += pollute;
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

export function checkColumnsNumbers(contents) {
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

export function checkPlateFact(contents, optPdfs, verify) {
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
                    if(line[2] == "OUI" && !Object.keys(optPdfs).includes("grille")) {
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

export function checkColumns(contents, ids) {
    let result = "";
    let checks = {};
    Object.keys(mandatoryCsvs).forEach(function(filename) {
        checks[filename] = {};
        checks[filename].errors = {};
        if("paramfact" == filename) {
            checks[filename].ok = true;
            $('#'+filename).addClass('green-file');
            return;
        }
        if(result != "") {
            return;
        }
        if(mandatoryCsvs[filename].tests) {
            const results = internalCheck(filename, contents[filename], contents, ids);
            result += results.result;
            ids = results.ids;
            checks[filename].errors = results.errors;
        }
        if(result != "") {
            checks[filename].ok = false;
            $('#'+filename).addClass('red-file');
        }
        else {
            checks[filename].ok = true;
            $('#'+filename).addClass('green-file');
        }
    });
    return {"result": result, "checks": checks, "ids": ids};
}

let arrayIds = {};

export function internalCheck(filename, conTest, contents, ids) {
    let result = "";
    let errors = {};
    mandatoryCsvs[filename].tests.forEach(function(test) {
        let resTest = "";
        let i = 0;
        let column = "";
        let colNum = [];
        conTest.forEach(function(line) {
            if(i == 0) {
                if(test.type == "unique") {
                    arrayIds = {};
                    test.id.forEach(function(col) {
                        if(column != "") {
                            column += " | ";
                        }
                        column += line[col];
                    });
                    colNum = test.id;
                }
                else {
                    column = line[test.col];
                    colNum = [test.col];
                }
            }
            else {
                const columns = mandatoryCsvs[filename].columns;
                let error = switchTest(columns, test, line, i, column, contents, ids);
                if(error != "") {
                    if(!errors["row-"+i]) {
                        errors["row-"+i] = {};
                    }
                    colNum.forEach(function(col) {
                        errors["row-"+i]["col-"+col] = messages[filename + test.msg];
                    });
                    if(resTest == "") {
                        resTest += messages[filename + test.msg] + "<br />";
                        resTest += "Fichier : " + filename + ".csv<br />";
                        resTest += "Colonne : '" + column + "'<br />";
                    }
                    resTest += "Erreur ligne " + (i+1) + " : '" + error + "'<br />";
                }
            }
            i++;
        });
        if((test.type == "unique") && !(test.noindex)) {
            ids[filename] = arrayIds;
        }
        if(test.type == "should") {
            Object.keys(retrieveIds(test.id[0], contents, ids)).forEach(function(id0) {
                Object.keys(retrieveIds(test.id[1], contents, ids)).forEach(function(id1) {
                    if(filename == "coeffprestation") {
                        const prestLine = contents["classeprestation"][retrieveIds("classeprestation", contents, ids)[id1]];
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
    return {"result": result, "ids": ids, "errors": errors};
}

function retrieveIds(filename, contents, ids) {
    if(ids[filename]) {
        return ids[filename];
    }
    let i = 0;
    arrayIds = {};
    let pos = "";
    mandatoryCsvs[filename].tests.forEach(function(test) {
        if((test.type == "unique") && !test.noindex) {
            pos = test.id;
        }
    });
    contents[filename].forEach(function(line) {
        if(i > 0) {
            let id = "";
            pos.forEach(function(col) {
                if(id != "") {
                    id += "_";
                }
                id += line[col];
            });
            arrayIds[id] = i;
        }
        i++;
    });
    return arrayIds;
}

function switchTest(columns, test, line, i, column, contents, ids) {
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
            if(!(((Object.keys(retrieveIds(columns[test.col].origin, contents, ids))).includes(line[test.col])) ||
                (columns[test.col].zero && (line[test.col] == 0)))) {
                return line[test.col];
            }
            break;
        case "ext":
            const idExt = line[test.col];
            const extLine = contents[test.extName][retrieveIds(test.extName, contents, ids)[idExt]];
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
                const catLine = contents["categorie"][retrieveIds("categorie", contents, ids)[line[1]]];
                if((Math.floor(Math.log10(line[test.col])) + 1) > (9 - catLine[4])) {
                    return line[test.col];
                }
            }
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
                arrayIds[id] = i;
            }
            break;
        case "itemk":
            if(line[test.col] > 0) {
                const idCat = line[test.col];
                const cateLine = contents["categorie"][retrieveIds("categorie", contents, ids)[idCat]];
                if(cateLine[6] != column) {
                    return idCat;
                }
            }
    }
    return "";
}
