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
foreach ($config as $k => $v) {
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
        <span class='green green-shadow'>enabled</span> <a href='/_sys/_action?autoplay=0' class="btn btn-sm btn-secondary">stop</a>
    <?php } else { ?>
        <span class='light'>disabled</span> <a href='/_sys/_action?autoplay=1' class="btn btn-sm btn-success">play</a>
    <?php } ?>
    • cron status: <span class='light'>? (only root)</span>
</p>
<table id="job_config" style="width:100%" class="mb-4">
    <thead>
        <tr class="gray">
            <td>#</td>
            <td style="width:100%">file</td>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($_APP['JOBS'] as $fn) {
            echo "<tr>";
            echo "<td><a href='/_sys/_action?run=$fn' class='green'><i class='fa-solid fa-play'></i></a></td>";
            echo "<td class='fn'>$fn</td>";
            echo "</tr>";
        }
        ?>
    </tbody>
</table>