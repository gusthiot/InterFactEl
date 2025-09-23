<?php

require_once("assets/Lock.php");
require_once("includes/State.php");
require_once("session.inc");

include("includes/lock.php");

/**
 * Main page
 */

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
                    <p>Accueil</p>
                    <p><a href="logout.php">Logout</a></p>
                </div>
            </div>	
            <div class="title <?php if(TEST_MODE) echo "test";?>">
                <h1 class="text-center p-1">Interface de facturation</h1>
                <h6 class="text-center">Welcome <i><?= USER ?></i></h6>
            </div>
            <?php include("includes/message.php"); 
            if(!empty($lockUser)) { ?>
                <div class="text-center"><?= $dlTxt ?></div>
            <?php }
            ?>
            <div id="index-canevas">
            <?php
                if(IS_SUPER) {
                    // Only supervisor can upload/download config files
                ?>
                
                <div class="index-primary">
                    <h3>Supervision</h3>
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
                if(DATA_GEST) {
                    ?>             
                    <div class="index-primary">                  
                        <h3>Gestion</h3>
                        <?php
                        if(!empty(DATA_GEST['facturation'])) {
                            ?>   
                            <div class="index-secondary">  
                                <h5>Facturation</h5>
                                <div class="tiles">
                                <?php
                                foreach(DATA_GEST['facturation'] as $plateforme => $name) {
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
                        }
                        if(!empty(DATA_GEST['tarifs'])) {
                            ?>      
                            <div class="index-secondary">                
                                <h5>Tarifs</h5>
                                <div class="tiles">
                                <?php
                                foreach(DATA_GEST['tarifs'] as $plateforme => $name) {
                                    $available = false;
                                    if(file_exists(DATA.$plateforme)) { 
                                        $available = true;
                                        $state = new State(DATA.$plateforme);
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
                                        <div class="desactived-tile center-two">
                                            <p class="num-tile"><?= $plateforme ?></p><p class="nom-tile"><?= $name ?></p>
                                            <svg class="icon feather icon-tile" aria-hidden="true">
                                            </svg>
                                        </div>
                                    <?php 
                                    }
                                }
                                ?>
                                </div>  
                            </div>   
                        <?php
                        }
                        if(!empty(DATA_GEST['reporting'])) {
                            ?>      
                            <div class="index-secondary">  
                                <h5>Reporting</h5>
                                <div class="tiles">
                                <?php
                                foreach(DATA_GEST['reporting'] as $plateforme => $name) {
                                ?>
                                    <div class="reporting tile center-two">
                                        <input type="hidden" id="plate-report" value="<?= $plateforme ?>" />
                                        <p class="num-tile"><?= $plateforme ?></p><p class="nom-tile"><?= $name ?></p>  
                                        <svg class="icon feather icon-tile" aria-hidden="true">
                                            <use xlink:href="#book"></use>
                                        </svg>
                                    </div>
                                <?php }
                                ?>
                                </div>
                            </div> 
                        <?php
                        }
                        ?>
                    </div>
                    <?php
                }
            ?>
            <div class="index-primary">
                <h3>Outils</h3>
                <div class="tiles">
                    <label class="tile center-one">
                        <form action="controller/viewTicket.php" method="post" id="form-view" enctype="multipart/form-data" >
                            <input type="file" name="zip_file" id="zip-view" accept=".zip">
                        </form>
                        <p>Visionner Tickets</p>
                        <svg class="icon feather icon-tile" aria-hidden="true">
                            <use xlink:href="#eye"></use>
                        </svg>
                    </label>
                    <label class="simulation tile center-one">
                        <form action="controller/uploadPrepa.php" method="post" class="form-simu" enctype="multipart/form-data" >
                            <input type="hidden" name="type" id="type" value="SIMU">   
                            <input id="SIMU" type="file" name="SIMU" <?= $disabled ?> class="zip-simu lockable" accept=".zip">
                        </form>
                        <p>Simulation</p>
                        <svg class="icon icon-tile" aria-hidden="true">
                            <use xlink:href="#activity"></use>
                        </svg>
                    </label>
                </div>
            </div>
        </div>
        <?php include("includes/footer.php");?> 
        <script src="js/index.js"></script>
  
	</body>
</html>
