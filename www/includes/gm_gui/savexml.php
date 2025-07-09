<?php

/* =============================================================================
    gmSaveXml.php
    -
============================================================================= */

include_once __DIR__.'/gm_init.php';

// Configuration des fichiers de destination
$files = [
    'units' => COMMON_PATH.'/param/rules/units.xml',
    'resources' => COMMON_PATH.'/param/rules/resources.xml',
    'buildings' => COMMON_PATH.'/param/rules/buildings.xml',
    'lands' => COMMON_PATH.'/param/rules/lands.xml'
];

// Vérification des données reçues
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['type'], $_POST['xml'])) {
    $type = $_POST['type'];
    $xmlContent = $_POST['xml'];

    // Vérifier si le type est valide
    if (array_key_exists($type, $files)) {
        $filePath = $files[$type];

        // Charger le contenu XML pour formater avec DOMDocument
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        if ($dom->loadXML($xmlContent)) {
            // Sauvegarder le contenu formaté dans le fichier
            if ($dom->save($filePath)) {
                echo json_encode(['success' => true, 'message' => "$type saved successfully."]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => "Failed to write $type to file."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Invalid XML content for $type."]);
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Unknown type: $type."]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
