<?php

/**
 *
 */
class Config
{

    /**
     * The json file name
     */
    const NAME = "config.json";

    /**
     * Extracts the json file content in an encoded string
     *
     * @param string $dir directory where to find the json file
     * @return string
     */
    static function load(string $dir): string
    {
        $config = "";
        $name = $dir."/".self::NAME;
        if((file_exists($name)) && (($open = fopen($name, "r")) !== false)) {
            $config = fread($open, filesize($name));
            fclose($open);
        }
        return $config;
    }

}
