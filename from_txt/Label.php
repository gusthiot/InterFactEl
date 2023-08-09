<?php


class Label {


    public $label;

    function __construct($dir) {
        $this->label = "";
        $file = $dir."/label.txt";
        if ((file_exists($file)) && (($open = fopen($file, "r")) !== false)) {
            $this->label = fread($open, filesize($file));    
            fclose($open);
        }
    }
    
    function getLabel() {
        return $this->label;
    }

}
?>
