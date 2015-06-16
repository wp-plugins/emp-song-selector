/*
 * Save song to playlist
 * @param int postID
 * @param string value
 */
function playlist(postID, value){
	    jQuery.ajax({
		url: MusicSelectorAjax.ajaxurl,
		data:{
			'action': 'ms_ajax_handler',
			'post_id':postID,
		    'value':value,
			'postNonce': MusicSelectorAjax.postNonce,
			'fn': 'save-to-playlist'
		},
		dataType: 'html',
		success:function(data){
		},
		error: function(errorThrown){
			console.log(errorThrown);
		}
    });
}
jQuery(document).ready(function() {



  jQuery("input.save-to-playlist[type=checkbox]").live('click', function () {


	postID = jQuery(this).val();
	value = "";
	if ( this.checked ) {
	  value = "on";
	}
	playlist(postID, value);
  });

    jQuery('.select_all_sample_songs').on('click',function(){
        if(jQuery(this).prop('checked')){
            jQuery('.save-to-playlist').prop('checked',true);
            jQuery('.select_all_sample_songs').prop('checked',true);

        }else{
            jQuery('.save-to-playlist').prop('checked',false);
            jQuery('.select_all_sample_songs').prop('checked',false);
        }

    });


    jQuery('.button.action').on('click',function(e){
      var bulk_ation=   jQuery(this).siblings('select').val();


        if(bulk_ation=='print_version'){

            var print_version_posts =[];
            //postID=129081;
           // value ='on';
            //playlist(postID, value);
            jQuery("input[name='post[]']").each(function(){


               if(jQuery(this).prop('checked')){
                    postID = jQuery(this).val();

                  /*  value = "";
                    if ( this.checked ) {
                        value = "on";
                    }
                    playlist(postID, value);*/


                   if(jQuery(this).val()!=''){
                       print_version_posts.push(jQuery(this).val());
                   }

               }

            });

          //  e.preventDefault();
           // location.reload();



if(print_version_posts!=''){

    while (print_version_posts.length > 0) {

        chunk_posts = print_version_posts.splice(0,30)

        jQuery.ajax({
            url: MusicSelectorAjax.ajaxurl,
            data:{
                'action': 'ms_ajax_handler',
                'print_version_posts':chunk_posts,

                'postNonce': MusicSelectorAjax.postNonce,
                'fn': 'save-print-version'
            },
            dataType: 'html',
            success:function(data){

                  //location.reload();

            },
            complete: function( data ) {
                location.reload();
            },
            error: function(errorThrown){
                console.log(errorThrown);
            }
        });


    }
    e.preventDefault();
   // location.reload();

    return false;
}



        }


        if(bulk_ation=='un_print_version'){
            var print_version_posts =[];

            jQuery("input[name='post[]']").each(function(){
                if(jQuery(this).prop('checked')){
                    if(jQuery(this).val()!=''){
                        print_version_posts.push(jQuery(this).val());
                    }

                }

            });
            if(print_version_posts!=''){



                while (print_version_posts.length > 0) {

                    chunk_posts = print_version_posts.splice(0,30)

                    jQuery.ajax({
                        url: MusicSelectorAjax.ajaxurl,
                        data:{
                            'action': 'ms_ajax_handler',
                            'un_print_version_posts':chunk_posts,

                            'postNonce': MusicSelectorAjax.postNonce,
                            'fn': 'un-print-version'
                        },
                        dataType: 'html',
                        success:function(data){

                            // location.reload();

                        },
                        complete: function( data ) {
                            location.reload();
                        },
                        error: function(errorThrown){
                            console.log(errorThrown);
                        }
                    });


                }
                e.preventDefault();
               // location.reload();

                return false;
            }



        }

    });

    if(jQuery('.post_type_page').val()=='songs'){
        jQuery('.search-box').remove();
    }


});

