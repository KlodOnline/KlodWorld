console.clear();
/*------------------------------------------------------------------------------
    External Library : 
 -----------------------------------------------------------------------------*/
const { execSync } = require('child_process');
const scriptName = __filename.split('/').pop();
const ini = require('ini')
const fs = require('fs')

// My Modules -----------------------------------------------------------------
const HELP = require('./inc/helpers');

// Protection against multi launch
const result = execSync(`ps aux | grep ${scriptName} | grep -v grep | grep -v 'sh -c'`).toString();
if (result.split('\n').length > 2) {
	HELP.log("Le script est déjà en cours d'exécution.");
    process.exit();
}

const CONFIG_INI = ini.parse(fs.readFileSync(__dirname + '/../common/param/config.ini', 'utf-8'))

// IO is *over HTTP* so we need to instanciate our HTTP server in order to lauch
// socket.io module
const server = require('http').createServer(); // Crée un serveur HTTP

// HELP.log();

// Initialise Socket.IO avec le serveur
const io = require('socket.io')(server, {
    cors: {
        origin: "https://" + CONFIG_INI['world']['world_ip']+  ":" + CONFIG_INI['world']['game_port'],
        // origin: "", // Acepte toute les origines
        methods: ["GET", "POST"],
        allowedHeaders: ["Content-Type", "Authorization"],
        credentials: true // Si tu utilises des cookies ou des sessions
    }
});

server.listen(8080, () => {
    HELP.log(`Server listening on port 8080`);
});

// My Modules -----------------------------------------------------------------

const server_io = require('./inc/server_io');

// Async Await and other NodeJS Lifestyle.
async function launch_game() {
    // Listen to the plebs ---------------------------------------------------------
    server_io.listen(io, CONFIG_INI['world']);
};

launch_game();