<?php
require_once("session.inc");
require_once("assets/Ticket.php");

checkGest($dataGest);
if(!isset($_GET["plate"]) || !isset($_GET["year"]) || !isset($_GET["month"]) || !isset($_GET["version"]) || !isset($_GET["run"])) {
    $_SESSION['alert-danger'] = "Manque un paramètre !";
    header('Location: index.php');
    exit;
}

$plateforme = $_GET['plate'];
checkPlateforme($dataGest, $plateforme);

$year = $_GET['year'];
$month = $_GET['month'];
$version = $_GET['version'];
$run = $_GET['run'];

$dir = DATA.$plateforme."/".$year."/".$month."/".$version."/".$run;
$s = [];

$ticket = new Ticket($dir."/ticket.json");
$clients = json_decode($ticket->getTicket(), true);
ksort($clients);

?>


<!DOCTYPE html>
<html lang="fr">
    <head>
        <?php include("includes/header.php");?> 
        <link rel="stylesheet" href="reveal.js/dist/reveal.css">
        <link rel="stylesheet" href="reveal.js/dist/theme/white.css">
        <link rel="stylesheet" href="css/ticket.css">
    </head>

    <body>
        <input type="hidden" id="plate" value="<?= $plateforme ?>" />
        <input type="hidden" id="year" value="<?= $year ?>" />
        <input type="hidden" id="month" value="<?= $month ?>" />
        <input type="hidden" id="version" value="<?= $version ?>" />
        <input type="hidden" id="run" value="<?= $run ?>" />
        <div id="combo">
            <select name="client" onchange="changeClient(this)">
            <?php
            $i = 0;
            foreach(array_keys($clients) as $title) {
                echo '<option value="'.$i.'">'.$title.'</option>';
                $i++;
            }
            ?>
            </select>
        </div>
        <div class="reveal">
            <div class="slides">
            <?php
            foreach($clients as $client) {
            ?>
                <section id="<?=$client['code']?>">
                    <section>
                        <div id="entete">
                        <?=$client['code']?> <br />
                        <?=$client['abrev']?> <br />
                        <?=$client['nom2']?> <br />
                        <?=$client['nom3']?> <br />
                        </div><br />
                        <div id="reference"><?=$client['ref']?></div>
                            <table id="tableau">
                                <tr>
                                    <td> Description </td>
                                    <td> Net amount <br /> [CHF] </td>
                                </tr>
                            <?php
                            foreach($client['articles'] as $article) {
                            ?>
                                <tr>
                                    <td> <?=$article['descr']?> <br /> <?=$article['texte']?> </td>
                                    <td id="toright"> <?=$article['net']?> </td>
                                </tr>
                            <?php }
                            ?>
                                <tr>
                                    <td id="toright">Total [CHF] : </td>
                                    <td id="toright"><?=$client['total']?></td>
                                </tr>
                            </table> 
                            <?php
                            if(array_key_exists("nom_zip", $client)) {
                            ?>
                            <table id="annexes">
                                <tr>
                                    <td>
                                        <a href="#" class="csv" target="new"><?=$client['nom_zip']?></a>
                                    </td>
                                </tr>
                            </table>
                            <?php }
                            ?>

                    </section>
                    <?php
                    foreach($client['factures'] as $facture) {
                    ?>
                    <section>
                        <?=$facture['ref']?> <br /><br />
                        <table id="tableau">
                            <tr>
                                <td>N° Poste </td>
                                <td> Name </td>
                                <td> Description </td>
                                <td> Net amount <br /> [CHF] </td>
                            </tr> 
                            <?php
                            foreach($facture['postes'] as $poste) {
                            ?>
                            <tr>
                                <td><?=$poste['poste']?></td>
                                <td><?=$poste['nom']?></td>
                                <td> <?=$poste['descr']?> <br /> <?=$poste['texte']?> </td>
                                <td id="toright"><?=$poste['net']?></td>
                            </tr>
                            <?php }
                            ?>
                            <tr>
                                <td colspan="3" id="toright">Total [CHF] : </td>
                                <td id="toright"><?=$facture['total']?></td>
                            </tr>
                        </table> 
                        <?php
                        if(array_key_exists("nom_pdf", $facture)) {
                        ?>
                        <table id="annexes">
                            <tr>
                                <td>
                                    <a href="#" class="pdf" target="new"><?=$facture['nom_pdf']?></a>
                                </td>
                            </tr>
                        </table>
                        <?php }
                        ?>
                    </section>
                    <?php }
                    ?>
                </section>
            <?php }
            ?>
            </div>
        </div>
        <?php 
        include("includes/footer.php");?> 
        <script src="reveal.js/dist/reveal.js"></script>
        <script src="js/ticket.js"></script>
        <script>
            Reveal.initialize();
        </script>
        <script>
            function changeClient(sel) {
                Reveal.slide(sel.value, 0);
            }
        </script>
  
	</body>
</html>
