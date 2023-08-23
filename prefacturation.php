<?php
require_once("session.php");
require_once("src/Label.php");

$plateforme = $_GET['plateforme'];
$year = $_GET['year'];
$month = $_GET['month'];
$version = $_GET['version'];
$run = $_GET['run'];
$dir = $plateforme."/".$year."/".$month."/".$version."/".$run;
$name = $gestionnaire->getGestionnaire($login)['plates'][$plateforme];
$suf = "_".$name."_".$year."_".$month."_".$version;

$label = new Label();
$ltxt = $label->load($dir);
if($ltxt == "") {
    $ltxt = $run;
}
?>


<!DOCTYPE html>
<html lang="fr">
    <?php include("commons/header.php");?> 

    <body>
        <div class="container-fluid">	
        <a href="plateforme.php?plateforme=<?= $plateforme ?>"><i class="bi bi-arrow-return-left"></i></a>	
        <h1 class="text-center p-1 pt-md-5"><?= $ltxt ?></h1>	
        <input type="hidden" id="dir" value="<?= $dir ?>" />
        <input type="hidden" id="suf" value="<?= $suf ?>" />
        
        <div class="text-center">
            <button type="button" id="label" class="btn btn-outline-dark">Etiqueter</button>
            <button type="button" id="info" class="btn btn-outline-dark">Afficher les infos</button>
            <button type="button" id="bills" class="btn btn-outline-dark">Afficher la liste des factures</button>
            <button type="button" id="ticket" class="btn btn-outline-dark">Contrôler le ticket</button>
            <button type="button" id="changes" class="btn btn-outline-dark">Afficher les modifications</button>
            <button type="button" id="invalidate" class="btn btn-outline-dark">Invalider</button>
            <button type="button" id="bilans" class="btn btn-outline-dark">Exporter Bilans & Stats</button>
            <button type="button" id="annexes" class="btn btn-outline-dark">Exporter Annexes csv</button>
            <button type="button" id="all" class="btn btn-outline-dark">Exporter Tout</button>
            <button type="button" id="send" class="btn btn-outline-success">Envoi SAP</button>
            <button type="button" id="finalize" disabled class="btn btn-outline-info">Finaliser SAP</button>
            <button type="button" id="resend" disabled class="btn btn-outline-danger">Renvoi SAP</button>
        </div>

        <div class="text-center" id="message"></div>

        <div class="text-center" id="display"></div>

        <?php
        ?>

        </div>
        <?php include("commons/footer.php");?> 
        <script src="js/prefacturation.js">var dir = "<?= $dir ?>";</script>
	</body>
</html>
