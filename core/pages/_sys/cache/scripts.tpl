<script type="text/javascript">
    $(function() {
        $('.clear-contains').click(function() {
            var input = prompt("Remover chaves contendo:");
            if (input) {
                var url = "?clear=" + encodeURIComponent(input);
                window.location.href = url; // Redireciona para a URL
            }
        });
    });
</script>