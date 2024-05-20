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
$first = true;
$current = false;
if(file_exists($plateforme)) { 
    $state->lastState($plateforme, new Lock());
    $state->currentState($plateforme);
    $first = false;
    $current = true;
    if(empty($state->getCurrent())) {
        $current = false;
        if(empty($state->getLast())) {
            $first = true;
        }
    }
}
$name = $gestionnaire->getGestionnaire($_SESSION['user'])['plates'][$plateforme];

$complet = false;
if(array_key_exists($plateforme, $gestionnaire->getGestionnaire($_SESSION['user'])['complet'])) {
    $complet = true;
}

function uploader(string $title, string $id, string $disabled)
{
    $html = '<input id="'.$id.'" type="file" name="'.$id.'" '.$disabled.' class="zip_file lockable" accept=".zip">';
    $html .= '<label class="btn but-line" for="'.$id.'">';
    $html .= $title;
    $html .= '</label>';
    return $html;
}

include("commons/lock.php");

?>


<!DOCTYPE html>
<html lang="fr">
    <head>
        <?php include("commons/header.php");?> 
    </head>

    <body>
        <div class="container-fluid">	
            <div id="head">
                <div id="div-logo">
                    <a href="index.php"><img src="icons/epfl-logo.png" alt="Logo EPFL" id="logo"/></a>
                </div>
                <div id="div-path">
                    <p><a href="index.php">Accueil</a> > Facturation <?= $name ?></p>
                </div>
            </div>	
            <h1 class="text-center p-1 pt-md-5"><?= $name ?></h1>
            
            <form action="controller/uploadPrepa.php" method="post" id="factform" enctype="multipart/form-data" >
                <div class="text-center">
                    <p>Facturation en cours : <?php echo (!empty($state->getCurrent())) ? $state->getCurrent() : "aucune";  ?></p>
                    <p>Dernière facturation : <?php echo (!empty($state->getLast())) ? $state->getLast() : "aucune";  ?></p> 
                    <input type="hidden" name="plate" id="plate" value="<?= $plateforme ?>" />
                    <input type="hidden" name="type" id="type" value="SAP">   
                    <div class="row" id="buttons">
                        <div class="col-sm">
                            <?php
                                if(!$first) { 
                                    echo '<div><button type="button" id="historique" class="btn but-line">Ouvrir l\'historique</button></div>';
                                    if(!$current) { 
                                        echo uploader("Facturation Pro Forma", "PROFORMA", $disabled);
                                    }             
                                    if($superviseur->isSuperviseur($_SESSION['user'])) {
                                        echo '<div><button type="button" id="destroy" '.$disabled.' class="btn but-red lockable">Supprimer tous les données de cette plateforme</button></div>';
                                    } 
                                } 
                            ?>
                        </div>
                        <div class="col-sm">
                            <?php
                                if(!$first) { 
                                    if(!$current) {
                                        echo uploader("Refaire factures : ".$state->getLastMonth()."/".$state->getLastYear(), "REDO", $disabled);
                                        echo uploader("Facturation nouveau mois : ".$state->getNextMonth()."/".$state->getNextYear(), "MONTH", $disabled);
                                    }
                                }
                                else {
                                    if($complet) {
                                        echo uploader("Préparer 1ère facturation", "FIRST", $disabled);
                                    }
                                } 
                            ?>
                        </div>    
                    </div>
                    <div class="row hidden" id="histo">
                        <div><button type="button" id="close-histo" class="btn but-line">Fermer l'historique</button></div>
                    </div>
                </div>

                <?php include("commons/message.php");

                if(!empty($lockedTxt)) {
                    $other = "";
                    if($lockedPlate != $plateforme) {
                        $other = " pour une autre plateforme";
                    }
                    echo'<div>'.$lockedProcess.' est en cours'.$other.'. Veuillez patientez et rafraîchir la page...</div>';
                }
                if(!empty($lockedUser)) {
                    echo'<div class="text-center">'.$dlTxt.'</div>';
                }
                ?>
                <div class="text-center" id="display"></div>
            </form>

            <div class="text-center" id="arbo">
            <?php
            if(file_exists($plateforme)) {   
            ?>
                <table class="table table-boxed">
                    <?php
                    foreach(State::scanDescSan($plateforme) as $year) {
                        foreach(State::scanDescSan($plateforme."/".$year) as $month) {
                            $versions = State::scanDescSan($plateforme."/".$year."/".$month);
                            if(count($versions) > 0) {
                                echo '<tr>';
                                echo '<td rowspan="'.count($versions).'">'.$month.' '.$year;
                                if (file_exists($plateforme."/".$year."/".$month."/lockm.csv")) {
                                    echo ' <svg class="icon" aria-hidden="true">
                                                <use xlink:href="#lock"></use>
                                            </svg> ';
                                }
                                echo '</td>';
                                $line = 0;
                                foreach($versions as $version) {
                                    if($line > 0){
                                        echo '<tr>';
                                    }
                                    echo '<td>'.$version;
                                    if (file_exists($plateforme."/".$year."/".$month."/".$version."/lockv.csv")) {
                                        echo ' <svg class="icon" aria-hidden="true">
                                                    <use xlink:href="#lock"></use>
                                                </svg> ';
                                    }
                                    echo '</td><td>';
                                    foreach(State::scanDescSan($plateforme."/".$year."/".$month."/".$version) as $run) {
                                        if($run != $lockedRun || $lockedProcess != "Une préfacturation") {
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
                                                echo ' <svg class="icon" aria-hidden="true">
                                                            <use xlink:href="#lock"></use>
                                                        </svg> ';
                                            }
                                            echo '</button> ';
                                            if($superviseur->isSuperviseur($_SESSION['user'])) {
                                            ?>
                                            <button type="button" <?= $disabled ?> class="btn but-red erase lockable" data-dir="<?= $plateforme."/".$year."/".$month ?>" data-run="<?= $run ?>">X</button>
                                            <?php
                                            }
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
                ?></table><?php
            }
            ?>
            </div>
        </div>
        <?php include("commons/footer.php");?> 
        <script src="js/plateforme.js"></script>
  
	</body>
</html>
