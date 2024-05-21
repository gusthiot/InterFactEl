<?php
require_once("session.php");
require_once("assets/Lock.php");
require_once("commons/State.php");

include("commons/lock.php");

?>


<!DOCTYPE html>
<html lang="fr">
    <head>
        <?php include("commons/header.php");?> 
    </head>

    <body>
        <div class="container-fluid">	
            <div id="head"><div id="div-logo"><a href="index.php"><img src="icons/epfl-logo.png" alt="Logo EPFL" id="logo"/></a></div><div id="div-path"><p>Accueil</p></div></div>	
            <h1 class="text-center">Interface de facturation</h1>
            <h6 class="text-center">Welcome <i><?= $_SESSION['user'] ?></i></h6>
            <?php include("commons/message.php"); 
            if(!empty($lockedUser)) {
                echo'<div class="text-center">'.$dlTxt.'</div>';
            }
            ?>
            <div id="canevas">
            <?php
                if($superviseur->isSuperviseur($_SESSION['user'])) {
                ?>
                
                <div id="supervision">
                    <h3 class="">Supervision</h3>
                    <div class="tiles">
                        <div type="button" id="download" class="tile center-one">
                            <p>Download CONFIG files</p>
                            <svg class="icon feather icon-tile" aria-hidden="true">
                                <use xlink:href="#download-cloud"></use>
                            </svg>
                        </div>
                        <label class="tile center-one">
                            <form action="controller/uploadConfig.php" method="post" id="upform" enctype="multipart/form-data" >
                                <input type="file" name="zip_file" id="zip_config" accept=".zip">
                            </form>
                            <p>Upload CONFIG files</p>
                            <svg class="icon feather icon-tile" aria-hidden="true">
                                <use xlink:href="#upload-cloud"></use>
                            </svg>
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
                                        <svg class="icon feather icon-tile" aria-hidden="true">
                                            <use xlink:href="#dollar-sign"></use>
                                        </svg>
                                    </div>';
                            }
                            ?>
                            </div>
                        </div>
                        <?php
                        if(!empty($dataGest['complet'])) {
                            ?>      
                            <div id="index-tarifs">                
                                <h5 class="">Nouveaux tarifs</h5>
                                <div class="tiles">
                                <?php
                                foreach($dataGest['complet'] as $plateforme => $name) {
                                    if(array_key_exists($plateforme, $dataGest['complet'])) {
                                        $available = false;
                                        if(file_exists($plateforme)) { 
                                            $available = true;
                                            $state->lastState($plateforme, new Lock());
                                            if(empty($state->getLast())) {
                                                $available = false;
                                            }
                                        }
                                        if($available) {   
                                            echo '<div class="tarifs tile center-two">
                                                    <input type="hidden" id="plateNum" value="'.$plateforme.'" />
                                                    <p class="num-tile">'.$plateforme.'</p><p class="nom-tile">'.$name.'</p>
                                                    <svg class="icon feather icon-tile" aria-hidden="true">
                                                        <use xlink:href="#settings"></use>
                                                    </svg>
                                                </div>';
                                        }
                                        else {  
                                            echo '<div class="center-two desactived-tile">
                                                    <p class="num-tile">'.$plateforme.'</p><p class="nom-tile">'.$name.'</p>
                                                    <i class="bi bi-gear"></i>
                                                </div>';
                                        }
                                    }
                                }
                                ?>
                            </div>   
                            <div id="simulation">                
                                <h5 class="">Simulation</h5>
                                <div class="tiles">
                                <?php
                                foreach($dataGest['complet'] as $plateforme => $name) {
                                    if(array_key_exists($plateforme, $dataGest['complet'])) {
                                        echo '<label class="simulation tile center-two">
                                                <form action="controller/uploadPrepa.php" method="post" class="factform" enctype="multipart/form-data" >
                                                    <input type="hidden" name="plate" id="plate" value="'.$plateforme.'" />
                                                    <input type="hidden" name="type" id="type" value="SIMU">   
                                                    <input id="SIMU" type="file" name="SIMU" '.$disabled.' class="zip_simu lockable" accept=".zip">
                                                </form>
                                                <p class="num-tile">'.$plateforme.'</p><p class="nom-tile">'.$name.'</p>
                                                <svg class="icon icon-tile" aria-hidden="true">
                                                    <use xlink:href="#activity"></use>
                                                </svg>
                                            </label>';
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
