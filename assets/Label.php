<?php


class Label 
{
    const NAME = "/label.txt";

    function load(string $dir): string 
    {
        $label = "";
        $file = $dir.self::NAME;
        if ((file_exists($file)) && (($open = fopen($file, "r")) !== false)) {
            $label = fread($open, filesize($file));    
            fclose($open);
        }
        return $label;
    }
    
    function save(string $dir, string $txt): bool 
    {
        $file = $dir.self::NAME;
        if((($open = fopen($file, "w")) !== false)) {
            if(fwrite($open, $txt) === false) {                
                return false;
            }
            fclose($open);
            return true;
        }
        return false;
    }

    function remove(string $dir): bool
    {
        $file = $dir.self::NAME;
        return unlink($file);

    }

}
?>
