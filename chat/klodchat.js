/*==============================================================================
    KLODCHAT

    Right now :
        Client connect with browser to IP:2080
        Docker links (ext) 2080 -> (int) 8080
        socketio server listens to 8080

    Todo :
    	(eventually, if possible)
    	Apache manage whole SSL thing, and a WSS reverse proxy
    	(if not possible)
    	NodeJS uses the same certificate as Apache, but manage is own SSL shit.

==============================================================================*/

console.clear();

/*------------------------------------------------------------------------------
    External Libraries
 -----------------------------------------------------------------------------*/
const { execSync } = require('child_process');
const ini = require('ini');
const fs = require('fs');

/*------------------------------------------------------------------------------
    Internal Modules
 -----------------------------------------------------------------------------*/
const HELP = require('./inc/helpers');

/*------------------------------------------------------------------------------
    Script Metadata
 -----------------------------------------------------------------------------*/
const scriptName = __filename.split('/').pop();

/*------------------------------------------------------------------------------
    Prevent Multiple Instances
    - Checks if another instance of this script is already running.
 -----------------------------------------------------------------------------*/
const result = execSync(`ps aux | grep ${scriptName} | grep -v grep | grep -v 'sh -c'`).toString();
if (result.split('\n').length > 2) {
    HELP.log("Le script est déjà en cours d'exécution.");
    process.exit();
}

/*------------------------------------------------------------------------------
    Load Configuration File
    - Reads INI file containing world/server parameters.
 -----------------------------------------------------------------------------*/
const CONFIG_INI = ini.parse(fs.readFileSync(__dirname + '/../common/param/config.ini', 'utf-8'));

/*------------------------------------------------------------------------------
    Initialize HTTP Server (required for Socket.IO)
    - No HTTP endpoints are served here; used only as a Socket.IO transport layer.
 -----------------------------------------------------------------------------*/
const server = require('http').createServer();

/*------------------------------------------------------------------------------
    Initialize Socket.IO with CORS configuration
    - Restricts connections to the configured frontend server address.
 -----------------------------------------------------------------------------*/
const io = require('socket.io')(server, {
    cors: {
        origin: "https://" + CONFIG_INI['world']['world_ip'] + ":" + CONFIG_INI['world']['game_port'],
        methods: ["GET", "POST"],
        allowedHeaders: ["Content-Type", "Authorization"],
        credentials: true
    }
});

/*------------------------------------------------------------------------------
    Start Server
    - Listens on port 8080 for incoming WebSocket connections.
 -----------------------------------------------------------------------------*/
server.listen(8080, () => {
    HELP.log(`Server listening on port 8080`);
});

/*------------------------------------------------------------------------------
    Import Server I/O Logic
    - Contains Socket.IO event handlers and communication logic.
 -----------------------------------------------------------------------------*/
const server_io = require('./inc/server_io');

/*------------------------------------------------------------------------------
    Launch Tchat I/O
    - Starts listening to client connections via server_io module.
 -----------------------------------------------------------------------------*/
// Async Await and other NodeJS Lifestyle.
async function launch_tchat() {
	// Listen to the plebs
    server_io.listen(io, CONFIG_INI['world']);
}

launch_tchat();
