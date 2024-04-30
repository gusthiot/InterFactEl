<?php

require_once("../src/Plateforme.php");
require_once("../src/Label.php");
require_once("State.php");
require_once("Zip.php");

class Parametres
{
    const FILES = ["articlesap.csv", "categorie.csv", "categprix.csv", "classeclient.csv", "classeprestation.csv", "coeffprestation.csv", 
        "groupe.csv", "overhead.csv", "paramfact.csv", "paramtext.csv", "partenaire.csv", "plateforme.csv", "logo.pdf"];

    const NAME = "/parametres.zip";

    static function saveFirst(string $dir, string $dirTarifs): bool
    {
        $zip = new ZipArchive;
        $in = $dir."/IN/";
        if ($zip->open($dirTarifs.self::NAME, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
            foreach(self::FILES as $file) {
                $zip->addFile($in.$file, $file);
            }
            $plateforme = new Plateforme($in."plateforme.csv");
            $tab = explode("/", $dir);
            $file = $plateforme->getFile($tab[1]).".pdf";
            $zip->addFile($in.$file, $file);
            if($zip->close()) {
                $label = new Label();
                $label->save($dirTarifs, "New");
                return true;
            }
        }
        return false;
    }

    static function getFiles(string $plate): array
    {
        $tmpFile = TEMP."plateforme.csv";
        foreach(State::scanDescSan("../".$plate) as $year) {
            foreach(State::scanDescSan("../".$plate."/".$year) as $month) {
                $zipFile = "../".$plate."/".$year."/".$month."/parametres.zip";
                if (file_exists($zipFile)) {
                    $zip = new ZipArchive;
                    if ($zip->open($zipFile)) {
                        for($i = 0; $i < $zip->count(); $i++) {
                            $fileName = $zip->getNameIndex($i);
                            $fileInfo = pathinfo($fileName);
                            if($fileInfo['basename'] == "plateforme.csv") {
                                copy("zip://".$zipFile."#".$fileName, $tmpFile);
                            }
                        }
                        $zip->close();
                    }
                }
            }
        }
        $plateforme = new Plateforme($tmpFile);
        $priceFile = $plateforme->getFile($plate).".pdf";
        unlink($tmpFile);
        
        $ret = self::FILES;
        $ret[] = $priceFile;
        return $ret;
    }


    static function importNew(string $dirTarifs, string $file, string $plate): string
    {
        if (file_exists($dirTarifs) || mkdir($dirTarifs, 0777, true)) {
            $ret = " ";
            $tmpDir = TEMP.'tarifs/';
            if (file_exists($tmpDir) || mkdir($tmpDir, 0777, true)) {
                $msg = Zip::unzip($file, $tmpDir);
                if(empty($msg)) {
                    $zip = new ZipArchive;
                    if ($zip->open($dirTarifs.self::NAME, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
                        foreach(self::getFiles($plate) as $file) {
                            if(file_exists($tmpDir.$file)) {
                                $zip->addFile($tmpDir.$file, $file);
                            }
                        }
                        if($zip->close()) {
                            $msg = "Zip correctement sauvegardé !";
                            $label = new Label();
                            if(!$label->save($dirTarifs, "New")) {
                                $msg .= " problème avec le label";
                            }
                        }
                        else {
                            $msg .= " close error";
                        }
                    }
                    else {
                        $msg .= " open error";
                    }
                }
                State::delDir($tmpDir);
                return $msg;
            }
        }
        return error_get_last();
    }
}
