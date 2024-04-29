<?php

require_once("../src/Plateforme.php");

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
        }
        if($zip->close()) {
            return true;
        }
        return false;
    }
}