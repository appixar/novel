<p class='green'>[Including main build... (routes/demo)]</p>

<?= pre($_APP["PAGE"], '$_APP[PAGE]'); ?>
<!--
// BUILD INSIDE BUILD!
// BUG FIX NEEDED: REDEFINE CONST=PAGE. GET NEW YML.
-->
<div class='ml-70'>
    <p class='green'>-----------------------------------------------------</p>
    <p class='green'>[Including a child build... (routes/_snippets/smile)]</p>
    <?php new Builder("_snippets/smile"); ?>
    <p class='green'>[End of child.]</p>
    <p class='green'>-----------------------------------------------------</p>
</div>

<?php
pre($_APP["SNIPPETS"], '$_APP[SNIPPETS]');
pre($_APP["PAGE"], '$_APP[PAGE]');
?>