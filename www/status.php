<?php

/* =============================================================================
    STATUS
        -> uses status.json & ini to publish a status system to the world !
============================================================================= */

// Can't use frontend_init.php : it needs authentication.
include_once __DIR__.'/../common/includes/common_init.php';

updateSessionStatus();
$status = SessionManager::get('status');

if ((time()) - $status['timestamp'] > (TIC_SEC * 2)) {
    echo '<img class="status" src="./img/server_down.png" title="KlodService Error">';
} elseif ((time()) - $status['timestamp'] > (TIC_SEC)) {
    echo '<img class="status" src="./img/server_await.png" title="Turn...">';
} else {
    echo '<img class="status" src="./img/server_up.png" title="Running !">';
}
echo '<br/><span class="nbplayer">'.$status['nb_players'].' <span class="trn">__PLAYERS__</span></span>';
