<?php

/**
 * Zip class kind of extends ZipArchive capacities with some useful functions
 */
class Zip 
{

    /**
     * Creates a specfic and complex zip archive to be downladed
     *
     * @param string $tmpFile temporary created zip file, deleted after download
     * @param string $dirname directory containing what needs to be compressed
     * @param string $lockFileName lock file name we don't want to compress
     * @param string $morefile file name we want to add which is not in $dirname
     * @return string empty, or error
     */
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

    /**
     * Scans and adds recursively the files from subdirectories, and keeps directories structure
     *
     * @param ZipArchive $zip object representing the archive we are creating
     * @param string $dirname directory containing what needs to be compressed
     * @param string $treename keep the level we are processing
     * @param string $lockFileName lock file name we don't want to compress
     * @return void
     */
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

    /**
     * Checks uploaded zip archive and unzip it
     *
     * @param string $file uploaded archive
     * @param string $dest directory where to save archive content
     * @return string empty, or error
     */
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

    /**
     * Checks if an uploaded archive has an authorized type
     *
     * @param string $type archive type
     * @return boolean
     */
    static function isAccepted(string $type): bool 
    {
        $acceptedTypes = ['application/zip', 'application/x-zip-compressed', 'multipart/x-zip', 'application/x-compressed'];
        return in_array($type, $acceptedTypes);
    }
}
