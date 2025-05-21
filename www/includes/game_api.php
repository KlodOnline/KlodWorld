<?php
/* =============================================================================
	Game API :
		-> Filter POST Data
		-> Do all usefull inclusion
		-> Asks to request.php object for info, to give it back to caller
		
============================================================================= */
ob_start();
include_once __DIR__.'/frontend_init.php';
include_once __DIR__.'/request_manager.php';

// REQUEST ---------------------------------------------------------------------
if (!isset($_POST['T'])) {
    http_response_code(400);
    ob_end_clean();
} else {
    // $type = htmlspecialchars($_POST['T']); // Filtre la valeur de 'T'
    $type = $_POST['T']; // Normalement inutile si filtre correctement + tard
    $requestManager = new RequestManager();
    $response = $requestManager->handleRequest($type, $_POST);
    ob_end_clean();
    echo json_encode($response);
}

?>