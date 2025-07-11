/*==============================================================================
    Minimap Module

    To Do Now :
        - Nothing

    To Do For v2.0 :
        - Ability to move minimap left/right with wraped world (cylinder)
    
==============================================================================*/

function AddRect(x1, y1, x2, y2,context){
    context.fillRect(x1,y1,x2,y2);
}
function hexToRgb(hex) {
  var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
  return result ? {
    r: parseInt(result[1], 16),
    g: parseInt(result[2], 16),
    b: parseInt(result[3], 16)
  } : null;
}

class Minimap {
    constructor(max_col, max_row){
	    /*--------------------------------------------------------------------------
	        Creation de la minimap
	    --------------------------------------------------------------------------*/
	    this.max_col = max_col;
	    this.max_row = max_row;
	    
	    // Ce qui existe -----------------------------------------------------------
	    this.mm_cv = document.getElementById('minimap');
	    this.fs_cv = document.getElementById('mm_focus');

	    this.mapdata = {};

	    // var this = this;
	    // Anciennement 'this.minimap()' -------------------------------------------
	    // Largeur Totale (fixe)
	    this.mm_W = 300;
	    // Taille de ses Hexagones :
	    this.mm_w = this.mm_W / (this.max_col+0.5);
	    this.mm_s = this.mm_w / Math.sqrt(3);
	    this.mm_h = this.mm_s * 2;
	    // Hauteur totale...
	    this.mm_H = this.max_row * this.mm_h*3/4 + this.mm_h/4;     
	    // Definir le "contexte" pour la geographie :
	    this.mm_cv.width = this.mm_W; 
	    this.mm_cv.height = this.mm_H;
	    this.mm_ctx = this.mm_cv.getContext('2d');

	    // Definir le "contexte" pour le scope (rect rouge) :
	    this.fs_cv.width = this.mm_W; 
	    this.fs_cv.height = this.mm_H;
	    this.fs_ctx = this.fs_cv.getContext('2d');    
    }

    launcher() {
        // this.terrains = Rules.lands;
        this.draw_minimap();            // Et dessiner la minimap...
        this.set_interacts();     
    }

    /*--------------------------------------------------------------------------
        Draw
    --------------------------------------------------------------------------*/
	draw_minimap() {

    // Affichage du message "loading"
    	this.mm_ctx.clearRect(0, 0, this.mm_W, this.mm_H); // Efface le canvas avant de dessiner
    	this.mm_ctx.fillStyle = 'rgba(0, 0, 0, 0.7)'; // Fond semi-transparent
    	this.mm_ctx.fillRect(0, 0, this.mm_W, this.mm_H);
        this.mm_ctx.fillStyle = '#e3c982'; // Couleur du texte 'loading'
        this.mm_ctx.font = '17px "Palatino Linotype", serif'; // Police Palatino Linotype
    	this.mm_ctx.textAlign = 'center';
    	this.mm_ctx.textBaseline = 'middle';
    	this.mm_ctx.fillText('Loading...', this.mm_W / 2, this.mm_H / 2);

	    C.ask_data('SCOPEG', [0, 0, this.max_col - 1, this.max_row - 1], this.set_mapdata.bind(this));
	    this.draw_focus();
	}

    set_mapdata(data) {
        this.add_mapdata(data);
        // Pour remplir le cache de MainView :
        // (Abandonne, trop lent...)
        // Bo.digest_data(data);
    }

	add_mapdata(data) {
	    for (const key in data) {
	        const entry = data[key];
	        if (!entry.o) continue; // Vérifie que entry.o existe

	        const mp_key = `${entry.c},${entry.r}`;
	        const add_me = entry.o.find(o => o.t === 'C') || entry.o.find(o => o.t === 'T');

	        if (add_me) {
	            if (!this.mapdata[mp_key] || add_me.t === 'C') {
	                this.mapdata[mp_key] = { o: add_me, new: true };
	            }
	        }
	    }

	    this.draw_geo();
	}

	draw_geo() {
	    for (const key in this.mapdata) {
	        const mapData = this.mapdata[key];

	        if (!mapData['new']) continue;

	        const coord = H.coord_to_pixel(H.coord(key.split(','), 'oddr'), this.mm_w, this.mm_h, this.mm_s);
	        const terrain_obj = mapData['o'];

	        if (terrain_obj.t === 'T' && terrain_obj.id !== 0) {
	            let map_data = Rules.getLandColor(terrain_obj.id);

	            if (map_data && map_data !== '#000000') {
	                let x1 = Math.round(coord[0] - this.mm_w / 2);
	                let y1 = Math.round((this.max_row * this.mm_h * 3 / 4) - (coord[1] + this.mm_h * 1 / 4));
	                let x2 = Math.round(1.73 * this.mm_s);
	                let y2 = Math.round(this.mm_s * 2);

	                this.mm_ctx.fillStyle = map_data;
	                this.mm_ctx.fillRect(x1, y1, x2, y2);
	            }
	        }

	        if (terrain_obj.t === 'C') {
	            let string_color = terrain_obj.co;
	            if (string_color[0] === '#') { string_color = 'Ba Ba'; }
	            const colors_tags = string_color.split(' ');

	            const spot_size = 2;
	            let x1 = Math.round(coord[0] - this.mm_w / 2) - spot_size;
	            let y1 = Math.round((this.max_row * this.mm_h * 3 / 4) - (coord[1] + this.mm_h * 1 / 4)) - spot_size;
	            let x2 = Math.round(1.73 * this.mm_s) + spot_size;
	            let y2 = Math.round(this.mm_s * 2) + spot_size;

	            // Bordure blanche
	            this.mm_ctx.fillStyle = 'rgb(255, 255, 255, 1)';
	            this.mm_ctx.fillRect(x1 - 2, y1 - 2, x2 + 4, y2 + 4);

	            // Triangle bas/droite
	            this.mm_ctx.beginPath();
	            this.mm_ctx.moveTo(x1 + x2 + 1, y1 - 1);
	            this.mm_ctx.lineTo(x1 + x2 + 1, y1 + y2 + 1);
	            this.mm_ctx.lineTo(x1 - 1, y1 + y2 + 1);
	            this.mm_ctx.fillStyle = Rules.colors[colors_tags[1]].hex;
	            this.mm_ctx.fill();
	            this.mm_ctx.closePath();

	            // Triangle Hautgauche
	            this.mm_ctx.beginPath();
	            this.mm_ctx.moveTo(x1 + x2 + 1, y1 - 1);
	            this.mm_ctx.lineTo(x1 - 1, y1 - 1);
	            this.mm_ctx.lineTo(x1 - 1, y1 + y2 + 1);
	            this.mm_ctx.fillStyle = Rules.colors[colors_tags[0]].hex;
	            this.mm_ctx.fill();
	            this.mm_ctx.closePath();
	        }

	        // Mark as processed
	        mapData.new = false;
	    }

	    return;
	}


    draw_focus() {
        // Dessine le Focus
        this.fs_ctx.clearRect(0, 0, this.mm_W, this.mm_H);
        this.fs_ctx.beginPath();
        this.fs_ctx.lineWidth = 2;
        this.fs_ctx.strokeStyle = "red";

        // convert LL Lat/lng to MM pixels :
        var left_bound = LEAFLET_MAP.getBounds().getWest() * this.mm_W / Mv.x_max;
        var down_bound = LEAFLET_MAP.getBounds().getSouth() * this.mm_H / Mv.y_max;
        var right_bound = LEAFLET_MAP.getBounds().getEast() * this.mm_W / Mv.x_max;
        var top_bound = LEAFLET_MAP.getBounds().getNorth() * this.mm_H / Mv.y_max;    

        var rect_w = (right_bound - left_bound);
        var rect_h = (top_bound - down_bound);
        var rect_x = (Math.floor(left_bound));
        var rect_y = (Math.floor(this.mm_H - top_bound));

        this.fs_ctx.rect(rect_x, rect_y, rect_w, rect_h);

        if ( (rect_x + rect_w) > this.mm_W )
            {
            var surplus = (rect_x + rect_w) - this.mm_W;
            this.fs_ctx.rect(0, rect_y, surplus, rect_h);
            }
        if (rect_x<0)
            {
            var surplus = rect_x;
            this.fs_ctx.rect(this.mm_W, rect_y, surplus, rect_h);
            }

        this.fs_ctx.closePath();
        this.fs_ctx.stroke();           
	}
    /*--------------------------------------------------------------------------
        do some math
    --------------------------------------------------------------------------*/
    terrain(col, line) {
        for (var i=0;i<this.world.terrains.length;i++)
            { if (this.world.terrains[i][0]===col && this.world.terrains[i][1]===line)
                { return this.world.terrains[i][2];; } }
        return 0;    
	}
    mouse_to_latlng(e) {
        //  mousevenet:
        var margin =parseInt($("#mm_frame").css("left"));
        var tx = Math.round(Mv.x_max * (e.clientX-margin)  / this.mm_W);
        var ty = Math.round(Mv.y_max * (this.mm_H-e.clientY+margin) / this.mm_H);
        return [ty,tx];
    }
    /*--------------------------------------------------------------------------
        Intercations
    --------------------------------------------------------------------------*/
    set_interacts() {
        // Permet de definir la ou les interactions ----------------------------
        // Le "clic focus" :
        this.fs_cv.addEventListener('click', (e) => 
            {
            Mv.goto(this.mouse_to_latlng(e));
            // this.draw_focus();
            });
	}
}
