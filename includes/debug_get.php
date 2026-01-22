<?php
// Displays contents of GET to the browser console
if (!empty($_GET)) {
    // Convert PHP array to JSON for safe JS handling
    $json_get = json_encode($_GET);
    ?>
    <script>
        console.group('PHP GET Debugger');
        console.table(<?php echo $json_get; ?>);
        console.log('Raw Data:', <?php echo $json_get; ?>);
        console.groupEnd();
    </script>
    <?php
}
?>