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
checkPlateforme($dataGest, "reporting", $plateforme);

$name = $dataGest['reporting'][$plateforme];
$dir = DATA.$plateforme;

if($dataGest) {
    include("includes/lock.php");
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
                    <p><a href="index.php">Accueil</a> > Reporting <?= $name ?></p>
                    <p><a href="logout.php">Logout</a></p>
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
                    <p>Concaténer</p>
                    <svg class="icon feather icon-tile" aria-hidden="true">
                        <use xlink:href="#anchor"></use>
                    </svg>
                </div>
                <div type="button" id="montants" class="select-period tile center-one">
                    <p>Montants facturés</p>
                    <svg class="icon feather icon-tile" aria-hidden="true">
                        <use xlink:href="#anchor"></use>
                    </svg>
                </div>
                <div type="button" id="rabais" class="select-period tile center-one">
                    <p>Rabais & Subsides</p>
                    <svg class="icon feather icon-tile" aria-hidden="true">
                        <use xlink:href="#anchor"></use>
                    </svg>
                </div>
                <div type="button" id="consomations" class="select-period tile center-three">
                    <p>Montants <br/> Consommations <br/> plateforme</p>
                    <svg class="icon feather icon-tile" aria-hidden="true">
                        <use xlink:href="#anchor"></use>
                    </svg>
                </div>
                <div type="button" id="runs" class="select-period tile center-two">
                    <p>Statistiques  <br/>  durées runs machines</p>
                    <svg class="icon feather icon-tile" aria-hidden="true">
                        <use xlink:href="#anchor"></use>
                    </svg>
                </div>
                <div type="button" id="usages" class="select-period tile center-two">
                    <p>Statistiques <br/> d’utilisation machines</p>
                    <svg class="icon feather icon-tile" aria-hidden="true">
                        <use xlink:href="#anchor"></use>
                    </svg>
                </div>
                <div type="button" id="consommables" class="select-period tile center-two">
                    <p>Statistiques <br/> Consommables</p>
                    <svg class="icon feather icon-tile" aria-hidden="true">
                        <use xlink:href="#anchor"></use>
                    </svg>
                </div>
                <div type="button" id="services" class="select-period tile center-two">
                    <p>Statistiques <br/> Services</p>
                    <svg class="icon feather icon-tile" aria-hidden="true">
                        <use xlink:href="#anchor"></use>
                    </svg>
                </div>
                <div type="button" id="penalites" class="select-period tile center-two">
                    <p>  Statistiques <br/> Pénalités</p>
                    <svg class="icon feather icon-tile" aria-hidden="true">
                        <use xlink:href="#anchor"></use>
                    </svg>
                </div>
                <div type="button" id="transactions" class="select-period tile center-two">
                    <p>Statistiques <br/> Transactions</p>
                    <svg class="icon feather icon-tile" aria-hidden="true">
                        <use xlink:href="#anchor"></use>
                    </svg>
                </div>
                <div type="button" id="plateforme" class="select-period tile center-two">
                    <p>Statistiques <br/> Plateforme</p>
                    <svg class="icon feather icon-tile" aria-hidden="true">
                        <use xlink:href="#anchor"></use>
                    </svg>
                </div>
                <div type="button" id="clients" class="select-period tile center-two">
                    <p>Nombre clients <br/> et utilisateurs</p>
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
