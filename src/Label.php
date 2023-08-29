<?php


class Label 
{

    function load(string $dir): string 
    {
        $label = "";
        $file = $dir."/label.txt";
        if ((file_exists($file)) && (($open = fopen($file, "r")) !== false)) {
            $label = fread($open, filesize($file));    
            fclose($open);
        }
        return $label;
    }
    
    function save(string $dir, string $txt): bool 
    {
        $file = $dir."/label.txt";
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
