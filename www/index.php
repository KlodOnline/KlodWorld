 <!--DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd"-->
<?php 
include_once __DIR__.'/includes/frontend_init.php';
?>
 <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" >
    <head>
        <link rel="icon" type="image/png" href="favicon.ico" />
        <meta name="author" content="Colin Boullard"/>
        <title>Klod Online : Le jeu de stratégie web d'un nouveau genre</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

        <!-- Ce qui vient de l'exterieur -->
        <!-- script src="https://cdnjs.cloudflare.com/ajax/libs/socket.io/2.0.1/socket.io.js"></script -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/socket.io/4.8.0/socket.io.min.js"></script>
        
        <!-- script src="https://code.jquery.com/jquery-3.6.0.js"></script -->
        <script src="./js/inc/jquery-3.6.0.min.js"></script>
        <script src="./js/inc/jquery-ui-1.13.3.min.js"></script>
        
        <!-- script src="https://code.jquery.com/ui/1.13.1/jquery-ui.js"></script -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/seedrandom/2.3.10/seedrandom.min.js"></script>

        <!-- CSS -->
        <link rel="stylesheet" type="text/css" href="http://code.jquery.com/ui/1.9.2/themes/base/jquery-ui.css"/>
<!--
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.4.0/dist/leaflet.css" integrity="sha512-puBpdR0798OZvTTbP4A8Ix/l+A4dHDD0DGqYW6RQ+9jxkRFclaxxQb/SJAWZfWAkuyeQUytO7+7N4QKrDh+drA==" crossorigin=""/>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.8.0/dist/leaflet.css" integrity="sha512-hoalWLoI8r4UszCkZ5kL8vayOGVae1oxXe/2A4AO6J9+580uKHDO3JdHb7NzwwzK5xr/Fs0W40kiNHxM9vyTtQ==" crossorigin=""/>
-->
        
        <!-- LEAFLET CSS & JS -->
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>  
        <!-- script src="./js/inc/leaflet/leaflet.js"></script -->

        <!-- Fait Maison -->
        <!-- CSS -->
        <link rel="stylesheet" type="text/css" title="Design" href="./css/game.css" />


    </head>
    <body>

    <audio id="background-music"></audio>
    <button id="toggle-music">Stop Music</button>
    
        <div id="game_menu">
            <div id="menu_gauche">
                <input id="orderSubmit" class="menu_icon i1" name="LOCAL_MAP" title="Carte locale" type=button>
                <input id="orderSubmit" class="menu_icon i2" name="WORLD_MAP" title="Carte du monde" type=button>
                <input id="orderSubmit" class="menu_icon i3" name="MANAGE_UNIT" title="Gestion des unités" type=button>
                <input id="orderSubmit" class="menu_icon i4" name="MANAGE_CITY" title="Gestion des villes" type=button>
            </div>
            <div id="server_main">
                <div id="server_info">
                    <div id="ld" >...</div>
                </div>
                <input id="orderSubmit" class="quit" name="QUIT_GAME" title="Quitter le jeu" type="button">
            </div>
            <div id="menu_droite">
                <input id="orderSubmit" class="menu_icon i5" name="TRADING" title="Commerce" type=button>
                <input id="orderSubmit" class="menu_icon i6" name="DIPLOMACY" title="Diplomatie" type=button>
                <input id="orderSubmit" class="menu_icon i7" name="MANAGE_MAILS" title="Courrier" type=button>
                <input id="orderSubmit" class="menu_icon i8" name="PLAY_OPTION" title="Royaume et options" type=button>
            </div>
        </div>
        <div id="mm_frame">
            <canvas id="minimap"></canvas>
            <canvas id="mm_focus"></canvas>
        </div>
        <div id="map"></div>
            
        <div id="tchat">
            <div id="tchat_content">
                <div id="messages" ></div>
                <input type="text" id="tchat_input"  />
            </div>
        </div>
        <div id="panel"></div>
        <div id="popup"></div>

        <div id="selection"></div>
        
        <div id="debug"></div>
        
        <!-- JS -->
        <script type="text/javascript">
            const WORLD_IP = (<?php echo '"'.WORLD_IP.'"'; ?>);
            const WEBSITE = (<?php echo '"'.WEBSITE.'"'; ?>);
            const CHATPORT = (<?php echo '"'.CHAT_PORT.'"'; ?>);
            // POST Token inclusion
            const token = "<?php echo SessionManager::get('token'); ?>";
        </script>

        <?php include_once __DIR__.'/includes/xml_rules.php'; ?>

        <!-- Juste des fonctions --> 
        <script type="text/javascript" src="./js/helpers.js"></script>

        <!-- Les Modules Autocharges --> 
        <script type="text/javascript" src="./js/hexalib.js"></script>
        <script type="text/javascript" src="./js/client-io.js"></script>
        <script type="text/javascript" src="./js/tchat.js"></script>
        <script type="text/javascript" src="./js/soundtrack.js"></script>
                
        <!-- Les Modules Charges par le main --> 
        <!-- script type="text/javascript" src="./js/game_events.js"></script -->
        <script type="text/javascript" src="./js/orders/order-iface.js"></script>

        <script type="text/javascript" src="./js/board-iface.js"></script>
        <script type="text/javascript" src="./js/welcome.js"></script>
        <script type="text/javascript" src="./js/mapview.js"></script>
        <script type="text/javascript" src="./js/minimap.js"></script>
        <script type="text/javascript" src="./js/menubar.js"></script>
        <script type="text/javascript" src="./js/selection.js"></script>

        <!-- Les fenetres et popup --> 
        <script type="text/javascript" src="./js/panels/panel-main.js"></script>
        <script type="text/javascript" src="./js/panels/panel-city.js"></script>
		<script type="text/javascript" src="./js/panels/panel-unit.js"></script>

        <script type="text/javascript" src="./js/popup.js"></script>

        <!-- Le Main, enfin ! --> 
        <script type="text/javascript" src="./js/main.js"></script>
        
    </body>
</html>