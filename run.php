<?php

require_once("assets/Label.php");
require_once("assets/Sap.php");
require_once("assets/Lock.php");
require_once("assets/Message.php");
require_once("assets/Plateforme.php");
require_once("session.inc");

/**
 * Page to manage a plateform facturation run
 */

if(!isset($_GET["plateforme"]) || !isset($_GET["year"]) || !isset($_GET["month"]) || !isset($_GET["version"]) || !isset($_GET["run"])) {
    $_SESSION['alert-danger'] = "Manque un paramètre !";
    header('Location: index.php');
    exit;
}
$plateforme = $_GET['plateforme'];
checkPlateforme("facturation", $plateforme);

$year = $_GET['year'];
$month = $_GET['month'];
$version = $_GET['version'];
$run = $_GET['run'];
$dir = DATA.$plateforme."/".$year."/".$month."/".$version."/".$run;

$plateformes = new Plateforme();
$name = $plateformes->getName($plateforme);

$messages = new Message();
$label = Label::load($dir);
if(empty($label)) {
    $label = $run;
}
$sap = new Sap($dir);
$status = $sap->status();
$lockRun = Lock::load($dir, "run");
$lockVersion = Lock::load(DATA.$plateforme."/".$year."/".$month."/".$version, "version");

$archive = file_exists(DATA.$plateforme."/".$year."/".$month."/archive.csv");

include("includes/lock.inc");

?>


<!DOCTYPE html>
<html lang="fr">
    <head>
        <?php include("includes/header.inc");?>
    </head>

    <body>
        <div class="container-fluid" id="container" data-plateforme="<?= $plateforme ?>" data-year="<?= $year ?>" data-month="<?= $month ?>" data-version="<?= $version ?>" data-run="<?= $run ?>">
            <div id="head">
                <div id="div-logo">
                    <a href="index.php"><img src="icons/epfl-logo.png" alt="Logo EPFL" id="logo-epfl"/></a>
                </div>
                <div id="div-path">
                    <p><a href="index.php">Accueil</a> > <a href="facturation.php?plateforme=<?= $plateforme ?>">Facturation <?= $name ?></a> > Prefacturation <?= $label ?></p>
                    <p><a href="logout.php">Logout</a></p>
                </div>
            </div>
            <div class="title <?php if(TEST_MODE) echo TEST_MODE; ?>">
                <h1 class="text-center p-1"><?= $label ?></h1>
            </div>
            <div id="actions">
                <?php

                $desList = $desRapport = $desControl = $desInvalid = $desEnvoi = $desFinal = $desRenvoi = $desBS = $desAnnexes = "desactived-tile";
                if(!$archive) {
                    $desList = $desControl = $desModifs = "";
                    if($status > 0) {
                        $desRapport = "";
                    }
                    if(!$disabled) {
                        if(($status < 4) && is_null($lockRun)) {
                            $desInvalid = "";
                        }
                        if(in_array($status, [1, 2, 3, 5, 6, 7]) && is_null($lockRun)) {
                            $desEnvoi = "";
                        }
                        if(in_array($status, [0, 1, 5, 6, 7]) && is_null($lockRun)) {
                            $desFinal = "";
                        }
                        if((in_array($status, [5, 6, 7]) && is_null($lockRun)) || (in_array($status, [1, 4, 5, 6, 7]) && !is_null($lockVersion) && ($lockVersion == $run))) {
                            $desRenvoi = "";
                        }
                    }
                }
                if(in_array($status, [0, 1, 4, 5, 6, 7]) && !is_null($lockVersion) && ($lockVersion == $run)) {
                    $desBS = "";
                    if(!$archive) {
                        $desAnnexes = "";
                    }
                }

                ?>

                <div class="links-group">
                    <h5 id="links-group-title">Informations</h5>
                    <ul class="list-unstyled">
                        <li><div id="open-label" class="tile tight-tile">Etiqueter</div></li>
                        <li><div id="open-info" class="tile tight-tile">Afficher les infos</div></li>

                        <li><div id="open-bills" class="tile tight-tile <?= $desList ?>">Afficher la liste des factures</div></li>
                        <li><div id="open-report" class="tile tight-tile <?= $desRapport ?>">Rapports d'envoi SAP</div></li>
                    </ul>
                </div>

                <div class="links-group">
                    <h5 id="links-group-title">Contrôles</h5>
                    <ul class="list-unstyled">
                        <li><div id="open-ticket" class="tile tight-tile <?= $desControl ?>">Contrôler le ticket</div></li>
                        <li><div id="open-changes" class="tile tight-tile <?= $desModifs ?>">Afficher les modifications</div></li>
                    </ul>
                </div>

                <div class="links-group">
                    <h5 id="links-group-title">Actions</h5>
                    <ul class="list-unstyled">
                        <li><div id="invalidate" class="tile tight-tile lockable <?= $desInvalid ?>">Invalider</div></li>
                        <li><div id="send" class="tile tight-tile green-tile lockable <?= $desEnvoi ?>">Envoi SAP</div></li>
                        <li><div id="finalize" class="tile tight-tile blue-tile lockable <?= $desFinal ?>">Finaliser SAP</div></li>
                        <li><div id="resend" data-msg="<?=$messages->getMessage('msg5')?>" class="tile tight-tile red-tile lockable <?= $desRenvoi ?>">Renvoi SAP</div></li>
                    </ul>
                </div>

                <div class="links-group">
                    <h5 id="links-group-title">Exportations</h5>
                    <ul class="list-unstyled">
                        <li><div id="bilans" class="tile tight-tile <?= $desBS ?>">Exporter Bilans & Stats</div></li>
                        <li><div id="annexes" class="tile tight-tile <?= $desAnnexes ?>">Exporter Annexes csv</div></li>
                        <li><div id="all" class="tile tight-tile">Exporter Tout</div></li>
                    </ul>
                </div>

            </div>

            <?php include("includes/message.inc");

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
        <?php include("includes/footer.inc");?>
        <script src="js/run.js"></script>
	</body>
</html>
