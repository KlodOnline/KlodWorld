/*==============================================================================
    KLODCHAT

    Right now :
        Client connect with browser to IP:CHAT_PORT (Cf. config.ini)
        socketio server listens to 8080
        It's up to you to bind 8080<->2080

    Todo :
    	(eventually, if possible)
    	Apache manage whole SSL thing, and a WSS reverse proxy
    	(if not possible)
    	NodeJS uses the same certificate as Apache, but manage is own SSL shit.

==============================================================================*/
console.clear();

const { execSync } = require('child_process');
const ini = require('ini');
const fs = require('fs');
const socketIo = require('socket.io');
const http = require('http');
const jwt = require('jsonwebtoken'); 

const HELP = require('./inc/helpers');
const COMMANDS = require('./inc/commands');

// Verrouillage d’instance
const scriptName = __filename.split('/').pop();
const result = execSync(`ps aux | grep ${scriptName} | grep -v grep | grep -v 'sh -c'`).toString();
if (result.split('\n').length > 2) {
    HELP.log("Error : Klodchat can't run twice.");
    process.exit();
}

// Chargement config
const CONFIG_INI = ini.parse(fs.readFileSync(__dirname + '/../common/param/config.ini', 'utf-8'));

// Serveur HTTP + socket.io
const server = http.createServer();
const io = new socketIo.Server(server, {
    cors: {
        origin: "https://" + CONFIG_INI['world']['world_ip'] + ":" + CONFIG_INI['world']['game_port'],
        methods: ["GET", "POST"],
        allowedHeaders: ["Content-Type", "Authorization"],
        credentials: true
    }
});


// Démarrage serveur
server.listen(8080, () => {
    HELP.log(`Server listening on port 8080`);
});

// Middleware Socket.IO pour vérifier le JWT à la connexion

io.use((socket, next) => {
    const token = socket.handshake.query?.token;
    if (!token) return next(new Error('Authentication error: Token missing'));

    jwt.verify(token, CONFIG_INI.world.jwt_secret, (err, decoded) => {
        if (err) return next(new Error('Authentication error: Invalid token'));

        socket.decoded = decoded;

        if (decoded.meta_id === 0) {
            return next(new Error('No account'));
        }

        if (!CONFIG_INI.world.demo) {
            const now = Math.floor(Date.now() / 1000);
            if (decoded.fee_paid < now) {
                return next(new Error('Fee unpaid'));
            }
        }

        socket.player_id = decoded.meta_id;
        socket.player_name = decoded.name;

        HELP.log(`User << ${socket.player_name} >> authenticated`, 'KLODCHAT');

        next();
    });
});


io.on('connection', (socket) => {
    HELP.log(`User connected: ${socket.player_id}`);

    const msg = `Bienvenue sur le serveur ${CONFIG_INI['world']['world_name']} !`;
    COMMANDS.sendToSocket(socket, 'TCH', { name: 'Server', msg, col: '#F04D84' });

    socket.on('disconnect', (reason) => {
        HELP.log(`User disconnected: ${socket.player_id} Reason: ${reason}`);
    });

    socket.on('COM', (msg, callback) => {
    	HELP.log(`Handling COM request : ${JSON.stringify(msg)}`, `KLODCHAT`)
		COMMANDS.handle(io, socket, msg, callback);
		// callback is used in www/js/client_io.js
    });

});
