<?php

require_once("../assets/Label.php");
require_once("State.php");
require_once("Zip.php");

class Parametres
{
    const FILES = ["articlesap.csv", "categorie.csv", "categprix.csv", "classeclient.csv", "classeprestation.csv", "coeffprestation.csv", 
        "groupe.csv", "overhead.csv", "paramfact.csv", "paramtext.csv", "partenaire.csv", "plateforme.csv", "logo.pdf", "grille.pdf"];

    const NAME = "/parametres.zip";

    static function saveFirst(string $dir, string $dirTarifs): bool
    {
        $zip = new ZipArchive;
        $in = $dir."/IN/";
        if ($zip->open($dirTarifs.self::NAME, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
            foreach(self::FILES as $file) {
                $zip->addFile($in.$file, $file);
            }
            if($zip->close()) {
                $label = new Label();
                $label->save($dirTarifs, "New");
                return true;
            }
        }
        return false;
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
                        foreach(self::FILES as $file) {
                            if(file_exists($tmpDir.$file)) {
                                $zip->addFile($tmpDir.$file, $file);
                            }
                        }
                        if($zip->close()) {
                            $msg = "";
                            $label = new Label();
                            if(!$label->save($dirTarifs, "New")) {
                                $msg = "Problème avec le label";
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
