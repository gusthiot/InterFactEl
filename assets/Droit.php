<?php

/**
 *
 */
class Droit
{

    /**
     * The json file name
     */
    const NAME = "droit.json";

    /**
     * Extracts the json file content in an encoded string
     *
     * @param string $dir directory where to find the json file
     * @return string
     */
    static function load(string $dir): string
    {
        $droits = "";
        $name = $dir."/".self::NAME;
        if((file_exists($name)) && (($open = fopen($name, "r")) !== false)) {
            $droits = fread($open, filesize($name));
            fclose($open);
        }
        return $droits;
    }

}
