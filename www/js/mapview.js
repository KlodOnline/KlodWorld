/*
    Réorganiser le code : 

        - Affichage de la carte
            > Rafraichissement (== affichage initial, c'est le cache qui fera les data)
                > appel des données
                > modification des élements visuels pertinents.

        - Interaction avec "la carte" en tant que telle (déplacement, zoom, link coords)

        - Interaction avec les élement de la carte (unités, ville)

    Tout ce qui est pathfinding, etc. doit être géré ailleurs.

*/



/*==============================================================================
    Mapview Module
        - The heart of the game : Interactive map.

    Known bug : 
        - If there is not any fog at first draw, its generation may result in
        its canva to be on top of everything, making units not clickable.

==============================================================================*/
"use strict";

function PriorityQueue(size = 500)
    {
    this.max_size = size;   // La TAILLE de ma QUEUE.  :3
    this.queue_limit = false;
    this.elements = {};

    // this.elements est un dict dont les clés sont des priorités
    // Chaque clé contient un array d'element qui sont des objets
    // (oui faut suivre aussi hein)

    this.log = function() {
        logMessage(' Queue Log : ');
        logMessage(' Size = '+this.queue_size());
        logMessage(JSON.stringify(this.elements));
    }

    this.queue_size = function() {
        let size = 0;
        for (let key in this.elements) { if (this.elements[key].length>0) { size = size + this.elements[key].length;} }        
        return size;
    };

    this.is_empty = function() {
        if ( Object.keys(this.elements).length < 1) {return true;}
        // if ( this.elements.length <1 ) {return true;}
        return false;
    };

    this.put = function(item, priority) {

    	// logMessage('Add in Queue (prio:'+priority+')');
    	// logMessage(item);

        if (!Number.isInteger(priority)) {
        	logMessage('PriorityQueue bad.');
        	return false;
        }

        var pkey = priority;
        var element = {item:item, priority:priority};
            
        if (typeof this.elements[pkey]==='undefined') { this.elements[pkey]= [element];  }
        else { this.elements[pkey].push(element);  }
    
        if (this.queue_limit) {
            // Techniquement, si notre QUEUE est trop GROSSE
            // On fait sauter les trop gros scores
            if (this.queue_size()>this.max_size) {
                let maxkey = Math.max(...Object.keys(this.elements));
                this.elements[maxkey].pop();
            }
        }

		// logMessage('Queue : ');
	    // logMessage(this.elements);

    };

	this.get = function () {
	    let elements = undefined;

	    // logMessage('Queue : ');
	    // logMessage(this.elements);

	    // Recherche de la première pile d'éléments valide
	    for (const [key, value] of Object.entries(this.elements)) {
	        if (value.length > 0) {
	            elements = value;
	            break;
	        } else {
	            delete this.elements[key];
	        }
	    }

	    // Aucun élément valide trouvé
	    if (!elements) {
	    	// logMessage('No valid elements');
	        return false;
	    }

	    // Retirer le premier élément de la pile
	    const first_element = elements.shift();
	    const pkey = first_element.priority;

	    // Mise à jour de la pile, si elle n'est pas vide
	    if (elements.length > 0) {
	        this.elements[pkey] = elements;
	    }

	    // Retour de l'élément extrait
	    return first_element.item;
	};


};

/*------------------------------------------------------------------------------
    Le Module
------------------------------------------------------------------------------*/
function Mapview(max_col, max_row, start_coord)
    {
    /*--------------------------------------------------------------------------
        Initialisation :
    --------------------------------------------------------------------------*/
    this.last_date = 0;
    this.cache = [];
    this.path =[];
    // Max Size :
    this.max_col = max_col;
    this.max_line = max_row;    
    this.start_coord = start_coord;
    this.shifted = false;
    this.mouse_coord = null;
    // X = Horizontale = LONGITURE \ Y = Verticale = LATTITUDE
    this.x_min = 0; this.x_max = this.max_col * (H_W) + (H_W/2);  
    this.y_min = 0; this.y_max = this.max_line * H_H*3/4 + H_H/4;

    this.receipt = true;
    
    this.perf = true;
    this.perf_run = false;
    this.perf_data = {};

    this.log = false; 

    // For specific functions :
    var that = this;
    // Objets utilises ---------------------------------------------------------
    // Leaflet :
    LEAFLET_MAP = new L.Map('map', 
        {
        crs: L.CRS.Simple,
        // Je ne suis pas sur de ces options, si elle sont utile toute deux.
        preferCanvas: true,
        //renderer: L.canvas(),
        // --
        inertiaMaxspeed: 5,
        inertiaDeceleration:80000,
        keyboardPanDelta:150,
        maxBounds: [[this.y_min-H_H, -Infinity], [this.y_max+H_H, Infinity]],

        // Zoom Options :
        zoomControl: false,
        minZoom: 2,
        maxZoom:4,
        // Only available if L_DISABLE_3D = false (index.php)
        zoomSnap : 0,
        zoomDelta : 0.5,
        wheelPxPerZoomLevel: 1000,
        // Avec animation = glitches dans la map...
        zoomAnimation: false,
        // -- 
        maxBoundsViscosity :1.0
        });

    this.lastzoom = 4;

    var imageUrl ="./pics/fog.png";
    var imageBounds = [[this.y_min*0.8, this.x_min*0.8], [this.y_max*1.2, this.x_max*1.2]];
    // var imageBounds =  [[0,0], [1000,1000]] ;
    var backgroundImage = L.imageOverlay(imageUrl, imageBounds, {opacity: 1, renderer:L.canvas()});
    backgroundImage.tag = 'NoDel';
    LEAFLET_MAP.addLayer(backgroundImage);


    /*--------------------------------------------------------------------------
        Ignition
    --------------------------------------------------------------------------*/
    this.launcher = function() {
        // Leaflet Takes Lat/Lng coords, that's y/x pixel coords ...    
        var pixel = H.coord_to_pixel(H.coord(this.start_coord.split(','), 'oddr'), H_W, H_H, H_S)
        var latlng = [pixel[1], pixel[0]];
        LEAFLET_MAP.setView(latlng, 3);        
        // Now, go.
        this.set_interacts();
        this.refresh();  
    };

    /*--------------------------------------------------------------------------
        Parse Server Data, Cache, & Draw :
    --------------------------------------------------------------------------*/
	this.refresh = function(e = null) {

		// logMessage('Refresh Asked !');

	    // Debounce using requestAnimationFrame for smoother animations
	    if (this.lastRefresh && (performance.now() - this.lastRefresh < 400) && e !== 'moveend' && e !== 'force') {
	        return;
	    }
	    this.lastRefresh = performance.now();

	    const center = LEAFLET_MAP.getBounds().getCenter();
	    const wrap_x = this.find_normal_x(center.lng);
	    
	    if (wrap_x !== center.lng) {
	        // Bug fix for right border cutting hexagons
	        const correction = center.lng < 0 ? -H_W/2 : H_W/2;
	        const newCenter = [center.lat, wrap_x + correction];
	        // Allow wrap-world moving; but flashy :
	        LEAFLET_MAP.panTo(newCenter, {animate: false}); 
	        return;
	    }

	    // Asynchronous server data request
	    const processData = (data) => {
	        this.digest_data(data);
	        this.refresh_graphics();
	        if (data.length > 0) {
	            Mm.add_mapdata(data);
	        }
	        this.receipt = true;
	    };

	    // Check for uncached hex bounds
	    const hexBounds = this.getHexBounds_NotInCache();
	    if (hexBounds !== false && this.receipt) {
	        this.receipt = false;
	        C.ask_data('SCOPEA', hexBounds, processData);
	    } else {
	        processData([]);
	    }
	};

    this.digest_data = function(server_data) {
        // Ici c'est la digestion des donnees serveur.
        Bo.digest_data(server_data);
        // Cette fonction detruit les objets "en trop" il faut ajouter notre 
        // memoire du path :
        // logMessage('MapView Digest data set path :');
        Bo.set_path(this.path);
    };

    this.refresh_graphics = function() {
        that.set_scope();
        that.draw_scope();
    };   

	this.set_scope_new = function() {
	    // Effacer directement tout le SCOPE
	    SCOPE.length = 0;

	    // Obtenir les limites hexagonales
	    const [minCol, minLine, maxCol, maxLine] = this.getHexBounds();

	    // Stocker les résultats des appels à Bo.objects_at pour éviter les appels multiples
	    const objectsCache = {};

	    for (let col = minCol; col <= maxCol; col++) {
	        for (let line = minLine; line <= maxLine; line++) {
	            if (!objectsCache.hasOwnProperty(`${col},${line}`)) {
	                objectsCache[`${col},${line}`] = Bo.objects_at(col, line);
	            }
	            let objects = objectsCache[`${col},${line}`];
	            if (objects) {
	                SCOPE.push(objects);

	                // Utiliser une boucle for classique pour potentiellement améliorer les performances
	                for (let objKey in objects) {
	                    if (objects.hasOwnProperty(objKey)) {
	                        objects[objKey].set_relat_place(`${col},${line}`);
	                    }
	                }
	            }
	        }
	    }
	};

    this.set_scope = function() {
    // Effacer directement tout le SCOPE
    SCOPE.length = 0;

    // Obtenir les limites hexagonales
    const [minCol, minLine, maxCol, maxLine] = this.getHexBounds();

    for (let col = minCol; col <= maxCol; col++) {
        for (let line = minLine; line <= maxLine; line++) {
            let objects = Bo.objects_at(col, line);
            if (objects) {
                SCOPE.push(objects);

                // Utiliser forEach pour optimiser la boucle
                Object.values(objects).forEach(obj => obj.set_relat_place(`${col},${line}`));
	            }
	        }
	    }
	};

	this.draw_scope = function() {

		// --
		logMessage('Drawing Scope ... (Time=0) ');

	    // Audit de DRAW_SCOPE !!
		const startTime = performance.now();

	    const layers_in_scope = [];
	    const tag_in_scope = new Set();
	    const tag_refresh = new Set();
	    const layers_drawn = new Map(); // Utilisation d'un Map pour suivre les layers et leurs tags

	    // Parcours du SCOPE
	    // This 'for' loop is audited & really fast.
	    // ~ 20ms
	    for (const sck of SCOPE) {
	        let no_vario = false;
	        for (const [, obj] of Object.entries(sck)) { // Utilisation de `Object.entries` pour éviter de créer un nouvel array
	            if (obj.tag === 'City') no_vario = true;

	            const layers = obj.create_layers(no_vario);
	            if (layers) {
	                layers_in_scope.push(layers);
	                tag_in_scope.add(obj.tag);

	                if (obj.to_update) {
	                    tag_refresh.add(obj.tag);
	                    obj.to_update = false;
	                }
	            }
	        }
	    }

	    let stepTime = performance.now();
        let stepExecTime = Math.round(stepTime - startTime); 
		logMessage('Scope ForLoop Time => ' + stepExecTime + ' ms.');

	    tag_in_scope.add('NoDel'); // Layers permanents

	    // Vérification et gestion des couches existantes
	    // ~ 80ms
	    LEAFLET_MAP.eachLayer(layer => {
	        if (layer.tag) {
	            layers_drawn.set(layer.tag, layer);

	            if (!tag_in_scope.has(layer.tag) || tag_refresh.has(layer.tag)) {
	                LEAFLET_MAP.removeLayer(layer);
	                layers_drawn.delete(layer.tag);
	            }
	        }
	    });

	    stepTime = performance.now();
        stepExecTime = Math.round(stepTime - startTime); 
		logMessage('LayerCleanUp Time => ' + stepExecTime + ' ms.');

		LEAFLET_MAP.options.zoomAnimation = false; // Désactiver l'animation de zoom
		LEAFLET_MAP.options.fadeAnimation = false; // Désactiver l'animation de fondu

		// Préparation des couches à ajouter
		const layersToAdd = layers_in_scope.filter(layer => !layers_drawn.has(layer.tag));

	    stepTime = performance.now();
        stepExecTime = Math.round(stepTime - startTime); 
		logMessage('LayerFiltering Time => ' + stepExecTime + ' ms.');




		const layerGroup = L.layerGroup(layersToAdd);
		LEAFLET_MAP.addLayer(layerGroup);

	    stepTime = performance.now();
        stepExecTime = Math.round(stepTime - startTime); 
		logMessage('AddLayer Time => ' + stepExecTime + ' ms.');

		
		

		// Mise à jour de l'état pour ne pas réajouter ces couches
		layersToAdd.forEach(layer => layers_drawn.set(layer.tag, true));  // Utilisation de set au lieu de add

		LEAFLET_MAP.options.zoomAnimation = true; // Désactiver l'animation de zoom
		LEAFLET_MAP.options.fadeAnimation = true; // Désactiver l'animation de fondu

	    stepTime = performance.now();
        stepExecTime = Math.round(stepTime - startTime); 

		logMessage('Total DrawLayer Time => ' + stepExecTime + ' ms.');
	};

    /*--------------------------------------------------------------------------
        Traitement diverses
    --------------------------------------------------------------------------*/
    this.leaflet_to_oddr = function(coord) {
        // Converts latLng to Oddr Hex coords :
        if (coord.lat!=='undefined') { var y = coord.lat; var x = coord.lng; }
        else { var y = coord[1]; var x = coord[0]; }
        return H.pixel_to([x,y], 'oddr', H_W, H_S);
    }; 
	this.find_normal_x = function(curr_x) {
        if (curr_x>this.x_max) {
            curr_x = curr_x - (this.x_max-1);
            curr_x = this.find_normal_x(curr_x);
        }
        if (curr_x<0) {
            curr_x = curr_x + (this.x_max-1);
            curr_x = this.find_normal_x(curr_x);
        }
        return curr_x;
    };
    this.find_normal_col=function(colonne, caller = 'myself', turn = 0) {
        return Bo.find_normal_col(colonne, ('MapView ' + caller), turn);
    };

    this.getHexBounds = function ()
        {
        // Gives Hex coords of current view, not wrapped :
        var ld_leaflet = {};    // Left Down Border
        ld_leaflet.lng = LEAFLET_MAP.getBounds().getWest();
        ld_leaflet.lat = LEAFLET_MAP.getBounds().getSouth();

        var tr_leaflet = {};    // Top Right Border
        tr_leaflet.lng = LEAFLET_MAP.getBounds().getEast();
        tr_leaflet.lat = LEAFLET_MAP.getBounds().getNorth();

        var ld_oddr = this.leaflet_to_oddr(ld_leaflet);
        var tr_oddr = this.leaflet_to_oddr(tr_leaflet);

        // Add margin of 1 Hex
        return [ld_oddr.col-1,ld_oddr.row-1,tr_oddr.col+1,tr_oddr.row+1];
        };

    this.getHexBounds_NotInCache = function()    
        {
        // In my current scope, get Hex coords rectangle of what to get from server :    
        var hb = this.getHexBounds();
        var nic_col_1 = 9999;   var nic_col_2 = 0;
        var nic_line_1 = 9999;  var nic_line_2 = 0;     
        for (var col=hb[0]; col<=hb[2]; col++)
            { for (var line=hb[1]; line<=hb[3]; line++)
                {
                if (typeof this.cache[[this.find_normal_col(col, 'getHexBoundnoincache'),line]]==='undefined')
                    {
                    if (col<nic_col_1) {nic_col_1 = col;}
                    if (col>nic_col_2) {nic_col_2 = col;}
                    if (line<nic_line_1) {nic_line_1 = line;}
                    if (line>nic_line_2) {nic_line_2 = line;}                    
                    }
                }
            }
        if (nic_col_1!==9999) { return [nic_col_1, nic_line_1,nic_col_2, nic_line_2];}
        return false;
        };
    /*--------------------------------------------------------------------------
        Other Modules Relationship
    --------------------------------------------------------------------------*/        
    this.add_select_by_id = function (tok_type, obj_id) {
        // obj_id = obj_id[0];
        const param_name = tok_type + '_id';
        LEAFLET_MAP.eachLayer(function(layer){
            if (layer[param_name]!=undefined) {
                if (obj_id == layer[param_name]) {
                    $(layer.getElement()).addClass('s'); 
                    return false;
                }                
            }
        });
        return;        
    }

    // Fonction pourrie : uid() est gourmand, ne pas utiliser.
    this.add_select_by_obj = function(object) {
        LEAFLET_MAP.eachLayer(function(layer){
            if (layer.pic_id == object.uid()) {
                $(layer.getElement()).addClass('s'); 
                return false; 
            }
        });
        return;
    }

    this.clear_select = function(unit_id=null) {
        $('.s').removeClass('s');
        if (this.path.length>0) { this.clear_path(); }
        return false;
    };

    /*--------------------------------------------------------------------------
        Unit Path deserves his own chapter :
    --------------------------------------------------------------------------*/

    this.set_path = function(path) {
    	// allowLogs();
        // Called By selection & order iface
        logMessage('set_path in mapview :');
        logMessage(JSON.stringify(path));
        const old_path = JSON.stringify(this.path);
        this.path = path;
        if (JSON.stringify(this.path)!==old_path) {
            Bo.set_path(this.path);
            this.refresh_graphics();
        }
        // disableLogs();
    };

    this.clear_path = function() {
        this.path = [];
        Bo.del_path();
        this.refresh_graphics(); 
    };

    /*--------------------------------------------------------------------------
        And the big motherfucking code of pathfinding :
    --------------------------------------------------------------------------*/         
    this.find_road = function(coord) { return  Bo.road_at(coord); };
    this.find_terrain = function(coord) { return Rules.getLandInfo(Bo.terrain_at(coord)); };

    this.move_cost = function(from_coord, next_coord, is_boat)
        {
        // Passer d'un terrain TERRE a MER implique un cout unique, mais élevé
        // Rappel : 1 TIC represente 1h de vie des pions.
        // 
        // Doit etre "Parent" de 'ordrexxx.js'->'move_tic'
        // 
        // if (typeof from_coord==='undefined' || typeof next_coord==='undefined') { return; }
        if ( typeof next_coord==='undefined') { return; }

        let cost = 0;                
        // const from_terrain = this.find_terrain(from_coord);
        const next_terrain = this.find_terrain(next_coord);

        // let from_road = this.find_road(from_coord);
        let next_road = this.find_road(next_coord);

        // De base, le terrain suivant fixe le cout : (mais...)
        cost = next_terrain.move * 1 ;

        // si on est pas un bateau, l'eau coute cher sauf si un pont :
        if (!is_boat && next_terrain.water===true) {
            if (next_road>0) { cost = Rules.getLandInfo(7).move * 1;  }
            // if (next_road>0) { cost = cost - 1;  }
            else { 
            	return Rules.boat_time * 1; 
        	}
        }

        // logMessage('Move Cost = ' + cost);

        return cost;
        };

    this.move_cost_old = function(from_coord, next_coord, is_boat)
        {
        // Passer d'un terrain TERRE a MER implique un cout unique, mais élevé
        // Rappel : 1 TIC represente 1h de vie des pions.
        // 
        // Doit etre "Parent" de 'ordrexxx.js'->'move_tic'
        // 
        if (typeof from_coord==='undefined' || typeof next_coord==='undefined') { return; }

        let cost = 0;                
        const from_terrain = this.find_terrain(from_coord);
        const next_terrain = this.find_terrain(next_coord);

        let from_road = this.find_road(from_coord);
        // if (from_road==null) {from_road = 0;}        
        let next_road = this.find_road(next_coord);
        // if (next_road==null) {next_road = 0;}

        // De base, le terrain suivant fixe le cout : (mais...)
        cost = next_terrain.move;

        // si on est pas un bateau, l'eau coute cher sauf si un pont :
        if (!is_boat && next_terrain.water===true) {
            if (next_road>0) { cost = Rules.getLandInfo(7).move;  }
            else { return Rules.boat_time; }
        }
    
        // if (next_terrain.water==true && next_road*1>0) { cost = Rules.terrains[7].move; }
        // Si on est un bateau d'origine, la terre est interdite 
        //   if (unit.rule().boat===true && next_terrain.water===false)  {return 999999;}
        // En cas de pont (route) on ignore cette histoire d'eau SI ON est sur terre.:
        // (une plaine)
        //       if (next_road>0 && from_terrain.water===false) { cost = Rules.terrains[7].move; }
        //   if (from_terrain.water===false && next_terrain.water===true && next_road<=0) { return Rules.boat_time; }
        // EAu -> eau == Dur si on est sur un pont (tant pis)
        //        if (from_terrain.water===true && next_terrain.water===true && from_road>0) { return Rules.boat_time; }
        

        // Prise en compte de l'usure pour la qualite de la route :
        cost = Math.ceil(cost / (1+(next_road/1000)))
        return cost;
        };

    this.heuristic = function(a, b)
        {
        // Cloning to freely manipulate : 
        var start = new H.coord([a.col, a.row], 'oddr');
        var goal = new H.coord([b.col, b.row], 'oddr');
        var cross = false;   
        var col_num = Math.abs(goal.col - start.col);
        if (col_num>this.max_col/2) {cross=true;}
        if (cross) {
            if (start.col<goal.col) {start.col=start.col+this.max_col;}
            else {goal.col=goal.col+this.max_col;}
        }
        let heuristic =  Math.abs(start.col - goal.col ) + Math.abs(start.row - goal.row);
        return heuristic;
        };
    this.neighbours = function(coord) { return Bo.neighbours_coords_cache[coord.stg()]; };


	this.find_path = function (start, end) {
	    // Vérification du terrain cible
	    const end_terrain = this.find_terrain(end);
	    // logMessage('Start: ' + start.stg());
	    // logMessage('Target: ' + end.stg());

	    let free_water = false;
	    if (end_terrain?.water === true) {
	        logMessage('On vise de l\'eau');
	        free_water = true;
	    }

	    // Gestion du cache
	    const cacheKey = `Pth-${start.stg()}-${end.stg()}`;
	    if (this.cache[cacheKey] !== undefined) {
	    	// logMessage('Gave up ! No cache ?');
	        return this.cache[cacheKey];
	    }

	    // Initialisation de la recherche
	    const frontier = new PriorityQueue();
	    frontier.put(start, 0);

	    const came_from = {};
	    const cost_so_far = {};
	    came_from[start.stg()] = null;
	    cost_so_far[start.stg()] = 0;

	    while (!frontier.is_empty()) {
	        const current = frontier.get();

	        // Sécurité si la position actuelle est invalide
	        if (!current) {
	        	logMessage('Gave up ! Why ?');
	            return false;
	        }
	        if (current.stg() === end.stg()) {
	            break;
	        }

	        // Récupération des voisins
	        const neighbours = this.neighbours(current);
	        for (const [key, next] of Object.entries(neighbours)) {
	            // Calcul du coût de mouvement
	            let move_cost = this.move_cost(current, next);
	            if (move_cost === undefined) {
	                move_cost = 50; // Valeur par défaut
	            }

	            const new_cost = cost_so_far[current.stg()] + move_cost;

	            // Vérification si le chemin est meilleur ou non exploré
	            const is_better =
	                cost_so_far[next.stg()] === undefined || new_cost < cost_so_far[next.stg()];
	            if (is_better) {
	                cost_so_far[next.stg()] = new_cost;
	                const priority = new_cost + this.heuristic(end, next);
	                frontier.put(next, priority);
	                came_from[next.stg()] = current;
	            }
	        }
	    }

	    // Reconstruction du chemin
	    let current = end;
	    const path = [];
	    while (current.stg() !== start.stg()) {
	        path.push(current);
	        current = came_from[current.stg()];
	    }

	    // Ajout de la position de départ et inversion du chemin
	    path.push(start);
	    path.reverse();

	    // Mise en cache du chemin calculé
	    this.cache[cacheKey] = path;

	    logMessage('Path found.');
	    // logMessage(path);

	    return path;
	};


    this.find_path_OLD = function(start, end) {
        
        // si on cible de l'eau, on a une appetence pour.
        
        const end_terrain = this.find_terrain(end);
        logMessage('Target :'+end.stg());
        // logMessage(end_terrain)
        // logMessage(end_terrain)

        let free_water = false;
        if (end_terrain.water === true) {
            logMessage('on vise de l eau');
            free_water=true;
        }

        // Ce cache est valide fonctionnel :
        var cache_txt = 'Pth-'+start.stg()+'-'+end.stg();
        if (typeof this.cache[cache_txt]!=='undefined') { return this.cache[cache_txt]; }

        // Search part ---------------------------------------------------------
        var frontier = new PriorityQueue();

        frontier.put(start, 0);
        
        var came_from = {};
        var cost_so_far = {};
        came_from[start.stg()] = null;
        cost_so_far[start.stg()] = 0;

        while (!frontier.is_empty()) {

            var current = frontier.get();

            // Possible if player put mouse out of map :
            if (current===false) { return false; }
            if (current.stg()===end.stg()) { break; }
            
            // Grab Neighbours coords in Board Cache :
            const neighbours = this.neighbours(current);

            for (var key in neighbours) {

                var next = neighbours[key];
                
                // Attention, move_cost doit être relu et doit TOUJOURS sortir
                // un chiffre de type entier !
                let move_cost = this.move_cost(current, next); //, free_water);
                if (move_cost==undefined) {move_cost = 50;}

                var new_cost = cost_so_far[current.stg()] + move_cost;
                
                // Si c'est indefini ou meilleur on colle un priorité.
                var is_better = false;
                if (typeof cost_so_far[next.stg()]==='undefined') {is_better=true;}
                if (new_cost<cost_so_far[next.stg()]) {is_better=true;}
                if (is_better) {
                    cost_so_far[next.stg()] = new_cost;
                    let priority = new_cost + this.heuristic(end, next);
                    frontier.put(next, priority);
                    came_from[next.stg()]=current;                        
                }

                // if (next.stg()==end.stg()) {  logMessage('Arrivee'); break;  }
                }

            }

        // Reconstruction part -------------------------------------------------    
        var current = end;
        var path = [];
        while (current.stg()!==start.stg())
            {
            path.push(current);
            current = came_from[current.stg()];
            }
        // Optional    
        path.push(start);
        path.reverse();
        // --
        // logMessage('cached !' + cache_txt)
        this.cache[cache_txt] = path;
        
        // -> A voir : On peut aussi mettre en cache toutes les étapes intermédiaires...

        logMessage('Path found.');
        logMessage(path);
        
        return path;



        };        
    /*--------------------------------------------------------------------------
        Interactions & Consequences 
    --------------------------------------------------------------------------*/
    this.set_interacts = function()
        {
        // Permet de definir la ou les interactions ----------------------------
        // Deplacement de la carte :
        LEAFLET_MAP.on('move', function(e) 
            {
            Mm.draw_focus();
            return false;
            });
        // fin de deplacement :
        LEAFLET_MAP.on('moveend', function(e) 
            {
            that.refresh('moveend');
            Mm.draw_focus();
            return false;
            });
        // fin de zoom :
        LEAFLET_MAP.on('zoomend', function(e) 
            {
            that.zoomend();
            Mm.draw_focus();
          //  logMessage(LEAFLET_MAP.getZoom());
          return false;
            });            
        LEAFLET_MAP.on('mousemove', function(e)
            {
            const mousemove_chronographe = new Chronographe();

            mousemove_chronographe.start('MV set_interacts');

//            e.originalEvent.stopPropagation();
            //e.originalEvent.preventDefault();

            // Capture des coordonnées de la souris en live :    
            const hex_c = that.leaflet_to_oddr(e.latlng);

            // logMessage(e.latlng);
            

            hex_c.col = that.find_normal_col(hex_c.col, 'mousemove');

            that.mouse_coord = hex_c;
            $('#debug').text('('+hex_c.stg()+')');

            // Au cas ou, on fait passer au module de selection qui s'en demerde :
            mousemove_chronographe.step('MV set_interacts');
            if (Se!=null) {Se.setTarget(hex_c);}
            
            mousemove_chronographe.stop('MV set_interacts');
            mousemove_chronographe.log(100);

            return false;

            });
        };
        // remarque

    this.zoomend = function() {
        // Corrige l'affichage des tooltips selon le zoom
        const zoom = LEAFLET_MAP.getZoom();
        if (zoom < 3.2 && (this.lastzoom ==0 || this.lastzoom>=3.2) ) {
            LEAFLET_MAP.eachLayer(function(l) {
                if (l.getTooltip()) {
                    let tooltip = l.getTooltip();
                    l.unbindTooltip().bindTooltip(tooltip, {
                        permanent: false,
                        offset : L.point(0,35)
                    })
                }
            })
        } else if (zoom >= 3.2  && (this.lastzoom ==0 || this.lastzoom<3.2) ) {
            LEAFLET_MAP.eachLayer(function(l) {
                if (l.getTooltip()) {
                    let tooltip = l.getTooltip();
                    l.unbindTooltip().bindTooltip(tooltip, {
                        permanent: true,
                        offset : L.point(0,45)
                    })
                }
            });
        }
        this.lastzoom = zoom;
    };

  /*  
    this.zoomend = function()
        {
        // Lors d'un zoom/dezoom, force la carte centrée verticalement 
        // Si elle peut être affichée entièrement.
        if (LEAFLET_MAP.getBounds().getNorth()>(this.y_max+H_H))
            { LEAFLET_MAP.setMaxBounds([[this.y_max/2, -Infinity], [this.y_max/2, +Infinity]]); }
        else 
            { LEAFLET_MAP.setMaxBounds([[this.y_min-H_H, -Infinity], [this.y_max+H_H, Infinity]]); }
        // Enfin, on reclame un deplacement de carte :
        LEAFLET_MAP.panTo(LEAFLET_MAP.getBounds().getCenter(),{animate:false});
        return false;
        };
*/        
    this.goto = function (lat_lng_array)
        {
        // Sers a Minimap     
        LEAFLET_MAP.panTo(lat_lng_array,{animate:false});
        Mm.draw_focus();
        return false;
        };
    C.socket.on('RST', function(data) 
        {
        that.cache =  [];
        Se.serverRefresh();
        that.refresh();
        return false;
        });

    $(document).on('contextmenu', function(e) {
        return false;
    }, false);

    $(document).on('click', function(e)
        {
        // Si on shift clic n'importe ou, on considere un link de coord :    
        if (that.shifted)  { T.add_input(' ['+that.mouse_coord.stg()+'] '); }
        else {
        	Se.setStep();
        }
        return false;
        });

    $(document).on('keyup keydown', function(e)
        {
        // Detection du  shift !    
        that.shifted = e.shiftKey;
        // Si on return FALSE on fait l'equivalent d'un PREVENTDEFAULT !!!! O_o
        // return false;
        } );    
};