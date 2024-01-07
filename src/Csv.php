<?php
class Csv 
{

    function extract(string $file): array 
    {
        $result = [];
        if ((file_exists($file)) && (($open = fopen($file, "r")) !== false)) {
            while (($data = fgetcsv($open, 1000, ",")) !== false) {
                if(mb_check_encoding($data[0], 'UTF-8')) {
                    $result[] = $data[0];

                }
                else {
                    $result[] = mb_convert_encoding($data[0], 'UTF-8', 'Windows-1252');
                }
            }
            fclose($open);
        }
        return $result;
    }

    function write(string $file, array $array): void 
    {
        if (($open = fopen($file, "w")) !== false) {
            foreach($array as $row) {
                $row = str_replace('"', '', $row);
                if(mb_check_encoding($row, 'UTF-8')) {
                    $row = mb_convert_encoding($row, 'Windows-1252', 'UTF-8');
                }
                if(!fputcsv($open, $row,';')) {
                    break;
                }
            }
            fclose($open);
        }
    }
    
}
?>
