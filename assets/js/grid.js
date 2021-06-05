class obser_GRID {
    static cookies;
    static settings;
    constructor() {

      this.cookies = new Proxy( [] , {
        get: (target, property) => {
          return target[property];
        },
        set: (target, property, value) => {
            property    = value.grid;
            value       = value.content;
          target[property] = value;
          return true;
        }
      });

      this.settings = new Proxy( [] , {
        get: (target, property) => {
          return (target[property] !== undefined) ? target[property] : null;
        },
        set: (target, property, values) => {

            let {grid,setting,value} = values;
            
            if(!target[grid]){
                target[grid] = {};
            }
            let settings        = target[grid];
            settings[setting]   = value;
            target[grid]        = settings;
          return true;
        }
      });


    }

    _add_cookie(grid = '', content = '') {
        if(grid  == '' || content == '') return;
        this.cookies.push({grid,content});
    }

    _add_setting(grid = '', setting = '',value = '' ,refresh = false) {
        if(grid  == '' || setting == '') return;
        this.settings.push({grid,setting,value});
        this._handler_settings(grid,refresh);
    }

    _handler_settings(grid,refresh){
        let settings = this._get_setting(grid);
        this.set_cookie(grid,settings);

        if(refresh){
                this.refresh_grid(grid,settings)
        }

    }


    _get_setting(grid,key = null) {
        return (key !== null) ? ( (this.settings[grid] !== null) && (this.settings[grid][key] !== null) ? this.settings[grid][key] : null) : ( (this.settings[grid] !== null ) ? this.settings[grid] : null);
    }


    _get_cookie(grid) {
        return (this.cookies[grid].length >= 1) ? this.cookies[grid] : false;
    }

    _flushCookie() {
        this.cookies.forEach(cookie => {
        });
        
    }
  
    set_cookie( grid = null , settings) {
        var expireDate  = new Date();
            expireDate.setTime(expireDate.getTime() + ( 10 * 600000));

        let expires             = "expires="+ expireDate.toUTCString(),
            settings_string     = JSON.stringify(settings),
            pathname            = window.location.pathname,
            cookie              = `wordpress_grid__${grid}=${settings_string};path=${pathname}`;
        document.cookie         = cookie;
        let obser_grid_cookie    = this.get_cookie(grid);
        this._add_cookie(grid,obser_grid_cookie);
    } 

    delete_cookie(grid) {
        document.cookie = `wordpress_grid__${grid}=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;`;
    }
    
    get_cookie(grid) {
        var name = `wordpress_grid__${grid}=`;
        var decodedCookie = decodeURIComponent(document.cookie);
        var ca = decodedCookie.split(';');
        for(var i = 0; i <ca.length; i++) {
          var c = ca[i];
          while (c.charAt(0) == ' ') {
            c = c.substring(1);
          }
          if (c.indexOf(name) == 0) {

            let cookie_string = c.substring(name.length, c.length);

            try {
                return JSON.parse(cookie_string);
            } catch (e) {
                return cookie_string;
            }
          }
        }
        return "";
    }


    refresh_grid(grid,atts){
    let obser_grid   =   jQuery('.contenedor-obser-grid[data-shortcode_id='+grid+']');
    let settings     =   obser_grid.attr("data-obser-grid-settings");
    settings         =   JSON.parse(settings);
    if(atts.paged) {
        settings['paged']  = atts.paged;
    }

    if(atts.filters) {
        let filters = atts.filters
        settings['filters']  = {...settings['filters'],...filters};
    }

    console.log(settings);
    jQuery.ajax({
        type: "GET",
        url: obser_core.ajax_url,
        data: {
            action      : settings.action,
            tag         : settings.tag,
            data        : settings,
            vc_post_id  : obser_grid.data("vcPostId"),
            _vcnonce    : obser_grid.data("vcPublicNonce"),
        },
        dataType: "html",
        beforeSend: function() {
            jQuery(obser_grid).children('.obser-custom-preloader').toggleClass('d-none');
        },
        success: function (response) {
            jQuery(obser_grid).children('.obser-custom-grid-items').html(response);
        },
        error: function (response) {
        },
        complete: function(){
            jQuery(obser_grid).children('.obser-custom-preloader').addClass('d-none');
        }
    });

    }

    handlerFields(grid,refresh = true){
        var $i      = 0;
        var fields  = {};

        jQuery('.inmmoob-searchform input, .inmmoob-searchform select').not('.no-filter').each(function(i,e){
            let val         = jQuery(this).val(),        
                mx_name     = jQuery(this).attr('name');
            if(!fields[mx_name]) fields[mx_name] = [];
            
            if(jQuery(this).is("select")){
                val = jQuery(this).children('option:selected').val();
                if(val !== ''){
                    fields[mx_name].push(val);
                }
            
            }else if(jQuery(this).is("input")){
                
                inputType   = jQuery(this).attr('type');
                if(inputType == 'checkbox' && jQuery(this).is(':checked')){
                    fields[mx_name].push(val);
                }else if((inputType == 'radio' && jQuery(this).is(':checked')) || inputType == 'text' || inputType == 'hidden'){
                    fields[mx_name] = val;
                }
            }

        });


        obser_grid._add_setting(grid,'filters',fields,refresh);

    }

    get_grid_by_gid(gid){
        return jQuery("div[data-gid="+gid+"]");
    }

}

var obser_grid = new obser_GRID();

jQuery(document).on('click','.prev-next-link', function(){
    let parent_grid = jQuery(this).parents('.contenedor-obser-grid').attr('data-shortcode_id'),
        next_page   = jQuery(this).attr('data-next_page'); 
        obser_grid.handlerFields(parent_grid,false);
        obser_grid._add_setting(parent_grid, 'paged',next_page,true);
});


jQuery('.inmmoob-searchform input,.inmmoob-searchform select').not('.no-filter').change(function(){
    

    let grid_gid        = jQuery(this).parents('.inmmoob-searchform').data('grid'),
        grid            = obser_grid.get_grid_by_gid(grid_gid),
        shortcode_id    = jQuery(grid).data('shortcode_id');

        if(jQuery(this).attr('name') !== 'heuristic'){
            jQuery('#heuristic, #heuristic_value').val('');
        }else{
            jQuery('.input-buscador--obser_stores_category, .input-buscador--obser_restaurants_category, .input-buscador--start_with').each(function(i,el){
                let def = jQuery(el).data('default');
                if(checked = def == 1) jQuery(el).prop('checked',checked);
            });
        }

        obser_grid._add_setting(shortcode_id, 'paged',1);
        obser_grid.handlerFields(shortcode_id);
});

jQuery('.buscador-obser').submit(function (e) { 
    e.preventDefault();
});




jQuery('select[name=\"property_types_taxonomy\"]').change(function(e){
    let property_type = jQuery(this).children('option:selected').val();
    if(property_type == '') property_type = 'inmuebles';
    var pathname = window.location.pathname; 
    var matches = pathname.match(/(?:^\/(?<gestion_type>[^?\n\/]+))(?:\/(?<property_type>[^?\n\/]+))?(?:\/(?<property_zone>[^?\n\/]+))?/i);
    if(matches[2]){
        pathname = pathname.replace(matches[2],property_type); 
    }else{
        pathname = `${pathname}inmuebles/`;
    }

    window.history.pushState("", "", pathname);

})


jQuery('select[name=\"property_zones_taxonomy\"]').change(function(e){
    let property_zone = jQuery(this).children('option:selected').val();

    console.log(property_zone);
    var pathname = window.location.pathname; 
    var matches = pathname.match(/(?:^\/(?<gestion_type>[^?\n\/]+))(?:\/(?<property_type>[^?\n\/]+))?(?:\/(?<property_zone>[^?\n\/]+))?/i);
    if(!matches[2]){
        pathname = `${pathname}inmuebles/`;
    }
    if(matches[3]){
        pathname = pathname.replace(matches[3],property_zone); 
    }else if(property_zone != ''){
        pathname = `${pathname}${property_zone}/`;
    }
    
    pathname = pathname.replace('//','/');


    window.history.pushState("", "", pathname);

})