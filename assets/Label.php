<?php

/**
 * Label class represents a text file with a label for a run or for parameters
 */
class Label
{

    /**
     * The text file name
     */
    const NAME = "label.txt";

    /**
     * Extracts the text file content as a string
     *
     * @param string $dir directory where to find the text file
     * @return string
     */
    static function load(string $dir): string
    {
        $label = "";
        $file = $dir."/".self::NAME;
        if ((file_exists($file)) && (($open = fopen($file, "r")) !== false)) {
            $label = fread($open, filesize($file));
            fclose($open);
        }
        return $label;
    }

    /**
     * Saves a string content to the text file by its location (remove old content)
     *
     * @param string $dir directory where to save the text file
     * @param string $txt content to be saved
     * @return boolean false if fopen returns an error, true otherwise
     */
    static function save(string $dir, string $txt): bool
    {
        $file = $dir."/".self::NAME;
        if((($open = fopen($file, "w")) !== false)) {
            if(fwrite($open, $txt) === false) {
                return false;
            }
            fclose($open);
            return true;
        }
        return false;
    }

    /**
     * Deletes the text file by its location
     *
     * @param string $dir directory where to delete the text file
     * @return boolean false if unlink returns an error, true otherwise
     */
    static function remove(string $dir): bool
    {
        $file = $dir."/".self::NAME;
        return unlink($file);
    }

}
