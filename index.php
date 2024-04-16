<?php
require_once("session.php");

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
            <div id="head"><div id="div-logo"><a href="index.php"><img src="img/EPFL_Logo_Digital_RGB_PROD.png" alt="Logo EPFL" id="logo"/></a></div><div id="div-path"><p>Accueil</p></div></div>	
            <h1 class="text-center">Interface de facturation</h1>
            <h6 class="text-center">Welcome <i><?= $_SESSION['user'] ?></i></h6>
            <div id="message"><?= $message ?></div>
            <div id="canevas">
            <?php
                if($superviseur->isSuperviseur($_SESSION['user'])) {
                ?>
                
                <div id="supervision">
                    <h3 class="">Supervision</h3>
                    <div class="tiles">
                        <div type="button" id="download" class="tile center-one">
                            <p>Download CONFIG files</p>
                            <i class="bi bi-download icon-tile"></i>
                        </div>
                        <label class="tile center-one">
                            <form action="controller/uploadConfig.php" method="post" id="upform" enctype="multipart/form-data" >
                                <input type="file" name="zip_file" id="zip_file" accept=".zip">
                            </form>
                            <p>Upload CONFIG files</p>
                            <i class="bi bi-upload icon-tile"></i>
                        </label>
                    </div>
                </div>
            <?php
                }
                if($dataGest = $gestionnaire->getGestionnaire($_SESSION['user'])) {
                    ?>  
                    
                    <div id="gestion">                  
                        <h3 class="">Gestion</h3>
                        <div id="facturation">  
                            <h5 class="">Facturation</h5>
                            <div class="tiles">
                            <?php
                            foreach($dataGest['plates'] as $plateforme => $name) {
                                echo '<div class="facturation tile center-two">
                                        <input type="hidden" id="plateNum" value="'.$plateforme.'" />
                                        <p class="num-tile">'.$plateforme.'</p><p class="nom-tile">'.$name.'</p>
                                        <i class="bi bi-cash-coin icon-tile"></i>
                                    </div>';
                            }
                            ?>
                            </div>
                        </div>
                        <?php
                        if(!empty($gestionnaire->getGestionnaire($_SESSION['user'])['tarifs'])) {
                            ?>      
                            <div id="index-tarifs">                
                                <h5 class="">Nouveaux tarifs</h5>
                                <div class="tiles">
                                <?php
                                foreach($dataGest['tarifs'] as $plateforme => $name) {
                                    if(array_key_exists($plateforme, $gestionnaire->getGestionnaire($_SESSION['user'])['tarifs'])) {
                                        echo '<div class="tarifs tile center-two">
                                                <input type="hidden" id="plateNum" value="'.$plateforme.'" />
                                                <p class="num-tile">'.$plateforme.'</p><p class="nom-tile">'.$name.'</p>
                                                <i class="bi bi-gear icon-tile"></i>
                                            </div>';
                                    }
                                }
                                ?>
                            </div>
                        <?php
                        }
                        ?>
                        </div>
                    </div>
                    <?php
                }
            ?>
            </div>
        </div>
        <?php include("commons/footer.php");?> 
        <script src="js/index.js"></script>
  
	</body>
</html>
