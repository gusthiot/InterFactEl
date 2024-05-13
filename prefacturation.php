<?php
require_once("session.php");
require_once("src/Label.php");
require_once("src/Sap.php");
require_once("src/Lock.php");
require_once("commons/State.php");

if(!isset($_GET["plateforme"]) || !isset($_GET["year"]) || !isset($_GET["month"]) || !isset($_GET["version"]) || !isset($_GET["run"])) {
    die("Manque un paramètre !");
}
$plateforme = $_GET['plateforme'];
if(!array_key_exists($plateforme, $gestionnaire->getGestionnaire($_SESSION['user'])['plates'])) {
    die("Ce numéro de plateforme n'est pas pris en compte !");
}
$year = $_GET['year'];
$month = $_GET['month'];
$version = $_GET['version'];
$run = $_GET['run'];
$dir = $plateforme."/".$year."/".$month."/".$version."/".$run;
$dirPrevMonth = $plateforme."/".State::getPreviousYear($year, $month)."/".State::getPreviousMonth($year, $month);
$param = "?plateforme=".$plateforme."&year=".$year."&month=".$month."&version=".$version."&run=".$run;
$name = $gestionnaire->getGestionnaire($_SESSION['user'])['plates'][$plateforme];
$suf = "_".$name."_".$year."_".$month."_".$version;

$label = new Label();
$labtxt = $label->load($dir);
if(empty($labtxt)) {
    $labtxt = $run;
}
$sap = new Sap();
$sap->load($dir);
$status = $sap->status();
$lock = new Lock();
$loctxt = $lock->load($dir, "run");
$lockv = new Lock();
$locvtxt = $lockv->load($plateforme."/".$year."/".$month."/".$version, "version");

$locklast = new Lock();
$state->lastState($plateforme, $locklast);
$dirTarifs = "";
if(empty($state->getLast())) {
    $dirTarifs = $plateforme."/".$year."/".$month;
}

if(isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']); 
}

$lockp = new Lock();
$lockedTxt = $lockp->load("./", "process");
$lockedPlate = "";
$lockedProcess = "";
$disabled = "";

if(!empty($lockedTxt)) {
    $disabled = "disabled";
    $lockedTab = explode(" ", $lockedTxt);
    if($lockedTab[0] == "prefa") {
        $lockedProcess = "Une préfacturation";
    }
    else {
        $lockedProcess = "Un envoi SAP";
    }
    $lockedPlate = $lockedTab[1];
    $other = "";
    if($lockedPlate != $plateforme) {
        $other = " pour une autre plateforme";
    }
    $message = '<div>'.$lockedProcess.' est en cours'.$other.'. Veuillez patientez et rafraîchir la page...</div>';
}
?>


<!DOCTYPE html>
<html lang="fr">
    <head>
        <?php include("commons/header.php");?> 
    </head>

    <body>
        <div class="container-fluid">	
            <div id="head"><div id="div-logo"><a href="index.php"><img src="icons/epfl-logo.png" alt="Logo EPFL" id="logo"/></a></div><div id="div-path"><p><a href="index.php">Accueil</a> > <a href="plateforme.php?plateforme=<?= $plateforme ?>">Facturation <?= $name ?></a> > Prefacturation <?= $labtxt ?></p></div></div>
            <h1 class="text-center p-1 pt-md-5"><?= $labtxt ?></h1>	
            <input type="hidden" id="dir" value="<?= $dir ?>" />
            <input type="hidden" id="dirPrevMonth" value="<?= $dirPrevMonth ?>" />
            <input type="hidden" id="suf" value="<?= $suf ?>" />
            <input type="hidden" id="plate" value="<?= $plateforme ?>" />
            <input type="hidden" id="dirTarifs" value="<?= $dirTarifs ?>" />
            
            <div id="actions" class="text-center">
                <button type="button" id="label" class="btn but-line">Etiqueter</button>
                <button type="button" id="info" class="btn but-line">Afficher les infos</button>
                <button type="button" id="bills" class="btn but-line">Afficher la liste des factures</button>
                <button type="button" id="ticket" data-param="<?= $param ?>" class="btn but-line">Contrôler le ticket</button>
                <button type="button" id="changes" class="btn but-line">Afficher les modifications</button>
                <?php 
                if(($status < 4) && !$loctxt) { ?>
                    <button type="button" id="invalidate" class="btn but-line">Invalider</button>
                <?php } 
                if(in_array($status, [0, 4, 5, 6, 7]) && $locvtxt && ($locvtxt == $run)) { ?>
                    <button type="button" id="bilans" class="btn but-line">Exporter Bilans & Stats</button>
                    <button type="button" id="annexes" class="btn but-line">Exporter Annexes csv</button>
                <?php } 
                ?>
                <button type="button" id="all" class="btn but-line">Exporter Tout</button>
                <?php 
                if(in_array($status, [1, 2, 3, 5, 6, 7]) && !$loctxt) {
                    echo '<button type="button" id="send" '.$disabled.' class="btn but-line-green lockable">Envoi SAP</button>';
                }
                if(in_array($status, [0, 5, 6, 7]) && !$loctxt) {
                    echo '<button type="button" id="finalize" '.$disabled.' class="btn but-line-blue lockable">Finaliser SAP</button>';
                }
                    if((in_array($status, [4, 5, 6, 7]) && !$loctxt) || (in_array($status, [4, 5, 6, 7]) && $locvtxt && ($locvtxt == $run))) {
                echo '<button type="button" id="resend" data-msg="'.$messages->getMessage('msg6').'" '.$disabled.' class="btn but-line-red lockable">Renvoi SAP</button>';
                }
                ?>
            </div>

            <div class="text-center" id="message"></div>

            <div class="text-center" id="content"></div>

        </div>
        <?php include("commons/footer.php");?> 
        <script src="js/prefacturation.js"></script>
	</body>
</html>
