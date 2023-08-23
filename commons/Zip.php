<?php

require_once("Data.php");

class Zip {

    static function getZipDir($tmp_file, $dirname, $morefile="") {
        $zip = new ZipArchive;
        if ($zip->open($tmp_file, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
            self::tree($zip, $dirname, "");
            if($morefile != "") {
                $zip->addFile($morefile, basename($morefile));
            }
            if($zip->close()) {
                header('Content-disposition: attachment; filename="'.basename($tmp_file).'"');
                header('Content-type: application/zip');
                readfile($tmp_file);
                ignore_user_abort(true);
                unlink($tmp_file);
            }
        }
    }

    static function tree($zip, $dirname, $treename) {
        $dir = opendir($dirname);
        while($file = readdir($dir)) {
            if(in_array($file, Data::$escaped)) {
                continue;
            }
            $path = $dirname.'/'.$file;
            $treepath = $treename ? ($treename.'/'.$file) : $file;
            if(is_file($path)) {
                $zip->addFile($path, $treepath);
            }
            if(is_dir($path)) {
                $zip->addEmptyDir($file);
                self::tree($zip, $path, $treepath);
            }
        }
        closedir($dir);
    }

    static function unzip($file, $dest) {
        $zip = new ZipArchive;
        if ($zip->open($file)) {
            $ret = "";
            for($i = 0; $i < $zip->count(); $i++) {
                $filename = $zip->getNameIndex($i);
                $fileinfo = pathinfo($filename);
                if($fileinfo['extension'] != "") {
                    if(!copy("zip://".$file."#".$filename, $dest.$fileinfo['basename'])) {
                        $errors= error_get_last();
                        $ret .= $errors['message'];
                    }
                }
            }                   
            $zip->close();
            return $ret;
        }
        return "error";
    }

    static function isAccepted($type) {
        $accepted_types = ['application/zip', 'application/x-zip-compressed', 'multipart/x-zip', 'application/x-compressed'];
        return in_array($type, $accepted_types);
    }
}
