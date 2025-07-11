/*==============================================================================
    Board
        Les objects contiennent leur carac intrinseque, plus de quoi aider
        leafconst a afficher le bouzin

    Pour info :
        Leafconst utilise '.addLayer' pour se faire ajouter des chose a dessiner
        les choses sont definies comme 
            L.'chose' (image, dessin, etc....)
    
    Donc chaque element doit avoir un 
        "my_layers" qui defini le ou les layers utiles 

    puis c'est ajouté à la carte visible via "addLayer" mais par mappview.
    

==============================================================================*/
"use strict";

const calc_cache = {};

/*------------------------------------------------------------------------------
    Le module :
 -----------------------------------------------------------------------------*/
function Board_Iface(max_col, max_row) {

    // Fonction utile avant la construction ------------------------------------
	this.find_normal_col = function (colonne, caller = 'myself', turn = 0) {
	    turn += 1;

	    // Ramène la colonne dans les limites [0, max_col - 1]
	    colonne = ((colonne % this.max_col) + this.max_col) % this.max_col;

	    return colonne;
	};
    this.neighbours_coords = function(coord) {
        const neighb_coords = H.all_neighbours(coord);
        // Hexalib gives neighbours in the wrong clockwise
        neighb_coords.reverse();
        // Hexalib may give out of map coords :
        for (const nk in neighb_coords) {
            neighb_coords[nk].col = this.find_normal_col(neighb_coords[nk].col, 'neighb_PreCalc.');
            if (neighb_coords[nk].row>=this.max_line || neighb_coords[nk].row<0) { delete neighb_coords[nk]; }            
        }
        return neighb_coords;
    };

    // Liste des objets existants --------------------------------------------------
    this.constructors = {
      Unit: Unit,
      Stack: Stack,
      Ground: Ground,
      City: City,
      Fog: Fog,
      Path: Path,
    };    

    this.max_col = max_col;
    this.max_row = max_row;
    this.objects_by_uid = {};
    this.objects_by_coords = {};
    this.randval = {};
    this.path_id = 0;

    this.neighbours_coords_cache = {};   // Une reference de coordonnees

    // Creer une liste des voisin en mode coord => [6xcoords] ?
    // Et creer une randomvalue par coord... Donc seeded ! :)
	for (let bcol = 0; bcol < max_col; bcol++) {
	    for (let brow = 0; brow < max_row; brow++) {
	        // Générer la coordonnée actuelle
	        const bcoord = H.coord([bcol, brow], 'Oddr');
	        const bcoordKey = bcoord.stg();

	        // Initialiser les objets associés à cette coordonnée
	        this.objects_by_coords[bcoordKey] = {};

	        // Stocker les voisins dans un cache
	        this.neighbours_coords_cache[bcoordKey] = this.neighbours_coords(bcoord);

	        // Générer une valeur aléatoire entre 0 et 9
	        let randomValue = Math.floor(Math.random() * 10);
	        
	        // Si la valeur est > 4, la ramener à 0
	        if (randomValue > 4) { randomValue = 0; }

	        // Stocker la valeur randomisée pour cette coordonnée
	        this.randval[bcoordKey] = randomValue;
	    }
	}

	this.digest_data = function(server_data) {
	    const first_unit = new Set();
	    const class_name_map = {
	        'U': 'Stack',
	        'T': 'Ground',
	        'F': 'Fog',
	        'C': 'City'
	    };
	    const recent_uids_sets = new Map();

	    // Parcours des données du serveur
	    while (server_data.length > 0) {
	        const data = server_data.pop();
	        const col = this.find_normal_col(+data.c, 'digesterdata');
	        const row = +data.r;
	        const obj_coord = H.coord([col, row], 'Oddr');
	        const coord_str = obj_coord.stg();
	        const objects = data.o;

	        let recent_uids = recent_uids_sets.get(coord_str) || new Set();
	        
	        // console.log(objects);

	        while (objects.length > 0) {
	            const obj = objects.pop();
	            const class_name = class_name_map[obj.t];

	            // if (class_name!=='Ground') { console.log(class_name); }
	            

	            if (!class_name) continue;

	            let uid_result;
	            if (class_name === 'Stack' && first_unit.has(coord_str)) {
	                // Fusionner les objets de type Stack
	                const new_object = new this.constructors[class_name](obj_coord, obj, this.randval[coord_str]);
	                uid_result = this.merge_stack(new_object);
	            } else {
	                // Créer un nouvel objet
	                const new_object = new this.constructors[class_name](obj_coord, obj, this.randval[coord_str]);
	                uid_result = this.push_object(new_object);
	                if (class_name === 'Stack') first_unit.add(coord_str);
	            }

	            recent_uids.add(uid_result);
	        }
	        recent_uids_sets.set(coord_str, recent_uids);

	        // Gestion des objets actuels aux coordonnées (col, row)
	        const curr_objects = this.objects_at(col, row);
	        if (curr_objects) {
	            for (const co_key in curr_objects) {
	                if (!recent_uids.has(curr_objects[co_key].uid())) {
	                    this.object_remove(curr_objects[co_key]);
	                }
	            }

	            // Supprimer si l'objet restant est "Fog"
	            if (Object.keys(curr_objects).length === 1 && curr_objects['Fog']) {
	                this.object_remove(curr_objects['Fog']);
	            }
	        }
	    }
	};

	this.digest_data_old = function(server_data) {
	    const first_unit = {};

	    // Parcours des données du serveur
	    while (server_data.length > 0) {
	        const data = server_data.pop();
	        const col = this.find_normal_col(+data.c, 'digesterdata');
	        const row = +data.r;
	        const obj_coord = H.coord([col, row], 'Oddr');
	        const objects = data.o;
	        const recent_uids = [];

	        while (objects.length > 0) {
	            const obj = objects.pop();
	            let class_name = {
	                'U': 'Stack',
	                'T': 'Ground',
	                'F': 'Fog',
	                'C': 'City'
	            }[obj.t] || '';

	            if (!class_name) continue;

	            let uid_result = '';
	            if (class_name === 'Stack' && first_unit[obj_coord.stg()] === 'done') {
	                // Fusionner les objets de type Stack
	                const new_object = new this.constructors[class_name](obj_coord, obj, this.randval[obj_coord.stg()]);
	                uid_result = this.merge_stack(new_object);
	            } else {
	                // Créer un nouvel objet
	                const new_object = new this.constructors[class_name](obj_coord, obj, this.randval[obj_coord.stg()]);
	                uid_result = this.push_object(new_object);
	                if (class_name === 'Stack') first_unit[obj_coord.stg()] = 'done';
	            }

	            recent_uids.push(uid_result);
	        }

	        // Gestion des objets actuels aux coordonnées (col, row)
	        let curr_objects = this.objects_at(col, row);
	        if (curr_objects) {
	            for (const co_key in curr_objects) {
	                if (!recent_uids.includes(curr_objects[co_key].uid())) {
	                    this.object_remove(curr_objects[co_key]);
	                }
	            }

	            // Supprimer si l'objet restant est "Fog"
	            if (Object.keys(curr_objects).length === 1 && curr_objects['Fog']) {
	                this.object_remove(curr_objects['Fog']);
	            }
	        }
	    }
	};

    /* -------------------------------------------------------------------------
        Objects Array maniulations & fun :
    ------------------------------------------------------------------------- */

    this.that_at = function(what, coord) {
        const objects = this.objects_at(coord.col, coord.row);
        if (objects[what]!=null) { return objects[what]; }
        return;
    }

    this.road_at = function(coord) {
        const objects = this.objects_at(coord.col, coord.row);
        if (objects['Ground']!=null) { if (objects['Ground'].ro*1>0) {
            return objects['Ground'].ro*1;} }
        return false;
    }    

    this.terrain_at = function(coord) {
        let terrain_id = 0;
        const objects = this.objects_at(coord.col, coord.row);
        if (objects['Ground']!=null) {terrain_id = objects['Ground'].id;}
        return terrain_id;
    };

    this.objects_at = function(col, row) {
        col = this.find_normal_col(col, 'objects_at');
        if (this.objects_by_coords[col+','+row]==null) {return {};}
        return  this.objects_by_coords[col+','+row]; 
    };

    /* -------------------------------------------------------------------------
        How to ordon object in my head :
    ------------------------------------------------------------------------- */
    this.merge_stack = function (game_obj) {

        // Get current stack
        const curr_stack = this.objects_by_coords[game_obj.coord.stg()]['Stack'];

        // Effacer ce qui est lie a notre actuel :
        this.coord_del(curr_stack);
        this.uid_del(curr_stack);

        // Joindre au stack existant :
        curr_stack.merge(game_obj); 

        // Et faire mettre à jour tout ce qui est lié au UID.
        this.coord_add(curr_stack);
        this.uid_add(curr_stack);

        return curr_stack.uid();
    }

    this.push_object = function(game_obj) {
        if (this.objects_by_uid[game_obj.uid()]==null) {
            this.uid_add(game_obj);
            this.coord_add(game_obj);
        } else {
            if (game_obj.coord.stg()!=this.objects_by_uid[game_obj.uid()].coord.stg()) {
                this.coord_del(this.objects_by_uid[game_obj.uid()]);
                this.coord_add(game_obj);
            }
        }
        return game_obj.uid();
    };

    this.uid_add = function(object) { this.objects_by_uid[object.uid()] = object; };
    this.coord_add = function(object) {
        if (this.objects_by_coords[object.coord.stg()]==undefined) {
            this.objects_by_coords[object.coord.stg()] = {};
        }
        this.objects_by_coords[object.coord.stg()][object.constructor.name] = object; 
    };

    this.object_remove = function(object) {
        if (object==undefined) {return false;}
        // Delete him !
        this.coord_del(object);
        this.uid_del(object);
    }
    this.coord_del = function(object) { delete(this.objects_by_coords[object.coord.stg()][object.constructor.name]); }
    this.uid_del = function(object) { delete(this.objects_by_uid[object.uid()]); }

    /* -------------------------------------------------------------------------
        Path
    ------------------------------------------------------------------------- */

    this.del_path = function() {
        // On detruit ces objets path ...
        for(const uid in this.objects_by_uid) {
            if (uid.split('-')[0]=='P') {
                this.object_remove(this.objects_by_uid[uid]);
            }
        }
        this.path_id = 0;
    };

	this.set_path = function (path = []) {
		// console.log('Set path in Board iface :');
		// console.log(path);

	    // Vérifier si le chemin est vide
	    if (path.length <= 0) return;

	    // Vérification des éléments du chemin pour s'assurer qu'ils possèdent la méthode `stg`
	    if (typeof path[0].stg !== 'function' || typeof path[path.length - 1].stg !== 'function') {
	        console.error('Path elements must have a stg() method.');
	        return;
	    }

	    // Créer l'identifiant unique du chemin
	    const puid = path[0].stg() + '-' + path[path.length - 1].stg();

	    // Supprimer le chemin existant
	    this.del_path();

	    let prev_coord = '';

	    // Parcourir et traiter chaque point du chemin
	    for (const p_key in path) {
	        const point = path[p_key];

	        // Vérification supplémentaire : s'assurer que chaque point a les propriétés attendues
	        if (!point.col || !point.row || typeof point.col !== 'number' || typeof point.row !== 'number') {
	            console.error(`Invalid path element at index ${p_key}:`, point);
	            continue;
	        }

	        // Créer les coordonnées à partir de `col` et `row`
	        const obj_coord = H.coord([point.col, point.row], 'Oddr');

	        // Ignorer les coordonnées déjà traitées
	        if (obj_coord.stg() === prev_coord) continue;

	        // Créer un nouvel objet de chemin et l'ajouter
	        const new_object = new this.constructors['Path'](obj_coord, {
	            id: this.path_id,
	            puid: puid,
	        });

	        this.push_object(new_object);

	        // Mettre à jour la coordonnée précédente
	        prev_coord = new_object.coord.stg();

	        // Incrémenter l'identifiant de chemin
	        this.path_id += 1;
	    }
	};

	this.add_path = function (path = []) {
	    // Si le chemin est vide, on quitte la fonction
	    if (path.length <= 0) return;

	    const puid = path[0].stg() + '-' + path[path.length - 1].stg();

	    // Vérifie si le dernier point du chemin a déjà un 'Path'
	    const lastCoordKey = path[path.length - 1].stg();
	    if (
	        this.objects_by_coords[lastCoordKey] && 
	        this.objects_by_coords[lastCoordKey]['Path'] != null
	    ) {
	        // Chemin inutile, car déjà existant
	        return false;
	    }

	    // Ajout des points du chemin
	    for (const point of path) {
	        // Génère une nouvelle ID pour le chemin
	        this.path_id += 1;

	        // Crée les coordonnées pour l'objet
	        const obj_coord = H.coord([point.col, point.row], 'Oddr');

	        // Crée un nouvel objet 'Path' avec les coordonnées et un ID unique
	        const new_object = new this.constructors['Path'](obj_coord, {
	            id: this.path_id,
	            puid: puid
	        });

	        // Ajoute l'objet à la liste des objets
	        this.push_object(new_object);
	    }
	};

    /* -------------------------------------------------------------------------
        Calculation and other methods ...
    ------------------------------------------------------------------------- */

    this.size  = function()  {
        return Object.keys(this.objects_by_uid).length;
    };

    this.neighbour_print = function(object, what) {
        const neighbours = this.neighbours(object);
        let print = '';
        for (const side in neighbours) {
            if (neighbours[side][what]==null) { continue;  }
            print = print +'.'+ neighbours[side][what].print; 
        }
        return print;
    } 
   
    this.neighbours = function (object) {
        // Version rapide
        const coords_list = this.neighbours_coords_cache[object.coord.stg()];

        // Version lente
        //const coords_list = this.neighbours_coords(object.coord);

        // Chaque cote un tableau d'objets....
        const neighbours = [];
        for (const nc_key in coords_list) { neighbours.push(this.objects_at(coords_list[nc_key].col, coords_list[nc_key].row) ); }
        return neighbours;
    };

    this.is_equal = function(elem1) {
        // Utilisation baroque non ? 
        // https://www.becomebetterprogrammer.com/javascript-array-filter/#Defining_a_Callback_Function_without_Optional_Paramaters
        return this == elem1;
    };

    this.is_inequal = function(elem1) {
        // Utilisation baroque non ? 
        // https://www.becomebetterprogrammer.com/javascript-array-filter/#Defining_a_Callback_Function_without_Optional_Paramaters
        return this != elem1;
    };

    this.is_sup = function(elem1) {
        // Utilisation baroque non ? 
        // https://www.becomebetterprogrammer.com/javascript-array-filter/#Defining_a_Callback_Function_without_Optional_Paramaters
        // this = La valeur dispo dasn l'objet teste
        // elem1 = la valeur issue du tableau que l'on teste contre.
        return elem1 < this;
    };    

    this.find_null_neighbours_directions = function(object) {
        // Neighbours est un arrai issu de "this.neighbours"
        const directions = [];
        const neighbours = this.neighbours(object);
        for (const side in neighbours) {
            if (Object.keys(neighbours[side]).length<1) {
                let d = (side*1-1);
                if (d<0){d=6+d;}
                directions.push(d);
            }
        }
        return directions;
    };

	this.get_inverse_directions = function (originDirection) {
	    // Définir l'inverse principal
	    const inverse = (originDirection + 3) % 6;

	    // Définir les inverses secondaires (par symétrie)
	    const secondaries = [(inverse + 1) % 6, (inverse - 1 + 6) % 6];
	    const tertiaries = [(secondaries[0] + 2) % 6, (secondaries[1] - 2 + 6) % 6];

	    // Retourner les directions dans l'ordre
	    return [inverse, ...secondaries, ...tertiaries];
	};


	this.find_neighbours_directions = function (object, what, param, values, func = this.is_equal) {
	    const directions = [];
	    const neighbours = this.neighbours(object);

	    for (const side in neighbours) {
	        const neighbor = neighbours[side];

	        // Ignorer les voisins qui n'ont pas la clé "what"
	        if (!neighbor[what]) continue;

	        // Vérifier si une valeur correspond
	        const matches = values.some(value => func.call(neighbor[what][param], value));
	        if (matches) {
	            const direction = (side - 1 + 6) % 6; // Simplification du calcul des directions
	            directions.push(direction);
	        }
	    }
	    return directions;
	};

};

/*------------------------------------------------------------------------------
    Les objets du module :
 -----------------------------------------------------------------------------*/

// GameObject : Ancestor of everything -----------------------------------------
function GameObject(coord, json_data, randval) {
    this.coord = coord;
    this.randval = randval;
    this.to_update = true;
    this.print=''
    // Hydrate me deep

    // console.log('Game Object construct :');

    for (const key_name in json_data) { if (key_name!=='t') {
        this[key_name] = json_data[key_name];
        // this.print = this.print+'.'+json_data[key_name]
        // if (key_name == 'co') { console.log(key_name+' : '+json_data[key_name]); }
    } }

    // Public method -----------------------------------------------------------
    this.set_relat_place = function(coord_stg) {
        if ( this.relat_place!=null) {
            if (coord_stg==this.relat_place) {return;} 
            this.to_update = true;
        }
        this.relat_place = coord_stg;
        this.center_px = H.coord_to_pixel(H.coord(coord_stg.split(','), 'oddr'), H_W, H_H, H_S);            
    };

    // Common to all objects ---------------------------------------------------
    this.tag = function() { return this.uid(); }
    this.tag_my_layers = function(layers) {

/*
        if (layers.length>1) {
            const layer_group = L.layerGroup(layers);
            layer_group.tag = this.tag();
            return layer_group;            
        } else {
*/
            if (layers.length>0) {

                const layer_group = L.layerGroup(layers);
                layer_group.tag = this.tag();                
                return layer_group;            

                // layers[0].tag = this.tag();
                // return layers[0];
            }
//        }
        return [];
    };

    // Default Method - can be overrided ---------------------------------------   
    this.create_layers = function() {
        // Par defaut, c'est un hexa blanc qui pete.
        const hexa = L.polygon([ this.pointy_hex_corner(0), this.pointy_hex_corner(1),
            this.pointy_hex_corner(2), this.pointy_hex_corner(3), 
            this.pointy_hex_corner(4), this.pointy_hex_corner(5)
            ]);
        hexa.setStyle({ color:null, fillColor:'#FFFFFF', fillOpacity:1, weight:0, interactive:false });
        // hexa.tag = this.tag();
        return hexa;
    };

    // Drawing Calculation Methods ---------------------------------------------
    this.color_class = function() { return helper_color_class(this.co); }
    this.background_colored_triangle = function(vario = 0, num = 0, color) {
        const corners = this.image_bounds_square(vario);
        
        let corn_1 = corners[0];
        let corn_2 = [corners[0][0], corners[1][1]];
        let corn_3 = corners[1];            
        
        if (num==1) {
            corn_1 = [corners[0][0], corners[1][1]];
            corn_2 = corners[1];            
            corn_3 = [corners[1][0], corners[0][1]];
        }
        if (num==2) {
            corn_1 = corners[1];
            corn_2 = [corners[1][0], corners[0][1]];          
            corn_3 = corners[0];
        }                
        if (num==3) {
            corn_1 = [corners[1][0], corners[0][1]];          
            corn_2 = corners[0];
            corn_3 = [corners[0][0], corners[1][1]];
        }

        // const corn_4 = [corners[1][0], corners[0][1]];
        
        return L.polygon([corn_1, corn_2, corn_3], {color: color, fillOpacity:1, weight: 1});
        
    }
    this.background_colored_square = function(vario = 0) {
        // console.log('bcs : '+vario+' '+this.uid());
        const corners = this.image_bounds_square(vario);
        // console.log(corners);
        const corn_1 = corners[0];
        const corn_2 = [corners[0][0], corners[1][1]];
        const corn_3 = corners[1];
        const corn_4 = [corners[1][0], corners[0][1]];
        const background = L.polygon([corn_1, corn_2, corn_3, corn_4], {color: this.co, fillOpacity:1, weight: 1});
        // background.tag = this.tag();
        return background;
    };
    this.image_bounds = function() {
        // A rectangle that circumscribes this Hex :
        const x1 = this.center_px[0] - H_W/2;   const x2 = this.center_px[0] + H_W/2;
        const y1 = this.center_px[1] - H_H/2;  const y2 = this.center_px[1] + H_H/2;
        return [[y1, x1], [y2, x2]];
    };
    this.image_bounds_square = function(vario=0) {
        // console.log('ibs : '+vario);
        // A Square inside this Hex :
        const x1 = this.center_px[0]+vario - H_W/6*2;   const x2 = this.center_px[0]+vario + H_W/6*2;
        const y1 = this.center_px[1]+vario - H_W/6*2;  const y2 = this.center_px[1]+vario + H_W/6*2;;
        return [[y1, x1], [y2, x2]];
    };
    this.pointy_hex_corner = function (side, size = H_S) {
        // Draw a side of my Hex (from : www.redblobgames.com )
        let data = null;
        const cache_name = 'phc-'+size+'-'+side
        if (calc_cache[cache_name]==null) {
            // Calcul complique et gourmand - Mais comme j'aime ton processeur, je le met en cache global !
            const angle_deg = 360 - (60 * side); 
            const angle_rad = Math.PI / 180 * angle_deg;
            calc_cache[cache_name] = [size * Math.cos(angle_rad), size * Math.sin(angle_rad)]; 
        }
        data = [(this.center_px[1] + calc_cache[cache_name][0]), (this.center_px[0] + calc_cache[cache_name][1])];
        return data;
    };
    this.side_middle = function(side) {
        // Gives the center spot of this specific side :
        // Now with a cache system. (I'm in love with your CPU)
        let data = null;
        const cache_name = 'sm-'+side
        if (calc_cache[cache_name]==null) {
            const angle_deg = 360 - (60 * side) - 30; 
            const angle_rad = Math.PI / 180 * angle_deg;
            calc_cache[cache_name] = [H_W/2 * Math.cos(angle_rad), H_W/2 * Math.sin(angle_rad)]; 
        }
        data = [(this.center_px[1] + calc_cache[cache_name][0]), (this.center_px[0] + calc_cache[cache_name][1])];
        return data;
    };    
};

function City(obj_coord, json_data, randval) {
    GameObject.call(this, obj_coord, json_data, randval);
    this.uid = function() {
        let tag = 'C-'+this.id;
        if (Se.isSelected(this)) { tag = tag + 's' }
        return tag;
    }    
    const that = this;

    this.unit_counter = function() {
        // Quand on appelle cette fonction, on modifie le look de la ville...

    };

   this.create_layers = function() {
        const layers = [];

        layers.push(this.background_colored_square())
        let css_class = 'C '+this.color_class();

        const picture = L.imageOverlay('./u/c0.png', this.image_bounds_square(), 
            { className:css_class, interactive:true, renderer:L.canvas()});
        // picture.bindPopup('POUET');
        // picture.bindTooltip('POUET', {permanent:true});
        // console.log(this);

        picture.bindTooltip(this.na, {
            permanent: false,
            direction: 'center',    // vire la petite pointe de direction
            offset : L.point(0,45),     // !! Overrided on zoom move by Mapview.zoomend
            className: 'cName'
        });

        picture.city_id = this.id;
        layers.push(picture)

        // const label = L.marker(this.image_bounds_square()).bindPopup('PROUT').openPopup();
        // layers.push(label)

        L.featureGroup(layers).on('click', this.click_me);

        return this.tag_my_layers(layers);
    };    

    this.click_me = function() {
        if (Se.currentPreparation()) {return false;}
        // Se.select_city(that);
        Se.select([that]);
        // Mv.add_select_by_id('city', that.id);
        
    };        
};
function Stack(coord, json_data, randval) {
    GameObject.call(this, coord, json_data, randval);

    this.units = [];
    this.units.push(new Unit(coord, json_data));

    this.uid = function() {
        let tag = '';
        for (const uk in this.units) { 
            tag =tag+this.units[uk].uid()+'.'; 
        }
        return tag;
    };
    const that = this;    

    this.merge = function(stack) {
        const curr_uid = this.uid();
        for (const suk in stack.units) {
            if (!curr_uid.includes(stack.units[suk].uid()))
                {this.units.push(stack.units[suk]); }
        }
    };

    this.create_layers = function(no_vario = false) {
        let vario = 0;
        const layers = [];
        for (const uk in this.units) { 
            if (this.units[uk].relat_place==undefined) { this.units[uk].set_relat_place(this.relat_place); }
            // La classe doit dépendre des couleurs du joueur.
            let css_class = 'U '+this.units[uk].color_class();
            if (Se.isSelected(this.units[uk])) { css_class = css_class + ' s' }
            if (no_vario) {vario = 0} else {vario=uk;}
            const picture = L.imageOverlay('./u/u'+this.units[uk].ty+'.png', this.units[uk].image_bounds_square(parseInt(vario)), 
                { className:css_class, 
                    idName:'test',
                    interactive:true, renderer:L.canvas()});
            picture.unit_id = this.units[uk].id;
            layers.push(picture);
        }
        // Attention, ici "click_me" est attaché au layer group. 
        // Donc, le "this" de ce "click_me" n'est plus Stack, mais layers !
        L.featureGroup(layers).on('click', this.click_me);
        return this.tag_my_layers(layers);
    };   

    this.click_me = function() {
        if (Se.currentPreparation()) {return false;}
        //Se.select_units(that.units); 
        Se.select(that.units);
        // for (const uk in that.units) { Mv.add_select_by_id('unit', that.units[uk].id) };
    };
}

function Unit(coord, json_data, randval) {
    GameObject.call(this, coord, json_data, randval);

    // let Se = new Selection();

    this.uid = function() {
        let tag = 'U-'+this.id;

        if (Se.isSelected(this)) { tag = tag + 's' }
        	
        return tag;
    }
    const that = this;
 
};

function check_url(url1, url2) {
    return fetch(url1, { method: 'HEAD' })
        .then(response => (response.ok ? url1 : url2)) // Retourne url1 si elle existe, sinon url2
        .catch(() => url2); // En cas d'erreur, retourne url2
}

function Ground(coord, json_data, randval) {
    GameObject.call(this, coord, json_data, randval);
    this.shape_mem = '';

    this.uid = function() { 
        // Le UID contient une representation des voisins du meme type
        // Pour pouvoir penser a refresh si le voisinage change
        const nb_print = Bo.neighbour_print(this, 'Ground');
        let owner = this.ow;
        if (owner === undefined) {owner = 'N';}
        return ('G-'+this.coord.stg()+'-'+owner+'-'+this.ro+'-'+nb_print);
    }

this.create_layers = function() {
    const layers = [];
    const imageBounds = this.image_bounds(); // compute once if bounds are static

    // Background layer
    let num = (this.ro > 0 || this.id === 13) ? 0 : this.randval;
    const url = Rules.getLandUrl(this.id, num);
    layers.push(L.imageOverlay(url, imageBounds, { className: 'T', interactive: true, renderer: L.canvas() }));

	// River logic
	if (this.id === 13) {
	    // Trouver les directions des rivières
	    
	    let riverDirections = Bo.find_neighbours_directions(this, 'Ground', 'id', [13,5]);

	    if (riverDirections.length > 0) {

	        // 1st Method : A layer by direction, covering all possibilities :
	    	// > Less Assets to prepare !
	        riverDirections.forEach(direction => {
	            layers.push(L.imageOverlay(`./h/r${direction}.png`, imageBounds, {
	            
	                className: 'T',
	                interactive: true,
	                renderer: L.canvas()
	            }));
	        });

	        //2nd Method : 1 layer for all, but you have to DRAW all posibilities
	    	// > Faster to render !
	    	/*
		    const implodedDirections = riverDirections.sort((a, b) => a - b).join('');
			console.log('Needed : r'+implodedDirections+'.png');
	        layers.push(L.imageOverlay(`./h/r${implodedDirections}.png`, imageBounds, {	
	        	className: 'T',
	            interactive: true,
	            renderer: L.canvas()
			}));
			*/

	    }

	    // Si la rivière a peu de voisins, vérifier la source montagneuse
	    if (riverDirections.length <= 1) {
	        const possibleSources = Bo.find_neighbours_directions(this, 'Ground', 'id', [3, 4, 14, 15, 16, 17, 18, 19]);

	        if (possibleSources.length > 0) {
	            // Calculer les directions inverses basées sur la première direction de rivière
	            const inverseDirections = Bo.get_inverse_directions(riverDirections[0]);

	            // Trouver la meilleure source montagneuse selon l'ordre des inverses
	            for (const inverseDirection of inverseDirections) {
	                if (possibleSources.includes(inverseDirection)) {
	                    layers.push(L.imageOverlay(`./h/s${inverseDirection}.png`, imageBounds, {
	                        className: 'T',
	                        interactive: true,
	                        renderer: L.canvas()
	                    }));
	                    break; // On s'arrête dès qu'une source valide est trouvée
	                }
	            }
	        }
	    }
	}

    // Road logic
    if (this.ro > 0) {
        layers.push(L.imageOverlay('./h/p6.png', imageBounds, { className: 'T', interactive: true, renderer: L.canvas() }));
        const roadDirections = Bo.find_neighbours_directions(this, 'Ground', 'ro', [0], Bo.is_inequal);
        roadDirections.forEach(direction => {
            layers.push(L.imageOverlay(`./h/p${direction}.png`, imageBounds, { className: 'T', interactive: true, renderer: L.canvas() }));
        });
    }

    // Ownership boundary
    if (this.ow != null) {
        const directions = Bo.find_neighbours_directions(this, 'Ground', 'ow', [this.ow]);
        const segments = [];
        for (let i = 0; i < 6; i++) {
            if (!directions.includes(i)) {
                let c0 = i, c1 = (i + 1) % 6; // Use modulo for circular indexing
                segments.push([this.pointy_hex_corner(c0, H_S - 0.2), this.pointy_hex_corner(c1, H_S - 0.2)]);
            }
        }
        if (segments.length > 0) {
            let colorString = this.co;
            const colors = colorString.startsWith('#') ? ['Ba', 'Ba'] : colorString.split(' ');
            layers.push(L.polyline(segments, { color: Rules.colors[colors[0]], opacity: 1, weight: 6, interactive: false }));
            layers.push(L.polyline(segments, { color: Rules.colors[colors[1]], opacity: 1, weight: 3, interactive: false }));
        }
    }

    // Null neighbors for edges
    const nullDirs = Bo.find_null_neighbours_directions(this);
    nullDirs.forEach(direction => {
        layers.push(L.imageOverlay(`./h/f${direction}.png`, imageBounds, { className: 'F', interactive: false, renderer: L.canvas() }));
    });

    return this.tag_my_layers(layers);
};

    this.create_layers_old = function() {
        
        const layers = [];

        // Les couches d'un terrain c'est :
        // Le fond
        let num = this.randval;
        if (this.ro * 1 > 0) {num = 0;}
        if (this.id === 13) {num = 0;}

        // Build url
        let url = Rules.getLandUrl(this.id, num);
        
        const fond =L.imageOverlay(url, this.image_bounds(), 
            { className:'T', interactive:true, renderer:L.canvas()}); 
        layers.push(fond);

        // riviere
        if (this.id === 13) {
            // (object, what, param, values, func=this.is_equal)
            const directions = Bo.find_neighbours_directions(this, 'Ground', 'id', [13,5]);
            for (const d_key in directions) {
                const fond =L.imageOverlay('./h/r'+directions[d_key]+'.png', this.image_bounds(), 
                    { className:'T', interactive:true, renderer:L.canvas()}); 
                layers.push(fond);
            }
            // La source est la direction inverse du voisin riviere.
            if (directions.length<=1) {
                const directions = Bo.find_neighbours_directions(this, 'Ground', 'id', [13]);
                if (directions.length>0) {
                    const fond =L.imageOverlay('./h/s'+(5-directions[0])+'.png', this.image_bounds(), 
                        { className:'T', interactive:true, renderer:L.canvas()}); 
                    layers.push(fond);
                }
            }
        }

        // route
        if (this.ro * 1 > 0) {
            // plus tard, un look different en fonction de l'etat de la route
            const road_fond =L.imageOverlay('./h/p6.png', this.image_bounds(), 
                { className:'T', interactive:true, renderer:L.canvas()}); 
            layers.push(road_fond);

            // (object, what, param, values )
            const directions = Bo.find_neighbours_directions(this, 'Ground', 'ro', [0], Bo.is_inequal);
            
//            let pic_name  = "r"+directions.toString().replaceAll(",","-")+".png"
//            console.log(pic_name);
//            // Beaucoup trop de possibilité différente pour fiare un pic a chaque fois, non ?
//            // Penser aussi que route+riviere = pont

            for (const d_key in directions) {
                const road_piece =L.imageOverlay('./h/p'+directions[d_key]+'.png', this.image_bounds(), 
                    { className:'T', interactive:true, renderer:L.canvas()}); 
                layers.push(road_piece);
            }
        }

        // Possesseur eventuel
        if (this.ow!=null) {
            const segments = [];
            const directions = Bo.find_neighbours_directions(this, 'Ground', 'ow', [this.ow]);

            for (let i=0; i<6; i++) {
                if (!directions.includes(i))     {
                    let c0 = i; let c1 = i + 1; if (c1>5) {c1=0;}
                    segments.push([ this.pointy_hex_corner(c0, H_S-0.2), this.pointy_hex_corner(c1, H_S-0.2) ]);
                }
            }

            if (segments.length>0) {
                // Une grosse ligne blanche pour contraster :
                // layers.push(L.polyline(segments, { color: '#FFFFFF', opacity:1, weight:8, interactive:false }));


                let string_color = this.co;
                if (string_color[0] == '#') { string_color = 'Ba Ba'; }
                const colors_tags = string_color.split(' ');

                layers.push(L.polyline(segments, { color: Rules.colors[colors_tags[0]], opacity:1, weight:6, interactive:false }));
                // Une fine ligne de ma couleur
                layers.push(L.polyline(segments, { color: Rules.colors[colors_tags[1]], opacity:1, weight:3, interactive:false }));
            }
        }

        const null_dirs = Bo.find_null_neighbours_directions(this)

        for (const d_key in null_dirs) {
            const road_piece =L.imageOverlay('./h/f'+null_dirs[d_key]+'.png', this.image_bounds(),  { className:'F', interactive:false, renderer:L.canvas()}); 
            layers.push(road_piece);
        }



        return this.tag_my_layers(layers);
    };
};

function Fog(coord, json_data, randval) {
    GameObject.call(this, coord, json_data, randval);
    this.uid = function() { return ('F-'+this.coord.stg()); }
    this.create_layers = function(){
        // FOG of WAR = Connu et pas visible.
        const hexa = L.polygon([ this.pointy_hex_corner(0), this.pointy_hex_corner(1),
            this.pointy_hex_corner(2), this.pointy_hex_corner(3), 
            this.pointy_hex_corner(4), this.pointy_hex_corner(5)
            ]);
        hexa.setStyle({ color:null, fillColor:'#000000', fillOpacity:0.4, weight:0, interactive:false });
        return this.tag_my_layers([hexa]);
    };
};

function Path(obj_coord, json_data, randval) {
    GameObject.call(this, obj_coord, json_data, randval);

    var redFlag = L.icon({
        iconUrl: './pics/red-flag.png',
        iconSize: [28, 38],
        iconAnchor: [10, 38],
    });


    this.uid = function() {
        const tag = 'P-'+this.id+'-'+this.puid;
        return tag; 
    }
    this.create_layers = function() {
        const layers = [];
        // A line from my previous to my next :
        // (object, what, param, values, func=this.is_equal)

        const prev_d = Bo.find_neighbours_directions(this, 'Path', 'id', [(this.id*1)-1]);
        const next_d = Bo.find_neighbours_directions(this, 'Path', 'id', [(this.id*1)+1]);

        if (prev_d.length>0) {
            const trace = L.polyline([[this.center_px[1], this.center_px[0]], this.side_middle(prev_d[0])],
                { color: '#FF2222', opacity:0.6, weight:8, interactive:false });
            layers.push(trace);
        }
/*        
        else {
            const mark = L.marker([this.center_px[1], this.center_px[0]]);
            layers.push(mark);            
        }
*/        
        if (next_d.length>0) {
            const trace = L.polyline([[this.center_px[1], this.center_px[0]], this.side_middle(next_d[0])],
                { color: '#FF2222', opacity:0.6, weight:8, interactive:false });
            layers.push(trace);
        } else {
            const mark = L.marker([this.center_px[1], this.center_px[0]],  {icon: redFlag});
            layers.push(mark);
        }
        return this.tag_my_layers(layers);
    };    
};
