/*==============================================================================
    HELPERS
==============================================================================*/
/*------------------------------------------------------------------------------
    Ze Library :
 -----------------------------------------------------------------------------*/
function Helpers() {

    var that = this;

    this.coordset_correction = function(coord_ary, max_col)
        {
        // Correction of a set of coord :
        for (var key in coord_ary) { this.coord_correction(coord_ary[key], max_col); }
        };
    this.coord_correction = function(coord, max_col)
        {
        coord.col = this.real_colonne(coord.col, max_col);
        };

    this.real_colonne = function(colonne, max_col)
        {
        if (colonne>max_col-1)
            {
            colonne = colonne - max_col;
            colonne = this.real_colonne(colonne, max_col);
            };
        if (colonne<0) 
            {
            colonne = colonne + max_col;
            colonne = this.real_colonne(colonne, max_col);
            };
        return colonne;            
        };            

    this.log = function(txt, who = '')
        {
        if (who!=='') {who = who + ': '}
	    const now = new Date();
	    const date = now.toLocaleDateString('fr-FR');
	    const time = now.toTimeString().split(' ')[0];
	    console.log(`${date} [${time}] ${who}${txt}`);
        };

    this.safelyParseJSON = function(json) {
        // This function cannot be optimised, it's best to
        // keep it small!
        let parsed = {};

        try { parsed = JSON.parse(json) }
        catch (e) { 
            this.log('Bad JSON Object !', 'HELPERS'); 
            // console.log(json);
        }

        return parsed // Could be undefined!
    };

    this.random_color = function(colors) {
        const shuffledArray = colors.sort((a, b) => 0.5 - Math.random());
        return shuffledArray[0]['name']+' '+shuffledArray[1]['name'];
        };

    this.random_color_old = function()
        {
        // Random color, with controlled Saturation and luminosity :
        const result = this.HSL_to_RGB(Math.floor(Math.random()*360), 75, 50);
        return result;
        };

    this.HSL_to_RGB = function(h, s, l)
        {
        // From :    
        // https://css-tricks.com/converting-color-spaces-in-javascript/    
        // Must be fractions of 1
        s /= 100;
        l /= 100;
        let c = (1 - Math.abs(2 * l - 1)) * s,
            x = c * (1 - Math.abs((h / 60) % 2 - 1)),
            m = l - c/2,
            r = 0,
            g = 0,
            b = 0;
        if (0 <= h && h < 60) { r = c; g = x; b = 0; } 
        else if (60 <= h && h < 120) { r = x; g = c; b = 0; }
        else if (120 <= h && h < 180) { r = 0; g = c; b = x; }
        else if (180 <= h && h < 240) { r = 0; g = x; b = c; }
        else if (240 <= h && h < 300) { r = x; g = 0; b = c; }
        else if (300 <= h && h < 360) { r = c; g = 0; b = x; }
        r = Math.round((r + m) * 255).toString(16);
        g = Math.round((g + m) * 255).toString(16);
        b = Math.round((b + m) * 255).toString(16);
        if (r.length === 1) {r = "0" + r;}
        if (g.length === 1) {g = "0" + g;}
        if (b.length === 1) {b = "0" + b;}
        return "#" + r + g + b;
        };

    this.build_request = function(obj_cols, tablename) {
        // const obj_cols = object.sql_columns();

        let chars_requ = "INSERT INTO "+tablename+" (";
        for (let ck in obj_cols) { chars_requ = chars_requ + obj_cols[ck] + ', ' };
        chars_requ = chars_requ.slice(0, -2);
        chars_requ = chars_requ + ") VALUES";
        chars_requ = chars_requ + " ? ";
        chars_requ = chars_requ + "ON DUPLICATE KEY UPDATE ";
        for (let ck in obj_cols) {  if (ck!=0) {chars_requ = chars_requ + obj_cols[ck]+'=VALUES('+obj_cols[ck]+'), ' } };
        chars_requ = chars_requ.slice(0, -2);
        chars_requ = chars_requ + ';';

        return chars_requ;
    };

    this.select_all = async function(table,sql_connection){
        const request = 'SELECT * FROM '+table+';';
        return await sql_connection.query(request); 
    };

    this.delete_object = async function(obj_id, table, sql_connection) { 
        const request = "DELETE FROM klodonline."+table+" WHERE id="+obj_id+";";
        return await sql_connection.query(request);
    };

    this.save_objects = async function(objects, table, sql_connection) {
        if (Object.keys(objects).length>0)  {
            const request = this.build_request(objects[Object.keys(objects)[0]].sql_columns(), table);
            const values = [];
            for (const o_key in objects) { 
                values.push(objects[o_key].sql_data());  
            }
            return await sql_connection.query(request, [values]);
        }
        const fake_return = [{'affectedRows':0}];
        return fake_return;
    };

    this.purge_objects = function(ids, table, sql_connection) {
        if (ids.length>0)  {

            this.log( ('SQL Deleting '+ids.length+' '+table+'...'), '?')
            let request = 'DELETE FROM '+table+' WHERE id IN ('
            for (const key in ids) { request = request + ids[key] + ', ' ;}
            request = request.slice(0, -2);
            request = request + ');';                

            sql_connection.query(request, function (err, result) {
                if (err) throw err;
                that.log((table+' deleted : '+result.affectedRows), '?')
                return true;
            });
        }
    };


};

module.exports = new Helpers();