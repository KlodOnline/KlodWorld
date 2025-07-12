/*==============================================================================

    OrderIface
 		Contient uniquement les définitions des classes d'ordres spécifiques et
 		leurs comportements intrinsèques. 

    ORDER() - Classe de base
    OrderMove(), BUILD_CITY(), RECRUIT_UNIT(), BUILD_ROAD(), etc.
    generate(units, data) - Initialisation de l'ordre
    server_version() - Format pour le serveur
    txt() - Affichage de l'ordre actif
    prep_txt() - Affichage de préparation

==============================================================================*/

/*------------------------------------------------------------------------------
    Le module :
 -----------------------------------------------------------------------------*/
/*
function OrderIface() {

    // Liste des ordres existants --------------------------------------------------
    // Tu ne VEUX PAS automatiser ça !
    this.constructors = {
        MOVE: OrderMove,  
        BUILD_CITY: OrderBuildCity,  
        BUILD_ROAD: OrderBuildRoad,  
        OrderMove_ROAD: OrderMoveRoad,  
        CHOP_WOOD: OrderChopWood,
        RECRUIT: OrderRecruit,
        BUILDING: OrderBuilding
    };

    this.createFromServer = function(units, order) {
        const new_order = new this.constructors[order.name]();
        new_order.generate(units, order);
        new_order.log_info();
        return new_order;
    };

    this.createFromGUI = function(units, name) {
        const order = { 'name':name, 'data':{} }
        const new_order = new this.constructors[order.name]();
        new_order.generate(units, order);
        new_order.log_info();
        return new_order;
    };    

};
*/
/*------------------------------------------------------------------------------
    Les Ordres geres :
        Il "ont" :
            - De quoi les afficher
            - De quoi les preparer
            - De quoi les bricoler

 -----------------------------------------------------------------------------*/

function OrderClass() {

    // Primitives Param --------------------------------------------------------
    this.name = this.constructor.name;
    this.tic = 0;
    this.use_path = false;

    // Primitives Methods ------------------------------------------------------
    this.generate = function(units, data) {
        this.units = units;
        if (data.tic!==undefined) { this.tic = data.tic; }
    };

    this.log_info = function() {
    	let log = ' Standing order.';
        if (this.use_path) {
        	log = ' Moving order; Path : ' + JSON.stringify(this.path);
        }
        logMessage('Info -ORDER- '+this.name+' @T(' + this.tic+') ' + log); 
    };

    this.txt = function() {
        // Build a string describing current order for cartouche :    
        const txt = ' End of '+this.name+' : ' + time_to_txt( tic_to_date(this.tic) ) ;
        const html = '<div id="'+this.name+'" class="O '+this.name+'">'+txt+'<br/>'
            +'<button class="CANCEL">CANCEL</button></div>';
        return html;
    };

    this.server_version = function() {
    	return [this.name];
    	// return;
    	// return { name : this.name, data : null }; 
    };

    this.prep_txt = function() {        
        const prep_order = '<div class="">'+this.name+'<br/>'
                +'<button class="VALIDATE">OK</button><button class="CANCEL">CANCEL</button></div>';
        return prep_order;
    };

};

function OrderMove() {

    OrderClass.call(this);
    this.fpt = 0;


    // Les comportements propre ------------------------------------------------
    this.generate = function(units, data) {
        // Notre "data" doit contenir :
        // fpt / path / tic

        this.units = units;
        this.fpt = 0;
        this.use_path = true;

        if (data.fpt!==undefined) { this.fpt = data.fpt; }
        if (data.tic!==undefined) { this.tic = data.tic; }

        logMessage('-_-_-_ Digesting Path data : ' + data.path);

        this.set_path(data.path);
    };

    this.prep_txt = function() {
    	// logMessage('doing prep move');
        // if (this.tmp_path.length<=0) {return false;}
        // if (this.tmp_path.length<=0) {return false;}
        // Build a string describing current order for cartouche :    
        const next_txt = ' Next Move : ' + time_to_txt( tic_to_date(this.tic_next_step()+Mb.last_turn) ) ;
        const full_txt = '<br/> End of Travel : ' + time_to_txt( tic_to_date(this.tic_fulltmppath()+Mb.last_turn) ) ;
        const txt = next_txt + full_txt;
        const html = '<div id="'+this.name+'" class=" '+this.name+'">'+txt+'<br/>'
            +'<button class="VALIDATE">OK</button><button class="CANCEL">CANCEL</button></div>';
        return html;            
    };  

    this.txt = function() {
        // Build a string describing current order for cartouche :    
        logMessage('TIC of execution : ' + this.tic);
        
        // const next_txt = ' Next Move : ' + time_to_txt( tic_to_date(this.tic+Mb.last_turn) ) ;
        // const full_txt = '<br/> End of Travel : ' + time_to_txt( tic_to_date(this.fpt+Mb.last_turn) ) ;

        const next_txt = ' Next Move : ' + time_to_txt( tic_to_date(this.tic_next_step()+Mb.last_turn) ) ;
        const full_txt = '<br/> End of Travel : ' + time_to_txt( tic_to_date(this.tic_fulltmppath()+Mb.last_turn) ) ;

        const txt = next_txt + full_txt;
        const html = '<div id="'+this.name+'" class=" '+this.name+'">'+txt+'<br/>'
            +'<button class="CANCEL">CANCEL</button></div>';
        return html;
    };    


	this.server_version = function() { 
	    if (!Array.isArray(this.path)) {
	        console.error("`this.path` n'est pas un tableau.");
	        return null;
	    }

	    // Construire les segments de `this.path`
	    // const path_stg_ary = this.path.map(step => step.stg());

    	// Exclure le premier élément de `this.path`
    	const path_stg_ary = this.path.slice(1).map(step => step.stg());

	    // Joindre les étapes avec des virgules, puis concaténer avec des tirets
	    const path_stg = path_stg_ary.join('_');

	    // Combiner avec `this.name` pour créer la chaîne finale
	    const server_version_data = `${this.name}-${path_stg}`;

	    // Afficher la version finale
	    logMessage("Version finale :", server_version_data);

	    // Retourner la chaîne finale
	    return server_version_data;
	};

    // Les comportements assignes ----------------------------------------------
    // OrderMove.prototype.setPathData = setPathData;
    OrderMove.prototype.last_path = last_path;
    OrderMove.prototype.add_to_path = add_to_path;
    OrderMove.prototype.filter_path = filter_path;
    OrderMove.prototype.tic_next_step = tic_next_step;
    OrderMove.prototype.tic_fulltmppath = tic_fulltmppath;
    OrderMove.prototype.set_path = set_path;
    OrderMove.prototype.speed =speed;

};

function OrderBuildCity() { OrderClass.call(this); };
function OrderChopWood() { OrderClass.call(this); };    
function OrderBuildRoad() { OrderClass.call(this); };   
function OrderRecruit() { OrderClass.call(this); };    
function OrderBuilding() { OrderClass.call(this); };     
// En vrai, c'est juste un OrderMove un peu agremente...
function OrderMoveRoad() { OrderClass.call(this); };

/*==============================================================================
    Les comportements des ordres
        Ce sont les briques reutilisables, souvent communes a plusieurs ordres.
        
 ==============================================================================*/


const filter_path = function(path) {
    // Avoid redeundancy !
    let clean_path = [H.coord( this.units[0].cr.split(','), 'oddr' )];
    for (var key in path) {
        let step_coord = H.coord([path[key].col, path[key].row], 'oddr');
        let last_ok = clean_path[clean_path.length-1];
        // Si les coordonnées sont differentes :
        if ( step_coord.stg()!==last_ok.stg() ) { clean_path.push(step_coord); }
    }
    return clean_path;
};

const add_to_path = function (tmp_path) {
    if (!tmp_path) {
        logMessage('add> Invalid path: null or undefined');
        return;
    }
    
    // Variable pour stocker le chemin validé
    let parsedPath = [];

    // Si tmp_path est une chaîne de caractères
    if (typeof tmp_path === 'string') {

    	logMessage('add> String to process');

        // Découpe la chaîne en utilisant "_" comme séparateur
        const coordsArray = tmp_path.split('_');
        
        // Parcours chaque coordonnée de la chaîne
        for (let coord of coordsArray) {

        	logMessage(`add> ${coord} to morph  to oddr`);

            // Sépare chaque coordonnée en x et y avec la virgule
            const [x, y] = coord.split(',').map(Number);

            // Vérifie si x et y sont des nombres valides
            if (isNaN(x) || isNaN(y)) {
                logMessage(`add> Invalid coordinate: ${coord}`);
                continue;  // Ignore cette coordonnée et passe à la suivante
            }

            logMessage('Coord valide, I push !');

            // Si les coordonnées sont valides, ajoute un objet avec type 'oddr'
            parsedPath.push(H.coord([x,y],'oddr'));
        }
    } 

    // Si tmp_path est déjà un tableau d'objets avec le type 'oddr'
    else if (Array.isArray(tmp_path)) {

    	logMessage('add> Array to process');

        for (let pathItem of tmp_path) {

        	logMessage('Are all oddr ?');

            // Vérifie si chaque élément est bien de type 'oddr'
            if (pathItem.type !== 'oddr' || isNaN(pathItem.col) || isNaN(pathItem.row)) {
                logMessage('add> Invalid path: Found an invalid "oddr" element');
                return;
            }

            // Si c'est valide, on ajoute l'élément dans parsedPath
            parsedPath.push(pathItem);
        }
    } else {
        // Si tmp_path n'est ni une chaîne de caractères ni un tableau d'objets valides, loggue un message d'erreur
        logMessage('add> Invalid path: Not a valid string or array of "oddr" objects');
        return;
    }

    // Si parsedPath est vide après traitement, cela signifie qu'il n'y avait pas de coordonnées valides
    if (parsedPath.length === 0) {
        logMessage('add> No valid coordinates found');
        return;
    }

    logMessage('add> ended; obtained :' + JSON.stringify(parsedPath));

    // Ajoute le chemin validé à l'existant et applique le filtrage
    this.path = this.filter_path([...this.path, ...parsedPath]);
};

const set_path = function(path) {
    // Attention, path peut etre vide ou nawak
    this.path = [];
    // Un path, c'est TOUJOURS depuis l'unite jusqu'a la suite :
    const first = [H.coord(this.units[0].cr.split(','),'oddr')];
    logMessage('Define Path or set a specific one (First)');
    this.add_to_path(first);
    logMessage('Define Path or set a specific one (path) ');
    this.add_to_path(path);
};

const speed = function () {
    // Calcul de vitesse (dans une func pour le jour ou on voudra grouper les unites)
    logMessage('Speed of Unit = ' + Rules.units[this.units[0].ty].move);
    return Rules.units[this.units[0].ty].move;
};

const last_path = function () {   

    if (!this.path || this.path.length === 0) { 
        // console.warn("Warning: last_path() appelé alors que path est vide !");
        // return null;  // Évite de retourner `undefined`
        // Si pas de path, seule ma position est valide :
        const first = [H.coord(this.units[0].cr.split(','),'oddr')];
        return first;
    }
    
    return this.path[this.path.length - 1]; 
};

const tic_next_step = function() {
    let value = 0;

    if (!this.path || !this.path[0] || !this.path[1]) {
        logMessage('No path to evaluate');
    } else {
        value = this.speed() + Mv.move_cost(this.path[0], this.path[1]);
    }

    logMessage('Nb Turn next step = ' + value);

    return value;
};

// Le monde actuel (320x250 TIC=5 min)
// Il faut ~12 jours pour faire le tour du monde !

/*
const tic_next_step_old = function() {
   	// Units have Move Value : 
   	// 1 -> 4 (Fast/Medium/Slow/VerySlow)
   	//
   	// Lands have Move Value :
   	// (1) 2 -> 5 (Roaded/Easy/Medium/Hard/VeryHard)
   	//
   	//	MoveUnit + LandMove = Fastness, Caped to Very Slow.
   	//
   	//	Fast : 2 turntoGo. VerySlow : 6 Turns to go.
   	// 
   	// Warning : move 0 = nothing
   	$nextTurn = $actor->getUnitTypeMovement() + $targetGround->getLandTypeMove();
   	if ($targetGround->getRoad()>0) { $nextTurn = $nextTurn - 1; }
   	if ($nextTurn>6) {$nextTurn = 6;}
	if ($nextTurn<1) {$nextTurn = 1;}
	return $nextTurn;	
};
*/

const tic_fulltmppath = function() {
    // Set a number of TICS for the whole path, according to player's known
    let total_tic = 0;
    let from_coord = this.path[0];
    for (const key in this.path) {
        if (typeof this.path[(key-1)]!=='undefined') { from_coord = this.path[(key-1)]; }
        else {continue;}
        let move_cost = Mv.move_cost(from_coord, this.path[key]);
        if (move_cost<30) {move_cost = move_cost + this.speed();}
        total_tic = total_tic + move_cost;
    }
    return total_tic;
};       

// FONCTIONS COMMUNES (PAS DES COMPORTEMENTS !) ================================