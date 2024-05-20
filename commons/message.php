<div class="text-center" id="message">
<?php
$alerts = ["alert-success", "alert-info", "alert-warning", "alert-danger"];
foreach($alerts as $alert) {
    if(isset($_SESSION[$alert])) { ?>
        <div class="alert <?= $alert ?> alert-dismissible fade show" role="alert">
            <?= $_SESSION[$alert] ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php
        unset($_SESSION[$alert]); 
    }
}
?>
</div>
