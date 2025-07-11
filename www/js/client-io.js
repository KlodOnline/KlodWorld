/*==============================================================================
    ClientIO v0.2.0
    Very Simple Communication with Game Server
	Desormais, tout sauf le tchat passe par game_api.php.

==============================================================================*/
"use strict";

function ClientIO() {

    /*--------------------------------------------------------------------------
        All that pre-exists : 
    --------------------------------------------------------------------------*/

    // if (typeof token === 'undefined') { document.location.href=WEBSITE; }

    this.last_request = Date.now();
    const that = this;

    /*--------------------------------------------------------------------------
        Server COM (Read / Send)
    --------------------------------------------------------------------------*/
    this.ask_data = function(type, msg='', callback=function(){}, first_try=true) {

        /* ---------------------------------------------------------------------
            Nouveau systeme :
                Il doit a terme gerer toutes les operations de lecture en php
                pour le client.
        --------------------------------------------------------------------- */

        logMessage('ASK DATA! ('+type+')');
        const startTime = performance.now();

        let request = 'T='+type;
        if (msg!='') {request = request + '&M='+msg}

        if (type=='SCOPE' || type=='NEWBIE' || type=='CSELECT'
        	|| type=='UORDERS' || type=='SET_ORDER' || type=='CANCEL_ORDER'
        	|| type=='SCOPEG' || type=='SCOPEA' || type=='GET_ORDER'
        	|| type=='CITY_INFO' || type=='UNIT_INFO'
        	) {

        	logMessage('Request Type : ' + type);

            // Audit request.php <--
            // const startTime = performance.now();

            let json_data = '{}';
            
            $.ajax({ 
                method:'POST',
                data: request,
                url: "../includes/game_api.php",
                cache: false
            }).done(function(data) {
                // Calculation time <--
                const endTime = performance.now();
                const executionTime = Math.round(endTime - startTime); 
                logMessage('AJAX Request > ' + executionTime + ' ms to do ' + request);
				try {
				    json_data = JSON.parse(data);
				    if (json_data['nope']===true) {
				    	if (first_try) {
				    		// Aloow a retry on request after 1 sec pause.
				    		setTimeout(function() { that.ask_data(type, msg, callback, false); }, 1000);
				    	} else {
				    		logMessage('Naaah gave up on this ! '+type);
				    	}
				    }
				} catch (error) {
					logForce("Erreur lors du parsing JSON :", data);
					console.log(error);
					console.log(data);
				    json_data = '';
				}
                callback(json_data);
            });
            return;   
        }

        /* ---------------------------------------------------------------------
            Ancien systeme :
        --------------------------------------------------------------------- */
        // Si le type a deja ete requete mais pas de reponse, on ne le rejoue pas !
        // Request temporizator (to avoid server kick)    
        while((Date.now()-that.last_request)<150) {  }
        that.last_request = Date.now();
        // Chaque module doit avoir son propre cache. Ne rien faire ici !    
    };
};

const C = new ClientIO();
