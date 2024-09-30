<?php
cb();
?>
<h5 class="gray"><i class="fa-solid fa-server"></i> vm general</h5>
<table id="vm" style="width:100%">
    <thead>
        <tr class="gray">
            <td style="width:10%">cpu</td>
            <td style="width:15%">ram</td>
            <td style="width:10%">disk</td>
            <td style="width:65%">uptime</td>
        </tr>
    </thead>
    <tbody>
    </tbody>
</table>
<p>&nbsp;</p>

<h5 class="mt-5 mb-4 gray"><i class="fa-solid fa-bolt"></i> processing now</h5>
<?php
foreach ($jobsConfig as $k => $v) {
?>
    <p class="mb-3"><strong><?= $v['title'] ?></strong></p>
    <table id="<?= $k ?>" style="width:100%" class="mb-4">
        <thead>
            <tr class="gray">
                <td>#</td>
                <td style="width:5%">user</td>
                <td style="width:10%">pid</td>
                <td style="width:5%">cpu %</td>
                <td style="width:5%">ram %</td>
                <td style="width:10%">start</td>
                <td style="width:65%">cmd</td>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
<?php
}
?>

<h5 class="mt-5 mb-4 gray"><i class="fa-solid fa-gears"></i> jobs control</h5>
<p class="mb-3 gray">
    autoplay:
    <?php if ($autoplay) { ?>
        <span class='green green-shadow'>enabled</span> <a href='/_sys/dashboard/_action?action=autoplay-off' class="btn btn-sm btn-secondary">stop</a>
    <?php } else { ?>
        <span class='light'>disabled</span> <a href='/_sys/dashboard/_action?action=autoplay-on' class="btn btn-sm btn-success">play</a>
    <?php } ?>
    • cron status: <span class='light'>? (only root)</span>
</p>
<table id="job_config" style="width:100%" class="mb-4">
    <thead>
        <tr class="gray">
            <td style="width:70px">#</td>
            <td>file</td>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($_APP['JOBS'] as $fn) {
        ?>
            <tr>
                <td>
                    <div class="playArea">
                        <a href='/_sys/dashboard/_action?action=run&fn=<?= $fn ?>' class='green'><i class='fa-solid fa-play'></i></a>
                    </div>
                    <div class="stopArea" style="display:none">
                        <?php
                        $basedir = Xplend::DIR_ROOT;
                        $stopBtn = 1;
                        if (file_exists("$basedir/$fn-stop")) $stopBtn = 0;
                        $restartBtn = 1;
                        if (file_exists("$basedir/$fn-restart")) $restartBtn = 0;
                        ?>
                        <?php if ($stopBtn) { ?>
                            <a href='/_sys/dashboard/_action?action=stop&fn=<?= $fn ?>' class='red'><i class='fa-solid fa-stop' style="width:20px;display:inline-block;"></i></a>
                        <?php } else { ?>
                            <a href='#' class='text-muted' style="width:20px;display:inline-block;"><i class="fa-regular fa-clock"></i></a>
                        <?php } ?>

                        <?php if ($restartBtn) { ?>
                            <a href='/_sys/dashboard/_action?action=restart&fn=<?= $fn ?>' class='text-info'><i class="fa-solid fa-arrows-rotate" style="width:20px;display:inline-block;"></i></a>
                        <?php } else { ?>
                            <a href='#' class='text-muted' style="width:20px;display:inline-block;"><i class="fa-regular fa-clock"></i></a>
                        <?php } ?>
                    </div>
                </td>
                <td class='fn'><?= $fn ?></td>
            </tr>
        <?php
        }
        ?>
    </tbody>
</table>

