<div class="text-center" id="message">
<?php
if(isset($_SESSION['message']) && isset($_SESSION['type'])) { ?>
    <div class="alert <?= $_SESSION['type'] ?> alert-dismissible fade show" role="alert">
        <?= $_SESSION['message'] ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
<?php
    unset($_SESSION['message']); 
    unset($_SESSION['type']); 
}
?>
</div>
