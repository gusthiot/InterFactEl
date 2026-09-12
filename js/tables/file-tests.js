'use strict';

export default class FileTests {

    constructor(messages, parametres, supervisor) {
        this.messages = messages;
        this.parametres = parametres;
        this.supervisor = supervisor;
        this.arrayIds = {};
    }

    internalCheck(filename, conTest, contents, ids, dimensions=[]) {
        let inIds = structuredClone(ids);
        let ok = true;
        let errors = {};
        if(conTest.length > 0) {
            for(let numTest in this.parametres[filename].tests) {
                const test = this.parametres[filename].tests[numTest];
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
                            ok = false;
                        }
                    }
                }
                if((test.type === "unique") && !(test.noindex)) {
                    inIds[filename] = this.arrayIds;
                }
                if(test.type === "self") {
                    if(!Object.keys(this.arrayIds).includes(this.supervisor)) {
                        ok = false;
                        for(let numRow = 0; numRow < len; numRow++) {
                            for(let col in colNum) {
                                if(!errors["row-"+numRow]) {
                                    errors["row-"+numRow] = {};
                                }
                                errors["row-"+numRow]["col-"+colNum[col]] = this.messages[filename + test.msg];
                            }
                        }
                    }
                }
            }
        }
        return {"ok": ok, "ids": inIds, "errors": errors};
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
                    if((Object.values(this.retrieveIds("categorie", contents, ids))).includes(line[test.col])) {
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
