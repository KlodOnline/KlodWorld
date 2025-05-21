<?php
/* -----------------------------------------------------------------------------
			MUST Be loaded at start of any php script who may do SQL or any
			security related tasks.
----------------------------------------------------------------------------- */
/* =============================================================================
	FRONT_END INIT :
		-> Includes all variables & files & anything that is usefull for front
============================================================================= */
//===================== COMMUNS ================================================
header( 'content-type: text/html; charset=utf-8' );
session_set_cookie_params([
    'lifetime' => 0, // Cookie will last until the browser is closed
    'path' => '/', // Available within the entire domain
    'domain' => '', // Default is the current domain
    'secure' => false, // Set to true if using HTTPS
    'httponly' => true, // Accessible only through the HTTP protocol
    'samesite' => 'Lax' // Controls when cookies are sent
]);
// Didn't manage to store session in RAM, it seem ?!
// ini_set('session.save_handler', 'files');
// ini_set('session.save_path', '');  // Utilisation de RAM uniquement
// ini_set('session.gc_maxlifetime', 3600);  // 1 heures
// if (session_status() == PHP_SESSION_NONE) { session_start(); }

// ==== INCLUDES ===============================================================
include_once __DIR__.'/../../common/includes/common_init.php';

//===================== DEBUG MODE (A virer en prod) ===========================
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
// --- Fin debug mode

// Sécurité : Prévention des injections de session
if (isset($_REQUEST['_SESSION'])) {
    die("");
}

//==================== CHOSES DIVERSES =========================================
define ('SERVER_OS','linux');	
date_default_timezone_set('Europe/Paris');

//==================== IDENTIFICATION ==========================================
$connection_log = '';
// Pas de session et pas de token => OUT.
$session_token = SessionManager::get('token');
if (!$session_token && !isset($_POST['token'])) { exit(); }

// Un token est envoyé en POST => Vérification et éventuelle mise à jour
if (isset($_POST['token'])) {
    $post_token = $_POST['token'];

    if (is_jwt_valid($post_token, 'SECRET_KEY')) {
        if (!$session_token) {
            // Première connexion
            $connection_log = 'connected.';
            SessionManager::set('token', $post_token);
        } elseif ($session_token !== $post_token) {
            // Rafraîchissement ou changement d'utilisateur
            $connection_log = 'refreshing or user switch.';
            SessionManager::set([
                'cache' => null, // Suppression du cache
                'token' => $post_token
            ]);
        }
    } else {
        // Token invalide => sortie immédiate
        exit();
    }
}
// Une session sans token ?! => OUT.
$session_token = SessionManager::get('token'); 
if (!$session_token) { exit(); }

// Décodage du payload du token
$token_parts = explode('.', $session_token);
$payload = json_decode(base64_decode(str_replace(['_', '-'], ['/', '+'], $token_parts[1])), true);

define('META_ID', 	(int) $payload['meta_id']);
define('PLAYER_NAME', 	(string) $payload['name']);
define('FEE_PAID', 	(int) $payload['fee_paid']);
define('CREDS_DEBUG', false);

if ($connection_log!=='') { logMessage(PLAYER_NAME.' ('.META_ID.') '.$connection_log); }
if (META_ID===0) {exit();}

// MEMORY ----------------------------------------------------------------------
define('MEM_DEBUG',   false);

@ini_set('memory_limit', '2048M');

// Logs pour le debug
if (MEM_DEBUG) { logMessage(" # LIMIT = " . ini_get('memory_limit')); }

// CREDITS CHECK ---------------------------------------------------------------
$now = new DateTime();
if (CREDS_DEBUG) { logMessage('Have credits?'); }
if (FEE_PAID < ($now->getTimestamp())) {
    if (CREDS_DEBUG) { logMessage('No more credits!'); }
    // In case of KlodAdmin or Demo, entering allowed !
    if (!DEMO and !META_ID===1) {
        if (CREDS_DEBUG) { logMessage('No credits, no game...'); }
        exit();
    } else {
        if (CREDS_DEBUG) { logMessage('But this is not about money...'); }
    }
}
if (CREDS_DEBUG) { logMessage('My tailor is rich!'); }

?>
