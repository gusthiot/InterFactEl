<?php
require_once("commons/session.php");
$plateforme = $_GET['plateforme'];
if(array_key_exists($plateforme, $gestionnaire->getGestionnaire($login))) {
    $name = $gestionnaire->getGestionnaire($login)[$plateforme];
}
else {
    die("Ce numéro de plateforme n'est pas pris en compte !");
}

function scanDescSan($dir) {
    return array_diff(scandir($dir, SCANDIR_SORT_DESCENDING), array('..', '.'));
}
?>


<!DOCTYPE html>
<html lang="fr">
    <?php include("commons/header.php");?> 

    <body>
        <div class="container-fluid">	
        <a href="index.php"><i class="bi bi-arrow-return-left"></i></a>	
        <h1 class="text-center p-1 pt-md-5"><?= $name ?></h1>
        
        <div class="text-center">
            <button type="button" id="historique" disabled class="btn btn-outline-dark">Consulter l'historique de la plateforme</button>
            <button type="button" id="export" disabled class="btn btn-outline-dark">Exporter des données de préparation</button>
            <button type="button" id="launch" disabled class="btn btn-outline-dark">Lancer une préfacturation</button>
        </div>
        <div class="text-center">
            <table class="table table-bordered">
        <?php
        foreach(scanDescSan($plateforme) as $year) {
            foreach(scanDescSan($plateforme."/".$year) as $month) {
                $versions = scanDescSan($plateforme."/".$year."/".$month);
                echo '<tr>';
                echo '<td rowspan="'.count($versions).'">'.$month.' '.$year.'</td>';
                foreach($versions as $version) {
                    echo '<td>'.$version.'</td>';
                    echo '<td>';
                    foreach(scanDescSan($plateforme."/".$year."/".$month."/".$version) as $run) {
                        $value = 'plateforme='.$plateforme.'&year='.$year.'&month='.$month.'&version='.$version.'&run='.$run;
                        echo ' <button type="button" value="'.$value.'" class="run btn btn-success"> '.$run.' <i class="bi bi-lock"></i></button> ';
                    }
                    echo '</td>';
                }
                echo '</tr>';
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
