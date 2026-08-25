'use strict';

export default class TablesTests {

    constructor(parameters) {
        if(parameters.mandatoryCsvs) {
            this.mandatoryCsvs = parameters.mandatoryCsvs;
        }
        else {
            this.mandatoryCsvs = {};
        }
        if(parameters.mandatoryPdfs) {
            this.mandatoryPdfs = parameters.mandatoryPdfs;
        }
        else {
            this.mandatoryPdfs = {};
        }
        if(parameters.optionalPdfs) {
            this.optionalPdfs = parameters.optionalPdfs;
        }
        else {
            this.optionalPdfs = {};
        }
    }

    checkAuthorized(files) {
        let polluting = [];
        for(let filename in files) {
            const name = filename.split('.')[0];
            if(Object.keys(this.mandatoryCsvs).includes(name)) {
                continue;
            }
            if(Object.keys(this.mandatoryPdfs).includes(name)) {
                continue;
            }
            if(Object.keys(this.optionalPdfs).includes(name)) {
                continue;
            }
            polluting.push(filename);
        }
        if(polluting.length > 0) {
            let list = "";
            let num = 0;
            for(; num < polluting.length; num++) {
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
            let number = this.mandatoryCsvs[filename].numcol;
            if(this.mandatoryCsvs[filename].numcolfile) {
                number = this.mandatoryCsvs[filename].numcolfile;
            }
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
                if(filename === "plateforme") {
                    if(line[0] === this.mandatoryCsvs[filename].labels[0]) {
                        if(line[2] != plateforme) {
                            result += messages["plateforme01"] + " <br />";
                        }
                    }
                    if(line[0] === this.mandatoryCsvs[filename].labels[7]) {
                        if(!["OUI", "NON"].includes(line[2])) {
                            result += messages["plateforme02"] + " <br />";
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
        let ret = {"result": "", "checks": {}, "ids": {}};
        let checks = {};
        for(let filename in this.mandatoryCsvs) {
            ret.checks[filename] = {};
            ret.checks[filename].errors = {};
            if(ret.result != "") {
                return ret;
            }
            if(this.mandatoryCsvs[filename].tests) {
                let dimensions = [];
                if(this.mandatoryCsvs[filename].bidim) {
                    dimensions = [contents[this.mandatoryCsvs[filename].columns[0].origin].length-1,
                                contents[this.mandatoryCsvs[filename].columns[1].origin].length-1];
                }
                const results = fileTest.internalCheck(filename, contents[filename], contents, ids, dimensions);
                ret.result += results.result;
                ret.ids = results.ids;
                ret.checks[filename].errors = results.errors;
            }
            if(ret.result != "") {
                ret.checks[filename].ok = false;
                $('#'+filename).addClass('red-file');
            }
            else {
                ret.checks[filename].ok = true;
                $('#'+filename).addClass('green-file');
            }
        }
        for(let filename in this.mandatoryPdfs) {
            ret.checks[filename] = {};
            ret.checks[filename].errors = {};
            if(ret.result != "") {
                return ret;
            }
            if(pdfs[filename]) {
                ret.checks[filename].ok = true;
                $('#'+filename).addClass('green-file');
            }
            else {
                ret.checks[filename].ok = false;
                $('#'+filename).addClass('red-file');
                ret.result += messages[filename + "01"] + "<br />";
            }
        }
        for(let filename in this.optionalPdfs) {
            ret.checks[filename] = {};
            ret.checks[filename].errors = {};
            if(ret.result != "") {
                return ret;
            }
            const cond = contents[this.optionalPdfs[filename].test.origin][7][2];
            if(optPdfs[filename]) {
                if(cond === "OUI") {
                    ret.checks[filename].ok = true;
                    $('#'+filename).addClass('green-file');
                }
                else {
                    ret.checks[filename].ok = false;
                    $('#'+filename).addClass('red-file');
                    ret.result += messages[filename + "01"] + "<br />";
                }
            }
            else {
                if(cond === "NON") {
                    ret.checks[filename].ok = true;
                    $('#'+filename).addClass('green-file');
                }
                else {
                    ret.checks[filename].ok = false;
                    $('#'+filename).addClass('red-file');
                    ret.result += messages[filename + "02"] + "<br />";

                }
            }
        }
        return ret;
    }
}
