<?php

/**
 * Logfile class represents a text file with the logs concerning running and sending bills
 */
class Logfile 
{

    /**
     * The text file name
     */
    const NAME = "logfile.log";

    /**
     * Extracts the text file content as a string
     *
     * @param string $dir directory where to find the text file
     * @return string
     */
    static function load(string $dir): string 
    {
        $logfile = "";
        $file = $dir."/".self::NAME;
        if ((file_exists($file)) && (($open = fopen($file, "r")) !== false)) {
            $logfile = fread($open, filesize($file));    
            fclose($open);
        }
        return $logfile;
    }
    
    /**
     * Writes additional string content in the text file
     *
     * @param string $dir directory where to find the text file to update
     * @param string $txt  content to be added
     * @return boolean true, or false if fwrite or fopen returns an error
     */
    static function write(string $dir, string $txt): bool 
    {
        $file = $dir."/".self::NAME;
        $content = self::load($dir);
        if((($open = fopen($file, "w")) !== false)) {
            if(fwrite($open, $txt.PHP_EOL.$content) === false) {                
                return false;
            }
            fclose($open);
            return true;
        }
        return false;
    }

}
