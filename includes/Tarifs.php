<?php

require_once("../assets/Label.php");
require_once("../assets/ParamZip.php");
require_once("State.php");
require_once("Zip.php");

/**
 * Tarifs class allows to manage the parameters archive
 * - one different version can be kept by month
 * - excepting the first one, the archive doesn't need to contain all the needed files
 */
class Tarifs
{

    /**
     * The names of the authorized and needed parameters files
     */
    const FILES = ["articlesap.csv", "categorie.csv", "categprix.csv", "classeclient.csv", "classeprestation.csv", "coeffprestation.csv",
        "groupe.csv", "overhead.csv", "paramfact.csv", "paramtext.csv", "partenaire.csv", "plateforme.csv", "logo.pdf", "grille.pdf",
        "base.csv", "basecateg.csv", "categkitem.csv"];

    /**
     * Saves the first parameters archive, when it's the first finalized facturation
     *
     * @param string $dir directory containing the facturation parameters files
     * @param string $dirTarifs directory where to save the new created archive
     * @return string empty, or error
     */
    static function saveFirst(string $dir, string $dirTarifs): string
    {
        $in = $dir."/IN/";
        $msg = self::createZip($dirTarifs, $in);
        if(empty($msg)) {
            if(!Label::save($dirTarifs, "New")) {
                return "Problème avec le label";
            }
            return "";
        }
        return $msg;
    }

    /**
     * Suppresses an archive
     *
     * @param string $dirTarifs directory where to find the archive
     * @return void
     */
    static function suppress(string $dirTarifs): void
    {
        unlink($dirTarifs."/".ParamZip::NAME);
        unlink($dir."/".Label::NAME);
    }

    /**
     * Corrects an archive, by replacing some files by new given ones
     *
     * @param string $dirTarifs directory where to find the archive
     * @param string $file archive containing the new files
     * @return string empty, or error
     */
    static function correct(string $dirTarifs, string $file): string
    {
        $tmpDir = TEMP.'tarifs_'.time().'/';
        if (file_exists($tmpDir) || mkdir($tmpDir, 0777, true)) {
            $msg = Zip::unzip($dirTarifs."/".ParamZip::NAME, $tmpDir);
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

    /**
     * Saves a new archive
     *
     * @param string $dirTarifs directory where to save the archive
     * @param string $file archive containing the new files
     * @return string empty, or error
     */
    static function importNew(string $dirTarifs, string $file): string
    {
        if (file_exists($dirTarifs) || mkdir($dirTarifs, 0777, true)) {
            $tmpDir = TEMP.'tarifs_'.time().'/';
            if (file_exists($tmpDir) || mkdir($tmpDir, 0777, true)) {
                $msg = Zip::unzip($file, $tmpDir);
                if(empty($msg)) {
                    $msg = self::createZip($dirTarifs, $tmpDir);
                    if(empty($msg)) {
                        if(!Label::save($dirTarifs, "New")) {
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

    /**
     * Exports the last complete parameters archive used
     *
     * @param string $tmpFile temporary archive file, deleted after download
     * @param string $dir directory containing last used parameters
     * @return string empty, or error
     */
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

    /**
     * Creates a zip archive containing the parameters files
     *
     * @param string $dest directory to save the archive
     * @param string $from directory to find the parameters files
     * @return string empty, or error
     */
    static function createZip(string $dest, string $from): string
    {
        $zip = new ZipArchive;
        $empty = true;
        if ($zip->open($dest."/".ParamZip::NAME, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
            foreach(self::FILES as $file) {
                if(file_exists($from.$file)) {
                    $zip->addFile($from.$file, $file);
                    $empty = false;
                }
            }
            if($zip->close()) {
                if($empty) {
                    return "auncun fichier éligible dans ce zip !";
                }
                else {
                    return "";
                }
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
