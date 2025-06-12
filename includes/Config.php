<?php

/**
 * Config Class represents the config directory and its content
 */
class Config
{

    /**
     * Checks and saves uploaded files
     *
     * @param string $file zip archive file name
     * @return string error or empty string
     */
    static function upload(string $file): string
    {
        $tmpDir = TEMP.'config_'.time().'/';

        if (file_exists($tmpDir) || mkdir($tmpDir, 0777, true)) {
            $msg = Zip::unzip($file, $tmpDir);
            if(empty($msg)) {
                if(file_exists($tmpDir.Gestionnaire::NAME)) {
                    $msg .= self::checkColumns($tmpDir, Gestionnaire::NAME, 4, true);
                }
                if(file_exists($tmpDir.Superviseur::NAME)) {
                    $msg .= self::checkColumns($tmpDir, Superviseur::NAME, 1);
                }
                if(file_exists($tmpDir.Message::NAME)) {
                    $msg .= self::checkColumns($tmpDir, Message::NAME, 2, false, Message::LABELS);
                }
                if(file_exists($tmpDir.ParamText::NAME)) {
                    $msg .= self::checkColumns($tmpDir, ParamText::NAME, 2, false, ParamText::LABELS);
                }

            }
            State::delDir($tmpDir);
            return $msg;
        }
        return error_get_last();
    }

    static function checkColumns($tmpDir, $file, $nb, $rights=false, $labels="")
    {
        $lines = Csv::extract($tmpDir.$file);
        $msg = "";
        if(!empty($labels)) {
            $keys = [];
        }
        foreach($lines as $i=>$line) {
            $tab = explode(";", $line);
            if(count($tab) != $nb) {
                $msg .= "ligne ".$i." de ".$file." n'a pas le bon nombre de champs";
            }
            if($rights && !((-1 < intval($tab[3])) && (intval($tab[3]) < 8))) {
                $msg .= "les droits de la ligne ".$i." de ".$file." doivent être entre 0 et 7 inclus";
            }
            if(!empty($labels)) {
                if(in_array($tab[0], $keys)) {
                    $msg .= "le label de la ligne ".$i." de ".$file." n'est pas unique";
                }
                else {
                    $keys[] = $tab[0];
                }
            }
        }
        if(!empty($labels)) {
            foreach($labels as $label) {
                if(!in_array($label, $keys)) {
                    $msg .= "le label ".$label." est manquant dans ".$file;
                }
            }
            foreach($keys as $key) {
                if(!in_array($key, $labels)) {
                    $msg .= "le label ".$key." n'a rien a faire dans ".$file;
                }
            }
            
        }

        if(empty($msg)) {
            copy($tmpDir.$file, CONFIG.$file);
        }
        return $msg;
    }

}
