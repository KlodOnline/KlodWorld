/*==============================================================================
    SHERPA
        "Assistant GM"
            - Repond aux demande de precision des joueurs
            - Note les ordres aux objets des joueurs 
            - Signale au GM si utile.

==============================================================================*/
const GAME_RULE = require('./game_init');
const ORDER_COOK = require('./order_iface');
const HELP = require('./helpers');
/*------------------------------------------------------------------------------
    Module
------------------------------------------------------------------------------*/
function Sherpa() {

    this.io = null;
    this.board = null;
    this.gm = null;
    this.clients = [];
    var that = this;
    
    // Initialisation ----------------------------------------------------------    
    this.initiate = function(board, gm, io) {
        this.io = io;
        this.board = board;
        this.gm = gm;
    };

    this.player_in = function(meta_id, name, paidto) {

        HELP.log('player_in '+meta_id+' '+name+' '+paidto)
        
        let player = this.board.player_from_id(meta_id);

        // HELP.log("player found is : "+JSON.stringify(player), 'SHERPA')

        
        if (player==undefined) {
            HELP.log('Here Comes a New Challenger !!! ', 'SHERPA');

            game_id = this.gm.new_player(meta_id, name, paidto);
            if (game_id===false) {
                HELP.log('Error : Existing ID ('+meta_id+')?!', 'SHERPA');
                return false;
            }
        } else {
            HELP.log('An old customer :) ', 'SHERPA');
            HELP.log('Update Fee Information ;-) ', 'SHERPA');
            player.paidto = paidto;
            game_id = player.id;
        }
        

        HELP.log('GameID='+game_id, 'SHERPA')
        return game_id;
    };

    // Doing his job -----------------------------------------------------------
/*

    this.set_order = function(pid, data)
        {
        var unit_ids = data.units
        var order_type = data.order.name;
        var order_data = data.order.data;        
        
        // Temporairement :
        //  -> Mettre les ordre comme ils arrivent, sans se poser de questions !
        return new Promise((resolve, reject)=>
            {
            if (typeof data.order==='undefined') {return false;}
            if (typeof data.order.name==='undefined') {return false;}                

            // Si il y a plusieurs unites, on prend comme base la plus... "nulle"
            // Donc l'ordre le plus long est considere comme le "bon" !

            // ---> A FAIRE ABSOLUMENT !!!

            for (var key in unit_ids)
                {
                if (typeof that.board.units[unit_ids[key]]!=='undefined')    
                    {
                    var unit = that.board.units[unit_ids[key]];
                    if ( this.board.unit_player_id(unit)===pid)
                        {
                        // Tout est legal maintenant on genere l'ordre et on l'attache a notre objet :
                        ORDER_COOK.prepare_order(that.board, unit, order_type, order_data);
                        if (unit.order!==undefined) {that.gm.add_timer(unit);}
                        }
                    }
                }                
            });
        
        };


    this.units_order = function(player_id, id_ary)
        {
        return new Promise((resolve, reject)=>
            {
            if (!this.board.player_exists(player_id)) {return false;}
            // var player = this.board.players[player_id];
            


            var units = [];
            var results = [];
            
            for (var key in id_ary)
                {
                if (typeof id_ary[key]==='undefined')   {continue;}
                if (id_ary[key]===null)                 {continue;}
                if (typeof this.board.units[id_ary[key]]!=='undefined')
                    { units.push(this.board.units[id_ary[key]]); }
                }
            for (var key in units)
                {
                results.push(this.board.unit_json(units[key], player_id));
                }
            resolve(results);
            });
        };

    this.cancel_order = function (unit_ids, pid)    
        {
        for (var key in unit_ids)
            {
            if (typeof this.board.units[unit_ids[key]]!=='undefined')    
                {
                if (this.board.unit_player_id(this.board.units[unit_ids[key]])) {
                    this.board.units[unit_ids[key]].order = null; 
                }
                else { return 'kick'; }
                }
            }
        };   
*/

    }

module.exports = new Sherpa();