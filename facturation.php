<?php

require_once("assets/Label.php");
require_once("assets/Sap.php");
require_once("assets/Lock.php");
require_once("includes/State.php");
require_once("session.inc");

if(!isset($_GET["plateforme"])) {
    $_SESSION['alert-danger'] = "Manque un numéro de plateforme !";
    header('Location: index.php');
    exit;
}
$plateforme = $_GET['plateforme'];
checkPlateforme($dataGest, "facturation", $plateforme);

// Check if first facturation, if one is running, which one is the last one
$dir = DATA.$plateforme;
$first = true;
$current = State::currentState($dir);
$state = new State($dir);
if(file_exists($dir)) {
    $first = false;
    if(empty($current)) {
        if(empty($state->getLast())) {
            $first = true;
        }
    }
}
$name = $dataGest['facturation'][$plateforme];

/**
 * Customized button to upload prepa
 *
 * @param string $title button title
 * @param string $id upload input id
 * @param string $disabled if button is disabled, when a process is running
 * @return string 
 */
function uploader(string $title, string $id, string $disabled): string
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
                    <p><a href="logout.php">Logout</a></p>
                </div>
            </div>	
            <div class="title <?php if(TEST_MODE) echo "test";?>">
                <h1 class="text-center p-1"><?= $name ?></h1>
            </div>	
            
            <form action="controller/uploadPrepa.php" method="post" id="form-fact" enctype="multipart/form-data" >
                <div class="text-center">
                    <p>Facturation en cours : <?php echo (!empty($current)) ? $current : "aucune";  ?></p>
                    <p>Dernière facturation : <?php echo (!empty($state->getLast())) ? $state->getLast() : "aucune";  ?></p> 
                    <input type="hidden" name="plate" id="plate" value="<?= $plateforme ?>" />
                    <input type="hidden" name="type" id="type" value="SAP">   
                    <div class="row" id="buttons">
                        <div class="col-sm">
                            <?php
                                if(!$first) { ?>
                                    <div><button type="button" id="open-historique" class="btn but-line">Ouvrir l'historique</button></div>
                                    <?php 
                                    if(empty($current)) { 
                                        echo uploader("Facturation Pro Forma : ".$state->getNextMonth()."/".$state->getNextYear(), "PROFORMA", $disabled);
                                    }             
                                    if($superviseur->isSuperviseur($user) && TEST_MODE == "TEST") {  
                                        ?>
                                        <div><button type="button" id="destroy" '.$disabled.' class="btn but-red lockable">Réinitialisation des tests : tout supprimer</button>
                                        </div>  
                                    <?php } 
                                }     
                                if($superviseur->isSuperviseur($user) && TEST_MODE == "TEST") {   
                                    echo uploader("Charger des archives", "ARCHIVE", $disabled);    
                                    $choices = [];
                                    if($first) {
                                        $title = "Charger une période";
                                    }
                                    else {
                                        $title = "Réinitialiser et charger une période";
                                    }
                                    $prod = str_replace("data", "../prod/data", DATA.$plateforme);
                                    foreach(array_reverse(glob($prod."/*", GLOB_ONLYDIR))  as $dirYear) {
                                        $year = basename($dirYear);
                                        foreach(array_reverse(glob($dirYear."/*", GLOB_ONLYDIR)) as $dirMonth) {
                                            $month = basename($dirMonth);
                                            $choices[$year.$month] = [$year, $month];
                                        }
                                    }?>
                                    <div><button type="button" id="period" '.$disabled.' data-choices="<?php echo htmlentities(json_encode($choices),ENT_QUOTES); ?>" class="btn but-red lockable"><?= $title ?></button>
                                    </div>
                                    <div id="first"></div>
                                    <div id="last">
                                    </div>
                                    <div id="reinit">
                                    </div>
                                <?php } 
                            ?>
                        </div>
                        <div class="col-sm">
                            <?php
                                if($first) {
                                    if($dataGest['tarifs'] && array_key_exists($plateforme, $dataGest['tarifs'])) {
                                        echo uploader("Préparer 1ère facturation", "FIRST", $disabled);
                                    } 
                                }
                                else {
                                    if(empty($current)) {
                                        echo uploader("Refaire factures : ".$state->getLastMonth()."/".$state->getLastYear(), "REDO", $disabled);
                                        echo uploader("Facturation nouveau mois : ".$state->getNextMonth()."/".$state->getNextYear(), "MONTH", $disabled);
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

                if(!empty($lockProcess)) {
                    $other = "";
                    if($lockedPlate != $plateforme) {
                        $other = " pour une autre plateforme";
                    }
                    echo'<div class="text-center" >'.$lockedProcessus.' est en cours'.$other.'. Veuillez patientez et rafraîchir la page...</div>';
                }
                if(!empty($lockUser)) {
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

                    // Listing of all year/month/version/run for th plateform
                    foreach(array_reverse(glob($dir."/*", GLOB_ONLYDIR)) as $dirYear) {
                        $year = basename($dirYear);
                        foreach(array_reverse(glob($dirYear."/*", GLOB_ONLYDIR)) as $dirMonth) {
                            $month = basename($dirMonth);
                            $dirVersions = array_reverse(glob($dirMonth."/*", GLOB_ONLYDIR));
                            if(count($dirVersions) > 0) {
                                echo '<tr>';
                                echo '<td rowspan="'.count($dirVersions).'">';
                                if (file_exists($dirMonth."/archive.csv")) { ?>
                                    <svg class="icon" aria-hidden="true">
                                        <use xlink:href="#star"></use>
                                    </svg>
                                <?php }
                                echo $month.' '.$year;
                                if (file_exists($dirMonth."/".Lock::FILES['month'])) { ?>
                                    <svg class="icon" aria-hidden="true">
                                        <use xlink:href="#lock"></use>
                                    </svg>
                                <?php }
                                echo '</td>';
                                $line = 0;
                                foreach($dirVersions as $dirVersion) {
                                    $version = basename($dirVersion);
                                    if($line > 0){
                                        echo '<tr>';
                                    }
                                    echo '<td>'.$version;
                                    if (file_exists($dirVersion."/".Lock::FILES['version'])) { ?>
                                        <svg class="icon" aria-hidden="true">
                                            <use xlink:href="#lock"></use>
                                        </svg>
                                    <?php }
                                    echo '</td><td>';
                                    foreach(array_reverse(glob($dirVersion."/*", GLOB_ONLYDIR)) as $dirRun) {
                                        $run = basename($dirRun);
                                        if($run != $lockedRun || $lockedProcessus != "Une préfacturation") {
                                            $value = 'year='.$year.'&month='.$month.'&version='.$version.'&run='.$run;
                                            $label = Label::load($dirRun);
                                            if(empty($label)) {
                                                $label = $run;
                                            }
                                            $sap = new Sap($dirRun);
                                            $lockRun = Lock::load($dirRun, "run");
                                            echo ' <button type="button" value="'.$value.'" class="open-run btn '.Sap::color($sap->status(), $lockRun).'"> '.$label;
                                            if ($lockRun) { ?>
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
        <script src="js/facturation.js"></script>
  
	</body>
</html>
