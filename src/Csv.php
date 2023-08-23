<?php
class Csv {

    function extract($file) {
        $result = [];
        if ((file_exists($file)) && (($open = fopen($file, "r")) !== false)) {
            while (($data = fgetcsv($open, 1000, ",")) !== false) {
                if(mb_check_encoding($data[0], 'UTF-8')) {
                    $result[] = $data[0];

                }
                else {
                    $result[] = mb_convert_encoding($data[0], 'UTF-8', 'ISO-8859-2');
                }
            }
            fclose($open);
        }
        return $result;
    }

    function write($file, $array) {
        if (($open = fopen($file, "w")) !== false) {
            foreach($array as $row) {
                if(!fputcsv($open, $row,';')) {
                    break;
                }
            }
            fclose($open);
        }
    }
    
}
?>
