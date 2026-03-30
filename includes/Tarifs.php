<?php

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
        if(file_exists($dirTarifs."/".ParamZip::NAME)){
            unlink($dirTarifs."/".ParamZip::NAME);
        }
        if(file_exists($dirTarifs."/".Label::NAME)){
            unlink($dirTarifs."/".Label::NAME);
        }
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
        if($zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
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
        if($zip->open($dest."/".ParamZip::NAME, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
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

    static function v0_exists($dirMonth)
    {
        if(file_exists($dirMonth."/0")) {
            foreach(globReverse($dirMonth."/0") as $dirRun) {
                $lockRun = Lock::load($dirRun, "run");
                if(is_null($lockRun) || $lockRun != "invalidate") {
                    return true;
                }
            }
        }
        return false;
    }

    static function status($dirMonth)
    {
        $status = 0;
        if(Unused::exists($dirMonth)) {
            $status += 1;
        }
        if(Tarifs::v0_exists($dirMonth)) {
            $status += 8;
            $dirVersion = globReverse($dirMonth)[0];
            if(Lock::exists($dirVersion, 'version')) {
                $status += 4;
            }
            if(floatval(basename($dirVersion)) > 0) {
                $status += 2;
            }
        }
        return $status;
    }

    static function label($dirMonth, $idem=false)
    {
        if(file_exists($dirMonth."/".ParamZip::NAME)) {
            return Label::load($dirMonth);
            if(empty($label)) {
                return "No label ?";
            }
            else {
                return $label;
            }
        }
        else {
            if($idem) {
                return "<i>Idem mois précédent</i>";
            }
            else {
                return "";
            }
        }
    }

    static function finalize($dirMonth)
    {
        if(Unused::exists($dirTarifs)) {

            $file = $dirTarifs."/newrates.csv";
            if((($open = fopen($file, "w")) !== false)) {
                fclose($open);
            }
            Unused::remove($dirTarifs);
        }
        if(file_exists($dirTarifs."/".ParamZip::NAME)){
            unlink($dirTarifs."/".ParamZip::NAME);
        }
    }

    static function warning9($dirMonth)
    {
        $messages = new Message();
        $unused = Unused::load($dirMonth);
        $version = Version::load('../');
        $vmin = $version["vi-min-controler"][2];
        if(floatval($unused) < floatval($vmin)) {
            return $messages->getMessage('msg9');
        }
        return "";
    }
}
