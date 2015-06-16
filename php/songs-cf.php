<?php
    $meta_box['songs'] = array(
        'id' => 'songs-meta-details',
        'title' => 'Song Information',
        'context' => 'normal',
        'priority' => 'high',
        'fields' => array(
            array(
			  'name' => 'Artist:',
                'desc' => 'Enter artist name...',
                'id' => 'artist_name',
                'type' => 'text',
                'default' => ''
            ),
            array(
			  	'name' => 'Length:',
			    'desc' => 'Enter song length (format: h:mm:ss)...',
                'id' => 'song_length',
                'type' => 'text',
                'default' => ''
            ),
            array(
                'name' => 'Genre:',
                'desc' => 'Enter genre...',
                'id' => 'song_genre',
                'type' => 'text',
                'default' => ''
            ),
		 	array(
                'name' => 'Year:',
                'desc' => 'Enter year (format: YYYY)...',
                'id' => 'song_year',
                'type' => 'text',
                'default' => ''
            ),
		  	array(
                'name' => 'Month:',
                'desc' => 'Enter month...',
                'id' => 'song_month',
                'type' => 'text',
                'default' => ''
            ),
            array(
			    'name' => 'Playlist:',
                'desc' => '',
                'id' => 'sample_playlist',
                'type' => 'checkbox',
                'default' => ''
              )
        )
    );

?>