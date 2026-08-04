let mandatoryCsvs = {};

export const optionalCsvs = ["categprix"];
export const mandatoryPdfs = {"logo": {
                            name: "Logo PDF"
                        }
                    };
export const optionalPdfs = {"grille": {
                            name: "Grille PDF"
                        }
                    };

export function setMandatoryCsvs(parameters) {
    mandatoryCsvs = parameters;
}

export function getMandatoryCsvs() {
    return mandatoryCsvs;
}
/*
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
}*/

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

export function checkPlateFact(plateforme, messages, contents, optPdfs, verify) {
    let result = "";
    const names = ["paramfact", "plateforme"];
    names.forEach(function(filename) {
        let arrayIds = {};
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

export function checkColumns(fileTest, contents, pdfs, optPdfs, ids) {
    let result = "";
    let checks = {};
    Object.keys(mandatoryCsvs).forEach(function(filename) {
        checks[filename] = {};
        checks[filename].errors = {};
        if(result != "") {
            return;
        }
        if(mandatoryCsvs[filename].tests) {
            const results = fileTest.internalCheck(filename, contents[filename], contents, ids);
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
    Object.keys(mandatoryPdfs).forEach(function(filename) {
        checks[filename] = {};
        checks[filename].errors = {};
        if(result != "") {
            return;
        }
        if(pdfs[filename]) {
            checks[filename].ok = true;
            $('#'+filename).addClass('green-file');
        }
        else {
            checks[filename].ok = false;
            $('#'+filename).addClass('red-file');
        }
    });
    Object.keys(optionalPdfs).forEach(function(filename) {
        checks[filename] = {};
        checks[filename].errors = {};
        if(result != "") {
            return;
        }
        if(optPdfs[filename]) {
            checks[filename].ok = true;
            $('#'+filename).addClass('green-file');
        }
        else {
            checks[filename].ok = false;
            $('#'+filename).addClass('red-file');
        }
    });

    return {"result": result, "checks": checks, "ids": ids};
}
