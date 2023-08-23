<?php


class Lock {


    public $lock;

    function load($dir) {
        $this->lock = "";
        $file = $dir."/lock.csv";
        if ((file_exists($file)) && (($open = fopen($file, "r")) !== false)) {
            $this->lock = fread($open, filesize($file));    
            fclose($open);
        }
        return $this->lock;
    }
    
    function save($dir, $txt) {
        $file = $dir."/lock.csv";
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
