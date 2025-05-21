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
function ServerIO()
    {
    const that = this;
    /*--------------------------------------------------------------------------
        Fonctions
    --------------------------------------------------------------------------*/    
    this.to_Json = function(str)
        {
        // HELP.log(str);    
        try 
            { JSON.parse(str); }
        catch (e) 
            { HELP.log('Hack attempt !', 'SIO');
            return false; }
        return JSON.parse(str);
        };

	this.find_socket = function(nick) {
		let foundSocket = false;
	    this.io.sockets.sockets.forEach((clientSocket) => {
	        if (nick === clientSocket.player_name) {
	        	logMessage('found '+nick);
	        	foundSocket = clientSocket; 
	        }
	    });
	    return foundSocket;
    };

    /*--------------------------------------------------------------------------
        Ecoute : Initialisation & aiguillage
    --------------------------------------------------------------------------*/
    // this.listen = function(io, sherpa, board, gamemaster, demomode) {
    this.listen = function(io, world_ini) {
        this.io = io;
        // this.sherpa = sherpa;     
        // this.board = board;            
        // this.gamemaster = gamemaster;
        demomode = world_ini['demo'];
        this.clients = [];    

        this.io.use(function(socket, next) {
            if (socket.handshake.query && socket.handshake.query.token) {
                /*--------------------------------------------------------------
                    Securite
                        L'authentification se fait sur un serveur tierce, qui 
                        laisse au client un token. 

                        Si le token est absent, socket.io ne repond pas.
                        Sinon, socket.decoded contient l'identite en clair
                        de notre utilisateur.

                --------------------------------------------------------------*/                

                jwt.verify(socket.handshake.query.token, 'SECRET_KEY', function(err, decoded) {

                    if (err) return next(new Error('Authentication error'));
                    socket.decoded = decoded;

                    HELP.log( JSON.stringify(socket.decoded), 'SIO');

                    if (socket.decoded.meta_id==0) {
                        HELP.log(" Player ID : "+socket.decoded.meta_id+" -> No Account, no game. ", "SIO");
                        return false;
                    }
                    
                    if (demomode!=true) {
                        if (socket.decoded.fee_paid<Math.ceil(Date.now()/1000)) {
                            HELP.log(" Player ID : "+socket.decoded.meta_id+" -> No Fee, no game. ", "SIO");
                            return false;
                        } else {
                            HELP.log(" Player ID : "+socket.decoded.meta_id+" -> Fee OK. ", "SIO");
                        }
                    } else {
                        HELP.log(" Player ID : "+socket.decoded.meta_id+" -> Fee "+socket.decoded.fee_paid+", but this is not about money.", "SIO");
                    }

                    // Loading Player DATA -------------------------------------
                    HELP.log(`User << ${socket.decoded.name} >> connected.`, 'SIO');

                    // socket.player_id = that.sherpa.player_in(socket.decoded.meta_id, socket.decoded.name, socket.decoded.fee_paid);
                    socket.player_id = socket.decoded.meta_id;
                    socket.player_name = socket.decoded.name;
                    socket.join('ingame');     // Rooms are Server side

                    // Count current players :
                    that.s_tchat_one(socket, 'Bienvenue sur le serveur "'+world_ini['name']+'" ! ');
                    that.ask_wlc(socket);

                    next();
                });
            } else { next(new Error('Authentication error')); }    
        }).on('connection', function(socket) { that.listener(socket); });
    };

    this.listener = function(socket)
        {
        // Client connect/disconnect -------------------------------------------
        // HELP.log(`Socket ${socket.id} connected.`, 'SIO');
        

        socket.last_com = Date.now();
        socket.on('disconnect', () =>  { HELP.log(`User << ${socket.decoded.name} >> disconnected.`, 'SIO'); });
        // Client requests : ---------------------------------------------------
        socket.on('COM', function(msg, callback)
            {
            // Avoid DoS :    
            if (Date.now()-socket.last_com<50)
                {
         //       that.kick_balls(socket, 'Possible DoS attempt.');    
         //       return false; 
                }
            socket.last_com = Date.now();
            /*******************************************************************
                    ADD HERE A STRING SANITIZER !!!
             ******************************************************************/
            msg = that.to_Json(msg);
            // HELP.log(msg);
            if (msg!==false)
                {
                // Oh well my good sir, what do you want ?    
                // var resp = null;
                // Find associated Func of this requet :
                var func_name = 'ask_'+msg.t.toLowerCase();

                // console.log(func_name)
                
                // Execute func :    
                if (typeof that[func_name]==='function')
                    {
                    // Only authenticted player can do that, except those who want to authenticate :    
                    if (typeof socket.player_id==='undefined' && func_name!=='ask_auth') {return false;}
                    that[func_name](socket, msg.m, callback); 
                    }
                else 
                    {
                    // Kick those scriptKiddies !
                    that.kick_balls(socket, 'Trying to ask unknown request.');
                    }
                }
            else
                {
                that.kick_balls(socket, 'Possible forged packet.');
                }
            });
        };
    this.kick_balls = function(socket, why)
        {
        HELP.log(' ['+socket.player_id+'] Kicked :'+why, 'SIO');    
    //    socket.disconnect();
        };
    /*--------------------------------------------------------------------------
        Envoi
    --------------------------------------------------------------------------*/        
    this.send_all = function(type, data)    
        {
        this.io.to('ingame').emit(type, data);
        };
    this.send_one = function(socket, type, data)
        {
        if (typeof socket.player_id==='undefined') {return false;}
        socket.emit(type, data);
        };
    /*--------------------------------------------------------------------------
        Tchat :
    --------------------------------------------------------------------------*/                    
    this.ask_tch = function(socket, data)
        {
        if (typeof socket.player_id==='undefined') {return false;}
        if (data.length<=0) {return false;}
        if (data.length>200) { data = data.substr(0,200); }

        if (socket.player_id=='1') {
            var msg = {name:socket.player_name, msg:data, col:'#0962B6'};
        } else {
            var msg = {name:socket.player_name, msg:data };    
        }
        this.send_all('TCH', msg);
        };
/*
	this.ask_wlc = function(socket) {
	    const room = this.io.sockets.adapter.rooms['ingame'];
	    const clientsCount = room ? Object.keys(room.sockets).length : 0; // Utilisez Object.keys pour obtenir le nombre de clients
	    this.s_tchat_one(socket, clientsCount + ' joueurs en ligne.');
	};
*/
    this.ask_wlc = function(socket)
        {
        let count = 0;

		// Énumérer les sockets
	    // Énumérer tous les clients connectés
	    this.io.sockets.sockets.forEach((clientSocket) => { count++; });
        this.s_tchat_one(socket, '('+count+') Joueurs en ligne.');
        };   


    this.ask_wlca = function(socket)
        {
        // Seulement pour admin !       
        // if (this.board.player_name(socket.player_id)!=='admin') {return false;}    
        if (socket.player_id!==1) {return false;}    
        // --
        var name_list = ''; var count = 0;

		// Énumérer les sockets
	    // Énumérer tous les clients connectés
	    this.io.sockets.sockets.forEach((clientSocket) => {
	        count++;
	        // console.log(`Client ${count}: Player ID: ${clientSocket.player_id}`);
	        if (clientSocket.player_name!==socket.player_name) {
	        	name_list = name_list +', [<span class="nick">'+ name +'</span>]';
	        } else {
	        	name_list += "["+clientSocket.player_name+"]";	
	        }
	    });
        this.s_tchat_one(socket, '('+count+') Joueurs en ligne : '+name_list);
        };   
/*
    this.ask_wlst = function(socket)
        {
        // Seulement pour admin !       
        // if (this.board.player_name(socket.player_id)!=='admin') {return false;}    
        if (socket.player_id!==1) {return false;}    
        // --
        var name_list = ''; var count = 0;

		// Énumérer les sockets
	    // Énumérer tous les clients connectés
	    this.io.sockets.sockets.forEach((clientSocket) => {
	        count++;
	        // console.log(`Client ${count}: Player ID: ${clientSocket.player_id}`);
	        if (clientSocket.player_name!==socket.player_name) {
	        	name_list = name_list +', [<span class="nick">'+ name +'</span>]';
	        } else {
	        	name_list += "["+clientSocket.player_name+"]";	
	        }
	    });
        this.s_tchat_one(socket, '('+count+') Joueurs en ligne : '+name_list);
        };        
*/
    this.ask_whisp = function(socket, data)
        {
        // whisp d'un joueur a un autre !
        var nick = data.nick;
        var msg = data.msg;
        var dest_socket = this.find_socket(nick);
        var sender = socket.player_name;
        // console.log(dest_socket);
        if (dest_socket!==false)
            {
            var w_msg = {name:sender, msg:msg, col:'#F980ef'};
            this.send_one(dest_socket, 'TCH', w_msg);    
            this.send_one(socket, 'TCH', w_msg);    
            }
        else 
            {
            this.s_tchat_one(socket, 'Pas de joueur "'+nick+'" en jeu.');
            }
        };    
    // Le Server Parle ! -------------------------------------------------------
    this.s_tchat_all = function(data)
        {
        var msg = {name:'Server', msg:data, col:'#F04D84'};
        this.send_all('TCH', msg);
        };        
    this.s_tchat_one = function(socket, data)
        {
        var msg = {name:'Server', msg:data, col:'#F04D84'};
        this.send_one(socket, 'TCH', msg);
        };     


    /*--------------------------------------------------------------------------
        To Sherpa :
            Est envoyé au Sherpa ce qui est techniquement propre. C'est le 
            Sherpa qui controlera la faisabilite selon le jeu.
            C'est aussi lui qui lit la board
            
    --------------------------------------------------------------------------*/

    // Game Init ---------------------------------------------------------------
/*    
    this.ask_init = function(socket, data, callback)
        {
        var promise = this.sherpa.game_init(socket.player_id);
        promise.then(function(results){callback(results);}, false);        
        };
    // Geography ---------------------------------------------------------------
    this.ask_scope = function(socket, data, callback)
        {
        var promise = this.sherpa.scope_data(socket.player_id, data);
        promise.then(function(results){callback(results);}, false);        
        };
*/        
/*
    this.ask_newbie = function(socket, data, callback) {
        var promise = this.sherpa.is_newbie(socket.player_id);
        promise.then(function(results){callback(results);}, false);        
    }
*/
    // Unit Orders -------------------------------------------------------------
/*        
    this.ask_uorders = function(socket, data, callback)
        {
        var promise = this.sherpa.units_order(socket.player_id, data);
        promise.then(function(results){callback(results);}, false);        
        };
    this.ask_set_order = function(socket, data, callback)
        {
        var promise = this.sherpa.set_order(socket.player_id, data);
        promise.then(function(results){callback(results);}, false);        
        };
    this.ask_cancel_order = function(socket, data, callback)
        {
        var result = this.sherpa.cancel_order(data.units, socket.player_id);
        if (result==='kick') 
            {
            HELP.log('Tentative d\'ordre a une unite tierce.', 'SIO');
            socket.disconnect();
            }
        callback();
        };          
*/
    }

module.exports = new ServerIO();