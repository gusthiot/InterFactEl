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

/**
 * Customized button to upload prepa
 *
 * @param string $title button title
 * @param string $id upload input id
 * @return string
 */
function uploader(string $title, string $id): string
{
    $html = '<input id="'.$id.'" type="file" name="'.$id.'" class="zip-file lockable" accept=".zip">';
    $html .= '<label class="btn but-line" for="'.$id.'">';
    $html .= $title;
    $html .= '</label>';
    return $html;
}

$verified = true;

?>


<!DOCTYPE html>
<html lang="fr">
    <head>
        <?php include("includes/header.inc");?>
    </head>

    <body>
        <div class="container-fluid">
            <input type="hidden" name="plate" id="plate" value="<?= $plateforme ?>" />
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
            <!--<div class="text-center" id="buttons">
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
            </div>-->

            <?php include("includes/message.inc"); ?>
            <div id="tarifs-content">
                <!--<input type="hidden" id="last-month" value="<?= $state->getLastMonth() ?>" />
                <input type="hidden" id="last-year" value="<?= $state->getLastYear() ?>" />-->
                <nav class="nav-tabs-light-wrapper">
                    <ul class="nav nav-tabs-light" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" href="#tarifs-list" data-toggle="tab"  role="tab" aria-controls="tarifs-list" aria-selected="true">Liste</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#tarifs-space" data-toggle="tab"  role="tab" aria-controls="tarifs-space" aria-selected="false">Espace</a>
                        </li>
                    </ul>
                </nav>
                <div class="tab-content p-3">
                    <div class="tab-pane fade show active" id="tarifs-list" role="tabpanel" aria-labelledby="list-tab">
                        <div class="over">
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
                                            /*
                                            $moment = 0;

                                            if($state->isSame($month, $year)) {
                                                $moment = 1;
                                            }
                                            elseif($state->isLater($month, $year)) {
                                                $moment = 2;
                                            }*/

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
                                                    <!--<button type="button" id="export-<?= $id ?>" class="btn but-line export">Exporter</button>-->
                                            <?php if($lastRun > 0) {
                                                echo '<button type="button" id="all-'.$id.'" data-run="'.$lastRun.'" data-version="'.$lastVersion.'" class="btn but-line all">Exporter tout</button>';
                                            }
                                            /*
                                            if($moment == 1) { ?>
                                                <label class="btn but-line">
                                                    <form action="controller/uploadTarifs.php" method="post" id="form-correct" enctype="multipart/form-data" >
                                                        <input type="hidden" name="plate" id="plate" value="<?= $plateforme ?>" />
                                                        <input type="hidden" name="type" value="correct" />
                                                        <input type="file" id="zip-correct" name="zip_file" class="zip-file" accept=".zip">
                                                    </form>
                                                    Corriger</label>
                                            <?php }
                                            */
                                            if($state->isLater($month, $year)) {
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
                    <div class="tab-pane fade" id="tarifs-space" role="tabpanel" aria-labelledby="space-tab">
                        <div id="actions" class="text-center">

                            <input id="tarifs-import" type="file" name="tarifs-import" class="zip-file lockable" accept=".zip" />
                            <label class="btn but-line" for="tarifs-import">Importer</label>

                            <button type="button" id="tarifs-read" class="btn but-line">Lire</button>
                            <button type="button" id="tarifs-save" class="btn but-line">Sauvegarder</button>
                            <button type="button" id="tarifs-check" class="btn but-line">Vérifier</button>
                            <?php
                            if($verified) {
                            ?>
                            <button type="button" id="tarifs-load" class="btn but-line">Charger</button>
                            <button type="button" id="tarifs-correct" class="btn but-line">Corriger : <?= $state->getLastMonth() ?>/<?= $state->getLastYear() ?></button>
                            <?php
                            }
                            ?>
                            <button type="button" id="tarifs-control" class="btn but-line">Contrôler</button>
                        </div>
                        <div id="tarifs-select"></div>
                        <div id="tarifs-files"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php include("includes/footer.inc");?>
        <script src="js/jquery-ui.min.js"></script>
        <script src="js/jszip.min.js"></script>
        <link rel="stylesheet" href="css/jquery-ui.min.css">
        <script src="js/tarifs.js"></script>
	</body>
</html>
