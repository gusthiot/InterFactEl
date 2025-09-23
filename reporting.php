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

include("includes/lock.php");

function displayTile($tiles) 
{
    foreach($tiles as $tile) {
    echo '<div type="button" id="'.$tile[0].'" class="select-period tile center-'.$tile[2].'">
            <p class="title">'.$tile[1].'</p>
            <svg class="icon feather icon-tile" aria-hidden="true">
                <use xlink:href="#anchor"></use>
            </svg>
        </div>';
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
            <input type="hidden" name="plate" id="plate" value="<?= $plateforme ?>" />        
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
            <?php include("includes/message.php");
            if(!empty($lockUser)) { ?>
                <div class="text-center"><?= $dlTxt ?></div>
            <?php }
            ?>
            <div id="report-tiles">
                <div class="report-chapter">
                    <h5>Clients & Utilisateurs</h5>
                    <div class="tiles">
                    <?php
                        $tiles = [
                        //    ["concatenation", "Concaténer", "one"],
                            ["montants", "Facturation", "one"],
                            ["rabais", "Rabais & Subsides", "one"],
                            ["clients", "Nombre Clients <br/> & Utilisateurs", "two"],
                            ["transactions", "Statistiques <br/> Transactions", "two"]
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
                            ["usages", "Statistiques <br/> Utilisation machines", "three"],
                            ["consommables", "Statistiques <br/> Consommables", "two"],
                            ["services", "Statistiques <br/> Services", "two"],
                            ["penalites", "Statistiques <br/> Pénalités", "two"]
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
                            ["propres", "Consommations <br/> propres Plateforme", "two"],
                            ["operateur", "Statistiques <br/> Heures opérateurs", "two"],
                            ["plateforme", "Statistiques <br/> Projets plateforme", "two"],
                            ["runs", "Statistiques <br/> Runs machines", "two"]
                            //["consommations", "Montants <br/> Consommations <br/> plateforme", "three"]
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
                            ["t1", "Extrait T1 Facturation (facture)", "three"],
                            ["t2", "Extrait T2 Facturation (annexe)", "three"],
                            ["t3f", "Extrait T3 Facturation (détails)", "three"],
                            ["t3s", "Extrait T3 Statistiques (détails)", "three"]
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
        <?php include("includes/footer.php");?> 
        <script src="js/jquery-ui.min.js"></script>
        <link rel="stylesheet" href="css/jquery-ui.min.css">
        <script src="js/reporting.js"></script>
	</body>
</html>
