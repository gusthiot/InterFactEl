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
                <h1 class="text-center p-1 pt-md-5"><?= $name ?></h1>
            </div>
            <input type="hidden" name="plate" id="plate" value="<?= $plateforme ?>" />        
            <?php include("includes/message.php");
            if(!empty($lockUser)) { ?>
                <div class="text-center"><?= $dlTxt ?></div>
            <?php }
            ?>
            <div class="text-center">
            <?php    
            $choices = [];
            foreach(array_reverse(glob($dir."/*", GLOB_ONLYDIR))  as $dirYear) {
                $year = basename($dirYear);
                foreach(array_reverse(glob($dirYear."/*", GLOB_ONLYDIR)) as $dirMonth) {
                    $month = basename($dirMonth);
                    if (file_exists($dirMonth."/lockm.csv")) {
                        $choices[$year.$month] = [$year, $month];
                    }
                }
            }
            if(count($choices) > 0) {
            ?>
                <div id="dates">
                    <div id="first">
                        <label for="from">De</label>
                        <select id="from" class="custom-select lockable" <?= $disabled ?> >
                        <option disabled selected></option>
                        <?php
                        $i = 0;
                        foreach($choices as $key=>$choice) {
                            echo '<option value="'.$key.'">'.$choice[1]." ".$choice[0].'</option>';
                        }
                        ?>
                        </select>
                    </div>
                    <div id="last">
                    </div>
                    <div id="generate">
                    </div>
                </div>
            <?php } ?>
            </div>
        </div>
        <?php include("includes/footer.php");?> 
        <script src="js/jquery-ui.min.js"></script>
        <link rel="stylesheet" href="css/jquery-ui.min.css">
        <script src="js/reporting.js"></script>
	</body>
</html>
