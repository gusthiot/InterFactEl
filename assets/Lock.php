<?php

/**
 * Lock class represents a csv/lock file with specific data
 * - lockm.csv means that the month is locked, and stay empty
 * - lockv.csv means that the version is locked, and contains the last accepted run
 * - lock.csv means that the run is locked, and contains 'finalized' or 'invalidate'
 * - process.lock means that a process is running and that actions are restricted, 
 *      and contains 'send' (for sending to SAP) or 'prefa' (for running prefacuration), plateforme number and run number
 */
class Lock 
{
    /**
     * Names of the files
     */
    const FILES = ['month'=>"lockm.csv", 'version'=>"lockv.csv", 'run'=>"lock.csv", 'process'=>"process.lock"];
    
    /**
     * States for the run lock
     */
    const STATES = ['finalized'=>"finalized", 'invalidate'=>"invalidate"];

    /**
     * Extracts the file content as a string by the desired type of file
     *
     * @param string $dir directory where to find the file
     * @param string $type the type of lock file wanted (to get the file name)
     * @return string the content, or false if type doesn't exists or loadByName returns false
     */
    static function load(string $dir, string $type): string|null 
    {
        $lock = "";
        if(array_key_exists($type, self::FILES)) {
            if(file_exists($dir."/".self::FILES[$type])) {
                $loaded = self::loadByName($dir."/".self::FILES[$type]);
                if(!is_null($loaded)) {
                    return trim($loaded);
                }
            }
        }
        return null;
    }

    /**
     * Extracts the file content as a string by the file name
     *
     * @param string $file the file name
     * @return string the content, or false if fopen returns an error
     */
    static function loadByName(string $file): string|null
    {
        if ((file_exists($file)) && (filesize($file) > 0) && (($open = fopen($file, "r")) !== false)) {
            $lock = fread($open, filesize($file));    
            fclose($open);
            return trim($lock);
        }
        return null;
    }
    
    /**
     * Saves content to the file by its location and type
     *
     * @param string $dir directory where to save the text file
     * @param string $type the type of lock file wanted (to get the file name)
     * @param string $txt content to be saved
     * @return boolean true, or false if type doesn't exists or saveByName returns false
     */
    static function save(string $dir, string $type, string $txt): bool 
    {
        if(array_key_exists($type, self::FILES)) {
            return self::saveByName($dir."/".self::FILES[$type], $txt);
        }
        return false;
    }

    /**
     * Saves content to the file by its name
     *
     * @param string $file the file name
     * @param string $txt content to be saved
     * @return boolean true, or false if fopen/fwrite returns an error
     */
    static function saveByName(string $file, string $txt): bool
    {
        if((($open = fopen($file, "w")) !== false)) {
            if(fwrite($open, $txt) === false) {                
                return false;
            }
            fclose($open);
            return true;
        }
        return false;
    }

}
