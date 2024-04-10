<?php
require_once("session.php");
require_once("commons/State.php");
require_once("src/Label.php");
require_once("src/Sap.php");
require_once("src/Lock.php");
if(!isset($_GET["plateforme"])) {
    die("Manque un numéro de plateforme !");
}

$plateforme = $_GET['plateforme'];

if(!array_key_exists($plateforme, $gestionnaire->getGestionnaire($_SESSION['user'])['tarifs'])) {
    die("Ce numéro de plateforme n'est pas pris en compte !");
}

$name = $gestionnaire->getGestionnaire($_SESSION['user'])['plates'][$plateforme];
$sciper = $gestionnaire->getGestionnaire($_SESSION['user'])['sciper'];

$message = "";
if(isset($_SESSION['message'])) {
    if($_SESSION['message'] == "zip") {
        $message = "Vous devez uploader une archive zip !";
    }
    elseif($_SESSION['message'] == "data") {
        $message = "Erreur de données";
    }
    elseif($_SESSION['message'] == "copy") {
        $message = "Erreur de copie sur le disque";
    }
    elseif($_SESSION['message'] == "error") {
        $message = "Erreur non documentée";
    }
    elseif($_SESSION['message'] == "success") {
        $message = "Les fichiers ont bien été enregistré";
    }
    else {
        $message = $_SESSION['message'];
    }
    unset($_SESSION['message']); 
}

?>


<!DOCTYPE html>
<html lang="fr">
    <head>
        <?php include("commons/header.php");?> 
    </head>

    <body>
        <div class="container-fluid">	
            <div id="head"><div id="div-logo"><a href="index.php"><img src="img/EPFL_Logo_Digital_RGB_PROD.png" alt="Logo EPFL" id="logo"/></a></div><div id="div-path"><p><a href="index.php">Accueil</a> > Tarifs <?= $name ?></p></div></div>	
            <h1 class="text-center p-1 pt-md-5"><?= $name ?></h1>
            <div class="text-center">
            <?php
            if(file_exists($plateforme)) { 
                ?>
                <button type="button" id="tarifs" class="btn btn-outline-dark">Tarif</button>
                <button type="button" id="etiquette" class="btn btn-outline-dark">Etiquette</button>
                <button type="button" id="export" class="btn btn-outline-dark">Export</button>
                <?php
            }
            ?>

</div>
<div class="text-center">
            <input name="month" id="month" class="date-picker"/>
            </div>



        </div>
        <?php include("commons/footer.php");?> 
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">

        <script src="js/tarifs.js"></script>
	</body>
</html>
