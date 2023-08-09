<?php
class Zip {

    static function getZipDir($tmp_file, $dirname) {
        $zip = new ZipArchive;
        if ($zip->open($tmp_file, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
            tree($zip, $dirname, "");
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
            if(in_array($file, array('..', '.'))) {
                continue;
            }
            $path = $dirname.'/'.$file;
            $treepath = $treename ? ($treename.'/'.$file) : $file;
            if(is_file($path)) {
                $zip->addFile($path, $treepath);
            }
            if(is_dir($path)) {
                $zip->addEmptyDir($file);
                tree($zip, $path, $treepath);
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
            //$zip->extractTo($dest);
            $zip->close();
            if($ret=="") {
                return "success";
            }
            return $ret;
        }
        return "error";
    }

    static function isAccepted($type) {
        $accepted_types = array('application/zip', 'application/x-zip-compressed', 'multipart/x-zip', 'application/x-compressed');
        return in_array($type, $accepted_types);
    }
}
