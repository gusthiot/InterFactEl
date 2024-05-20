<?php

require_once("State.php");

class Zip 
{

    static function getZipDir(string $tmpFile, string $dirname, string $morefile=""): void 
    {
        $zip = new ZipArchive;
        if ($zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
            self::tree($zip, $dirname, "");
            if(!empty($morefile)) {
                $zip->addFile($morefile, basename($morefile));
            }
            if($zip->close()) {
                header('Content-disposition: attachment; filename="'.basename($tmpFile).'"');
                header('Content-type: application/zip');
                readfile($tmpFile);
                ignore_user_abort(true);
                unlink($tmpFile);
            }
        }
    }

    static function setZipDir(string $tmpFile, string $dirname, string $morefile=""): void 
    {
        $zip = new ZipArchive;
        if ($zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
            self::tree($zip, $dirname, "");
            if(!empty($morefile)) {
                $zip->addFile($morefile, basename($morefile));
            }
            $zip->close();
        }
    }

    static function tree(ZipArchive $zip, string $dirname, string $treename): void 
    {
        $dir = opendir($dirname);
        while($file = readdir($dir)) {
            if(in_array($file, State::ESCAPED)) {
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

    static function unzip(string $file, string $dest): string 
    {
        $zip = new ZipArchive;
        if ($zip->open($file)) {
            $ret = "";
            for($i = 0; $i < $zip->count(); $i++) {
                $fileName = $zip->getNameIndex($i);
                $fileInfo = pathinfo($fileName);
                if(array_key_exists('extension', $fileInfo)) {
                    if(!copy("zip://".$file."#".$fileName, $dest.$fileInfo['basename'])) {
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

    static function isAccepted(string $type): bool 
    {
        $acceptedTypes = ['application/zip', 'application/x-zip-compressed', 'multipart/x-zip', 'application/x-compressed'];
        return in_array($type, $acceptedTypes);
    }
}
