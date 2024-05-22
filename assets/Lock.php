<?php


class Lock 
{
    const FILES = ['month'=>"/lockm.csv", 'version'=>"/lockv.csv", 'run'=>"/lock.csv", 'process'=>"/process.lock"];
    const STATES = ['finalized'=>"finalized", 'invalidate'=>"invalidate"];

    function load(string $dir, string $type): string 
    {
        $lock = "";
        if(array_key_exists($type, self::FILES)) {
            $file = $dir.self::FILES[$type];
        }
        else {
            $file = $dir.$type;
        }
        if ((file_exists($file)) && (($open = fopen($file, "r")) !== false)) {
            $lock = fread($open, filesize($file));    
            fclose($open);
            return $lock;
        }
        else {
            return false;
        }
    }
    
    function save(string $dir, string $type, string $txt): bool 
    {
        if(array_key_exists($type, self::FILES)) {
            $file = $dir.self::FILES[$type];
        }
        else {
            $file = $dir.$type;
        }
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
?>
