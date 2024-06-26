<?php
require_once("session.inc.php");
require_once("includes/State.php");
require_once("assets/Label.php");
require_once("assets/Sap.php");
require_once("assets/Lock.php");

checkGest($dataGest);
if(!isset($_GET["plateforme"])) {
    $_SESSION['alert-danger'] = "Manque un numéro de plateforme !";
    header('Location: index.php');
    exit;
}
$plateforme = $_GET['plateforme'];
checkPlateforme($dataGest, $plateforme);

$dir = DATA.$plateforme;
$first = true;
$current = false;
$state = new State();
if(file_exists($dir)) { 
    $state->lastState($dir, new Lock());
    $state->currentState($dir);
    $first = false;
    $current = true;
    if(empty($state->getCurrent())) {
        $current = false;
        if(empty($state->getLast())) {
            $first = true;
        }
    }
}
$name = $gestionnaire->getGestionnaire($user)['plates'][$plateforme];

$complet = false;
if(array_key_exists($plateforme, $gestionnaire->getGestionnaire($user)['complet'])) {
    $complet = true;
}

function uploader(string $title, string $id, string $disabled)
{
    $html = '<input id="'.$id.'" type="file" name="'.$id.'" '.$disabled.' class="zip-file lockable" accept=".zip">';
    $html .= '<label class="btn but-line" for="'.$id.'">';
    $html .= $title;
    $html .= '</label>';
    return $html;
}

include("includes/lock.php");

?>


<!DOCTYPE html>
<html lang="fr">
    <head>
        <?php include("includes/header.php");?> 
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
            
            <form action="controller/uploadPrepa.php" method="post" id="form-fact" enctype="multipart/form-data" >
                <div class="text-center">
                    <p>Facturation en cours : <?php echo (!empty($state->getCurrent())) ? $state->getCurrent() : "aucune";  ?></p>
                    <p>Dernière facturation : <?php echo (!empty($state->getLast())) ? $state->getLast() : "aucune";  ?></p> 
                    <input type="hidden" name="plate" id="plate" value="<?= $plateforme ?>" />
                    <input type="hidden" name="type" id="type" value="SAP">   
                    <div class="row" id="buttons">
                        <div class="col-sm">
                            <?php
                                if(!$first) { ?>
                                    <div><button type="button" id="open-historique" class="btn but-line">Ouvrir l'historique</button></div>
                                    <?php 
                                    if(!$current) { 
                                        echo uploader("Facturation Pro Forma", "PROFORMA", $disabled);
                                    }             
                                    if($superviseur->isSuperviseur($user) && TEST_MODE == "TEST") { ?>
                                        <div><button type="button" id="destroy" '.$disabled.' class="btn but-red lockable">Réinitialisation des tests : tout supprimer</button></div>
                                    <?php } 
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
                    <div class="row hidden" id="historique-div">
                        <div><button type="button" id="close-historique" class="btn but-line">Fermer l'historique</button></div>
                    </div>
                </div>

                <?php include("includes/message.php");

                if(!empty($lockedTxt)) {
                    $other = "";
                    if($lockedPlate != $plateforme) {
                        $other = " pour une autre plateforme";
                    }
                    echo'<div class="text-center" >'.$lockedProcess.' est en cours'.$other.'. Veuillez patientez et rafraîchir la page...</div>';
                }
                if(!empty($lockedUser)) {
                    echo'<div class="text-center">'.$dlTxt.'</div>';
                }
                ?>
                <div class="text-center" id="display"></div>
            </form>

            <div class="text-center" id="plate-content">
            <?php
            if(file_exists($dir)) {   
            ?>
                <table class="table table-boxed">
                    <?php
                    foreach(State::scanDesc($dir) as $year) {
                        foreach(State::scanDesc($dir."/".$year) as $month) {
                            $versions = State::scanDesc($dir."/".$year."/".$month);
                            if(count($versions) > 0) {
                                echo '<tr>';
                                echo '<td rowspan="'.count($versions).'">'.$month.' '.$year;
                                if (file_exists($dir."/".$year."/".$month."/lockm.csv")) { ?>
                                    <svg class="icon" aria-hidden="true">
                                        <use xlink:href="#lock"></use>
                                    </svg>
                                <?php }
                                echo '</td>';
                                $line = 0;
                                foreach($versions as $version) {
                                    if($line > 0){
                                        echo '<tr>';
                                    }
                                    echo '<td>'.$version;
                                    if (file_exists($dir."/".$year."/".$month."/".$version."/lockv.csv")) { ?>
                                        <svg class="icon" aria-hidden="true">
                                            <use xlink:href="#lock"></use>
                                        </svg>
                                    <?php }
                                    echo '</td><td>';
                                    foreach(State::scanDesc($dir."/".$year."/".$month."/".$version) as $run) {
                                        if($run != $lockedRun || $lockedProcess != "Une préfacturation") {
                                            $value = 'year='.$year.'&month='.$month.'&version='.$version.'&run='.$run;
                                            $label = new Label();
                                            $labtxt = $label->load($dir."/".$year."/".$month."/".$version."/".$run);
                                            if(empty($labtxt)) {
                                                $labtxt = $run;
                                            }
                                            $sap = new Sap();
                                            $sap->load($dir."/".$year."/".$month."/".$version."/".$run);
                                            $status = $sap->status();
                                            $lock = new Lock();
                                            $loctxt = $lock->load($dir."/".$year."/".$month."/".$version."/".$run, "run");
                                            echo ' <button type="button" value="'.$value.'" class="open-run btn '.Sap::color($status, $loctxt).'"> '.$labtxt;
                                            if ($loctxt) { ?>
                                                <svg class="icon" aria-hidden="true">
                                                    <use xlink:href="#lock"></use>
                                                </svg>
                                            <?php }
                                            echo '</button>';
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
        <?php include("includes/footer.php");?> 
        <script src="js/plateforme.js"></script>
  
	</body>
</html>
