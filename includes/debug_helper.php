<?php
/**
 * FUNCTION 1: Release the Hounds
 * Forces PHP to scream about every single mistake.
 */
function release_the_hounds() {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    
    // Log to console that debugging is live
    echo "<script>console.info('%c Hounds Released: Full Error Reporting Active ', 'background: #800000; color: #fff;');</script>";
}

/**
 * FUNCTION 2: Database Trace
 * Targets specific data arrays (like $user or $myProducts) and tables them in the console.
 */
function trace_database($data, $label = 'Database Trace') {
    if (empty($data)) {
        echo "<script>console.warn('Trace [{$label}]: No data found (Empty Array/False).');</script>";
        return;
    }

    $json_data = json_encode($data);
    ?>
    <script>
        console.group('%c <?php echo $label; ?> ', 'background: #2a7082; color: #fff; font-weight: bold;');
        console.table(<?php echo $json_data; ?>);
        console.groupEnd();
    </script>
    <?php
}
?>