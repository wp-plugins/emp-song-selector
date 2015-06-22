var flag, songIDs, all, html, count, runtime, hours, minutes, seconds, currentPage, pageCount, userArgs, playlistID;
count=0;
runtime = 0;



jQuery('.full_screen_width').parents('.container').css('width','100%');

function savePlaylist(playlist, playlistID) {
    var imageUrl =  jQuery('.loading_img').attr('datasrc');
    jQuery('.loading_img').html('<img src=" '+imageUrl+'">');
    jQuery('.loading_img').show();


    var count_tracks = jQuery.map(playlist, function(n, i) { return i; }).length;

    var tracks = '';
    jQuery.each(playlist, function(key,valueObj){
        tracks+=valueObj+'-@'
    });


  jQuery.ajax({
	url: PlaylistAjax.ajaxurl,
	type: "POST",
	data:{
	  'action': 'fe-ajax',
	  'fn':'save-playlist',
	  'playlist':playlist,
	  'playlist_id':playlistID,
	  'postNonce': PlaylistAjax.postNonce
	},
	dataType: 'html',
	success:function(data){

        jQuery('.loading_img').hide();
	 if (jQuery("#playlist-selection option[value='" + playlistID + "']").length == 0) {
		// Add new Playlist on the overlay
		jQuery('#playlists-status').after('<div class="playlist-items" datasrc="'+tracks+'" ><input type="radio" name="playlist_id" id="' + encodeURIComponent(playlistID) + '" value="' + playlistID + '"> ' + playlistID +'  ('+count_tracks+')</input><span style="cursor:pointer;margin-left:15px;color:red" class="delete-playlist" >(X) delete</span></div>');
		// Add new playlist to the Playlist select menu
		var o = new Option(playlistID, playlistID);
		jQuery('#playlist-selection').append(o);
          jQuery("#playlist-selection option[value='" + playlistID + "']").append( "<p> (Total:"+count_tracks+")</p>" );

	  }else{
         jQuery('#playlists input[name="playlist_id"]' ).each(function() {
             if(jQuery(this).val()== playlistID){
                 jQuery(this).parent('div').attr('datasrc',tracks);
                 if(playlistID=='Background Music' ||  playlistID=='Dancing Music'){
                     var delete_playlist =  '';
                 }else{
                     var delete_playlist =  '<span style="cursor:pointer;margin-left:15px;color:red" class="delete-playlist" >(X) delete</span>';
                 }

                 jQuery(this).parent('div'). html('<input type="radio" name="playlist_id" id="' + encodeURIComponent(playlistID) + '" value="' + playlistID + '"> ' + playlistID +'  ('+count_tracks+')</input>'+delete_playlist);


             }
         });

         jQuery("#playlist-selection option[value='" + playlistID + "']"). html( playlistID + "  <p> (Total:"+count_tracks+")</p>" );
     }
	  //Close the overlay
	  jQuery('div.playlists_overlay').remove();
	  jQuery('#playlists').hide();
	  // Display the stats and PDF links
	  jQuery( "#stats-window" ).slideDown( "slow", function() {
		jQuery("#playlist-pdf").parent().attr("href", data);
		jQuery('#stats-window').fadeIn('slow');
	  });
	},
	error: function(errorThrown){
	  //alert('error');
	  console.log(errorThrown);
	}
  });
}

if(jQuery('#limit-results-selection option:selected').val()=='10'){
    jQuery('.music_selector_thead_tr').css('width','100%');
    jQuery('.try_me_button').css('right','13px');

}
function deleteSamplePlaylist(playlistID) {
    var imageUrl =  jQuery('.loading_img').attr('datasrc');
    jQuery('.loading_img').html('<img src=" '+imageUrl+'">');
    jQuery('.loading_img').show();
    jQuery.ajax({
        url: PlaylistAjax.ajaxurl,
        type: "POST",
        data:{
            'action': 'fe-ajax',
            'fn':'delete-sample-playlist',

            'playlist_id':playlistID,
            'postNonce': PlaylistAjax.postNonce
        },
        dataType: 'html',
        success:function(data){

            jQuery('.loading_img').hide();

        },
        error: function(errorThrown){
            //alert('error');
            console.log(errorThrown);
        }
    });
}

function deletePlaylist(playlist, playlistID) {
    var imageUrl =  jQuery('.loading_img').attr('datasrc');
    jQuery('.loading_img').html('<img src=" '+imageUrl+'">');
    jQuery('.loading_img').show();
    jQuery.ajax({
        url: PlaylistAjax.ajaxurl,
        type: "POST",
        data:{
            'action': 'fe-ajax',
            'fn':'delete-playlist',
            'playlist':playlist,
            'playlist_id':playlistID,
            'postNonce': PlaylistAjax.postNonce
        },
        dataType: 'html',
        success:function(data){
            jQuery('.loading_img').hide();
            jQuery('#playlists input[name="playlist_id"]' ).each(function() {
               if(jQuery(this).val()== playlistID){
                  jQuery(this).parent('div').remove();
               }
            });
            jQuery("#playlist-selection option[value='" + playlistID + "']").remove();

            /*if (jQuery("#playlist-selection option[value='" + playlistID + "']").length == 0) {
                // Add new Playlist on the overlay
                jQuery('#playlists-status').after('<div class="playlist-items"><input type="radio" name="playlist_id" id="' + encodeURIComponent(playlistID) + '" value="' + playlistID + '"> ' + playlistID + '</input></div>');
                // Add new playlist to the Playlist select menu
                var o = new Option(playlistID, playlistID);
                jQuery('#playlist-selection').append(o);
            }*/
            //Close the overlay
            jQuery('div.playlists_overlay').remove();
            jQuery('#playlists').hide();
            // Display the stats and PDF links
            jQuery( "#stats-window" ).slideDown( "slow", function() {
                jQuery("#playlist-pdf").parent().attr("href", data);
                jQuery('#stats-window').fadeIn('slow');
            });
        },
        error: function(errorThrown){
            //alert('error');
            console.log(errorThrown);
        }
    });
}
function ucfirst( str ) {   // Make a string&#039;s first character uppercase
                            //
                            // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)

    var f = str.charAt(0).toUpperCase();
    return f + str.substr(1, str.length-1);
}

/*
* Query songs
* @params array args
*/
function querySongs(args) {
    var imageUrl =  jQuery('.playlist_loading').attr('datasrc');
    jQuery('.playlist_loading').html('<img src=" '+imageUrl+'">');
    jQuery('.playlist_loading').show();

  jQuery.ajax({
	url: PlaylistAjax.ajaxurl,
	type: "POST",
	data:{
	  'action': 'fe-ajax',
	  'fn':'query',
	  'postNonce': PlaylistAjax.postNonce,
	  args:args
	},
	dataType: 'json',
	success:function(data){

        jQuery('.playlist_loading').hide();
	  userArgs = {};


	  // Pagination
	  pageCount = Math.ceil( data.count / Number( jQuery('#limit-results-selection').val()));
	  if (pageCount > 1) {
		jQuery('#song-nav').css('display', 'inline!important').css('z-index', '900');
		jQuery('#total-pages').text(pageCount);
	  } else {
		jQuery('#song-nav').css('display', 'none!important').css('z-index', '-1');
	  }

	  // Append Results

       if(data.count<11){
           jQuery('.try_me_button').css('right','13px');
       }
	  jQuery('#music_selector_results').replaceWith(data.html);


       if(data.count>800){
           var count_k = ' ~'+ Math.round(data.count/1000)+ ' k';
       }else{
           var count_k = data.count;
       }



if(args.playlist_selection=='sample'){

    jQuery('#playlist-selection option:selected').text('Print Version Playlist (Total:'+count_k+' )');
}else{
    jQuery('#playlist-selection option:selected').text(ucfirst(args.playlist_selection) +' Playlist (Total:'+count_k+' )');
}




        if(data.count<11){
            jQuery('.music_selector_thead_tr').css('width','100%');
        }
        jQuery('#music_selector_results').css('width','100%');
	  all =true;
	  jQuery('.select-single-song').each(function() {

		if (songIDs.indexOf(jQuery(this).attr('value')) > -1) {
		   jQuery(this).parent().parent().addClass('selected-track');
		  jQuery(this).attr("checked", "checked");
		  if (flag) {
			runtime = parseInt(runtime) + parseInt(jQuery('#song-' + jQuery(this).attr('value')));
		  }
		}
		if (!this.checked) {all=false;}
		jQuery("#" + this.id).change(function() {


		  if ( jQuery(this).prop('checked') ) {
              if(jQuery.inArray(this.value,songIDs)==-1){
                  jQuery(this).parent().parent().addClass('selected-track');
                  songIDs.push(jQuery(this).attr('value'));
                  count++;
                  runtime = parseInt(runtime) + parseInt(this.name);
              }

		  } else {
              if(jQuery.inArray(this.value,songIDs)>-1){
			 jQuery(this).parent().parent().removeClass('selected-track');
			var removeItem =jQuery(this).attr('value');
			songIDs = jQuery.grep( songIDs, function( n ) {
			  return n !== removeItem;
			});
			count--;
			runtime = parseInt(runtime) - parseInt(this.name);}
		  }

		  hours = parseInt( runtime / 3600 ) % 24;
		  minutes = parseInt( runtime / 60 ) % 60;
		  seconds = runtime % 60;
		  runtime_formatted = (hours < 10 && hours != 0 ? "0" + hours : hours) + "h " + (minutes < 10 ? "0" + minutes : minutes) + "m " + (seconds  < 10 ? "0" + seconds : seconds) +"s";
		  jQuery('#playlist-length').replaceWith('<span id="playlist-length">' + runtime_formatted + '</span>');
		  jQuery('#song-count').replaceWith('<span id="song-count">' + count + '</span>');
		});
	  });
	  if (flag) {

		hours = parseInt( runtime / 3600 ) % 24;
		minutes = parseInt( runtime / 60 ) % 60;
		seconds = runtime % 60;
		runtime_formatted = (hours < 10 && hours != 0 ? "0" + hours : hours) + "h " + (minutes < 10 ? "0" + minutes : minutes) + "m " + (seconds  < 10 ? "0" + seconds : seconds) +"s";
		jQuery('#playlist-length').replaceWith('<span id="playlist-length">' + runtime_formatted + '</span>');
		jQuery('#song-count').replaceWith('<span id="song-count">' + count + '</span>');
		flag = false;
	  }
	  if (all) {
		jQuery('#select-all-songs').attr('checked', 'checked');
	  } else {
		jQuery('#select-all-songs').removeAttr("checked");
	  }
	},
	error: function(errorThrown){
	  //alert('error');
	  console.log(errorThrown);
	}
  });
}
function generatePDFLink (username, playlistName) {
var pdfdir = jQuery('.pdf_url').attr('value');

    if (playlistName == 'sample') {
	url = pdfdir + 'song-list.pdf'

  } else {
	var filename = encodeURIComponent(playlistName);
	var url = pdfdir + username + '-' + filename + '.pdf';
  }

  return url;
}
function collectArgs() {
  if ( jQuery('#playlist-selection').val() == 'selected') {


	if (songIDs >0) {
	  userArgs['post__in'] = songIDs;
	} else {
	  userArgs['post__in'] = null;
	}
  } else {

	userArgs['playlist_selection'] = jQuery('#playlist-selection').val();
  }
  userArgs['posts_per_page'] = jQuery('#limit-results-selection').val();
  userArgs['offset'] = parseInt( jQuery('#limit-results-selection').val()) * (Math.abs(parseInt(jQuery('#current-page').val())) - 1);
  if (jQuery('#search-by').val() == 'title' && jQuery('#search-term').val() != "") {
	userArgs['s'] = jQuery('#search-term').val();
  } else if (jQuery('#search-term').val() != "") {
	userArgs['search_by'] =  jQuery('#search-by').val();
	userArgs['search_term'] = jQuery('#search-term').val();
  }



  return userArgs;

}
jQuery(document).ready(function() {
    jQuery('.avail-playlist-pdf').on('click',function(){
       var playlist_name =jQuery('#playlist-selection').val();
        if(playlist_name=='selected'){
            playlist_name='-selected';
        }if(playlist_name=='sample'){
            playlist_name='song-list';
        }
        jQuery(setTimeout(function(){deleteSamplePlaylist(playlist_name)}, 60000));
    });


  jQuery('#show-playlists').on('click', function(e){
	//jQuery('body').prepend('<div class="playlists_overlay"></div>');
	jQuery('#playlists').fadeIn(500);
	jQuery('div.playlists_overlay, #playlists a.close').on('click', function(){
	 // jQuery('div.playlists_overlay').remove();
	  jQuery('#playlists').hide();
	});
	e.preventDefault();
  });

    jQuery('#song-nav').on('click','#next-batch',function () {

        currentPage = Math.abs(parseInt(jQuery('#current-page').val()));
        if (currentPage !=  parseInt(jQuery('#total-pages').text())) {
            currentPage++;
        } else {
            currentPage = 1;
        }
        jQuery('#current-page').val(currentPage);
        userArgs = collectArgs();
        querySongs(userArgs);
        if(jQuery('#limit-results-selection option:selected').val()=='10'){
            jQuery('.music_selector_thead_tr').css('width','100%');
            jQuery('.try_me_button').css('right','13px');

        }
    });

    jQuery('#prev-batch').click(function () {
        currentPage = Math.abs(parseInt(jQuery('#current-page').val()));
        if (currentPage != 1) {
            currentPage--;
        } else {
            currentPage = parseInt(jQuery('#total-pages').text());
        }
        if(jQuery('#limit-results-selection option:selected').val()=='10'){
            jQuery('.music_selector_thead_tr').css('width','100%');
            jQuery('.try_me_button').css('right','13px');

        }
        jQuery('#current-page').val(currentPage);
        userArgs = collectArgs();
        querySongs(userArgs);
    });





  jQuery("#music_selector_table").stupidtable();



  userArgs={};
  songIDs = new Array();
  jQuery('#current-page').val(1);

  userArgs = collectArgs();

  querySongs(userArgs);

  jQuery('#search-songs').click(function () {
	jQuery('#current-page').val(1);
	userArgs = collectArgs();
	querySongs(userArgs);
  });

  jQuery('#limit-results').change(function () {
      if(jQuery('#limit-results-selection option:selected').val()=='10'){
          jQuery('.music_selector_thead_tr').css('width','100%');
          jQuery('.try_me_button').css('right','13px');

      }else{
          jQuery('.try_me_button').css('right','30px');
          jQuery('.music_selector_thead_tr').css('width','-moz-calc(100% - 18px)');
          jQuery('.music_selector_thead_tr').css('width','-webkit-calc(100% - 18px)');
          jQuery('.music_selector_thead_tr').css('width','-o-calc(100% - 18px)');
          jQuery('.music_selector_thead_tr').css('width','calc(100% - 18px)');


      }
	jQuery('#current-page').val(1);
	userArgs['playlist_selection'] = jQuery('#playlist-selection').val();
	userArgs['posts_per_page'] = jQuery('#limit-results-selection').val();
	userArgs['offset'] = parseInt( jQuery('#limit-results-selection').val()) * (Math.abs(parseInt(jQuery('#current-page').val())) - 1);

	querySongs(userArgs);
  });

  var initialPlaylistSelect = jQuery('#playlist-selection').val();
  if (initialPlaylistSelect == 'selected' || initialPlaylistSelect == 'full') {
	  jQuery('.avail-playlist-pdf').fadeOut('fast');
	} else {
	  jQuery('.avail-playlist-pdf').css('display', 'inline');

	  if(initialPlaylistSelect == 'sample') {
		var newUrl = generatePDFLink("", initialPlaylistSelect);

		jQuery('.avail-playlist-pdf').attr('title', 'Print Version PDF');
		jQuery('.avail-playlist-pdf').attr('href', newUrl);
	  } else {
		var newUrl = generatePDFLink(username, initialPlaylistSelect);
		jQuery('.avail-playlist-pdf').attr('href', newUrl);
		jQuery('.avail-playlist-pdf').attr('title', initialPlaylistSelect + ' PDF');
	  }
	}

  jQuery('#playlist-selection').change(function () {


	jQuery('#current-page').val(1);
	userArgs['posts_per_page'] = jQuery('#limit-results-selection').val();
	if (this.value == 'selected') {
	  userArgs['playlist_selection'] = this.value;
	  jQuery('.avail-playlist-pdf').fadeIn('fast');
        var newUrl = generatePDFLink("", this.value);
        jQuery('.avail-playlist-pdf').attr('href', newUrl);
	  userArgs['post__in'] = songIDs;
	} else if (this.value == 'full') {
	  userArgs['playlist_selection'] = this.value;
	  jQuery('.avail-playlist-pdf').fadeOut('fast');
	} else {
	  jQuery('.avail-playlist-pdf').css('display', 'inline');
	  if(this.value == 'sample') {
		userArgs['playlist_selection'] = this.value;
		var newUrl = generatePDFLink("", this.value);

		jQuery('.avail-playlist-pdf').attr('title', 'Print Version PDF');
		jQuery('.avail-playlist-pdf').attr('href', newUrl);
	  } else {
		userArgs['playlist_selection'] = this.value;
		var newUrl = generatePDFLink(username, this.value);

          jQuery('.avail-playlist-pdf').attr('href', newUrl);
		jQuery('.avail-playlist-pdf').attr('title', this.value + ' PDF');
	  }
	}
      playlistID = jQuery('#playlist-selection option:selected').val();


      userArgs['playlistID'] = playlistID;










	querySongs(userArgs);
  });



  jQuery('#current-page').bind("enterKey",function(e){
	pageCount = parseInt(jQuery('#total-pages').text());
	currentPage = Math.abs(parseInt(jQuery('#current-page').val()));
	if (currentPage > pageCount) {
	  jQuery('#current-page').val(pageCount);
	} else if (isNaN(currentPage)) {
	  jQuery('#current-page').val(1);
	} else {
	  jQuery('#current-page').val(currentPage);
	}
	userArgs = collectArgs();
	querySongs(userArgs);
  });
  jQuery('#current-page').keyup(function(e){
	if(e.keyCode == 13){
	  jQuery(this).trigger("enterKey");
	}
  });
  jQuery('#search-term').bind("enterKey",function(e){
	jQuery('#current-page').val(1);
	userArgs = collectArgs();
	querySongs(userArgs);
  });
  jQuery('#search-term').keyup(function(e){
	if(e.keyCode == 13){
	  jQuery(this).trigger("enterKey");
	}
  });
  if(jQuery('#search-by').val() == 'song_genre') {
	availableGenres = ( typeof(availableGenres) =="string"  ? JSON.parse(availableGenres) : availableGenres);
	jQuery('#search-term').autocomplete ({
	  source:availableGenres
	});
  }
  jQuery('#search-by').change(function () {
	if(this.value == 'song_genre') {
	  availableGenres = ( typeof(availableGenres) =="string"  ? JSON.parse(availableGenres) : availableGenres);
	  jQuery('#search-term').autocomplete ({
		source:availableGenres
	  });
	} else if (jQuery('#search-term').data('uiAutocomplete')) {
		jQuery('#search-term').autocomplete("destroy");
		jQuery('#search-term').removeData('uiAutocomplete');
	} else {
	  console.log(jQuery('#search-term').data());
	}
  });



  jQuery('#current-selection-q').click(function () {
	jQuery( "#playlist-selection" ).val('selected').trigger('click');



	userArgs['playlist_selection'] = 'selected';
	jQuery('.avail-playlist-pdf').fadeIn('fast');



	userArgs['post__in'] = songIDs;


      userArgs['playlist_selection'] = 'selected';
      var newUrl = generatePDFLink("", 'selected');

      jQuery('.avail-playlist-pdf').attr('title', 'Print Version PDF');
      jQuery('.avail-playlist-pdf').attr('href', newUrl);



	querySongs(userArgs);
  });

  jQuery("input#select-all-songs[type=checkbox]").live('click', function () {
	if (jQuery(this).prop('checked') ) {
	  jQuery(".select-single-song").each(function() {
		if(!jQuery(this).prop('checked')) {

		 if(jQuery.inArray(this.value,songIDs)==-1){
             jQuery(this).parent().parent().addClass('selected-track');
             songIDs.push(jQuery(this).attr('value'));
             count++;
             runtime = parseInt(runtime) + parseInt(jQuery(this).attr('name'));
         }


		}
	  }).attr("checked", "checked");

	} else {
	  jQuery(".select-single-song").each(function() {

          if(jQuery(this).prop('checked')) {

              if(jQuery.inArray(this.value,songIDs)>-1){
                  jQuery(this).parent().parent().removeClass('selected-track');
                  var removeItem = jQuery(this).attr('value');
                  songIDs = jQuery.grep( songIDs, function(n) {
                      return n !== removeItem;
                  });

                  count--;
                  runtime = parseInt(runtime) - parseInt(jQuery(this).attr('name'));
              }


          }
	  }).removeAttr("checked");

	}
	hours = parseInt( runtime / 3600 ) % 24;
	minutes = parseInt( runtime / 60 ) % 60;
	seconds = runtime % 60;
	runtime_formatted = (hours < 10 && hours != 0 ? "0" + hours : hours) + "h " + (minutes < 10 ? "0" + minutes : minutes) + "m " + (seconds  < 10 ? "0" + seconds : seconds) +"s";
	if(count==0){
        runtime_formatted=0;
        runtime=0;

    songIDs=[];
    }



        jQuery('#playlist-length').replaceWith('<span id="playlist-length">' + runtime_formatted + '</span>');


	jQuery('#song-count').replaceWith('<span id="song-count">' + count + '</span>');
  });

  jQuery('#submit-playlist').click(function() {
      var playlistObject = {};


      if(jQuery('input[name=playlist_id]:checked').val() != 'new-playlist') {
          var old_tracks = jQuery('input[name=playlist_id]:checked').parent('div').attr('datasrc');
          var playlistObject = {};
          for (var i = 0; i < songIDs.length; ++i) {
              var song = songIDs[i];
              if (old_tracks.indexOf(song) >= 0) {

              } else {
                  playlistObject[i] = songIDs[i];
              }

          }
      }else{
          for (var i = 0; i < songIDs.length; ++i) {


                  playlistObject[i] = songIDs[i];


          }


      }


    var song =  jQuery.makeArray( songIDs );
	if (song.length > 0) {
	  playlistID = (jQuery('input[name=playlist_id]:checked').val() == 'new-playlist' ? jQuery("#new-playlist-name").val() : jQuery('input[name=playlist_id]:checked').val());
	  if(jQuery('input[name=playlist_id]:checked').val() != 'new-playlist'){
         var old_tracks =jQuery('input[name=playlist_id]:checked').parent('div').attr('datasrc');
          var count_new_tracks = jQuery.map(playlistObject, function(n, i) { return i; }).length;



          if(old_tracks.indexOf("-@") >= 0){

              var old_tracks=old_tracks.split('-@');

              for (var i = 0; i < old_tracks.length; ++i) {

                  if(old_tracks[i]!=''){


                      playlistObject[i+count_new_tracks] = old_tracks[i];
                  }

              }


          }


      }


        savePlaylist(playlistObject, playlistID);
	} else {
	  alert("You haven't selected any songs!");
	}
  });

        jQuery(".delete-playlist").live('click', function () {
        var playlistObject = {};
        for (var i = 0; i < songIDs.length; ++i) {
            playlistObject[i] = songIDs[i];
        }
        if (jQuery(this).siblings('input[type="radio"]').prop('checked')) {
            playlistID = (jQuery('input[name=playlist_id]:checked').val() == 'new-playlist' ? jQuery("#new-playlist-name").val() : jQuery('input[name=playlist_id]:checked').val());
            if(jQuery('input[name=playlist_id]:checked').val() != 'new-playlist'){
                var old_tracks =jQuery('input[name=playlist_id]:checked').parent('div').attr('datasrc');
                var count_new_tracks = jQuery.map(playlistObject, function(n, i) { return i; }).length;

                if(old_tracks.indexOf("-@") >= 0){

                    var old_tracks=old_tracks.split('-@');

                    for (var i = 0; i < old_tracks.length; ++i) {

                        if(old_tracks[i]!=''){

                            playlistObject[i+count_new_tracks] = old_tracks[i];
                        }

                    }


                }


            }
            deletePlaylist(playlistObject, playlistID);
        } else {
            alert("You haven't selected any playlists!");
        }
    });






  jQuery('#save-playlist-close').click(function() {
	  jQuery('div.playlists_overlay').remove();
	  jQuery('#playlists').hide();
  });



});
