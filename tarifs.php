<?php
require_once("session.php");
require_once("commons/State.php");
require_once("src/Lock.php");
require_once("src/Label.php");
if(!isset($_GET["plateforme"])) {
    die("Manque un numéro de plateforme !");
}

$plateforme = $_GET['plateforme'];

if(!array_key_exists($plateforme, $gestionnaire->getGestionnaire($_SESSION['user'])['tarifs'])) {
    die("Ce numéro de plateforme n'est pas pris en compte !");
}

$name = $gestionnaire->getGestionnaire($_SESSION['user'])['plates'][$plateforme];
$sciper = $gestionnaire->getGestionnaire($_SESSION['user'])['sciper'];

if(file_exists($plateforme)) { 
    $lockv = new Lock();
    $state->lastState($plateforme, $lockv);
}

$message = "";
if(isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
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
            <input type="hidden" name="plate" id="plate" value="<?= $plateforme ?>" />
                    <div class="text-center" id="buttons">
                <?php
                if(file_exists($plateforme)) { 
                    ?>
                    <form action="controller/uploadTarifs.php" method="post" id="upform" enctype="multipart/form-data" >
                        <input type="hidden" name="plate" id="plate" value="<?= $plateforme ?>" />
                        <button type="button" id="import" class="btn but-line">Importer de nouveaux tarifs</button>
                        <div id="more" class="text-center">
                        </div>
                    </form>
                    <?php
                }
                ?>
            </div>

            <div class="text-center" id="message"><?= $message ?></div>
            <div class="text-center" id="arbo">
            <?php
            if(file_exists($plateforme)) {   
            ?>
                <input type="hidden" id="lastMonth" value="<?= $state->getLastMonth() ?>" />
                <input type="hidden" id="lastYear" value="<?= $state->getLastYear() ?>" />
                <table class="table table-bordered">
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
        <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
        <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
        <script src="js/tarifs.js"></script>
	</body>
</html>
