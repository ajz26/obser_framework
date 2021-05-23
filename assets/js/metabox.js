jQuery(document).ready( function($) {

    jQuery('button.media_link.upload_button').click(function(e) {
        let field = jQuery(this).attr('data-field'),
            input_field = document.getElementById(field),
            image_field = document.getElementById(`${field}-preview-image`); 
            console.log(image_field);
           e.preventDefault();
           var image_frame;
           if(image_frame){
               image_frame.open();
           }
           // Define image_frame as wp.media object
           image_frame = wp.media({
                         title: 'Select Media',
                         multiple : false,
                         library : {
                              type : 'image',
                          }
                     });

                     image_frame.on('close',function() {
                        var selection =  image_frame.state().get('selection');
                        var gallery_ids = new Array();
                        var my_index = 0;
                        selection.each(function(attachment) {
                           gallery_ids[my_index] = attachment['id'];
                           my_index++;
                        });
                        var ids = gallery_ids.join(",");
                        jQuery(input_field).val(ids);
                        Refresh_Image(ids,image_field);
                     });

                    image_frame.on('open',function() {
                      var selection =  image_frame.state().get('selection');
                      var ids       = jQuery(input_field).val().split(',');
                      ids.forEach(function(id) {
                        var attachment = wp.media.attachment(id);
                        attachment.fetch();
                        selection.add( attachment ? [ attachment ] : [] );
                      });

                    });
                  image_frame.open();
   });

});

// Ajax request to refresh the image preview
function Refresh_Image(the_id,field){
    console.log(field);
      var data = {
          action: 'get_image_url_by_id',
          id: the_id
      };

      jQuery.get(ajaxurl, data, function(response) {

          if(response.success === true) {
              jQuery(field).attr( 'src', response.data.image);
          }
      });
}


// jQuery('.ob-nav-tab-item a').click(function(e){
//     e.preventDefault();

//     // jQuery('section.ob_settings-section.current').removeClass('current');
//     //     jQuery('.ob_settings-nav-anchor.current').removeClass('current');
//     //     jQuery("a.ob_settings-nav-anchor[data-id="+section+"]").addClass('current');
//     //     jQuery("section.ob_settings-section[data-id="+section+"]").addClass('current');

// });

jQuery(document).on('click','.ob-nav-tab-item a', function (e) {
    e.preventDefault();
    let new_tab = jQuery(this).attr('href');
    jQuery('.ob-nav-tab-item.current').removeClass('current');
    jQuery('.ob-nav-tab-content.current').removeClass('current');
    jQuery(".ob-nav-tab-item a[href="+new_tab+"]").parents('.ob-nav-tab-item').addClass('current');
    jQuery(new_tab).addClass('current');
});