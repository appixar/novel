<?php
if (@!$_SESSION['_sys']['auth']) {
    header("Location: ./auth");
    exit;
}
prex($_SESSION);