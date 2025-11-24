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
     * @param string $lessfile file name we don't want to compress
     * @param string $morefile file url we want to add which is not in $dirname
     * @return string empty, or error
     */
    static function setZipDir(string $tmpFile, string $dirname, string $lessfile="", string $morefile=""): string
    {
        $zip = new ZipArchive;
        $res = $zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($res === true) {
            self::tree($zip, $dirname, "", $lessfile);
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
     * @param string $lessfile file name we don't want to compress
     * @return void
     */
    static function tree(ZipArchive $zip, string $dirname, string $treename, string $lessfile=""): void
    {
        $dir = opendir($dirname);
        while($file = readdir($dir)) {
            $avoid = ['.', '..'];
            if(!empty($lessfile)) {
                $avoid[] = $lessfile;
            }
            if(in_array($file, $avoid)) {
                continue;
            }
            $path = $dirname.'/'.$file;
            $treepath = $treename ? ($treename.'/'.$file) : $file;
            if(is_file($path)) {
                $zip->addFile($path, $treepath);
            }
            if(is_dir($path)) {
                $zip->addEmptyDir($file);
                self::tree($zip, $path, $treepath, $lessfile);
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
            if(!$zip->extractTo($dest)) {
                $errors= error_get_last();
                return $errors['message'];
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

    /**
     * Gives the message for each upload error
     *
     * @param integer $error upload error
     * @return string
     */
    static function getErrorMessage(int $error): string
    {
        $phpFileUploadErrors = array(
            0 => 'There is no error, the file uploaded with success',
            1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
            2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
            3 => 'The uploaded file was only partially uploaded',
            4 => 'No file was uploaded',
            6 => 'Missing a temporary folder',
            7 => 'Failed to write file to disk.',
            8 => 'A PHP extension stopped the file upload.',
        );
        return $phpFileUploadErrors[$error];
    }
}
