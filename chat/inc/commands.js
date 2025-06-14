/*==============================================================================
    COMMANDS

    Manages tchat commands, like whisp, talk, ingame etc.

==============================================================================*/
const HELP = require('./helpers');

class CommandHandler {
    constructor() {
        this.io = null;
    }

    setIO(io) {
        this.io = io;
    }

    parseJSON(str) {
        try {
            return JSON.parse(str);
        } catch {
            HELP.log('Hack attempt !', 'COMMANDS');
            return false;
        }
    }

    getSocketByNick(nick) {
        if (!this.io || typeof nick !== 'string') return false;

        for (const socket of this.io.sockets.sockets.values()) {
            if (socket.player_name === nick) {
                HELP.log(`found ${nick}`, 'COMMANDS');
                return socket;
            }
        }

        return false;
    }

    sendToSocket(socket, event, data) {
        if (!socket?.player_id) return;
        socket.emit(event, data);
    }

    broadcast(event, data) {
        HELP.log(`Sending ${JSON.stringify(data)}`, 'COMMANDS');
        // this.io.to('ingame').emit(event, data);
		this.io.emit(event, data); // Envoie à **tous** les sockets connectés
    }

    kick(socket, reason) {
        HELP.log(`Kick: ${reason}`, 'COMMANDS');
        try {
            socket.emit('KICK', reason);
            socket.disconnect(true);
        } catch {}
    }

    // --- Commandes ---

    ask_tch(socket, message) {
        if (!socket?.player_id || !message?.length) return;

        const msg = message.length > 200 ? message.slice(0, 200) : message;

        const payload = {
            name: socket.player_name,
            msg,
            col: socket.player_id === '1' ? '#0962B6' : undefined,
        };

        this.broadcast('TCH', payload);
    }

    ask_wlc(socket, _, callback) {
        if (!socket?.player_id) return;

        const count = this.io.sockets.sockets.size;
        const msg = `(${count}) Joueurs en ligne.`;

        this.sendToSocket(socket, 'TCH', { name: 'Server', msg, col: '#F04D84' });
        callback?.({ success: true });
    }

	ask_wlca(socket, _, callback) {
	    if (Number(socket.player_id) !== 1) return;

	    const sockets = Array.from(this.io.sockets.sockets.values());
	    const nameList = sockets.map(s => {
	        const name = s.player_name;
	        return s === socket ? `[${name}]` : `[<span class="nick">${name}</span>]`;
	    });

	    const msg = `(${sockets.length}) Joueurs en ligne : ${nameList.join(', ')}`;
	    this.sendToSocket(socket, 'TCH', { name: 'Server', msg, col: '#F04D84' });
	    callback?.({ success: true });
	}


    ask_whisp(socket, { nick, msg }, callback) {
        if (!socket?.player_id || typeof nick !== 'string' || typeof msg !== 'string') return;

        const dest = this.getSocketByNick(nick);
        const payload = { name: socket.player_name, msg, col: '#F980ef' };

        if (dest) {
            this.sendToSocket(dest, 'TCH', payload);
            this.sendToSocket(socket, 'TCH', payload);
            callback?.({ success: true });
        } else {
            const errorMsg = `Pas de joueur "${nick}" en jeu.`;
            this.sendToSocket(socket, 'TCH', { name: 'Server', msg: errorMsg, col: '#F04D84' });
            callback?.({ error: errorMsg });
        }
    }

    handle(io, socket, rawMsg, callback) {
        this.setIO(io);
        const now = Date.now();

        socket.last_com ??= 0;
        if (now - socket.last_com < 50) {
            HELP.log(`Possible DoS attempt from ${socket.player_name || 'unknown'}`, 'COMMANDS');
            this.kick(socket, 'Too many requests, slow down.');
            return;
        }
        socket.last_com = now;

        if (typeof rawMsg !== 'string' || rawMsg.length > 1000) {
            this.kick(socket, 'Invalid message format or message too long.');
            return;
        }

        const msg = this.parseJSON(rawMsg);
        if (!msg || typeof msg.t !== 'string') {
            this.kick(socket, 'Malformed or invalid JSON message.');
            return;
        }

        const funcName = `ask_${msg.t.toLowerCase()}`;

        if (typeof this[funcName] !== 'function') {
            this.kick(socket, `Unknown request type: ${msg.t}`);
            return;
        }

        if (!socket.player_id && funcName !== 'ask_auth') {
            HELP.log(`Unauthorized request '${funcName}' blocked`, 'COMMANDS');
            return;
        }

        try {
            this[funcName](socket, msg.m, callback);
        } catch (err) {
            HELP.log(`Error in handler '${funcName}': ${err.message}`, 'COMMANDS');
            callback?.({ error: 'Internal server error.' });
        }
    }
}

module.exports = new CommandHandler();
