<?php
$lockForm = "";
if (@!$_ENV['SYS_PASS']) $lockForm = ".env password is missing (SYS_PASS)";
if (@!$_APP['MONITOR'] or @!$_APP['MONITOR']['ENABLED']) $lockForm = "Monitor is disabled";

