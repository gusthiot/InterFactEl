<?php
require_once("session.inc.php");
require_once("includes/State.php");
require_once("assets/Parametres.php");
require_once("assets/Lock.php");
require_once("assets/Label.php");
require_once("assets/Sap.php");

checkGest($dataGest);
if(!isset($_GET["plateforme"])) {
    $_SESSION['alert-danger'] = "Manque un numéro de plateforme !";
    header('Location: index.php');
    exit;
}

$plateforme = $_GET['plateforme'];
checkPlateforme($dataGest, $plateforme);

$name = $gestionnaire->getGestionnaire($user)['plates'][$plateforme];
$dir = DATA.$plateforme;
$available = false;
$state = new State();
if(file_exists($dir)) { 
    $available = true;
    $state->lastState($dir, new Lock());
    if(empty($state->getLast())) {
        $available = false;
    }
}

?>


<!DOCTYPE html>
<html lang="fr">
    <head>
        <?php include("includes/header.php");?> 
    </head>

    <body>
        <div class="container-fluid">	
            <div id="head"><div id="div-logo"><a href="index.php"><img src="icons/epfl-logo.png" alt="Logo EPFL" id="logo"/></a></div><div id="div-path"><p><a href="index.php">Accueil</a> > Tarifs <?= $name ?></p></div></div>	
            <h1 class="text-center p-1 pt-md-5"><?= $name ?></h1>
                    <div class="text-center" id="buttons">
                <?php
                if($available) { 
                    ?>
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
                    <?php
                }
                ?>
            </div>

            <?php include("includes/message.php"); ?>
            <div class="text-center" id="tarifs-content">
            <?php
            if($available) {
            ?>
                <input type="hidden" id="last-month" value="<?= State::getPreviousMonth($state->getLastYear(), $state->getLastMonth()) ?>" />
                <input type="hidden" id="last-year" value="<?= State::getPreviousYear($state->getLastYear(), $state->getLastMonth()) ?>" />
                <table id="tarifs" class="table table-boxed">
                    <?php
                    foreach(State::scanDesc($dir) as $year) {
                        foreach(State::scanDesc($dir."/".$year) as $month) {
                            if (file_exists($dir."/".$year."/".$month."/".Parametres::NAME)) {
                                $label = new Label();
                                $labtxt = $label->load($dir."/".$year."/".$month);
                                if(empty($labtxt)) {
                                    $labtxt = "No label ?";
                                }
                                $moment = 0;

                                if(State::isSame($state->getLastMonth(), $state->getLastYear(), $month, $year)) {
                                    $moment = 1;
                                }
                                elseif(State::isLater($state->getLastMonth(), $state->getLastYear(), $month, $year)) {
                                    $moment = 2;
                                }

                                $lastRun = 0;
                                $lastVersion = 0;
                                foreach(State::scanDesc($dir."/".$year."/".$month) as $version) {
                                    foreach(State::scanDesc($dir."/".$year."/".$month."/".$version) as $run) {                                        
                                        $sap = new Sap();
                                        $sap->load($dir."/".$year."/".$month."/".$version."/".$run);
                                        $status = $sap->status();
                                        if($status > 1) {
                                            $lastRun = $run;
                                            $lastVersion = $version;
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
                                if (file_exists($dir."/".$year."/".$month."/lockm.csv")) { ?>
                                    <svg class="icon" aria-hidden="true">
                                        <use xlink:href="#lock"></use>
                                    </svg>
                                <?php } ?>
                                </td>
                                <td>
                                    <button id="<?= $id ?>" type="button" class="collapse-title collapse-title-desktop collapsed" data-toggle="collapse" data-target="#collapse-<?= $id ?>" aria-expanded="false" aria-controls="collapse-<?= $id ?>"><?= $labtxt?></button>
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
                ?></table><?php
            }
            ?>
            </div>
        </div>
        <?php include("includes/footer.php");?> 
        <script src="js/jquery-ui.min.js"></script>
        <link rel="stylesheet" href="css/jquery-ui.min.css">
        <script src="js/tarifs.js"></script>
	</body>
</html>
