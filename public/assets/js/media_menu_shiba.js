(function ($, _, Backbone) {
wp.media.shibaMlibEditGallery = {
     
    frame: function() {
        if ( this._frame )
            return this._frame;
 
        this._frame = wp.media({
            id:         'my-frame',               
            frame:      'post',
            state:      'gallery-edit',
            title:      wp.media.view.l10n.editGalleryTitle,
            editing:    true,
            multiple:   true,
        });
        return this._frame;
    },
 
    init: function() {
        $('#upload-and-attach-link').click( function( event ) {
            event.preventDefault();
 
            wp.media.shibaMlibEditGallery.frame().open();
 
        });
    }
};
 
$(document).ready(function(){
    $( wp.media.shibaMlibEditGallery.init );
});

}(jQuery, _, Backbone));