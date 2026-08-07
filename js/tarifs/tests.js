let mandatoryCsvs = {};

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

export function checkAuthorized(contents, pdfs, optPdfs) {
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
        let verbe = "sera";
        if(num > 1) {
            verbe = "seront";
        }
        return '" ' + list +'" ne ' + verbe + ' pas pris en compte';
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

export function checkPlateFact(plateforme, messages, contents, optPdfs) {
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
                        result += messages["plateforme01"] + " <br />";
                    }
                }
                if(line[0] == mandatoryCsvs[filename].labels[7]) {
                    if(!["OUI", "NON"].includes(line[2])) {
                        result += messages["plateforme02"] + " <br />";
                    }
                    if(line[2] == "OUI" && !Object.keys(optPdfs).includes("grille")) {
                        result += messages["grille01"] + " <br />";
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

export function checkColumns(fileTest, contents, pdfs, optPdfs, ids, messages) {
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
