/*==============================================================================
    ServerIO
        - Fournit aux client des réponses :)
        - Check Legality according to string sanitizer & security

        -> SO : Do authentication also !

    Pour l'authentification :
        Emettre un JWT sur le site principal ?
        Tester ce JWT sur mes sites secondaires ? 

==>>>
https://stackoverflow.com/questions/36788831/authenticating-socket-io-connections-using-jwt

==============================================================================*/
/*------------------------------------------------------------------------------
    Includes
------------------------------------------------------------------------------*/
const HELP = require('./helpers');
const jwt = require('jsonwebtoken');

/*------------------------------------------------------------------------------
    Module
------------------------------------------------------------------------------*/
function ServerIO() {
    const that = this;
    /*--------------------------------------------------------------------------
        Fonctions
    --------------------------------------------------------------------------*/    
	this.to_Json = function(str) {
	    try {
	        return JSON.parse(str);
	    } catch (e) {
	        HELP.log('Hack attempt !', 'SIO');
	        return false;
	    }
	};
	this.find_socket = function(nick) {
	    for (const socket of this.io.sockets.sockets.values()) {
	        if (socket.player_name === nick) {
	            HELP.log(`found ${nick}`, 'SIO');
	            return socket;
	        }
	    }
	    return false;
	};

    /*--------------------------------------------------------------------------
        Ecoute : Initialisation & aiguillage
	--------------------------------------------------------------------------*/
	this.listen = function(io, world_ini) {
	    this.io = io;
	    const demomode = world_ini['demo'];
	    const JWT_SECRET = world_ini['jwt_secret'];
	    this.clients = [];

	    this.io.use((socket, next) => {
	        const token = socket.handshake.query?.token;

	        if (!token) {
	            return next(new Error('Authentication error'));
	        }

	        jwt.verify(token, JWT_SECRET, (err, decoded) => {
	            if (err) {
	                return next(new Error('Authentication error'));
	            }

	            socket.decoded = decoded;
	            HELP.log(JSON.stringify(socket.decoded), 'SIO');

	            // Check if player is valid
	            if (socket.decoded.meta_id === 0) {
	                HELP.log(`Player ID : ${socket.decoded.meta_id} -> No Account, no game.`, 'SIO');
	                return next(new Error('No account'));
	            }

	            // Check fee payment if not demo mode
	            if (!demomode) {
	                const now = Math.ceil(Date.now() / 1000);
	                if (socket.decoded.fee_paid < now) {
	                    HELP.log(`Player ID : ${socket.decoded.meta_id} -> No Fee, no game.`, 'SIO');
	                    return next(new Error('Fee unpaid'));
	                } else {
	                    HELP.log(`Player ID : ${socket.decoded.meta_id} -> Fee OK.`, 'SIO');
	                }
	            } else {
	                HELP.log(`Player ID : ${socket.decoded.meta_id} -> Fee ${socket.decoded.fee_paid}, but this is not about money.`, 'SIO');
	            }

	            // Initialize player data on socket
	            HELP.log(`User << ${socket.decoded.name} >> connected.`, 'SIO');
	            socket.player_id = socket.decoded.meta_id;
	            socket.player_name = socket.decoded.name;
	            socket.join('ingame');

	            // Send welcome messages
	            that.s_tchat_one(socket, `Bienvenue sur le serveur "${world_ini['name']}" !`);
	            that.ask_wlc(socket);

	            next();
	        });
	    });

	    this.io.on('connection', socket => that.listener(socket));
	};

	this.listener = function(socket) {
	    // Timestamp pour anti-flood (DoS)
	    socket.last_com = 0;

	    // Log à la déconnexion
	    socket.on('disconnect', () => {
	        HELP.log(`User << ${socket.decoded?.name || 'unknown'} >> disconnected.`, 'SIO');
	    });

	    // Gestion des commandes clients
	    socket.on('COM', (msg, callback) => {
	        const now = Date.now();

	        // Anti-flood : ignore les requêtes trop rapprochées (< 50 ms)
	        if (now - socket.last_com < 50) {
	            HELP.log(`Possible DoS attempt from user ${socket.player_name || 'unknown'}`, 'SIO');
	            that.kick_balls(socket, 'Too many requests, slow down.');
	            return;
	        }
	        socket.last_com = now;

	        // String sanitizer basique (à adapter selon le contexte)
	        if (typeof msg !== 'string' || msg.length > 1000) {
	            that.kick_balls(socket, 'Invalid message format or message too long.');
	            return;
	        }

	        // Parse JSON sécurisé
	        const parsedMsg = that.to_Json(msg);
	        if (!parsedMsg || typeof parsedMsg.t !== 'string') {
	            that.kick_balls(socket, 'Malformed or invalid JSON message.');
	            return;
	        }

	        // Construire le nom de la fonction cible dynamiquement
	        const funcName = 'ask_' + parsedMsg.t.toLowerCase();

	        // Vérifie que la fonction existe dans le module
	        if (typeof that[funcName] !== 'function') {
	            that.kick_balls(socket, `Unknown request type: ${parsedMsg.t}`);
	            return;
	        }

	        // Autorisation : l'utilisateur doit être authentifié sauf pour 'ask_auth'
	        if (typeof socket.player_id === 'undefined' && funcName !== 'ask_auth') {
	            HELP.log(`Unauthorized request '${funcName}' blocked`, 'SIO');
	            return;
	        }

	        // Exécution sécurisée de la fonction demandée
	        try {
	            that[funcName](socket, parsedMsg.m, callback);
	        } catch (error) {
	            HELP.log(`Error in handler '${funcName}': ${error.message}`, 'SIO');
	            if (typeof callback === 'function') {
	                callback({ error: 'Internal server error.' });
	            }
	        }
	    });
	};

	this.kick_balls = function(socket, reason) {
	    HELP.log(` [${socket.player_id}] Kicked: ${reason}`, 'SIO');
	    // Décommenter si tu veux vraiment déconnecter :
	    // socket.disconnect();
	};

	/*--------------------------------------------------------------------------
	    Envoi de messages
	--------------------------------------------------------------------------*/
	this.send_all = function(eventType, data) {
	    this.io.to('ingame').emit(eventType, data);
	};

	this.send_one = function(socket, eventType, data) {
	    if (typeof socket.player_id === 'undefined') return false;
	    socket.emit(eventType, data);
	};

	/*--------------------------------------------------------------------------
	    Chat global
	--------------------------------------------------------------------------*/
	this.ask_tch = function(socket, message) {
	    if (typeof socket.player_id === 'undefined') return false;
	    if (!message || message.length === 0) return false;

	    // Troncature du message si trop long
	    if (message.length > 200) message = message.substring(0, 200);

	    const msgObj = {
	        name: socket.player_name,
	        msg: message,
	        col: socket.player_id === '1' ? '#0962B6' : undefined,
	    };

	    this.send_all('TCH', msgObj);
	};

	/*--------------------------------------------------------------------------
	    Afficher le nombre de joueurs en ligne au client
	--------------------------------------------------------------------------*/
	this.ask_wlc = function(socket) {
	    // Compter tous les sockets connectés dans la room "ingame"
	    let count = 0;
	    this.io.sockets.sockets.forEach(() => count++);
	    this.s_tchat_one(socket, `(${count}) Joueurs en ligne.`);
	};

	/*--------------------------------------------------------------------------
	    Liste détaillée des joueurs en ligne (Admin only)
	--------------------------------------------------------------------------*/
	this.ask_wlca = function(socket) {
	    if (socket.player_id !== 1) return false; // Admin check

	    let count = 0;
	    let nameList = [];

	    this.io.sockets.sockets.forEach(clientSocket => {
	        count++;
	        if (clientSocket.player_name !== socket.player_name) {
	            nameList.push(`[<span class="nick">${clientSocket.player_name}</span>]`);
	        } else {
	            nameList.push(`[${clientSocket.player_name}]`);
	        }
	    });

	    this.s_tchat_one(socket, `(${count}) Joueurs en ligne : ${nameList.join(', ')}`);
	};

	/*--------------------------------------------------------------------------
	    Whisper privé entre joueurs
	--------------------------------------------------------------------------*/
	this.ask_whisp = function(socket, data) {
	    const { nick, msg } = data;
	    const destSocket = this.find_socket(nick);
	    const sender = socket.player_name;

	    if (destSocket !== false) {
	        const whisperMsg = { name: sender, msg: msg, col: '#F980ef' };
	        this.send_one(destSocket, 'TCH', whisperMsg);
	        this.send_one(socket, 'TCH', whisperMsg);
	    } else {
	        this.s_tchat_one(socket, `Pas de joueur "${nick}" en jeu.`);
	    }
	};

	/*--------------------------------------------------------------------------
	    Messages serveur
	--------------------------------------------------------------------------*/
	this.s_tchat_all = function(message) {
	    const msgObj = { name: 'Server', msg: message, col: '#F04D84' };
	    this.send_all('TCH', msgObj);
	};

	this.s_tchat_one = function(socket, message) {
	    const msgObj = { name: 'Server', msg: message, col: '#F04D84' };
	    this.send_one(socket, 'TCH', msgObj);
	};
};

module.exports = new ServerIO();