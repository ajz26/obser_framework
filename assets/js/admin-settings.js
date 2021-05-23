const {opt_name} = ob_settings;



class GMR {
    static data = [];
    static currentSection;


    constructor() {

        this.data = new Proxy([], {
            get: (target, property) => {
                return target[property];
            },
            set: (target, property, value) => {
                target[property] = value;
                return true;
            }
        });

        this.currentSection = new Proxy({}, {
            get: (target, property) => {
                return target[property];
            },
            set: (target, property, value) => {
                target[property] = value;
                return true;
            }
        });

    }

    _get_data() {
        return (this.data) ? this.data : false;
    }

    _add_data(key,val) {
        this.data[key] = val; 
        this._handler_data_changes(key);
    }

    _modify_section(section) {
        this.currentSection = section;
        this._handler_section_changes(this.currentSection);
        this._change_title(section)

    }

    _handler_section_changes(section){
        jQuery('section.ob_settings-section.current').removeClass('current');
        jQuery('.ob_settings-nav-anchor.current').removeClass('current');
        jQuery("a.ob_settings-nav-anchor[data-id="+section+"]").addClass('current');
        jQuery("section.ob_settings-section[data-id="+section+"]").addClass('current');
    }

    _change_title(section){
        let title = jQuery("section.ob_settings-section[data-id="+section+"] .ob_settings-section-heading h3").text();
        jQuery('.ob_settings-header-title h2').text(title);
    }

    
    _handler_data_changes(key){
        
       let data = this._get_data();
        
        if(!data) return;
        
        // this._enable_save_button();
    }
    
}

const gmr = new GMR();

// jQuery(document).on('click','.gm-button.show-vehicles', function (e) { 
//     e.preventDefault();

//     let data = gmr._get_data();
//     if(data.cuota > 0 && data.gmarcos_tipo_contrato !== ''  && data.gmarcos_antiguedad !== '' &&  data.rentable == true){
//         actualizar_resultados();
//     }
// });

jQuery(document).ready(function () {
    jQuery.each(ob_settings['fields'], function (index, field) { 
        gmr._add_data(field,jQuery(`#obser-field-${field}`).val());
    });
    
    if(current_section = new URL(document.URL).hash.substr(1)){
        gmr._modify_section(current_section);
    }

});



jQuery(document).on('click','.ob_settings-nav-anchor', function (e) {
    section = jQuery(this).attr('data-id');
    gmr._modify_section(section);
});

jQuery(`#ob_settings-form-${opt_name}`).children('input , select , textarea').change(function(e) {
    console.log(e);
    console.log('cambio')
});

jQuery(document).on('click','.btn-ob_settings-header-cta', function(e){
    e.preventDefault();

    save_settings();
})
function save_settings(){
    let data = {
        action : `save_${opt_name}`
    };
    jQuery.each(ob_settings['fields'], function (index, field) { 
        data[field] = jQuery(`#obser-field-${field}`).val();
    });
    jQuery.ajax({
        type: "POST",
        url: ob_settings['ajax_url'],
        data,
        // dataType: "dataType",
        success: function (response) {
            alert('Datos actualizados');
        }
    });
}