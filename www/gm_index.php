<?php
include_once __DIR__.'/includes/gm_gui/gm_init.php';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr">
<head>
    <link rel="icon" type="image/png" href="favicon.ico" />
    <meta name="author" content="Colin Boullard"/>
    <title>Klod Online GM GUI</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <!-- Ce qui vient de l'exterieur -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/socket.io/4.8.0/socket.io.min.js"></script>
    <script src="./js/inc/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.1/jquery-ui.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/seedrandom/2.3.10/seedrandom.min.js"></script>

    <!-- CSS -->
    <link rel="stylesheet" type="text/css" href="http://code.jquery.com/ui/1.9.2/themes/base/jquery-ui.css"/>

    <!-- Fait Maison -->
    <link rel="stylesheet" type="text/css" title="Design" href="./css/gm_gui.css" />
	<!-- JS pour interactivité -->
	<script src="./js/gm_gui/main.js"></script>
	<script src="./js/gm_gui/xmltables.js"></script>
	

</head>
<body>

<header>
    <b>Game Master Interface</b> <?php echo('(<i>Bonjour '.PLAYER_NAME.'!</i>)'); ?>
    <nav>
        <ul>
            <li><a href="javascript:void(0)" onclick="showSection('units')">Unités</a></li>
            <li><a href="javascript:void(0)" onclick="showSection('buildings')">Bâtiments</a></li>
            <li><a href="javascript:void(0)" onclick="showSection('resources')">Ressources</a></li>
            <li><a href="javascript:void(0)" onclick="showSection('lands')">Terrains</a></li>
            <li><a href="javascript:void(0)" onclick="showSection('world')">Création du Monde</a></li>
            <li><a href="javascript:void(0)" onclick="showSection('quests')">Quêtes & Divinités</a></li>
        </ul>
    </nav>
</header>

<main id="content">

    <!-- Sections dynamiques -->
    <div id="units" class="section" style="display: none;"></div>
    <div id="buildings" class="section" style="display: none;"></div>
    <div id="resources" class="section" style="display: none;"></div>
    <div id="lands" class="section" style="display: none;"></div>
    <div id="world" class="section" style="display: none;"></div>
    <div id="quests" class="section" style="display: none;"></div>

</main>
</body>
</html>
