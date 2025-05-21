<?php
/* =============================================================================
    STATUS with conditional update based on filemtime and session cache
============================================================================= */
include_once __DIR__.'/frontend_init.php';
updateSessionStatus();
echo json_encode( SessionManager::get('status') );
?>
