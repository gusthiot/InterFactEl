<div class="text-center" id="message">
<?php
/**
 * a div to display the uncoming messages from controllers
 */
$alerts = ["alert-success", "alert-info", "alert-warning", "alert-danger"];
foreach($alerts as $alert) {
    if(isset($_SESSION[$alert])) { ?>
        <div class="alert <?= $alert ?> alert-dismissible fade show reduce" role="alert">
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
