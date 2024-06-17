<?php
require_once("../commons/Zip.php");
require_once("../commons/Params.php");
require_once("../assets/Parametres.php");
require_once("../assets/Paramedit.php");
require_once("../assets/Paramtext.php");
require_once("../assets/Lock.php");
require_once("../config.php");
require_once("../session.php");

if(isset($_GET['type'])) {
    $type = $_GET['type'];
    $tmpFile = TEMP.$type.'.zip';
   
    if($type==="config") {
        readZip($tmpFile, CONFIG);
    }
    elseif($type==="prefa") {     
        $locku = new Lock();
        $fileName = $locku->loadByName("../".$sciper.".lock");
        if(!empty($fileName)) {   
            header('Content-disposition: attachment; filename="'.basename($fileName).'"');
            header('Content-type: application/zip');
            readfile($fileName);
            unlink($fileName);
            unlink("../".$sciper.".lock");
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
                $fileName = $dirMonth."/".Parametres::NAME;
                header('Content-disposition: attachment; filename="'.Parametres::NAME.'"');
                header('Content-type: application/zip');
                readfile($fileName);
            }
            else {
                if(isset($_GET['version']) && isset($_GET['run'])) {
                    $dirRun = $dirMonth."/".$_GET['version']."/".$_GET['run'];
                    if($type==="bilans") {
                        readZip($tmpFile, $dirRun."/Bilans_Stats/");
                    }
                    elseif($type==="annexes") {
                        readZip($tmpFile, $dirRun."/Annexes_CSV/");
                    }
                    elseif($type==="all") {
                        readZip($tmpFile, $dirRun."/");
                    }
                    elseif($type==="sap") {
                        readCsv($dirRun."/sap.csv");
                    }
                    elseif($type==="modif") {
                        if(isset($_GET['pre'])) {               
                            $name = $gestionnaire->getGestionnaire($_SESSION['user'])['plates'][$_GET['plate']];
                            $filename = $_GET['pre']."_".$name."_".$_GET['year']."_".$_GET['month']."_".$_GET['version'];
                            readCsv($dirRun."/".$filename.".csv");
                        }
                    }
                    elseif($type==="alltarifs") {
                        $res =Params::exportLast($tmpFile, $dirRun);
                        if(empty($res)) {
                            header('Content-disposition: attachment; filename="'.Parametres::NAME.'"');
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

function readCsv(string $fileName): void 
{
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.basename($fileName).'"');
    header('Content-Length: ' . filesize($fileName));
    readfile($fileName);

}

function readZip(string $tmpFile, string $dest): void
{
    $res = Zip::setZipDir($tmpFile, $dest, Lock::FILES['run']);
    if(empty($res)) {
        header('Content-disposition: attachment; filename="'.basename($tmpFile).'"');
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

?>
