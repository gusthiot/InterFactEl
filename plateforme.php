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

$message = "";
if(isset($_SESSION['message'])) {
    if($_SESSION['message'] == "zip") {
        $message = "Vous devez uploader une archive zip !";
    }
    elseif($_SESSION['message'] == "data") {
        $message = "Erreur de données";
    }
    elseif($_SESSION['message'] == "copy") {
        $message = "Erreur de copie sur le disque";
    }
    elseif($_SESSION['message'] == "error") {
        $message = "Erreur non documentée";
    }
    elseif($_SESSION['message'] == "success") {
        $message = "Les fichiers ont bien été enregistré";
    }
    else {
        $message = $_SESSION['message'];
    }
    unset($_SESSION['message']); 
}

?>


<!DOCTYPE html>
<html lang="fr">
    <head>
        <?php include("commons/header.php");?> 
    </head>

    <body>
        <div class="container-fluid">	
        <a href="index.php"><i class="bi bi-arrow-return-left"></i></a>	
        <h1 class="text-center p-1 pt-md-5"><?= $name ?></h1>
        <input type="hidden" id="plateNum" value="<?= $plateforme ?>" />
        <input type="hidden" id="sciperNum" value="<?= $sciper ?>" />
        
        <div class="text-center">
            <p>Facturation en cours : <?php echo (!empty($state->getCurrent())) ? $state->getCurrent() : "aucune";  ?></p>
            <p>Dernière facturation : <?php echo (!empty($state->getLast())) ? $state->getLast() : "aucune";  ?></p> 
        <?php
        if(file_exists($plateforme)) { 
        ?>
            <button type="button" id="historique" class="btn btn-outline-dark">Consulter l'historique de la plateforme</button>
        <?php
            if(empty($state->getCurrent()) && !empty($state->getLast())) {
                ?>
                <button type="button" id="redo" class="btn btn-outline-dark">Refaire factures</button>
                <button type="button" id="month" class="btn btn-outline-dark">Facturation nouveau mois</button>
                <button type="button" id="proforma" class="btn btn-outline-dark">Facturation Pro Forma</button>
                <?php
            }
        }
        if(empty($state->getLast()) && empty($state->getCurrent())) {
        ?>
            <button type="button" data-type="FIRST" class="btn btn-outline-dark prepare">1ère préparation</button>
        <?php
        }
        ?>
        <button type="button" data-type="SIMU" class="btn btn-outline-dark prepare">Simulation</button>
        </div>

        <div class="text-center" id="message"><?= $message ?></div>
        <div class="text-center" id="display"></div>
        <div class="text-center">
            <table class="table table-bordered">
        <?php
        if(file_exists($plateforme)) {
            
            if($superviseur->isSuperviseur($_SESSION['user'])) {
            ?>
            <div class="text-center">
            <button type="button" id="destroy" class="btn btn-danger">Supprimer tous les données de cette plateforme</button>
            </div>
            <?php
            }
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
        }
        ?>
            </table
        </div>
        </div>
        <?php include("commons/footer.php");?> 
        <script src="js/plateforme.js"></script>
  
	</body>
</html>
