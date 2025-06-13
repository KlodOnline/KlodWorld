/*==============================================================================
    SHERPA
        "Assistant GM"
            - Repond aux demande de precision des joueurs
            - Note les ordres aux objets des joueurs 
            - Signale au GM si utile.

==============================================================================*/
// const GAME_RULE = require('./game_init');
// const ORDER_COOK = require('./order_iface');
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
}
module.exports = new Sherpa();