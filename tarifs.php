<?php
require_once("session.php");
require_once("commons/State.php");
require_once("assets/Lock.php");
require_once("assets/Label.php");
if(!isset($_GET["plateforme"])) {
    die("Manque un numéro de plateforme !");
}

$plateforme = $_GET['plateforme'];

if(!array_key_exists($plateforme, $gestionnaire->getGestionnaire($_SESSION['user'])['complet'])) {
    die("Ce numéro de plateforme n'est pas pris en compte !");
}

$name = $gestionnaire->getGestionnaire($_SESSION['user'])['plates'][$plateforme];

$available = false;
if(file_exists($plateforme)) { 
    $available = true;
    $state->lastState($plateforme, new Lock());
    if(empty($state->getLast())) {
        $available = false;
    }
}

?>


<!DOCTYPE html>
<html lang="fr">
    <head>
        <?php include("commons/header.php");?> 
    </head>

    <body>
        <div class="container-fluid">	
            <div id="head"><div id="div-logo"><a href="index.php"><img src="icons/epfl-logo.png" alt="Logo EPFL" id="logo"/></a></div><div id="div-path"><p><a href="index.php">Accueil</a> > Tarifs <?= $name ?></p></div></div>	
            <h1 class="text-center p-1 pt-md-5"><?= $name ?></h1>
                    <div class="text-center" id="buttons">
                <?php
                if($available) { 
                    ?>
                    <form action="controller/uploadTarifs.php" method="post" id="upform" enctype="multipart/form-data" >
                        <input type="hidden" name="plate" id="plate" value="<?= $plateforme ?>" />
                        <input name="month-picker" id="month-picker" class="date-picker"/>
                        <div>
                            <label class="btn but-line">
                                <input type="file" id="zip-tarifs" name="zip_file" class="zip_file" accept=".zip">
                                Importer de nouveaux tarifs applicables dès ce mois
                            </label>
                        </div>
                    </form>
                    <?php
                }
                ?>
            </div>

            <?php include("commons/message.php"); ?>
            <div class="text-center" id="arbo">
            <?php
            if($available) {
            ?>
                <input type="hidden" id="lastMonth" value="<?= $state->getLastMonth() ?>" />
                <input type="hidden" id="lastYear" value="<?= $state->getLastYear() ?>" />
                <table class="table table-boxed">
                    <?php
                    foreach(State::scanDescSan($plateforme) as $year) {
                        foreach(State::scanDescSan($plateforme."/".$year) as $month) {
                            if (file_exists($plateforme."/".$year."/".$month."/parametres.zip")) {
                                $label = new Label();
                                $labtxt = $label->load($plateforme."/".$year."/".$month);
                                if(empty($labtxt)) {
                                    $labtxt = "No label ?";
                                }
                                $id = $year."-".$month;
                                echo '<tr>';
                                echo '<td>'.$month.' '.$year.'</td>';
                                echo '<td><span><button id="'.$id.'" type="button" class="btn but-white param">'.$labtxt.'</button></span><span id="more-'.$id.'"></span></td>';
                                echo '</tr>';
                            }
                        }
                    }
                ?></table><?php
            }
            ?>
            </div>
        </div>
        <?php include("commons/footer.php");?> 
        <script src="js/jquery-ui.min.js"></script>
        <link rel="stylesheet" href="css/jquery-ui.min.css">
        <script src="js/tarifs.js"></script>
	</body>
</html>
