<?php

require_once("Zip.php");
require_once("State.php");

class Config
{
    const FILES = ["gestionnaire.csv", "message.csv", "superviseur.csv"];

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
