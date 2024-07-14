<?php

require_once("Zip.php");
require_once("State.php");

/**
 * Config Class represents the config directory and its content
 */
class Config
{
    /**
     * The names of the csv needed and accepted files
     */
    const FILES = ["gestionnaire.csv", "message.csv", "superviseur.csv"];

    /**
     * Checks and saves uploaded files
     *
     * @param string $file zip archive file name
     * @param string $dirConfig path to the config directory
     * @return string error or empty string
     */
    static function upload(string $file, string $dirConfig): string
    {
        $tmpDir = TEMP.'config_'.time().'/';

        if (file_exists($tmpDir) || mkdir($tmpDir, 0777, true)) {
            $msg = Zip::unzip($file, $tmpDir);
            if(empty($msg)) {
                foreach(self::FILES as $file) {
                    if(file_exists($tmpDir.$file)) {
                        copy($tmpDir.$file, $dirConfig.$file);
                    }
                }
            }
            State::delDir($tmpDir);
            return $msg;
        }
        return error_get_last();
    }

}
