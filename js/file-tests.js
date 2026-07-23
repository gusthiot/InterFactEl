export class FileTests {

    constructor(messages, parameters) {
        this.messages = messages;
        this.parameters = parameters;
        this.arrayIds = {};
    }

    internalCheck(filename, conTest, contents, ids) {
        let result = "";
        let errors = {};
        for(let test in this.parameters[filename].tests) {
            let resTest = "";
            let column = "";
            let colNum = [];
            for(let numRow = 0; numRow < conTest.length; numRow++) {
                if(numRow == 0) {
                    if(test.type == "unique") {
                        this.arrayIds = {};
                        test.id.forEach(function(col) {
                            if(column != "") {
                                column += " | ";
                            }
                            column += conTest[numRow][col];
                        });
                        colNum = test.id;
                    }
                    else {
                        column = conTest[numRow][test.col];
                        colNum = [test.col];
                    }
                }
                else {
                    const columns = this.parameters[filename].columns;
                    let error = this.switchTest(columns, test, conTest[numRow], numRow, column, contents, ids);
                    if(error != "") {
                        if(!errors["row-"+numRow]) {
                            errors["row-"+numRow] = {};
                        }
                        for(let col in colNum) {
                            errors["row-"+numRow]["col-"+col] = this.messages[filename + test.msg];
                        }
                        if(resTest == "") {
                            resTest += this.messages[filename + test.msg] + "<br />";
                            resTest += "Fichier : " + filename + ".csv<br />";
                            resTest += "Colonne : '" + column + "'<br />";
                        }
                        resTest += "Erreur ligne " + (numRow+1) + " : '" + error + "'<br />";
                    }
                }
            }
            if((test.type == "unique") && !(test.noindex)) {
                ids[filename] = this.arrayIds;
            }
            if(test.type == "should") {
                for(let id0 in Object.keys(retrieveIds(test.id[0], contents, ids))) {
                    for(let id1 in Object.keys(retrieveIds(test.id[1], contents, ids))) {
                        if(filename == "coeffprestation") {
                            const prestLine = contents["classeprestation"][retrieveIds("classeprestation", contents, ids)[id1]];
                            if(prestLine[3] != "OUI") {
                                return;
                            }
                        }
                        const id = id0 + "_" + id1;
                        if(!Object.keys(this.arrayIds).includes(id)) {
                            if(resTest == "") {
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
        return {"result": result, "ids": ids, "errors": errors};
    }

    retrieveIds(filename, contents, ids) {
        if(ids[filename]) {
            return ids[filename];
        }
        let pos = "";
        for(let test in this.parameters[filename].tests) {
            if((test.type == "unique") && !test.noindex) {
                pos = test.id;
            }
        }
        let aIds = {};
        for(let numRow = 0; numRow < contents[filename].length; numRow++) {
            if(numRow > 0 || this.parameters[filename].notitles) {
                let id = "";
                pos.forEach(function(col) {
                    if(id != "") {
                        id += "_";
                    }
                    id += contents[filename][numRow][col];
                });
                aIds[id] = numRow;
            }
        }
        return aIds;
    }

    switchTest(columns, test, line, i, column, contents, ids) {
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
                if(Object.keys(this.arrayIds).includes(id)) {
                    return id;
                }
                else {
                    this.arrayIds[id] = i;
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
}
