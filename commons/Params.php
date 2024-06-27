<?php

require_once("../assets/Label.php");
require_once("../assets/Parametres.php");
require_once("State.php");
require_once("Zip.php");

class Params
{
    const FILES = ["articlesap.csv", "categorie.csv", "categprix.csv", "classeclient.csv", "classeprestation.csv", "coeffprestation.csv", 
        "groupe.csv", "overhead.csv", "paramfact.csv", "paramtext.csv", "partenaire.csv", "plateforme.csv", "logo.pdf", "grille.pdf"];

    static function saveFirst(string $dir, string $dirTarifs): string
    {
        $in = $dir."/IN/";
        $msg = self::createZip($dirTarifs, $in);
        if(empty($msg)) {
            $label = new Label();
            if(!$label->save($dirTarifs, "New")) {
                return "Problème avec le label";
            }
            return "";
        }
        return $msg;
    }

    static function suppress(string $dirTarifs): void
    {
        unlink($dirTarifs."/".Parametres::NAME);
        unlink($dir."/".Label::NAME);
    }

    static function correct(string $dirTarifs, string $file): string
    {
        $tmpDir = TEMP.'tarifs_'.time().'/';
        if (file_exists($tmpDir) || mkdir($tmpDir, 0777, true)) {
            $msg = Zip::unzip($dirTarifs."/".Parametres::NAME, $tmpDir);
            if(empty($msg)) {
                $msg = Zip::unzip($file, $tmpDir);
                if(empty($msg)) {
                    $msg = self::createZip($dirTarifs, $tmpDir);
                }
            }
            State::delDir($tmpDir);
            return $msg;
        }
        return error_get_last();
    }

    static function importNew(string $dirTarifs, string $file): string
    {
        if (file_exists($dirTarifs) || mkdir($dirTarifs, 0777, true)) {
            $tmpDir = TEMP.'tarifs_'.time().'/';
            if (file_exists($tmpDir) || mkdir($tmpDir, 0777, true)) {
                $msg = Zip::unzip($file, $tmpDir);
                if(empty($msg)) {
                    $msg = self::createZip($dirTarifs, $tmpDir);
                    if(empty($msg)) {
                        $label = new Label();
                        if(!$label->save($dirTarifs, "New")) {
                            $msg = "Problème avec le label";
                        }
                    }
                }
                State::delDir($tmpDir);
                return $msg;
            }
        }
        return error_get_last();
    }

    static function exportLast(string $tmpFile, string $dir): string 
    {
        $zip = new ZipArchive;
        if ($zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
            foreach(self::FILES as $file) {
                if(file_exists($dir."/IN/".$file)) {
                    $zip->addFile($dir."/IN/".$file, $file);
                }
            }
            if($zip->close()) {
                return "";
            }
            else {
                return "close error";
            }
        }
        else {
            return "open error";
        }
    }

    static function createZip(string $dest, string $from): string
    {
        $zip = new ZipArchive;
        if ($zip->open($dest."/".Parametres::NAME, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
            foreach(self::FILES as $file) {
                if(file_exists($from.$file)) {
                    $zip->addFile($from.$file, $file);
                }
            }
            if($zip->close()) {
                return "";
            }
            else {
                return "close error";
            }
        }
        else {
            return "open error";
        }
    }
}
