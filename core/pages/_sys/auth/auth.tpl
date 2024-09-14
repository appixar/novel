<div id="login-page">

    <?php if ($lockForm) { ?>
        <div class='alert alert-warning'><i class='fa fa-warning'></i> <?= $lockForm ?></div>
    <?php } ?>
    <?= cb() ?>
    <h5 class="gray my-4"><i class="fa-solid fa-lock"></i> login</h5>
    <form action="<?= PAGE_POST ?>" method="POST">
        <div class="mb-3">
            <label class="form-label">Password</label>
            <input required type="password" name="pass" class="form-control" placeholder="" <?= ($lockForm) ? "disabled" : "" ?>>
        </div>
        <button type="submit" class="btn btn-primary mb-3" <?= ($lockForm) ? "disabled" : "" ?>>Enter</button>
    </form>
</div>
