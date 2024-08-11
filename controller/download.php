<?php

require_once("../assets/ParamZip.php");
require_once("../assets/Lock.php");
require_once("../includes/Zip.php");
require_once("../includes/Tarifs.php");
require_once("../session.inc");

/**
 * Called to download whatever is available to be downloaded
 */
if(isset($_GET['type'])) {
    $type = $_GET['type'];
    $tmpFile = TEMP.$type.'_'.time().'.zip';
   
    if($type==="config") {
        // config zip, only for supervisor
        if(!$superviseur->isSuperviseur($user)) {
            header('Location: ../index.php');
            exit;
        }
        readZip($type, $tmpFile, CONFIG);
    }
    elseif($type==="prefa") {
        // prefacturation, only for the user running it
        $fileName = Lock::loadByName("../".$user.".lock");
        if(!empty($fileName)) {   
            header('Content-disposition: attachment; filename="'.basename($fileName).'"');
            header('Content-type: application/zip');
            readfile($fileName);
            unlink($fileName);
            unlink("../".$user.".lock");
        }
        else {
            $_SESSION['alert-danger'] = "ce fichier n'est plus disponible";
            header('Location: ../index.php');
        }
    }
    else {
        if(isset($_GET['plate']) && isset($_GET['year']) && isset($_GET['month'])) {
            $dirMonth = DATA.$_GET['plate']."/".$_GET['year']."/".$_GET['month'];
            if($type==="tarifs") { 
                // tarifs uploaded for a given month 
                checkPlateforme($dataGest, "tarifs", $_GET['plate']);
                $fileName = $dirMonth."/".ParamZip::NAME;
                header('Content-disposition: attachment; filename="'.ParamZip::NAME.'"');
                header('Content-type: application/zip');
                readfile($fileName);
            }
            else {
                checkPlateforme($dataGest, "facturation", $_GET['plate']);
                if(isset($_GET['version']) && isset($_GET['run'])) {
                    $dirRun = $dirMonth."/".$_GET['version']."/".$_GET['run'];
                    if($type==="bilans") {
                        // bilans of a run
                        readZip($type, $tmpFile, $dirRun."/Bilans_Stats/");
                    }
                    elseif($type==="annexes") {
                        // annexes of a run
                        readZip($type, $tmpFile, $dirRun."/Annexes_CSV/");
                    }
                    elseif($type==="all") {
                        // all files of a run
                        readZip($type, $tmpFile, $dirRun."/");
                    }
                    elseif($type==="sap") {
                        // bills list of a run
                        readCsv($dirRun."/sap.csv");
                    }
                    elseif($type==="modif") {
                        // modification file (journal, client, modifications) of a run
                        if(isset($_GET['pre'])) {               
                            $name = $dataGest['facturation'][$_GET['plate']];
                            $filename = $_GET['pre']."_".$name."_".$_GET['year']."_".$_GET['month']."_".$_GET['version'];
                            readCsv($dirRun."/".$filename.".csv");
                        }
                    }
                    elseif($type==="ticketcsv") {
                        // annexes csv of a run
                        if(isset($_GET['nom'])) { 
                            $fileName =  $dirRun."/Annexes_CSV/".$_GET['nom'];
                            header('Content-type: application/zip');
                            header('Content-Disposition: attachment; filename="'.$_GET['nom'].'"');
                            readfile($fileName);
                        }
                    }
                    elseif($type==="ticketpdf") {
                        // annexes pdf of a run
                        if(isset($_GET['nom'])) { 
                            $fileName =  $dirRun."/Annexes_PDF/".$_GET['nom'];
                            header('Content-Type: application/octet-stream');
                            header('Content-Disposition: attachment; filename="'.$_GET['nom'].'"');
                            header('Content-Length: ' . filesize($fileName));
                            readfile($fileName);
                        }
                    }
                    elseif($type==="alltarifs") {
                        // all tarifs files used for a month
                        $res =Tarifs::exportLast($tmpFile, $dirRun);
                        if(empty($res)) {
                            header('Content-disposition: attachment; filename="'.ParamZip::NAME.'"');
                            header('Content-type: application/zip');
                            readfile($tmpFile);
                            ignore_user_abort(true);
                            unlink($tmpFile);
                        }
                        else {
                            unlink($tmpFile);
                            $_SESSION['alert-danger'] = $res;
                            header('Location: ../index.php');
                        }
                    }
                    else {
                        $_SESSION['alert-danger'] = "erreur download";
                        header('Location: ../index.php');
                    }
                }
                else {
                    $_SESSION['alert-danger'] = "erreur download";
                    header('Location: ../index.php');
                }
            }
        }
        elseif(isset($_GET['unique'])) {
            if($type==="ticketcsv") {
                // annexes csv of a run
                if(isset($_GET['nom'])) { 
                    $fileName =  TEMP.$_GET['unique']."/Annexes_CSV/".$_GET['nom'];
                    header('Content-type: application/zip');
                    header('Content-Disposition: attachment; filename="'.$_GET['nom'].'"');
                    readfile($fileName);
                }
            }
            elseif($type==="ticketpdf") {
                // annexes pdf of a run
                if(isset($_GET['nom'])) { 
                    $fileName =  TEMP.$_GET['unique']."/Annexes_PDF/".$_GET['nom'];
                    header('Content-Type: application/octet-stream');
                    header('Content-Disposition: attachment; filename="'.$_GET['nom'].'"');
                    header('Content-Length: ' . filesize($fileName));
                    readfile($fileName);
                }
            }
        }
        else {
            $_SESSION['alert-danger'] = "erreur download";
            header('Location: ../index.php');
        }
    }
}
else {
    $_SESSION['alert-danger'] = "erreur download";
    header('Location: ../index.php');
}

/**
 * Provides a csv file to be downloaded
 *
 * @param string $fileName csv file name
 * @return void
 */
function readCsv(string $fileName): void 
{
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.basename($fileName).'"');
    header('Content-Length: ' . filesize($fileName));
    readfile($fileName);
}

/**
 * Provides a zip archive to be downloaded
 *
 * @param string $name zip archive name
 * @param string $tmpFile temporary zip archive file, will be deleted after download
 * @param string $dir directory that will be compressed
 * @return void
 */
function readZip(string $name, string $tmpFile, string $dir): void
{
    $res = Zip::setZipDir($tmpFile, $dir, Lock::FILES['run']);
    if(empty($res)) {
        header('Content-disposition: attachment; filename="'.$name.'.zip"');
        header('Content-type: application/zip');
        readfile($tmpFile);
        ignore_user_abort(true);
        unlink($tmpFile);
    }
    else {
        unlink($tmpFile);
        $_SESSION['alert-danger'] = $res;
        header('Location: ../index.php');
    }
}

