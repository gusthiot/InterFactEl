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
?>
