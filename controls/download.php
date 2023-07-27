<?php

if(isset($_GET['type'])) {
    $type = $_GET['type'];
    $tmp_file = '../tmp/'.$type.'.zip';
   
    if($type=="config") {
        zip($tmp_file, "../CONFIG/*");
    }
    if($type=="bilans") {
        if(isset($_GET['dir'])) {
            zip($tmp_file, "../".$_GET['dir']."/Bilans_Stats/*");
        }
    }
    if($type=="annexes") {
        if(isset($_GET['dir'])) {
            zip($tmp_file, "../".$_GET['dir']."/Annexes_CSV/*");
        }
    }
    if($type=="all") {
        if(isset($_GET['dir'])) {
            zip($tmp_file, "../".$_GET['dir']."/*");
        }
    }
    if($type=="sap") {
        if(isset($_GET['dir'])) {
            $filename = "../".$_GET['dir']."/sap.csv";
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.basename($filename).'"');
            header('Content-Length: ' . filesize($filename));
            readfile($filename);
        }
    }
}

function zip($tmp_file, $dir) {
    $zip = new ZipArchive;
    if ($zip->open($tmp_file, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
        $options = array('remove_all_path' => TRUE);
        $zip->addGlob($dir, GLOB_BRACE, $options);
        if($zip->close()) {
            header('Content-disposition: attachment; filename="'.basename($tmp_file).'"');
            header('Content-type: application/zip');
            readfile($tmp_file);
            ignore_user_abort(true);
            unlink($tmp_file);
        }
    }
}
?>
