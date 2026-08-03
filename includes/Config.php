<?php

/**
 * Config class represents the config directory and its content
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

        if(file_exists($tmpDir) || mkdir($tmpDir, 0777, true)) {
            $msg = Zip::unzip($file, $tmpDir);
            if(empty($msg)) {
                if(file_exists($tmpDir.Gestionnaire::NAME)) {
                    self::addMsg($msg, self::checkColumns($tmpDir, Gestionnaire::NAME, 4, true, true, [], true));
                }
                if(file_exists($tmpDir.Superviseur::NAME)) {
                    self::addMsg($msg, self::checkColumns($tmpDir, Superviseur::NAME, 1));
                }
                if(file_exists($tmpDir.Message::NAME)) {
                    self::addMsg($msg, self::checkColumns($tmpDir, Message::NAME, 2, false, false, Message::LABELS));
                }
                if(file_exists($tmpDir.ParamText::NAME)) {
                    self::addMsg($msg, self::checkColumns($tmpDir, ParamText::NAME, 2, false, false, ParamText::LABELS));
                }
                if(file_exists($tmpDir.Plateforme::NAME)) {
                    self::addMsg($msg, self::checkColumns($tmpDir, Plateforme::NAME, 5));
                }
                if(file_exists($tmpDir.Personnel::NAME)) {
                    self::addMsg($msg, self::checkColumns($tmpDir, Personnel::NAME, 4));
                }
            }
            State::delDir($tmpDir);
            return $msg;
        }
        return error_get_last();
    }

    /**
     * Checks the csv files columns number
     *
     * @param string $tmpDir directory were to temporary find the new config files
     * @param string $file file name
     * @param integer $nb the expected number of columns
     * @param bool $header if csv file has header
     * @param boolean $rights if we also want to check the rights
     * @param array $labels if we also want to check if all the labels exists, and only the expected ones
     * @param bool $order if we want to check tiles order column
     * @return string error or empty string
     */
    static function checkColumns(string $tmpDir, string $file, int $nb, bool $header=true, bool $rights=false, array $labels=[], bool $order=false): string
    {
        $lines = Csv::extract($tmpDir.$file, $header);
        $msg = "";
        if(!empty($labels)) {
            $keys = [];
        }
        if($order) {
            $tilesOrder = [];
        }
        foreach($lines as $i=>$line) {
            if(count($line) != $nb) {
                self::addMsg($msg, "ligne ".$i." de ".$file." n'a pas le bon nombre de champs");
            }
            if($rights && !(is_numeric($line[3]) && (-1 < intval($line[3])) && (intval($line[3]) < 8))) {
                self::addMsg($msg, "les droits de la ligne ".$i." de ".$file." doivent être un nombre entier entre 0 et 7 inclus");
            }
            if(!empty($labels)) {
                if(in_array($line[0], $keys)) {
                    self::addMsg($msg, "le label de la ligne ".$i." de ".$file." n'est pas unique");
                }
                else {
                    $keys[] = $line[0];
                }
            }
            if($order) {
                if(!(is_numeric($line[3]) && (0 < intval($line[3])))) {
                    self::addMsg($msg, "l'ordre de tuile' de la ligne ".$i." de ".$file." doit être un nombre entier > 0");
                }
                $couple = $line[0]."-".$line[3];
                if(in_array($couple, $tilesOrder)) {
                    self::addMsg($msg, "le couple 'login - ordre de tuile' de la ligne ".$i." de ".$file." n'est pas unique");
                }
                else {
                    $tilesOrder[] = $couple;
                }
            }
        }
        if(!empty($labels)) {
            foreach($labels as $label) {
                if(!in_array($label, $keys)) {
                    self::addMsg($msg, "le label ".$label." est manquant dans ".$file);
                }
            }
            foreach($keys as $key) {
                if(!in_array($key, $labels)) {
                    self::addMsg($msg, "le label ".$key." n'a rien a faire dans ".$file);
                }
            }

        }

        if(empty($msg)) {
            copy($tmpDir.$file, CONFIG.$file);
        }
        return $msg;
    }

    static function addMsg(string &$msg, string $addendum): void
    {
        if($msg != "") {
            $msg .= "<br/>";
        }
        $msg .= $addendum;
    }

}
