<?php


class Label {


    public $label;

    function load($dir) {
        $this->label = "";
        $file = $dir."/label.txt";
        if ((file_exists($file)) && (($open = fopen($file, "r")) !== false)) {
            $this->label = fread($open, filesize($file));    
            fclose($open);
        }
        return $this->label;
    }
    
    function save($dir, $txt) {
        $file = $dir."/label.txt";
        if((($open = fopen($file, "w")) !== false)) {
            if(fwrite($open, $txt) === FALSE) {                
                return FALSE;
            }
            fclose($open);
            return TRUE;
        }
    }

}
?>
