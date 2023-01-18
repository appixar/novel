<p>Including a child build...</p>
<?php
// BUILD INSIDE BUILD!
// BUG FIX NEEDED: REDEFINE CONST=PAGE. GET NEW YML.
$arion->build("_snippets/mychart");
?>