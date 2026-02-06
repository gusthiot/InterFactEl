<?php

require_once("assets/ParamZip.php");
require_once("assets/Lock.php");
require_once("assets/Label.php");
require_once("assets/Sap.php");
require_once("assets/Message.php");
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
$mp = State::lastRun($dir);
if(!$available) {
    $_SESSION['alert-danger'] = "Les tarifs de cette plateforme ne peuvent pas être modifiés !";
    header('Location: index.php');
    exit;
}
$messages = new Message();

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
                <!--<input type="hidden" id="mp-month" value="<?= $mp['month'] ?>" />-->
                <!--<input type="hidden" id="mp-year" value="<?= $mp['year'] ?>" />-->
                <!--<input type="hidden" id="msg" value="<?= $messages->getMessage('msg8') ?>" />-->
                <nav class="nav-tabs-light-wrapper">
                    <ul class="nav nav-tabs-light" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" href="#tarifs-list" data-toggle="tab" id="menu-list" role="tab" aria-controls="tarifs-list" aria-selected="true">Liste</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#tarifs-space" data-toggle="tab"  role="tab" aria-controls="tarifs-space" aria-selected="false">Espace</a>
                        </li>
                    </ul>
                </nav>
                <div class="tab-content p-3">
                    <!-- Liste -->
                    <div class="tab-pane fade show active" id="tarifs-list" role="tabpanel" aria-labelledby="list-tab">
                        <div class="over-tarifs">
                            <table id="tarifs" class="table table-boxed">
                                <?php
                                foreach(globReverse($dir) as $dirYear) {
                                    $year = basename($dirYear);
                                    foreach(globReverse($dirYear) as $dirMonth) {
                                        $month = basename($dirMonth);
                                        if(file_exists($dirMonth."/".ParamZip::NAME)) {
                                            $label = Label::load($dirMonth);
                                            if(empty($label)) {
                                                $label = "No label ?";
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
                                            if(file_exists($dirMonth."/".Lock::FILES['month'])) { ?>
                                                <svg class="icon" aria-hidden="true">
                                                    <use xlink:href="#lock"></use>
                                                </svg>
                                            <?php }
                                            if(file_exists($dirMonth."/unused.csv")) {
                                                if(State::isSameAs($month, $year, $mp['month'], $mp['year'])) { ?>
                                                    <button aria-hidden="true" type="button" class="btn-invisible" data-toggle="popover" data-trigger="focus"
                                                        data-content="<?= $messages->getMessage('msg7') ?>">
                                                        <svg class="icon icon-selectable red" aria-hidden="true">
                                                            <use xlink:href="#alert-triangle"></use>
                                                        </svg>
                                                    </button>
                                            <?php }
                                            } ?>
                                            </td>
                                            <td>
                                                <button id="<?= $id ?>" type="button" class="collapse-title collapse-title-desktop collapsed" data-toggle="collapse" data-target="#collapse-<?= $id ?>" aria-expanded="false" aria-controls="collapse-<?= $id ?>"><?= $label?></button>
                                                <div class="collapse collapse-item collapse-item-desktop" id="collapse-<?= $id ?>">
                                                    <button type="button" id="etiquette-<?= $id ?>" class="btn but-line etiquette">Etiquette</button>
                                                    <!--<button type="button" id="export-<?= $id ?>" class="btn but-line export">Exporter</button>-->
                                            <?php if($lastRun > 0) {
                                                echo '<button type="button" id="all-'.$id.'" data-run="'.$lastRun.'" data-version="'.$lastVersion.'" class="btn but-line all">Exporter tout</button>';
                                            }
                                            if(State::isLaterThan($month, $year, $mp['month'], $mp['year'])) {
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
                    <!-- Espace -->
                    <div class="tab-pane fade" id="tarifs-space" role="tabpanel" aria-labelledby="space-tab">
                        <div id="tarifs-top">
                            <div id="tarifs-left">
                                <div class="tarifs-column">
                                    <label class="mini-tile for="tarifs-import">
                                        <input id="tarifs-import" type="file" name="tarifs-import" class="zip-file lockable" accept=".zip" />
                                        Importer
                                    </label>
                                    <div type="button" id="tarifs-read" class="mini-tile">Lire</div>
                                </div>
                            </div>
                            <div id="tarifs-center">
                                <div id="tarifs-select"></div>
                                <div id="tarifs-files"></div>
                            </div>
                            <div id="tarifs-right">
                                <div class="tarifs-column">
                                    <div type="button" id="tarifs-load" class="mini-tile desactived-tile">Charger</div>
                                    <div type="button" id="tarifs-remove" class="mini-tile">Effacer</div>
                                </div>
                            </div>
                        </div>
                        <div id="tarifs-bottom">
                            <div type="button" id="tarifs-cancel" class="mini-tile desactived-tile">Annuler</div>
                            <div type="button" id="tarifs-save" class="mini-tile desactived-tile">Sauvegarder</div>
                            <div type="button" id="tarifs-check" class="mini-tile desactived-tile">Vérifier</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include("includes/footer.inc");?>
        <script src="js/jquery-ui.min.js"></script>
        <script src="js/jszip.min.js"></script>
        <script src="js/papaparse.min.js"></script>
        <link rel="stylesheet" href="css/jquery-ui.min.css">
        <script src="js/tarifs.js"></script>
	</body>
</html>
