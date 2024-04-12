<?php
require_once("session.php");
require_once("commons/State.php");
require_once("src/Label.php");
require_once("src/Sap.php");
require_once("src/Lock.php");
if(!isset($_GET["plateforme"])) {
    die("Manque un numéro de plateforme !");
}
$plateforme = $_GET['plateforme'];

if(!array_key_exists($plateforme, $gestionnaire->getGestionnaire($_SESSION['user'])['plates'])) {
    die("Ce numéro de plateforme n'est pas pris en compte !");
}
if(file_exists($plateforme)) { 
    $lockv = new Lock();
    $state->lastState($plateforme, $lockv);
    $state->currentState($plateforme);
}
$name = $gestionnaire->getGestionnaire($_SESSION['user'])['plates'][$plateforme];
$sciper = $gestionnaire->getGestionnaire($_SESSION['user'])['sciper'];

$first = false;
$current = true;
if(empty($state->getCurrent())) {
    $current = false;
    if(empty($state->getLast())) {
        $first = true;
    }
}


$message = "";
if(isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']); 
}


function uploader(string $title, string $id)
{
    $html = '<label class="up-but">';
    $html .= '<input id="'.$id.'" type="file" name="zip_file" class="zip_file" accept=".zip">';
    $html .= $title;
    $html .= '</label>';
    return $html;
}

?>


<!DOCTYPE html>
<html lang="fr">
    <head>
        <?php include("commons/header.php");?> 
    </head>

    <body>
        <div class="container-fluid">	
            <div id="head"><div id="div-logo"><a href="index.php"><img src="img/EPFL_Logo_Digital_RGB_PROD.png" alt="Logo EPFL" id="logo"/></a></div><div id="div-path"><p><a href="index.php">Accueil</a> > Facturation <?= $name ?></p></div></div>	
            <h1 class="text-center p-1 pt-md-5"><?= $name ?></h1>
            
            <form action="controller/uploadPrepa.php" method="post" id="factform" enctype="multipart/form-data" >
                <div class="text-center">
                    <p>Facturation en cours : <?php echo (!empty($state->getCurrent())) ? $state->getCurrent() : "aucune";  ?></p>
                    <p>Dernière facturation : <?php echo (!empty($state->getLast())) ? $state->getLast() : "aucune";  ?></p> 
                    <input type="hidden" name="plate" id="plate" value="<?= $plateforme ?>" />
                    <input type="hidden" name="sciper" id="sciper" value="<?= $sciper ?>" />
                    <input type="hidden" name="type" id="type" value="SAP">   
                    <div class="row" id="buttons">
                        <div class="col-sm">
                            <?php 
                                echo uploader("Simulation", "SIMU");
                                if(!$first) { 
                                    echo '<div><button type="button" id="historique" class="btn btn-outline-dark">Ouvrir l\'historique</button></div>';
                                    if(!$current) { 
                                        echo '<div><button type="button" id="proforma" class="btn btn-outline-dark">Facturation Pro Forma</button></div>'; 
                                    }   
                                    if(array_key_exists($plateforme, $gestionnaire->getGestionnaire($_SESSION['user'])['tarifs'])) {
                                        echo '<div><button type="button" id="tarifs" class="btn btn-outline-dark">Nouveaux tarifs</button></div>'; 
                                    }              
                                    if($superviseur->isSuperviseur($_SESSION['user'])) {
                                        echo '<div><button type="button" id="destroy" class="btn btn-danger">Supprimer tous les données de cette plateforme</button></div>';
                                    } 
                                } 
                            ?>
                        </div>
                        <div class="col-sm">
                            <?php
                                if($first) { 
                                    echo uploader("Préparer 1ère facturation", "FIRST");
                                } 
                                else {
                                    if(!$current) {
                                        echo '<div><button type="button" id="redo" class="btn btn-outline-dark">Refaire factures : '.$state->getLastMonth()."/".$state->getLastYear().' </button></div>';
                                        echo '<div><button type="button" id="month" class="btn btn-outline-dark">Facturation nouveau mois : '.$state->getNextMonth()."/".$state->getNextYear().' </button></div>';
                                    }
                                }
                            ?>
                        </div>    
                    </div>
                    <div class="row hidden" id="histo">
                        <div><button type="button" id="close-histo" class="btn btn-outline-dark">Fermer l'historique</button></div>
                    </div>
                </div>

                <div class="text-center" id="message"><?= $message ?></div>
                <div class="text-center" id="display"></div>
            </form>

            <div class="text-center" id="arbo">
            <?php
            if(file_exists($plateforme)) {   
            ?>
                <table class="table table-bordered">
                    <?php
                    foreach(State::scanDescSan($plateforme) as $year) {
                        foreach(State::scanDescSan($plateforme."/".$year) as $month) {
                            $versions = State::scanDescSan($plateforme."/".$year."/".$month);
                            echo '<tr>';
                            echo '<td rowspan="'.count($versions).'">'.$month.' '.$year;
                            if (file_exists($plateforme."/".$year."/".$month."/lockm.csv")) {
                                echo ' <i class="bi bi-lock"></i> ';
                            }
                            echo '</td>';
                            $line = 0;
                            foreach($versions as $version) {
                                if($line > 0){
                                    echo '<tr>';
                                }
                                echo '<td>'.$version;
                                if (file_exists($plateforme."/".$year."/".$month."/".$version."/lockv.csv")) {
                                    echo ' <i class="bi bi-lock"></i> ';
                                }
                                echo '</td><td>';
                                foreach(State::scanDescSan($plateforme."/".$year."/".$month."/".$version) as $run) {
                                    $value = 'plateforme='.$plateforme.'&year='.$year.'&month='.$month.'&version='.$version.'&run='.$run;
                                    $label = new Label();
                                    $labtxt = $label->load($plateforme."/".$year."/".$month."/".$version."/".$run);
                                    if(empty($labtxt)) {
                                        $labtxt = $run;
                                    }
                                    $sap = new Sap();
                                    $sap->load($plateforme."/".$year."/".$month."/".$version."/".$run);
                                    $status = $sap->status();
                                    $lock = new Lock();
                                    $loctxt = $lock->load($plateforme."/".$year."/".$month."/".$version."/".$run, "run");
                                    echo ' <button type="button" value="'.$value.'" class="run btn '.Sap::color($status, $loctxt).'"> '.$labtxt;
                                    if ($loctxt) {
                                        echo ' <i class="bi bi-lock"></i> ';
                                    }
                                    echo '</button> ';
                                    if($superviseur->isSuperviseur($_SESSION['user'])) {
                                    ?>
                                    <button type="button" class="btn btn-danger erase" data-dir="<?= $plateforme."/".$year."/".$month ?>" data-run="<?= $run ?>">X</button>
                                    <?php
                                    }
                                }
                                echo '</td>';
                                
                                if($line > 0){
                                    echo '</tr>';
                                }
                                $line++;
                            }
                            echo '</tr>';
                        }
                    }
                ?></table><?php
            }
            ?>
            </div>
        </div>
        <?php include("commons/footer.php");?> 
        <script src="js/plateforme.js"></script>
  
	</body>
</html>
