<?php
require_once("session.php");

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
    <head>
        <?php include("commons/header.php");?> 
    </head>

    <body>
        <div class="container-fluid">		
        <h1 class="text-center pt-md-5">Interface de facturation</h1>
        <h2 class="text-center pt-md-5">Welcome <?= $_SESSION['user'] ?></h2>
        <?php
            if($superviseur->isSuperviseur($_SESSION['user'])) {
            ?>
            <h3 class="text-center pt-md-5">Supervision</h3>
            <div class="text-center">
                <button type="button" id="download" class="btn btn-outline-dark">Download CONFIG files</button>
                <div>
                    <form action="controller/uploadConfig.php" method="post" id="upform" enctype="multipart/form-data" >
                        <input type="file" name="zip_file" id="zip_file" accept=".zip">
                        <button type="button" id="upload" class="btn btn-outline-dark">Upload CONFIG files</button>
                    </form>
                </div>
                <div id="message"><?= $message ?></div>
            </div>
        <?php
            }
            if($dataGest = $gestionnaire->getGestionnaire($_SESSION['user'])) {
                ?>                    
                <h3 class="text-center pt-md-5">Gestion</h3>
                <div>
                <?php
                foreach($dataGest['plates'] as $plateforme => $name) {
                    echo '<button type="button" value="'.$plateforme.'" class="plateforme btn btn-primary">'.$plateforme.' - '.$name.'</button>';
                }
                ?>
                </div>
        <?php
            }
        ?>

        </div>
        <?php include("commons/footer.php");?> 
        <script src="js/index.js"></script>
  
	</body>
</html>
