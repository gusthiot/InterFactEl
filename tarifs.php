<?php

require_once("assets/Unused.php");
require_once("assets/Version.php");
require_once("assets/ParamZip.php");
require_once("assets/Lock.php");
require_once("assets/Label.php");
require_once("assets/Sap.php");
require_once("assets/Message.php");
require_once("assets/ParamText.php");
require_once("includes/State.php");
require_once("includes/Tarifs.php");
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
$messages = new Message();
$version = Version::load('./');

$paramtext = new ParamText();

$m0 = "";
$m0Dis = "";
$status = "";

/**
 * Customized button to upload prepa
 *
 * @param string $title button title
 * @param string $id upload input id
 * @return string
 */
function uploader(string $title, string $id): string
{
    return '<input id="'.$id.'" type="file" name="'.$id.'" class="zip-file lockable" accept=".zip">
            <label class="btn but-line" for="'.$id.'">
            '.$title.
            '</label>';
}

/**
 * Display a tarif line in the list
 *
 * @param string $year tarif year
 * @param string $month tarif month
 * @param string $dirMonth tarif directory
 * @param string $warning warning text to display if any
 * @param boolean $lock if a lock icon have to be displayed or not
 * @return void
 */
function tarifLine(string $year, string $month, string $dirMonth, string $warning, bool $lock=false): void
{
    if(Label::exists($dirMonth)) {
        $label = Label::load($dirMonth);
        if(empty($label)) {
            $label = "No label ?";
        }
        $id = $year."-".$month;
        echo '<tr>';
        echo '<td>'.$month.' '.$year;
        if($lock) {
            $lastRun = 0;
            $lastVersion = 0;
            foreach(globReverse($dirMonth) as $dirVersion) {
                foreach(globReverse($dirVersion) as $dirRun) {
                    $sap = new Sap($dirRun);
                    if(Lock::exists($dirRun, 'run') || $sap->status() > 1) {
                        $lastRun = basename($dirRun);
                        $lastVersion = basename($dirVersion);
                        break;
                    }
                }
                if($lastRun > 0) {
                    break;
                }
            }
            echo '<svg class="icon" aria-hidden="true">
                    <use xlink:href="#lock"></use>
                </svg>';
        }
        if(!empty($warning)) {
            echo Tarifs::warningButton($warning);
        }
        echo '</td>';
        echo '<td>';
        echo '<button id="'.$id.'" type="button" class="collapse-title collapse-title-desktop collapsed" data-toggle="collapse" data-target="#collapse-'.$id.'" aria-expanded="false" aria-controls="collapse-'.$id.'">'.$label.'</button>
                <div class="collapse collapse-item collapse-item-desktop" id="collapse-'.$id.'">
            <button type="button" id="etiquette-'.$id.'" class="btn but-line etiquette">Etiquette</button>';
        if($lock) {
            echo '<button type="button" id="all-'.$id.'" data-run="'.$lastRun.'" data-version="'.$lastVersion.'" class="btn but-line all">Exporter tout</button>';
        }
        echo '<div id="label-'.$id.'"></div>';
        echo '</div></td></tr>';
    }
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
            <input type="hidden" name="messages" id="messages" value="<?php echo htmlentities(json_encode($messages->getMessages()),ENT_QUOTES); ?>" />
            <input type="hidden" name="paramtext" id="paramtext" value="<?php echo htmlentities(json_encode($paramtext->getParams()),ENT_QUOTES); ?>" />
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

            <?php include("includes/message.inc"); ?>
            <div id="tarifs-content">
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
                                        if(Lock::exists($dirMonth, 'month')) {
                                            tarifLine($year, $month, $dirMonth, "", true);
                                        }
                                        else {
                                            if(Tarifs::v0_exists($dirMonth)) {
                                                if(empty($m0)) {
                                                    $m0Dis = $month."/".$year;
                                                    $m0 = $year.$month;
                                                    $status = Tarifs::status($dirMonth);
                                                    in_array($status, [3, 5, 7]) ? $warning = $messages->getMessage('msg7') : $warning = "";
                                                    tarifLine($year, $month, $dirMonth, $warning);
                                                }
                                                else {
                                                    tarifLine($year, $month, $dirMonth, "");
                                                }
                                            }
                                            else {
                                                tarifLine($year, $month, $dirMonth, Tarifs::warning9($dirMonth, $version));
                                            }
                                        }
                                    }
                                }
                                if(empty($m0)) {
                                    $m = $state->getNextMonth();
                                    $y = $state->getNextYear();
                                    $m0Dis = $m."/".$y;
                                    $m0 = $y.$m;
                                }
                            ?></table>
                        </div>
                    </div>
                    <!-- Espace -->
                    <div class="tab-pane fade" id="tarifs-space" role="tabpanel" aria-labelledby="space-tab">
                    <input type="hidden" name="m0" id="m0" value="<?= $m0 ?>" />
                    <input type="hidden" name="status" id="status" value="<?= $status ?>" />
                        <div id="tarifs-top">
                            <div id="tarifs-left">
                                <div class="tarifs-column">
                                    <label class="tile mini-tile" for="tarifs-import">
                                        <input id="tarifs-import" type="file" name="tarifs-import" class="zip-file lockable" accept=".zip" />
                                        Importer
                                    </label>
                                    <div id="tarifs-read" class="tile mini-tile">Lire</div>
                                </div>
                            </div>
                            <div id="tarifs-center">
                                <div id="tarifs-select"></div>
                                <div id="tarifs-files"></div>
                                <div id="tarifs-manage"></div>
                            </div>
                            <div id="tarifs-right">
                                <div class="tarifs-column">
                                    <div id="tarifs-load" class="tile mini-tile desactived-tile">Ecrire</div>
                                    <div id="tarifs-remove" class="tile mini-tile">Effacer</div>
                                </div>
                            </div>
                        </div>
                            <?= $m0Dis." | status : ".$status ?>

                        <div id="tarifs-bottom">
                            <div id="tarifs-cancel" class="tile mini-tile desactived-tile">Annuler</div>
                            <div id="tarifs-save" class="tile mini-tile desactived-tile">Sauvegarder</div>
                            <div id="tarifs-check" class="tile mini-tile desactived-tile">Vérifier</div>
                        </div>
                    </div>
                <div class="modal fade" id="save-modal" tabindex="-1" role="dialog" aria-labelledby="save-modal-title" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" id="save-modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="scroll-modal-title">Voulez-vous sauvegarder les paramètres existants ?</h5>
                                    <button type="button" class="close" id="close-modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" id="modal-no">Non</button>
                                <button type="button" class="btn btn-primary" id="modal-yes">Oui</button>
                            </div>
                        </div>
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
