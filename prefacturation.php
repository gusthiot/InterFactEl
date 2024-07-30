<?php

require_once("assets/Label.php");
require_once("assets/Sap.php");
require_once("assets/Lock.php");
require_once("assets/Message.php");
require_once("session.inc");

checkGest($dataGest);
if(!isset($_GET["plateforme"]) || !isset($_GET["year"]) || !isset($_GET["month"]) || !isset($_GET["version"]) || !isset($_GET["run"])) {
    $_SESSION['alert-danger'] = "Manque un paramètre !";
    header('Location: index.php');
    exit;
}
$plateforme = $_GET['plateforme'];
checkPlateforme($dataGest, $plateforme);

$year = $_GET['year'];
$month = $_GET['month'];
$version = $_GET['version'];
$run = $_GET['run'];
$dir = DATA.$plateforme."/".$year."/".$month."/".$version."/".$run;
$name = $gestionnaire->getGestionnaire($user)['plates'][$plateforme];

$messages = new Message();
$label = Label::load($dir);
if(empty($label)) {
    $label = $run;
}
$sap = new Sap($dir);
$status = $sap->status();
$lockRun = Lock::load($dir, "run");
$lockVersion = Lock::load(DATA.$plateforme."/".$year."/".$month."/".$version, "version");

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
                    <p><a href="index.php">Accueil</a> > <a href="plateforme.php?plateforme=<?= $plateforme ?>">Facturation <?= $name ?></a> > Prefacturation <?= $label ?></p>
                    <p><a href="logout.php">Logout</a></p>
                </div>
            </div>
            <div class="title <?php if(TEST_MODE) echo "test";?>">
                <h1 class="text-center p-1 pt-md-5"><?= $label ?></h1>
            </div>
            <input type="hidden" id="plate" value="<?= $plateforme ?>" />
            <input type="hidden" id="year" value="<?= $year ?>" />
            <input type="hidden" id="month" value="<?= $month ?>" />
            <input type="hidden" id="version" value="<?= $version ?>" />
            <input type="hidden" id="run" value="<?= $run ?>" />
            
            <div id="actions" class="text-center">
                <button type="button" id="open-label" class="btn but-line">Etiqueter</button>
                <button type="button" id="open-info" class="btn but-line">Afficher les infos</button>
                <button type="button" id="open-bills" class="btn but-line">Afficher la liste des factures</button>
                <button type="button" id="open-ticket" class="btn but-line">Contrôler le ticket</button>
                <button type="button" id="open-changes" class="btn but-line">Afficher les modifications</button>
                <?php 
                if(($status < 4) && !$lockRun) {
                    echo '<button type="button" id="invalidate" '.$disabled.' class="btn but-line lockable">Invalider</button>';
                } 
                if(in_array($status, [0, 4, 5, 6, 7]) && $lockVersion && ($lockVersion == $run)) { ?>
                    <button type="button" id="bilans" class="btn but-line">Exporter Bilans & Stats</button>
                    <button type="button" id="annexes" class="btn but-line">Exporter Annexes csv</button>
                <?php } 
                ?>
                <button type="button" id="all" class="btn but-line">Exporter Tout</button>
                <?php 
                if(in_array($status, [1, 2, 3, 5, 6, 7]) && !$lockRun) {
                    echo '<button type="button" id="send" '.$disabled.' class="btn but-line-green lockable">Envoi SAP</button>';
                }
                if(in_array($status, [0, 5, 6, 7]) && !$lockRun) {
                    echo '<button type="button" id="finalize" '.$disabled.' class="btn but-line-blue lockable">Finaliser SAP</button>';
                }
                    if((in_array($status, [4, 5, 6, 7]) && !$lockRun) || (in_array($status, [4, 5, 6, 7]) && $lockVersion && ($lockVersion == $run))) {
                echo '<button type="button" id="resend" data-msg="'.$messages->getMessage('msg5').'" '.$disabled.' class="btn but-line-red lockable">Renvoi SAP</button>';
                }
                ?>
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

            <div class="text-center" id="prefa-content"></div>

        </div>
        <?php include("includes/footer.php");?> 
        <script src="js/prefacturation.js"></script>
	</body>
</html>
