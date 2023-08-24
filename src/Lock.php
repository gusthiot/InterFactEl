<?php


class Lock 
{

    function load(string $dir): string 
    {
        $lock = "";
        $file = $dir."/lock.csv";
        if ((file_exists($file)) && (($open = fopen($file, "r")) !== false)) {
            $lock = fread($open, filesize($file));    
            fclose($open);
        }
        return $lock;
    }
    
    function save(string $dir, string $txt): bool 
    {
        $file = $dir."/lock.csv";
        if((($open = fopen($file, "w")) !== false)) {
            if(fwrite($open, $txt) === false) {                
                return false;
            }
            fclose($open);
            return true;
        }
    }

}
?>
