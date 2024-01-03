<script>
    $(function() {
        function flash() {
            $('.title').toggle();
            setTimeout(function() {
                flash();
            }, 1000);
        }
        flash();
    });
</script>