'use strict';

export default class TablesTests {

    constructor(parameters) {
        this.mandatoryCsvs = parameters.mandatoryCsvs;
        this.mandatoryPdfs = parameters.mandatoryPdfs;
        this.optionalPdfs = parameters.optionalPdfs;
    }

    checkAuthorized(contents, pdfs, optPdfs) {
        let polluting = [];
        for(let filename in contents) {
            if(!Object.keys(this.mandatoryCsvs).includes(filename)) {
                polluting.push(filename+".csv");
            }
        }
        for(let filename in pdfs) {
            if(!Object.keys(this.mandatoryPdfs).includes(filename)) {
                polluting.push(filename+".pdf");
            }
        }
        for(let filename in optPdfs) {
            if(!Object.keys(this.optionalPdfs).includes(filename)) {
                polluting.push(filename+".pdf");
            }
        }
        if(polluting.length > 0) {
            let list = "";
            for(let num = 0; num < polluting.length; num++) {
                if(num > 0) {
                    list += ", ";
                }
                list += polluting[num];
            }
            let verbe = "sera";
            if(num > 1) {
                verbe = "seront";
            }
            return '" ' + list +'" ne ' + verbe + ' pas pris en compte';
        }
        return "";
    }

    checkColumnsNumbers(contents) {
        let result = "";
        for(let filename in this.mandatoryCsvs) {
            $('#'+filename).removeClass('red-file');
            $('#'+filename).removeClass('green-file');
            const number = this.mandatoryCsvs[filename].numcol;
            for(let num = 0; num < contents[filename].length; num++) {
                const line = contents[filename][num];
                if(number != line.length) {
                    result += "la ligne " + (num + 1) + " du fichier " + filename + ".csv contient " + line.length + " colonnes au lieu de " + number + "<br />";
                    $('#'+filename).addClass('red-file');
                }
            }
        }
        return result;
    }

    checkPlateFact(plateforme, messages, contents, optPdfs) {
        let result = "";
        const names = ["paramfact", "plateforme"];
        for(let filename of names) {
            let arrayIds = {};
            for(let num = 0; num < contents[filename].length; num++) {
                const line = contents[filename][num];
                if(!Object.keys(arrayIds).includes(line[0])) {
                    arrayIds[line[0]] = num;
                }
                else {
                    result += "le label '" + line[0] + "' est présent plus d'une fois dans  " + filename + ".csv <br />";
                }
                if(filename == "plateforme") {
                    if(line[0] == this.mandatoryCsvs[filename].labels[0]) {
                        if(line[2] != plateforme) {
                            result += messages["plateforme01"] + " <br />";
                        }
                    }
                    if(line[0] == this.mandatoryCsvs[filename].labels[7]) {
                        if(!["OUI", "NON"].includes(line[2])) {
                            result += messages["plateforme02"] + " <br />";
                        }
                        if(line[2] == "OUI" && !Object.keys(optPdfs).includes("grille")) {
                            result += messages["grille01"] + " <br />";
                        }
                    }
                }
            }
            if(Object.keys(arrayIds).length != this.mandatoryCsvs[filename].labels.length) {
                result += "le fichier " + filename + " doit contenir " + this.mandatoryCsvs[filename].labels.length + " étiquettes <br />";
            }
            for(let label of this.mandatoryCsvs[filename].labels) {
                if(!Object.keys(arrayIds).includes(label)) {
                    result += "le fichier " + filename + " doit contenir l'étiquette : '" + label + "' <br />";
                }
            }
        }
        return result;
    }

    checkColumns(fileTest, contents, pdfs, optPdfs, ids, messages) {
        let result = "";
        let checks = {};
        for(let filename in this.mandatoryCsvs) {
            checks[filename] = {};
            checks[filename].errors = {};
            if(result != "") {
                return;
            }
            if(this.mandatoryCsvs[filename].tests) {
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
        }
        for(let filename in this.mandatoryPdfs) {
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
        }
        for(let filename in this.optionalPdfs) {
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
        }
        return {"result": result, "checks": checks, "ids": ids};
    }
}
