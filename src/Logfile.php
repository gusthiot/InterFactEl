<?php


class Logfile 
{
    const NAME = "/logfile.log";

    function load(string $dir): string 
    {
        $logfile = "";
        $file = $dir.self::NAME;
        if ((file_exists($file)) && (($open = fopen($file, "r")) !== false)) {
            $logfile = fread($open, filesize($file));    
            fclose($open);
        }
        return $logfile;
    }
    
    function write(string $dir, string $txt): bool 
    {
        $file = $dir.self::NAME;
        if((($open = fopen($file, "a")) !== false)) {
            if(fwrite($open, $txt.PHP_EOL) === false) {                
                return false;
            }
            fclose($open);
            return true;
        }
        return false;
    }

}
?>
