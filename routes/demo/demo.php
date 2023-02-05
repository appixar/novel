<?php
pre($_APP["PAGE"]);

echo "<p>Including a child build...</p>";

// BUILD INSIDE BUILD!
// BUG FIX NEEDED: REDEFINE CONST=PAGE. GET NEW YML.
new Builder("_snippets/smile");
//$app->fragment("_snippets/smile");
pre($_APP["SNIPPETS"]);
?>