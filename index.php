<?php
require_once("session.php");
require_once("assets/Lock.php");
require_once("includes/State.php");

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
            <div id="head"><div id="div-logo"><a href="index.php"><img src="icons/epfl-logo.png" alt="Logo EPFL" id="logo"/></a></div><div id="div-path"><p>Accueil</p></div></div>	
            <h1 class="text-center">Interface de facturation</h1>
            <h6 class="text-center">Welcome <i><?= $user ?></i></h6>
            <?php include("includes/message.php"); 
            if(!empty($lockedUser)) { ?>
                <div class="text-center"><?= $dlTxt ?></div>
            <?php }
            ?>
            <div id="index-canevas">
            <?php
                if($superviseur->isSuperviseur($user)) {
                ?>
                
                <div id="index supervision">
                    <h3 class="">Supervision</h3>
                    <div class="tiles">
                        <div type="button" id="download-config" class="tile center-one">
                            <p>Download CONFIG files</p>
                            <svg class="icon feather icon-tile" aria-hidden="true">
                                <use xlink:href="#download-cloud"></use>
                            </svg>
                        </div>
                        <label class="tile center-one">
                            <form action="controller/uploadConfig.php" method="post" id="form-config" enctype="multipart/form-data" >
                                <input type="file" name="zip_file" id="zip-config" accept=".zip">
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
                if($dataGest) {
                    ?>  
                    
                    <div id="index-gestion">                  
                        <h3 class="">Gestion</h3>
                        <div id="index-facturation">  
                            <h5 class="">Facturation</h5>
                            <div class="tiles">
                            <?php
                            foreach($dataGest['plates'] as $plateforme => $name) {
                            ?>
                                <div class="facturation tile center-two">
                                    <input type="hidden" id="plate-fact" value="<?= $plateforme ?>" />
                                    <p class="num-tile"><?= $plateforme ?></p><p class="nom-tile"><?= $name ?></p>  
                                    <svg class="icon feather icon-tile" aria-hidden="true">
                                        <use xlink:href="#dollar-sign"></use>
                                    </svg>
                                </div>
                            <?php }
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
                                $state = new State();
                                foreach($dataGest['complet'] as $plateforme => $name) {
                                    if(array_key_exists($plateforme, $dataGest['complet'])) {
                                        $available = false;
                                        if(file_exists(DATA.$plateforme)) { 
                                            $available = true;
                                            $state->lastState(DATA.$plateforme, new Lock());
                                            if(empty($state->getLast())) {
                                                $available = false;
                                            }
                                        }
                                        if($available) {   
                                        ?>
                                            <div class="tarifs tile center-two">
                                                <input type="hidden" id="plate-tarifs" value="<?= $plateforme ?>" />
                                                <p class="num-tile"><?= $plateforme ?></p><p class="nom-tile"><?= $name ?></p>
                                                <svg class="icon feather icon-tile" aria-hidden="true">
                                                    <use xlink:href="#settings"></use>
                                                </svg>
                                            </div>
                                        <?php }
                                        else {  
                                        ?>
                                            <div class="center-two desactived-tile">
                                                <p class="num-tile"><?= $plateforme ?></p><p class="nom-tile"><?= $name ?></p>
                                                <i class="bi bi-gear"></i>
                                            </div>
                                        <?php }
                                    }
                                }
                                ?>
                            </div>   
                            <div id="index-simulation">                
                                <h5 class="">Simulation</h5>
                                <div class="tiles">
                                <?php
                                foreach($dataGest['complet'] as $plateforme => $name) {
                                    if(array_key_exists($plateforme, $dataGest['complet'])) {
                                    ?>
                                        <label class="simulation tile center-two">
                                            <form action="controller/uploadPrepa.php" method="post" class="form-simu" enctype="multipart/form-data" >
                                                <input type="hidden" name="plate" id="plate-simu" value="<?= $plateforme ?>" />
                                                <input type="hidden" name="type" id="type" value="SIMU">   
                                                <input id="SIMU" type="file" name="SIMU" '.$disabled.' class="zip_simu lockable" accept=".zip">
                                            </form>
                                            <p class="num-tile"><?= $plateforme ?></p><p class="nom-tile"><?= $name ?></p>
                                            <svg class="icon icon-tile" aria-hidden="true">
                                                <use xlink:href="#activity"></use>
                                            </svg>
                                        </label>
                                    <?php }
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
        <?php include("includes/footer.php");?> 
        <script src="js/index.js"></script>
  
	</body>
</html>
