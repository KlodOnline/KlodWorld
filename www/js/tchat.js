"use strict";

/*==============================================================================
    TchatIO v0.2.0
    Minimal WebSocket interface for in-game chat communication
==============================================================================*/

function TchatIO() {

    // Redirect if auth token is missing
    if (typeof token === 'undefined') {
        location.href = WEBSITE;
        return;
    }

    // Connect to chat server
    this.socket = io.connect(`http://${WORLD_IP}:${CHATPORT}`, {
        query: { token }
        // transports: ['websocket'] // optional forced transport
    });

    // Send data via WebSocket
    this.ask_data = (type, msg = '', callback = () => {}, first_try = true) => {
        const payload = JSON.stringify({ t: type, m: msg });
        logMessage(`WS SEND: ${payload}`);
        this.socket.emit('COM', payload, callback);
    };

    // Optional: expose socket for external listeners
    this.on = (event, handler) => this.socket.on(event, handler);
    this.disconnect = () => this.socket.disconnect();
    this.reconnect = () => this.socket.connect();
};

// Global instance
const TC = new TchatIO();

/*==============================================================================
    Tchat Module - Real-time chat window with commands and interaction
==============================================================================*/

function Tchat() {
    this.history = [];
    this.currKey = 0;
    const that = this;

    // Returns current time formatted as [HH:MM:SS]
    this.humanDate = () => {
        const d = new Date();
        return `[${String(d.getHours()).padStart(2,'0')}:` +
               `${String(d.getMinutes()).padStart(2,'0')}:` +
               `${String(d.getSeconds()).padStart(2,'0')}]`;
    };

    // Display a received message in chat, with optional color and link coords formatting
    this.receiveMsg = (nick, msg, color = '#d3d3d3') => {
        // Convert coordinates [xxx,yyy] to clickable spans
        msg = msg.replace(/\[[0-9]+,[0-9]+\]/g, c => `<span class="coords">${c}</span>`);
        const txt = `<span style="color:${color}">${that.humanDate()} [<span class="nick">${nick}</span>]: ${msg}</span><br/>`;
        
        $('#messages').append(txt).animate({scrollTop: $('#messages')[0].scrollHeight}, 50);

        if (document.hidden && color === '#F980ef') blinkTab('New whisp !');

        that.fadeToVisible();
    };

    // Display a client/system message in yellow
    this.clientTalk = msg => that.receiveMsg('Client', msg, '#efcd25');

    // Chat command handlers
    this.cmd_help = () => that.clientTalk(
        `/help : This message\n` +
        `/ig : Number of players online\n` +
        `/w NICK : Private message to [NICK]`
    );
    this.cmd_ig = () => TC.ask_data('WLC');
    this.cmd_w = args => {
        const nick = args[1];
        const msg = args.slice(2).join(' ');
        TC.ask_data('WHISP', { nick, msg });
    };
    this.cmd_iga = () => TC.ask_data('WLCA'); // Admin secret command

    // Add text to chat input and focus it
    this.addInput = txt => {
        $('#tchat_input').val($('#tchat_input').val() + txt).focus();
    };

    // Fade chat window visible, then fade out after delay (default)
    this.fadeToVisible = (withTimer = true) => {
        clearTimeout(this.fadeoutTimer);
        $("#tchat").stop(true, true).fadeTo(300, 1);
        if (withTimer) {
            this.fadeoutTimer = setTimeout(() => {
                $("#tchat").stop(true, true).fadeTo(15000, 0.1);
            }, 3000);
        }
    };

    // Make chat window draggable and resizable within body
    $("#tchat").resizable({ containment: "body" });
    $("#tchat").draggable({ containment: "body" });

    // UI event bindings
    $("#tchat").mouseleave(() => that.fadeToVisible());
    $("#tchat").mouseenter(() => that.fadeToVisible(false));
    $("#tchat_input").focus(() => that.fadeToVisible());

    // Click nick to prepare private message
    $('#tchat').on('click', '.nick', function () {
        $('#tchat_input').val('/w ' + $(this).text() + ' ').focus();
        return false;
    });

    // Click coords to move map view
    $('#tchat').on('click', '.coords', function () {
        const coords = $(this).text().slice(1, -1).split(',');
        const latlng = H.coord_to_pixel(H.coord(coords, 'oddr'), H_W, H_H, H_S);
        Mv.goto([latlng[1], latlng[0]]);
    });

    // Input key handling: up/down history navigation and send on enter
    $('#tchat_input').keyup(e => {
        that.fadeToVisible();

        if (e.key === 'ArrowUp') {
            that.currKey = Math.max(0, that.currKey - 1);
            $('#tchat_input').val(that.history[that.currKey] || '');
            return false;
        }
        if (e.key === 'ArrowDown') {
            that.currKey = Math.min(that.history.length, that.currKey + 1);
            $('#tchat_input').val(that.history[that.currKey] || '');
            return false;
        }

        if (e.key === 'Enter') {
            const txt = $('#tchat_input').val();
            if (!txt) return false;

            that.history.push(txt);
            that.currKey = that.history.length;

            if (txt.startsWith('/')) {
                const args = txt.split(' ');
                const cmd = args[0].slice(1);
                const func = that['cmd_' + cmd];
                if (typeof func === 'function') {
                    func(args);
                } else {
                    that.clientTalk('Unknown command. Use /help for valid commands.');
                }
            } else {
                that.send(txt);
            }
            $('#tchat_input').val('');
            return false;
        }
    });

    // Send chat message via TC.ask_data WebSocket wrapper
    this.send = msg => TC.ask_data('TCH', msg);

    // Listen to incoming chat messages from server
    TC.socket.on('TCH', data => that.receiveMsg(data.name, data.msg, data.col));
};

const T = new Tchat();
