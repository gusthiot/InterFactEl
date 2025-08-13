<?php

require_once("assets/ParamZip.php");
require_once("assets/Lock.php");
require_once("assets/Label.php");
require_once("assets/Sap.php");
require_once("includes/State.php");
require_once("session.inc");

/**
 * Page to manage the tarifs for a given plateform, upload and modify them
 */

if(!isset($_GET["plateforme"])) {
    $_SESSION['alert-danger'] = "Manque un numéro de plateforme !";
    header('Location: index.php');
    exit;
}

$plateforme = $_GET['plateforme'];
checkPlateforme("tarifs", $plateforme);

$name = DATA_GEST['tarifs'][$plateforme];
$dir = DATA.$plateforme;
$available = false;
$state = new State($dir);
if(file_exists($dir)) { 
    $available = true;
    if(empty($state->getLast())) {
        $available = false;
    }
}
if(!$available) {
    $_SESSION['alert-danger'] = "Les tarifs de cette plateforme ne peuvent pas être modifiés !";
    header('Location: index.php');
    exit;
}

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
                    <p><a href="index.php">Accueil</a> > Tarifs <?= $name ?></p>
                    <p><a href="logout.php">Logout</a></p>
                </div>
            </div>	
            <div class="title <?php if(TEST_MODE) echo "test";?>">
                <h1 class="text-center p-1"><?= $name ?></h1>
            </div>
            <div class="text-center" id="buttons">
                <form action="controller/uploadTarifs.php" method="post" id="form-tarifs" enctype="multipart/form-data" >
                    <input type="hidden" name="plate" id="plate" value="<?= $plateforme ?>" />
                    <input type="hidden" name="type" value="new" />
                    <input name="month-picker" id="month-picker" class="date-picker"/>
                    <div>
                        <label class="btn but-line">
                            <input type="file" id="zip-tarifs" name="zip_file" class="zip-file" accept=".zip">
                            Importer de nouveaux tarifs applicables dès ce mois
                        </label>
                    </div>
                </form>
            </div>

            <?php include("includes/message.php"); ?>
            <div class="text-center" id="tarifs-content">
                <input type="hidden" id="last-month" value="<?= $state->getLastMonth() ?>" />
                <input type="hidden" id="last-year" value="<?= $state->getLastYear() ?>" />
                <table id="tarifs" class="table table-boxed">
                    <?php
                    foreach(globReverse($dir) as $dirYear) {
                        $year = basename($dirYear);
                        foreach(globReverse($dirYear) as $dirMonth) {
                            $month = basename($dirMonth);
                            if (file_exists($dirMonth."/".ParamZip::NAME)) {
                                $label = Label::load($dirMonth);
                                if(empty($label)) {
                                    $label = "No label ?";
                                }
                                $moment = 0;

                                if($state->isSame($month, $year)) {
                                    $moment = 1;
                                }
                                elseif($state->isLater($month, $year)) {
                                    $moment = 2;
                                }

                                $lastRun = 0;
                                $lastVersion = 0;
                                foreach(globReverse($dirMonth) as $dirVersion) {
                                    foreach(globReverse($dirVersion) as $dirRun) {
                                        $sap = new Sap($dirRun);
                                        if(file_exists($dirRun."/lock.csv") || $sap->status() > 1) {
                                            $lastRun = basename($dirRun);
                                            $lastVersion = basename($dirVersion);
                                            break;
                                        }
                                    }
                                    if($lastRun > 0) {
                                        break;
                                    }
                                }
                                $id = $year."-".$month;
                                echo '<tr>';
                                echo '<td>'.$month.' '.$year;
                                if (file_exists($dirMonth."/".Lock::FILES['month'])) { ?>
                                    <svg class="icon" aria-hidden="true">
                                        <use xlink:href="#lock"></use>
                                    </svg>
                                <?php } ?>
                                </td>
                                <td>
                                    <button id="<?= $id ?>" type="button" class="collapse-title collapse-title-desktop collapsed" data-toggle="collapse" data-target="#collapse-<?= $id ?>" aria-expanded="false" aria-controls="collapse-<?= $id ?>"><?= $label?></button>
                                    <div class="collapse collapse-item collapse-item-desktop" id="collapse-<?= $id ?>">
                                        <button type="button" id="etiquette-<?= $id ?>" class="btn but-line etiquette">Etiquette</button>
                                        <button type="button" id="export-<?= $id ?>" class="btn but-line export">Exporter</button>                            
                                <?php if($lastRun > 0) {
                                    echo '<button type="button" id="all-'.$id.'" data-run="'.$lastRun.'" data-version="'.$lastVersion.'" class="btn but-line all">Exporter tout</button>';
                                }
                                if($moment == 1) { ?>
                                    <label class="btn but-line">
                                        <form action="controller/uploadTarifs.php" method="post" id="form-correct" enctype="multipart/form-data" >
                                            <input type="hidden" name="plate" id="plate" value="<?= $plateforme ?>" />
                                            <input type="hidden" name="type" value="correct" />
                                            <input type="file" id="zip-correct" name="zip_file" class="zip-file" accept=".zip">
                                        </form>
                                        Corriger</label>
                                <?php }
                                if($moment == 2) {
                                    echo '<button type="button" id="suppress-'.$id.'" class="btn but-line suppress">Supprimer</button>';
                                } ?>
                                <div id="label-<?= $id ?>"></div>
                                </div></td></tr>
                            <?php }
                        }
                    }
                ?></table>
            </div>
        </div>
        <?php include("includes/footer.php");?> 
        <script src="js/jquery-ui.min.js"></script>
        <link rel="stylesheet" href="css/jquery-ui.min.css">
        <script src="js/tarifs.js"></script>
	</body>
</html>
