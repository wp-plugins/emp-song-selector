/*
* Save song to playlist
* @param int postID
* @param string value
*/
function resetCounts(){
  jQuery.ajax({
	url: MusicSelectorAjax.ajaxurl,
	data:{
	  'action': 'ms_ajax_handler',
	  'type': 'POST',
	  timeout: 3000000,
	  'postNonce': MusicSelectorAjax.postNonce,
	  'fn': 'calculate-counts'
	},
	dataType: 'html',
	beforeSend:function(){
		jQuery("#counts-stats").addClass('ajax-load');
	},
	success:function(data){
	  jQuery("#counts-stats").removeClass('ajax-load');
	  jQuery("#counts-stats").html(data);
	},
	error: function(errorThrown){
	  console.log(errorThrown);
	}
  });
}
function updateGenres(){
  jQuery.ajax({
	url: MusicSelectorAjax.ajaxurl,
	data:{
	  'action': 'ms_ajax_handler',
	  'type': 'POST',
	  timeout: 3000000,
	  'postNonce': MusicSelectorAjax.postNonce,
	  'fn': 'update-genres'
	},
	dataType: 'html',
	beforeSend:function(){
		jQuery("#genres-stats").addClass('ajax-load');
	},
	success:function(data){
	  jQuery("#genres-stats").removeClass('ajax-load');
	  jQuery("#genres-stats").html(data);
	},
	error: function(errorThrown){
	  console.log(errorThrown);
	}
  });
}
function generatePDF(playlist){
  jQuery.ajax({
	url: MusicSelectorAjax.ajaxurl,
	data:{
	  'action': 'ms_ajax_handler',
	  'type': 'POST',
	 timeout: 3000000,
	  'playlist':playlist,
	  'postNonce': MusicSelectorAjax.postNonce,
	  'fn': 'generate-pdf'
	},
	dataType: 'html',
	beforeSend:function(){
		jQuery("#" + playlist.charAt(0) + "-pdf-link").removeClass('pdf-link').addClass('ajax-load');
	},
	success:function(data){

		jQuery("#" +playlist.charAt(0) + "-pdf-link").removeClass('ajax-load').addClass('pdf-link');
	},
	error: function(errorThrown){
	  console.log(errorThrown);
	}
  });
}
jQuery(document).ready(function() {
   jQuery('#upload-btn').click(function(e) {
        e.preventDefault();
        var image = wp.media({
            title: 'Upload Image',
            // mutiple: true if you want to upload multiple files at once
            multiple: false
        }).open()
            .on('select', function(e){
                // This will return the selected image from the Media Uploader, the result is an object
                var uploaded_image = image.state().get('selection').first();
                // We convert uploaded_image to a JSON object to make accessing it easier
                // Output to the console uploaded_image
                console.log(uploaded_image);
                var image_url = uploaded_image.toJSON().url;
                // Let's assign the url value to the input field
                jQuery('#image_url').val(image_url);
            });
    });



  var currentID;
  jQuery(".generate-pdf").each(function () {
	jQuery(this).live('click', function () {
	  generatePDF(this.name);
	});
  });
  jQuery("#playlists-counts").live('click', function () {
      jQuery('#counts-stats').html('');
	resetCounts();
  });
  jQuery("#available-genres").live('click', function () {
	updateGenres();
  });
  jQuery("#reset-genres").live('click', function () {
	resetGenres();
  });


});