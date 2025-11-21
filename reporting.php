<?php

require_once("assets/Lock.php");
require_once("includes/State.php");
require_once("session.inc");

/**
 * Page to generate and display different reports
 */

if(!isset($_GET["plateforme"])) {
    $_SESSION['alert-danger'] = "Manque un numéro de plateforme !";
    header('Location: index.php');
    exit;
}

$plateforme = $_GET['plateforme'];
checkPlateforme("reporting", $plateforme);

$name = DATA_GEST['reporting'][$plateforme];
$dir = DATA.$plateforme;

include("includes/lock.inc");

/**
 * Displays html tiles from parameters
 *
 * @param array $tiles tiles parameters
 * @return void
 */
function displayTile(array $tiles): void 
{
    foreach($tiles as $tile) {
    echo '<div type="button" id="'.$tile[0].'" class="select-period tile center-'.$tile[2].'">
            <p class="title">'.$tile[1].'</p>
            <svg class="icon feather icon-tile" aria-hidden="true">
                <use xlink:href="'.$tile[3].'"></use>
            </svg>
        </div>';
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
            <input type="hidden" name="disabled" id="disabled" value="<?= $disabled ?>" />
            <div id="head">
                <div id="div-logo">
                    <a href="index.php"><img src="icons/epfl-logo.png" alt="Logo EPFL" id="logo"/></a>
                </div>
                <div id="div-path">
                    <p class="title"><a href="index.php">Accueil</a> > Reporting <?= $name ?></p>
                    <p class="title"><a href="logout.php">Logout</a></p>
                </div>
            </div>	
            <div class="title <?php if(TEST_MODE) echo "test";?>">
                <h1 class="text-center p-1"><?= $name ?></h1>
            </div>
            <input type="hidden" name="plate" id="plate" value="<?= $plateforme ?>" />        
            <?php include("includes/message.inc");
            if(!empty($lockUser)) { ?>
                <div class="text-center"><?= $dlTxt ?></div>
            <?php }
            ?>
            <div id="report-parameters">
                <svg class="icon feather icon-parameters" aria-hidden="true">
                    <use xlink:href="#settings"></use>
                </svg>
                <div id="report-inputs" >
                    <div class="custom-controls-inline">
                        Séparateur : &nbsp; <div class="custom-control custom-radio">
                            <input type="radio" value="pv" id="pv" name="separator" class="custom-control-input"
                        <?php
                                if($_SESSION['separator'] == "pv") {
                                    echo "checked";
                                }
                        ?>
                            >
                            <label class="custom-control-label" for="pv">Point-virgule</label>
                        </div>
                        <div class="custom-control custom-radio">
                            <input type="radio" value="v" id="v" name="separator" class="custom-control-input"
                        <?php
                                if($_SESSION['separator'] == "v") {
                                    echo "checked";
                                }
                        ?>
                            >
                            <label class="custom-control-label" for="v">Virgule</label>
                        </div>
                    </div>
                    <div class="custom-controls-inline">
                        Encodage : &nbsp;  <div class="custom-control custom-radio">
                            <input type="radio" value="Windows-1252" id="win" name="encoding" class="custom-control-input"
                        <?php 
                                if($_SESSION['encoding'] == "Windows-1252") {
                                    echo "checked";
                                }
                        ?>
                            >
                            <label class="custom-control-label" for="win">Windows-1252</label>
                        </div>
                        <div class="custom-control custom-radio">
                            <input type="radio" value="UTF-8" id="utf" name="encoding" class="custom-control-input"
                        <?php
                                if($_SESSION['encoding'] == "UTF-8") {
                                    echo "checked";
                                }
                        ?>                    
                            >
                            <label class="custom-control-label" for="utf">UTF-8</label>
                        </div>
                    </div>
                </div>
            </div>
            <div id="report-tiles">
                <div class="report-chapter">
                    <h5>Clients & Utilisateurs</h5>
                    <div class="tiles">
                    <?php
                        $tiles = [
                            ["montants", "Facturation", "one", "#dollar-sign"],
                            ["rabais", "Rabais & Subsides", "one", "#gift"],
                            ["clients", "Nombre Clients <br/> & Utilisateurs", "two", "#users"],
                            ["transactions", "Statistiques <br/> Transactions", "two", "#trending-up"]
                        ];
                        displayTile($tiles);
                    ?>
                    </div>
                </div>
                <div class="report-chapter">
                    <h5>Prestations</h5>
                    <div class="tiles">
                    <?php
                        $tiles = [
                            ["usages", "Statistiques <br/> Utilisation machines", "three", "#trending-up"],
                            ["consommables", "Statistiques <br/> Consommables", "two", "#trending-up"],
                            ["services", "Statistiques <br/> Services", "two", "#trending-up"],
                            ["penalites", "Statistiques <br/> Pénalités", "two", "#trending-up"]
                        ];
                        displayTile($tiles);
                    ?>
                    </div>
                </div>
                <div class="report-chapter">
                    <h5>Plateforme</h5>
                    <div class="tiles">
                    <?php
                        $tiles = [           
                            ["propres", "Consommations <br/> propres Plateforme", "two", "#shopping-cart"],
                            ["operateur", "Statistiques <br/> Heures opérateurs", "two", "#clock"],
                            ["plateforme", "Statistiques <br/> Projets plateforme", "two", "#star"],
                            ["runs", "Statistiques <br/> Runs machines", "two", "#anchor"]
                        ];
                        displayTile($tiles);
                    ?>
                    </div>
                </div>
                <div class="report-chapter">
                    <h5>Extraits</h5>
                    <div class="tiles">
                    <?php
                        $tiles = [
                            ["t1", "Extrait T1 Facturation <br/> (facture)", "three", "#filter"],
                            ["t2", "Extrait T2 Facturation <br/> (annexe)", "three", "#filter"],
                            ["t3f", "Extrait T3 Facturation <br/> (détails)", "three", "#filter"],
                            ["t3s", "Extrait T3 Statistiques <br/> (détails)", "three", "#filter"]
                        ];
                        displayTile($tiles);
                    ?>
                    </div>
                </div>
            </div>
            <div id="back"><a href="#">
                <svg class="icon feather" aria-hidden="true">
                    <use xlink:href="#arrow-left"></use>
                </svg>
                Back</a>
            </div>
            <div id="report-title"></div>
            <div id="report-period"></div>
            <div id="report-content"></div>
        </div>
        <?php include("includes/footer.inc");?> 
        <script src="js/jquery-ui.min.js"></script>
        <link rel="stylesheet" href="css/jquery-ui.min.css">
        <script src="js/reporting.js"></script>
	</body>
</html>
