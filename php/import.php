<?php
if (!class_exists('ZC_MS_Import')) {
  class ZC_MS_Import {
	function __construct() {
	}
	function import_page () {
	  global $zc_ms;
	  _log($_FILES);
	  $all_stats = $zc_ms->get_plugin_option( 'stats' );


	  $last_update = $all_stats['last_update_stats'];
	  $last_date = $last_update['date'];
	  $last_count = $last_update['count'];
	  $last_filename = $last_update['filename'];
		?>
		<div class="wrap">
		  <div id="overlay" class="hidden"></div>
		  <div id="icon-edit" class="icon32 icon32-posts-songs">
		  </div>
		  <h2>Import Songs
		  </h2>
        </div><BR />
		<?php
		 if (isset($_POST['uploadfile'])) {
				?><div>Grab a cup of coffee, as this may take a while.<BR />
		<?php
				$this->fileupload_process();
		} else if (isset($_POST['resetsongs'])) {
			?><div>Grab a cup of coffee, as this may take a while.<BR />
		<?php
				$this->reset_songs_posts();
		} else {
	  	?>
		<div>
		  <div id="stats">

			<div>Last update: <?php echo  $last_date; ?></div>
			<div>Filename: <?php echo  $last_filename; ?></div>
			<div># of songs imported: <?php echo  $last_count; ?></div>
		  </div><BR />
		<?php
		   $this->fileupload( 'CSV Music Import:');
		  ?></div><?php
		 }
	}
	/**
	* Form builder helper
	*
	* @param string $label Field label
	* @return none
	*/
	function fileupload( $label ) {
		?><tr>
		<td class="left_label"> <?php
			echo $label; ?>
		</td>
		<td>
		  <form name="uploadfile" id="uploadfile_form" method="POST" enctype="multipart/form-data" action="<?php echo $_SERVER['REQUEST_URI'].'#uploadfile'; ?>" accept-charset="utf-8" >
			<input type="file" name="uploadfiles[]" id="uploadfiles" size="35" class="uploadfiles" />
			<input class="button-primary" type="submit" name="uploadfile" id="uploadfile_btn" value="Upload"  />
		  </form>
		  <span><i>CSV Format: Title, Artist, Length, Genre, Year, Month</i></span>

		</td>
<td><div>&nbsp;</div></td>
		<td>Delete All Songs:</td>
		<td>
		  <form name="resetsongs" id="resetsongs_form" method="POST" enctype="multipart/form-data" action="<?php echo $_SERVER['REQUEST_URI'].'#resetsongs'; ?>" accept-charset="utf-8" >
			<input class="button-secondary" type="submit" name="resetsongs" id="resetsongs_btn" value="Reset Music Library"  />
		  </form>
		</td>
	  </tr>  <?php
	}
	/**
	* Handle file uploads
	*
	* @todo check nonces
	* @todo check file size
	*
	* @return none
	*/
	function fileupload_process() {
	  $uploadfiles = $_FILES['uploadfiles'];

	  if (is_array($uploadfiles)) {
		foreach ($uploadfiles['name'] as $key => $value) {
		  // look only for uploded files
		  if ($uploadfiles['error'][$key] == 0) {
			$filetmp = $uploadfiles['tmp_name'][$key];
			set_time_limit(0);
			if (($handle = fopen($filetmp, "r")) !== FALSE) {
			
			  //update_option( $option, $new_value ); 
			  $flag = true;
			  $songs = explode("\n",file_get_contents($filetmp));
			  $count = count( $songs );
			  $args = array($filetemp, date('r'), $count);
			  $this->stats_update($args);
			  unset($songs);
			  $time = $count / 6;
			  echo "Total item count: " . $count . "<BR /> Estimated time required to complete this task: " . sprintf('%02dh %02dm %02ds', ($time/3600),($time/60%60), $time%60) . "<BR /><BR />";
			   while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
					if($flag) { $flag = false; echo "<BR />"; $count--; continue; }
					$this->process_custom_post($data, $count);
				 	$count--;
			  }
			  echo "Done!";
			  fclose($handle);
			}
			unlink($filetmp); // delete the temp csv file
		  }
		}
	  }
	} // END: file_upload_process()
	function stats_update($args = false) {
	  global $zc_ms;
	  if( is_array($args) ) {
		$count = $args[2];
	   	$stats = array(
				'filename' => $args[0],
		  		'date' => $args[1],
				'count' => $count
				);
		$zc_ms->update_sub_option('stats', 'last_update_stats', $stats);
	  	$total_count = (int)$count + (int)$all_stats['total_song_count'];
	  } else if( $args === false) {
		$total_count = 0;
	  }
	  $zc_ms->update_sub_option('stats', 'total_song_count', $total_count);
	}	
	function process_custom_post($song, $count) {
				$track =  (array_key_exists(0, $song) && $song[0] != "" ?  $song[0] : 'N/A');
				$artist = (array_key_exists(1, $song) && $song[1] != ""  ?  $song[1] : 'N/A');
				$length = (array_key_exists(2, $song) && $song[2] != ""  ?  $song[2] : 'N/A');
				$genre = (array_key_exists(3, $song) && $song[3] != ""  ?  $song[3] : 'N/A');
				$year = (array_key_exists(4, $song) && $song[4] != ""  ?  $song[4] : 'N/A');
	  			$month = (array_key_exists(5, $song) && $song[5] != ""  ?  $song[5] : 'N/A');
				$custom_post = array();
				$custom_post['post_type'] = 'songs';
				$custom_post['post_status'] = 'publish';
				$custom_post['post_title'] = $track;
				echo "Importing " . $artist  . " - " . $track . " <i> (" . $count ." items remaining)...</i><BR />";
				$post_id = wp_insert_post( $custom_post );
				update_post_meta($post_id, 'artist_name', $artist);
				update_post_meta($post_id, 'song_length', $length);
				update_post_meta($post_id, 'song_genre', $genre);
				update_post_meta($post_id, 'song_year', $year);
	  			update_post_meta($post_id, 'song_month', $month);
	}
	function reset_songs_posts() {
	  set_time_limit(0);
	  $current_post_count = 0;
	  $args = array('post_type' => 'songs','post_status' => 'publish',  'fields' => 'ids', 'posts_per_page' => -1 );
	  $query = new WP_Query($args);
	  if( $query->have_posts() ) {
		$total = $query->post_count;
		while( $current_post_count !== $total ) {
		  $current_ID = $query->posts[$current_post_count];
		  $current_post_count++;
		  echo "Deleting song ID: ". $current_ID . " <i>(Item #" . $count .")...</i><BR />";
		  wp_delete_post($current_ID , true);
		}
		echo "Done!";
	  }
	  /*
	  $my_query = new WP_Query(array( 'post_type' => 'songs', 'posts_per_page' => -1));
	  $total = $my_query->post_count;
	  $time = $total / 6;
	  if ($my_query !=null) { echo "Total item count: ". $total . "<BR /> Estimated time required to complete this task: " . sprintf('%02dh %02dm %02ds', ($time/3600),($time/60%60), $time%60) . "<BR /><BR />";}
	  $count = 1;
	  if( $my_query->have_posts() ) {
  			while ($my_query->have_posts()) : $my_query->the_post();
		  		$current_ID = get_the_ID();
				echo "Deleting song ID: ". $current_ID . " <i>(Item #" . $count .")...</i><BR />";
				wp_delete_post($current_ID , true);
				$count++;
			endwhile;
			echo "Done!";
			wp_reset_query();
			} */
	  $this->stats_update();
   }
  } // END: class
}

?>
