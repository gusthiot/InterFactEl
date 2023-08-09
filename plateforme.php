<?php
require_once("commons/session.php");
require_once("commons/Data.php");
require_once("from_txt/Label.php");
$plateforme = $_GET['plateforme'];
if(array_key_exists($plateforme, $gestionnaire->getGestionnaire($login))) {
    $name = $gestionnaire->getGestionnaire($login)[$plateforme];
}
else {
    die("Ce numéro de plateforme n'est pas pris en compte !");
}

$message = "";
if(isset($_GET['message'])) {
    if($_GET['message'] == "zip") {
        $message = "Vous devez uploader une archive zip !";
    }
    elseif($_GET['message'] == "data") {
        $message = "Erreur de données";
    }
    elseif($_GET['message'] == "copy") {
        $message = "Erreur de copie sur le disque";
    }
    elseif($_GET['message'] == "error") {
        $message = "Erreur non documentée";
    }
    elseif($_GET['message'] == "success") {
        $message = "Les fichiers ont bien été enregistré";
    }
    else {
        $message = $_GET['message'];
    }
}

?>


<!DOCTYPE html>
<html lang="fr">
    <?php include("commons/header.php");?> 

    <body>
        <div class="container-fluid">	
        <a href="index.php"><i class="bi bi-arrow-return-left"></i></a>	
        <h1 class="text-center p-1 pt-md-5"><?= $name ?></h1>
        <input type="hidden" id="plate" value="<?= $plateforme ?>" />
        
        <div class="text-center">
        <?php
        if(file_exists($plateforme)) { 
        ?>
            <button type="button" id="historique" class="btn btn-outline-dark">Consulter l'historique de la plateforme</button>
            <button type="button" id="export" class="btn btn-outline-dark">Exporter des données de préparation</button>
        <?php
        }
        ?>
            <button type="button" id="launch" class="btn btn-outline-dark">Lancer une préfacturation</button>
        </div>

        <div class="text-center" id="message"><?= $message ?></div>
        <div class="text-center" id="display"></div>
        <div class="text-center">
            <table class="table table-bordered">
        <?php
        if(file_exists($plateforme)) {
            foreach(Data::scanDescSan($plateforme) as $year) {
                foreach(Data::scanDescSan($plateforme."/".$year) as $month) {
                    $versions = Data::scanDescSan($plateforme."/".$year."/".$month);
                    echo '<tr>';
                    echo '<td rowspan="'.count($versions).'">'.$month.' '.$year.'</td>';
                    foreach($versions as $version) {
                        echo '<td>'.$version.'</td>';
                        echo '<td>';
                        foreach(Data::scanDescSan($plateforme."/".$year."/".$month."/".$version) as $run) {
                            $value = 'plateforme='.$plateforme.'&year='.$year.'&month='.$month.'&version='.$version.'&run='.$run;
                            $label = new Label($plateforme."/".$year."/".$month."/".$version."/".$run);
                            $ltxt = $label->getLabel();
                            if($ltxt == "") {
                                $ltxt = $run;
                            }
                            echo ' <button type="button" value="'.$value.'" class="run btn btn-success"> '.$ltxt.' <i class="bi bi-lock"></i></button> ';
                        }
                        echo '</td>';
                    }
                    echo '</tr>';
                }
            }
        }
        ?>
            </table
        </div>

        </div>
        <?php include("commons/footer.php");?> 
        <script src="js/plateforme.js"></script>
  
	</body>
</html>
