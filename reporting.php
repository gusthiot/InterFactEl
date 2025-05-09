<?php

require_once("assets/Lock.php");
require_once("includes/State.php");
require_once("session.inc");

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
                <div type="button" id="concatenation" class="select-period tile center-one">
                    <p class="title">Concaténer</p>
                    <svg class="icon feather icon-tile" aria-hidden="true">
                        <use xlink:href="#anchor"></use>
                    </svg>
                </div>
                <div type="button" id="montants" class="select-period tile center-one">
                    <p class="title">Montants facturés</p>
                    <svg class="icon feather icon-tile" aria-hidden="true">
                        <use xlink:href="#anchor"></use>
                    </svg>
                </div>
                <div type="button" id="rabais" class="select-period tile center-one">
                    <p class="title">Rabais & Subsides</p>
                    <svg class="icon feather icon-tile" aria-hidden="true">
                        <use xlink:href="#anchor"></use>
                    </svg>
                </div>
                <div type="button" id="consommations" class="select-period tile center-three">
                    <p class="title">Montants <br/> Consommations <br/> plateforme</p>
                    <svg class="icon feather icon-tile" aria-hidden="true">
                        <use xlink:href="#anchor"></use>
                    </svg>
                </div>
                <div type="button" id="runs" class="select-period tile center-three">
                    <p class="title">Statistiques  <br/>  durées runs machines</p>
                    <svg class="icon feather icon-tile" aria-hidden="true">
                        <use xlink:href="#anchor"></use>
                    </svg>
                </div>
                <div type="button" id="usages" class="select-period tile center-three">
                    <p class="title">Statistiques <br/> d’utilisation machines</p>
                    <svg class="icon feather icon-tile" aria-hidden="true">
                        <use xlink:href="#anchor"></use>
                    </svg>
                </div>
                <div type="button" id="consommables" class="select-period tile center-two">
                    <p class="title">Statistiques <br/> Consommables</p>
                    <svg class="icon feather icon-tile" aria-hidden="true">
                        <use xlink:href="#anchor"></use>
                    </svg>
                </div>
                <div type="button" id="services" class="select-period tile center-two">
                    <p class="title">Statistiques <br/> Services</p>
                    <svg class="icon feather icon-tile" aria-hidden="true">
                        <use xlink:href="#anchor"></use>
                    </svg>
                </div>
                <div type="button" id="penalites" class="select-period tile center-two">
                    <p class="title">  Statistiques <br/> Pénalités</p>
                    <svg class="icon feather icon-tile" aria-hidden="true">
                        <use xlink:href="#anchor"></use>
                    </svg>
                </div>
                <div type="button" id="transactions" class="select-period tile center-two">
                    <p class="title">Statistiques <br/> Transactions</p>
                    <svg class="icon feather icon-tile" aria-hidden="true">
                        <use xlink:href="#anchor"></use>
                    </svg>
                </div>
                <div type="button" id="plateforme" class="select-period tile center-two">
                    <p class="title">Statistiques <br/> Plateforme</p>
                    <svg class="icon feather icon-tile" aria-hidden="true">
                        <use xlink:href="#anchor"></use>
                    </svg>
                </div>
                <div type="button" id="clients" class="select-period tile center-two">
                    <p class="title">Nombre clients <br/> et utilisateurs</p>
                    <svg class="icon feather icon-tile" aria-hidden="true">
                        <use xlink:href="#anchor"></use>
                    </svg>
                </div>
            </div>

            <div id="period"></div>
            <div id="report-content"></div>
        </div>
        <?php include("includes/footer.php");?> 
        <script src="js/jquery-ui.min.js"></script>
        <link rel="stylesheet" href="css/jquery-ui.min.css">
        <script src="js/reporting.js"></script>
	</body>
</html>
