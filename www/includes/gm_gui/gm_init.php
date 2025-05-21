<?php
include_once __DIR__.'/../frontend_init.php';

// Ensure proper access rights
if (META_ID != '1') { exit('404'); }

include_once COMMON_PATH.'/includes/world_generator.php';

@ini_set('memory_limit', '2048M');
@ini_set('max_execution_time', '360');

?>
