/*==============================================================================
    Tchat Module

        Le proposer aussi dans une fenetre totalement séparée ?

==============================================================================*/

function Tchat()
    {
    this.last = []; 
    this.curr_key = 0;
    // this.last_whisp = '';
        
    var that = this;
  
    this.human_date = function()
        {
        var d = new Date();
        return ('['+ (('0'+d.getHours()).slice(-2))+':'
            +(('0'+d.getMinutes()).slice(-2))+':'
            +(('0'+d.getSeconds()).slice(-2))+']');
        };
    this.rcpt_msg = function (nick, msg, color = '#d3d3d3')
        {
        // Si dans le message il y a une position ( [xxx,yyy] ) on la transforme en link de mapview :) 
        var coords_regex = /\[[0-9]+\,[0-9]+\]/g;
        msg = msg.replace(coords_regex, function(stg){return '<span class="coords">'+stg+'</span>';});
        var txt = '<span style="color:'+color+'">'+this.human_date()+' [<span class="nick">'+nick+'</span>]: '+msg+'</span><br/>';

        // if (color==='#F980ef') { this.last_whisp = nick; }

        $('#messages').append(txt);
        $('#messages').animate({scrollTop: $('#messages')[0].scrollHeight}, 50);
        
        if (document.hidden) 
            { if (color==='#F980ef') { blinkTab('New whisp !'); } }
        
        this.fade_to_visible();

        };
    this.client_talk = function(msg)
        {
        this.rcpt_msg('Client', msg, '#efcd25');
        };
        
    this.cmd_help = function()
        {
        var msg = '</br>/help : Ce message </br>'
            +'/ig : Nombre de joueurs en ligne </br>'
            +'/w NICK : Message privé à [NICK]';
        this.client_talk(msg);
        };
    this.cmd_ig = function()
        {
        C.ask_data('WLC');
        };
/*        
    this.cmd_r = function(txt)
        {
        logMessage(this.last_whisp)
        logMessage(txt)
        var msg = txt.slice(1, txt.length);

        if (this.last_whisp=='') {return false;}
        var json_obj = {'nick':this.last_whisp, 'msg':msg.join(' ') };    
        C.ask_data('WHISP', json_obj);
        };           
*/        
    this.cmd_w = function(txt)
        {
        var msg = txt.slice(2, txt.length);
        var json_obj = {'nick':txt[1], 'msg':msg.join(' ') };    
        C.ask_data('WHISP', json_obj);
        };    
    // --- SECRET ADMIN COMMAND ---    
    this.cmd_iga = function() { C.ask_data('WLCA'); };
    // this.cmd_list = function() { C.ask_data('WLST'); };  

    // ---------------    
    this.add_input = function(txt)
        {
        txt = $('#tchat_input').val()+txt ;
        $('#tchat_input').val(txt);
        $('#tchat_input').focus();

        // this.fade_to_visible();
        };


    // We can play with fade in fade out !
    this.fade_to_visible = function(withtimer=true) {
        // console.log('backvisible !')
        clearTimeout(this.fadeoutTimer);
        $("#tchat").stop( true, true ).fadeTo(300, 1);
        if (withtimer) {
            this.fadeoutTimer = setTimeout(function(){
                $("#tchat").stop( true, true ).fadeTo(15000, 0.1);    
            }, 3000);
        }
    };



    // Moveable and resizable
    $( "#tchat" ).resizable({containment:"body"});
    $( "#tchat" ).draggable({containment:"body"});    
    

    // Evenement qui amene le tchat a s'effacer
    $("#tchat").mouseleave(function(){ that.fade_to_visible(); });    
    //  + une reception de message (Cf. fonction rcpt)

    // Element qui ramene le tchat durablement
    $("#tchat").mouseenter(function(){ that.fade_to_visible(false); });
    $("#tchat_input").focus(function(){ that.fade_to_visible(); });
    // + taper au clavier dans l'input (Cf keyup)



    $('#tchat').on('click', '.nick', function(e)
        {
        // https://stackoverflow.com/questions/19393656/span-jquery-click-not-working/19393669
        var nick = $(this).text();
        $('#tchat_input').val('/w '+nick+' ');
        $('#tchat_input').focus();
        return false;
        });
    $('#tchat').on('click', '.coords', function(e)
        {
        // Magique ^^ Va là ou on a linké les coords :) :) :)    
        var coords = $(this).text();
        coords = coords.substring(1, coords.length-1);
        var c_ary = coords.split(',');
        var latlng = H.coord_to_pixel(H.coord(c_ary,'oddr'), H_W, H_H, H_S);
        var latlng = [latlng[1],latlng[0]];
        Mv.goto(latlng);
        });

    $('#tchat_input').keyup(function(e)
        {
        that.fade_to_visible();

        // Petit historique maison !    
        if(e.keyCode === 38) {
            that.curr_key--;    
            if (that.curr_key<=0) {that.curr_key=0;}
            $('#tchat_input').val(that.last[that.curr_key]);
            return false;
        }
        if(e.keyCode === 40) {
            that.curr_key++;
            if (that.curr_key>that.last.length) {that.curr_key=that.last.length;}
            $('#tchat_input').val(that.last[that.curr_key]);
            return false;
        }

        // Push entree :    
        if(e.keyCode === 13)
            {

            const txt = $('#tchat_input').val();
            if (txt.length<=0) {return false;}
            that.last.push(txt);
            that.curr_key = that.last.length;

            // Detect Special commands :
            if (txt[0]==='/')
                {
                var special_txt = txt.split(' ');
                var cmd = special_txt[0];
                var func_name = 'cmd_' + cmd.substring(1,cmd.length);
                if (typeof that[func_name]==='function')
                    {
                    that[func_name](special_txt);    
                    }
                else 
                    {
                    that.client_talk('Commande inconnue. Utilisez /help pour avoir la liste des commandes valides.');
                    }
                }
            else 
                {
                // Classical txt :    
                that.send($('#tchat_input').val());
                }
                
            $('#tchat_input').val('');
            return false;
            }
        
    //    return false;

            
        });       
        
    this.send = function(msg) { C.ask_data('TCH', msg); };

    C.socket.on('TCH', function (data)  { that.rcpt_msg(data.name, data.msg, data.col); });        
        
        
    }
