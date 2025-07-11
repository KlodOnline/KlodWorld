/*==============================================================================
    PANEL
    	Panel Caller
    	Give it a type, it requests serveur  create the needed panel

==============================================================================*/
"use strict";

class Panel {

    constructor() {
		this.panelTemplate = `
			<div id="panel_container">
				<button class="close-btn">X</button>
                <div id="panel_title">
                </div>
                <div id="panel_content">
                </div>
            </div>`;

    }

    details(type, id) {
    	allowLogs();

    	logMessage('Calling Panel! Type: ' + type + ' ID: ' + id);

        $('#panel').html('Loading ...'); 
        $('#panel').css('z-index', 15);
        let apiRequest = null;
        if (type=='city') { apiRequest = 'CITY_INFO'; }
        if (type=='unit') { apiRequest = 'UNIT_INFO'; }
        // if (type=='mailbox') { apiRequest = 'MAILBOX'; }
        if (apiRequest==null) {return;}

        logMessage('Server dear server ... ');
        C.ask_data(apiRequest, id, this.show.bind(this, type, id));

	    // C.ask_data(apiRequest, id, (data) => { this.show(type, id, data); });

        disableLogs();
    }

    // Fonction pour afficher un panneau
    show(type, id, data = null) {

    	allowLogs();
        

        let content = '';
        
        logMessage('Showing panel !');

        let panelObject = null;

        // Sélectionner le bon contenu selon le type du panneau
        if (type === 'city')  { panelObject = new PanelCity(id, data); }
        if (type === 'unit')  { panelObject = new PanelUnit(id, data); }
        //if (type === 'mailbox') { panelObject = new PanelMailbox(data); }

        // Créer le panneau en utilisant le template de base
        let panelDiv = $(this.panelTemplate);
        
        // Ajouter le contenu spécifique dans la zone dédiée
        panelDiv.find('#panel_title').html(panelObject.title());
        panelDiv.find('#panel_content').html(panelObject.content());
        
        // Appliquer les fonctionnalités supplémentaires (fermer le panneau, draggable...)
        this.setupPanel(panelDiv);

        // definir et monrer le resultat :
		$('#panel').html(panelDiv); 
        // $('#panel').css('z-index', 15);
        $("#panel").draggable({ containment: 'body' }); 
        
        // Désactiver les logs après l'affichage
        disableLogs();
        
    }

    // Fonction pour gérer les actions sur le panneau
    setupPanel(panelDiv) {

        // Fermer le panneau lorsque le bouton "X" est cliqué
        panelDiv.find(".close-btn").on('click', function() {
            $(this).closest("#panel").css('z-index', -5); 
        });

    }

    delete() {
    	$('#panel').css('z-index', -5);  
    }

	initEvents() {

	    // Key press to delete selection (Esc key)
	    $(document).on('keyup keydown', function(e) {
	        if (e.keyCode === 27) {
	            Pl.delete(); 
	            return; 
	        }
	    });

	}

    // $('#panel').html('init'); 
	// $('#panel').css('z-index', 15);
    

}
