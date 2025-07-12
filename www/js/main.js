/*==============================================================================
    Main.JS 
        - Regroupe les elemtns partagés entre les differents "modules" du jeu.
        - Module qui charge les elements du jeu necessaire a tout le reste
        - et ensuite qui lance les modules qui vont bien
==============================================================================*/
"use strict";
import { OrderAPI } from './order/order-api.js';
import { OrderFactory } from './order/order-factory.js';
import { OrderUI } from './order/order-ui.js';

/*------------------------------------------------------------------------------
    container des modules : (variables accessibles a tous)
------------------------------------------------------------------------------*/
Math.seedrandom('UniqueSeedForGraphicsYeah');

let LEAFLET_MAP = null;                 // Leaflet map object

let Mv = null;
let Mm = null;
let Mb = null;
let Se = null;
let Or = null;
let Bo = null;
let Ge = null;
let Pl = null;
let Pp = null;

// A voir si on doit pas les faire passer plutôt que de les avoir là (a la fin)
const SCOPE = [];
let SELECTION = null;

const H_S = 5;                    // Hexagons Size in Lat/Lng
const H_W = Math.sqrt(3) * H_S;   // Hexagon Width ...
const H_H = H_S*2;                // ... and height.



/*------------------------------------------------------------------------------
    Le Module en lui meme : 
------------------------------------------------------------------------------*/
function GameInit() {

    var that = this;        

    // First module to launch !
    Mb = new Menubar();

	/***************************************************************************
	    Utilitarian
	***************************************************************************/
    this.launch_game = function(latlng) {

        // global Rules;
        this.max_col = Rules.max_col;
        this.max_row = Rules.max_row;
		this.start_loc = latlng;

        // On injecte les regles CSS des couleurs des pions.
        let rootRule = ':root {';
        for (const ck in Rules.colors) {
            let bgRule = 'img.Bg'+ck+' { --MainC: var(--'+ck+');}'
            document.styleSheets[1].insertRule(bgRule, 0);
            let boRule = 'img.Bo'+ck+' { --SecC: var(--'+ck+');}'
            document.styleSheets[1].insertRule(boRule, 0);
            rootRule = rootRule +' --'+ck+': '+Rules.colors[ck]+';';  
        }
        rootRule = rootRule + '}';
        document.styleSheets[1].insertRule(rootRule, 0);

        // On demarre ceux qui n'ont pas besoin de se preparer :
        logMessage("Launching modules !", "main.js");
        // Or = new OrderFactory();
        // OrderAPI = new OrderAPI();
        // OrNew = new OrderFactory();
        Se = new Selection();
        Pl = new Panel();
        Pp = new Popup();
        // Ge = new Game_Events();

        // On prepare les modules :
        Mv = new Mapview(this.max_col, this.max_row, this.start_loc);
        Mm = new Minimap(this.max_col, this.max_row);
        Bo = new Board_Iface(this.max_col, this.max_row);

        // On les demarre :
        Mv.launcher();
        Mm.launcher();
    };

    var callback_newbie = function(newb_data) {
    	if (newb_data.retry===true) {
    		logForce("Server locked, can not add newbie. Waiting 3 sec.");
			setTimeout(function() { C.ask_data('NEWBIE', '', callback_newbie); }, 3000);
    		return;
    	}
        if (newb_data.is_newb===true) {
            logForce("Is a newbie !");
            Welcome = new Welcome();
            Welcome.play();

        } else { 
        	logForce("Is not a newbie !");
    	}

        // Asking for Newbie is an opportunity to find a start place !
		that.launch_game(newb_data.start_loc);

    }    

    C.ask_data('NEWBIE', '', callback_newbie);
    
}

new GameInit();
