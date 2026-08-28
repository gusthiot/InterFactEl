'use strict';

export default class FileTests {

    constructor(messages, parametres) {
        this.messages = messages;
        this.parametres = parametres;
        this.arrayIds = {};
    }

    internalCheck(filename, conTest, contents, ids, dimensions=[]) {
        let inIds = structuredClone(ids);
        let result = "";
        let errors = {};
        if(conTest.length > 0) {
            for(let numTest in this.parametres[filename].tests) {
                const test = this.parametres[filename].tests[numTest];
                let resTest = "";
                let column = "";
                let colNum = [];
                if(test.type === "unique") {
                    this.arrayIds = {};
                    for(let col of test.id) {
                        if(column != "") {
                            column += " | ";
                        }
                        column += conTest[0][col];
                    }
                    colNum = test.id;
                }
                else {
                    column = conTest[0][test.col];
                    colNum = [test.col];
                }
                let len = conTest.length;
                for(let numRow = 0; numRow < len; numRow++) {
                    if(numRow > 0 || this.parametres[filename].notitles) {
                        const columns = this.parametres[filename].columns;
                        let error = this.switchTest(columns, test, conTest[numRow], numRow, column, contents, ids);
                        if(error != "") {
                            let row = numRow;
                            if(dimensions.length > 0) {
                                row = Math.floor(numRow/dimensions[1]) + 1;
                            }
                            if(!errors["row-"+row]) {
                                errors["row-"+row] = {};
                            }
                            if(dimensions.length > 0) {
                                let col = numRow % dimensions[1] - 1;
                                errors["row-"+row]["col-"+col] = this.messages[filename + test.msg];
                            }
                            else {
                                for(let col in colNum) {
                                    errors["row-"+row]["col-"+colNum[col]] = this.messages[filename + test.msg];
                                }
                            }
                            if(resTest === "") {
                                resTest += this.messages[filename + test.msg] + "<br />";
                                resTest += "Fichier : " + filename + ".csv<br />";
                                resTest += "Colonne : '" + column + "'<br />";
                            }
                            resTest += "Erreur ligne " + (row) + " : '" + error + "'<br />";
                        }
                    }
                }
                if((test.type === "unique") && !(test.noindex)) {
                    inIds[filename] = this.arrayIds;
                }
                if(test.type === "should") {
                    for(let num0 in Object.keys(this.retrieveIds(test.id[0], contents, ids))) {
                        const id0 = Object.keys(this.retrieveIds(test.id[0], contents, ids))[num0];
                        for(let num1 in Object.keys(this.retrieveIds(test.id[1], contents, ids))) {
                            const id1 = Object.keys(this.retrieveIds(test.id[1], contents, ids))[num1];
                            if(filename === "coeffprestation") {
                                const prestLine = contents["classeprestation"][this.retrieveIds("classeprestation", contents, ids)[id1]];
                                if(prestLine[3] != "OUI") {
                                    continue;
                                }
                            }
                            const id = id0 + "_" + id1;
                            if(!Object.keys(this.arrayIds).includes(id)) {
                                if(resTest === "") {
                                    resTest += this.messages[filename + test.msg] + "<br />";
                                    resTest += "Fichier : " + filename + ".csv<br />";
                                    resTest += "Colonne : '" + column + "'<br />";
                                }
                                resTest += "Le couple '" + id0 + "' et '" + id1 + "' n'existe pas <br />";
                            }
                        }
                    }
                }
                result += resTest;
            }
        }
        return {"result": result, "ids": inIds, "errors": errors};
    }

    retrieveIds(filename, contents, ids) {
        if(ids[filename]) {
            return ids[filename];
        }
        let pos = "";
        for(let numTest in this.parametres[filename].tests) {
            const test = this.parametres[filename].tests[numTest];
            if((test.type === "unique") && !test.noindex) {
                pos = test.id;
            }
        }
        let aIds = {};
        let len = contents[filename].length;
        for(let numRow = 0; numRow < len; numRow++) {
            if(numRow > 0 || this.parametres[filename].notitles) {
                let id = "";
                for(let col of pos) {
                    if(id != "") {
                        id += "_";
                    }
                    id += contents[filename][numRow][col];
                }
                aIds[id] = numRow;
            }
        }
        return aIds;
    }

    switchTest(columns, test, line, numRow, column, contents, ids) {
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
                if(!((Object.keys(this.retrieveIds(columns[test.col].origin, contents, ids)).includes(line[test.col])) ||
                    (columns[test.col].zero && (line[test.col] === "0")))) {
                    return line[test.col];
                }
                break;
            case "ext":
                if((Object.keys(this.retrieveIds(columns[test.col].origin, contents, ids))).includes(line[test.col])) {
                    const extLine = contents[test.extName][this.retrieveIds(test.extName, contents, ids)[line[test.col]]];
                    if(extLine[test.extCol] != test.extValue) {
                        return line[test.col];
                    }
                }
                break;
            case "num":
                if(line[test.col] === "") {
                    return line[test.col];
                }
                let nb = Number(line[test.col]);
                if(Number.isNaN(nb)) {
                    return line[test.col];
                }
                if(columns[test.col].int && !Number.isInteger(nb)) {
                    return line[test.col];
                }
                if((nb < 0)) {
                    return line[test.col];
                }
                if(!columns[test.col].zero && (nb === 0)) {
                    return line[test.col];
                }
                if(columns[test.col].max && (nb > Number(columns[test.col].max))) {
                    return line[test.col];
                }
                if(test.special) {
                    if(!(Object.keys(this.retrieveIds("categorie", contents, ids))).includes(line[1])) {
                        return line[1];
                    }
                    const catLine = contents["categorie"][this.retrieveIds("categorie", contents, ids)[line[1]]];
                    if(Math.floor(Math.log10(nb) + 1) > (9 - catLine[4])) {
                        return line[test.col];
                    }
                }
                break;
            case "unique":
                let id = "";
                for(let col of test.id) {
                    if(id != "") {
                        id += "_";
                    }
                    id += line[col];
                }
                if(Object.keys(this.arrayIds).includes(id)) {
                    return id;
                }
                else {
                    this.arrayIds[id] = numRow;
                }
                break;
            case "itemk":
                if(line[test.col] != "0") {
                    if((Object.keys(this.retrieveIds("categorie", contents, ids))).includes(line[test.col])) {
                        const cateLine = contents["categorie"][this.retrieveIds("categorie", contents, ids)[line[test.col]]];
                        if(cateLine[6] != column) {
                            return line[test.col];
                        }
                    }
                }
                break;
            case "id0":
                if(line[test.col] == "0") {
                    return line[test.col];
                }
                break;
        }
        return "";
    }
}
