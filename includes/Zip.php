<?php

class Zip 
{

    static function setZipDir(string $tmpFile, string $dirname, string $lockFileName, string $morefile=""): string 
    {
        $zip = new ZipArchive;
        $res = $zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($res === true) {
            self::tree($zip, $dirname, "", $lockFileName);
            if(!empty($morefile)) {
                $zip->addFile($morefile, basename($morefile));
            }
            if(!$zip->close()) {
                return "error to close zip";
            }
            return "";
        }
        else {
            return $res;
        }
    }

    static function tree(ZipArchive $zip, string $dirname, string $treename, string $lockFileName): void
    {
        $dir = opendir($dirname);
        while($file = readdir($dir)) {
            if(in_array($file, ['.', '..', $lockFileName])) {
                continue;
            }
            $path = $dirname.'/'.$file;
            $treepath = $treename ? ($treename.'/'.$file) : $file;
            if(is_file($path)) {
                $zip->addFile($path, $treepath);
            }
            if(is_dir($path)) {
                $zip->addEmptyDir($file);
                self::tree($zip, $path, $treepath, $lockFileName);
            }
        }
        closedir($dir);
    }

    static function unzip(string $file, string $dest): string 
    {
        $zip = new ZipArchive;
        $res = $zip->open($file);
        if ($res === true) {
            for($i = 0; $i < $zip->count(); $i++) {
                $fileName = $zip->getNameIndex($i);
                $fileInfo = pathinfo($fileName);
                if(array_key_exists('extension', $fileInfo)) {
                    if(!copy("zip://".$file."#".$fileName, $dest.$fileInfo['basename'])) {
                        $errors= error_get_last();
                        return $errors['message'];
                    }
                }
            }
            if(!$zip->close()) {
                return "error to close zip";
            }
            return "";
        }
        else {
            return $res;
        }
    }

    static function isAccepted(string $type): bool 
    {
        $acceptedTypes = ['application/zip', 'application/x-zip-compressed', 'multipart/x-zip', 'application/x-compressed'];
        return in_array($type, $acceptedTypes);
    }
}
