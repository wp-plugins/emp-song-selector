<?php
function import_page () {
global $zc_ms;
$all_stats = $zc_ms->get_plugin_option( 'stats' );
$last_update = $all_stats['last_update_stats'];
$last_date = $last_update['date'];
$last_count = $last_update['count'];
$last_filename = $last_update['filename'];

$args = array('post_type' => 'songs','post_status' => 'publish',  'fields' => 'ids', 'posts_per_page' => -1 );//, 'offset' => $x * 1000);
$query = new WP_Query($args);
if( $query->have_posts() ) {
    $total_songs = $query->post_count;
}

?>
<div class="wrap" xmlns="http://www.w3.org/1999/html" xmlns="http://www.w3.org/1999/html">
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
fileupload_process();
} else if (isset($_POST['resetsongs'])) {
?><div>Grab a cup of coffee, as this may take a while.<BR />
<?php

$a = $total_songs;


$b = 2000;

$c =ceil($a/$b);






// reset_songs_posts($all_stats['total_song_count']);
for($i=0;$i<$c;$i++){
    reset_songs_posts($b);
}

} else {
    ?>
    <div>
    <div id="stats">
        <div><b>Last update: </b><?php echo  $last_date; ?></div>
        <div><b>Filename:</b> <?php echo  $last_filename; ?></div>
        <div><b># of Songs imported:</b> <?php echo  $last_count; ?></div>
    </div><BR />
    <?php
    fileupload( '<b>CSV Music Import:</b>', $all_stats);
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
        <span><i><b>CSV Format:</b> Title, Artist, Length, Genre, Year, Month, Sample Playlist (on)</i></span>
        <p> <span><i style="color: red">Note: Limit upload to 5000 songs per 'Import' to reduce chance of errors</i></span></p>
        <p> <span><i>Download MP3 Tag Tools  <a target="_blank" href="http://www.mp3tag.de/en/download.html">http://www.mp3tag.de/en/download.html</a></i></span></p>
    </td>
    <td><div>&nbsp;</div></td>
    <td><b>Delete All Songs:</b></td>
    <td>
        <form name="resetsongs" id="resetsongs_form" method="POST" enctype="multipart/form-data" action="<?php echo $_SERVER['REQUEST_URI'].'#resetsongs'; ?>" accept-charset="utf-8" >
            <input class="button-secondary" type="submit" name="resetsongs" id="resetsongs_btn" value="Reset Music Library"  />
        </form>
    </td>
    </tr>  <BR />
    <div id="status">
        <div><i>Processing your request...</i></div>
        <div><i>Grab a cup of coffee, as this may take a while.</i></div>
        <div id="loader"></div>
    </div><?php
}
/**
 * Handle file uploads
 *
 * @todo check nonces
 * @todo check file size
 *
 * @return none
 */
function fileupload_process($all_stats = false) {
    //ini_set('memory_limit', '64M');
    set_time_limit(0);
    $uploadfiles = $_FILES['uploadfiles'];

    if (is_array($uploadfiles)) {
        foreach ($uploadfiles['name'] as $key => $value) {
            // look only for uploded files
            if ($uploadfiles['error'][$key] == 0) {
                $filetmp = $uploadfiles['tmp_name'][$key];
                if (($handle = fopen($filetmp, "r")) !== FALSE) {
                    $num=  count(file($filetmp));
                    //update_option( $option, $new_value );
                    $flag = true;
                    //$songs = explode("\n",file_get_contents($filetmp));
                    //$count = count( $songs );
                    $count = 0;
                    //unset($songs);
                    //$time = $count / 6;
                    // echo "Total item count: " . $count . "<BR /> Estimated time required to complete this task: " . sprintf('%02dh %02dm %02ds', ($time/3600),($time/60%60), $time%60) . "<BR /><BR />";
                    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                        if($flag) { $flag = false; echo "<BR />"; $count--; continue; }

                        if(isset($total_songs)){
                            if($count+$total_songs<=20000){
                                $currently_processed = process_custom_post($data,$count,$num);
                                $count++;
                            }
                        }else{
                            if($count<=20000){
                                $currently_processed = process_custom_post($data,$count,$num);
                                $count++;
                            }

                        }


                    }
                    echo "<span style='float: left;width: 1000px;text-align: center;margin-top: 10px;'>Done!</span><br> <a style='  display: inline-block;
  width: 100%;' href=".$_SERVER['REQUEST_URI'].">Import More</a>";
                    $args = array($uploadfiles['name'][0], date('r'), $count);
                    $updated = stats_update($args);
                    fclose($handle);
                }
                unlink($filetmp); // delete the temp csv file
            }
        }
    }
} // END: file_upload_process()
function stats_update($args = false, $all_stats = false) {

    global $zc_ms;
    if( is_array($args) ) {
        $count = $args[2];
        $stats = array(
            'filename' => $args[0],
            'date' => $args[1],
            'count' => $count
        );
        $updated = $zc_ms->update_sub_option('stats', 'last_update_stats', $stats);
        $total_count = (int)$count + (int)$all_stats['total_song_count'];
    } else if( $args === false) {
        $total_count = 0;
    }
    $updated = $zc_ms->update_sub_option('stats', 'total_song_count', $total_count);
}

function loader(){
    echo '<div id="upload_loader" style="width: 1000px;
  border: 1px solid #0074A2;
  position: relative;
  float: left;
  height: 30px;
  line-height: 30px;
  border-radius: 21px;"><span style="height: 30px;
  display: block;
  width: 0px;
  background-color: #7AD03A;
  border-radius: 21px;text-align: center;"><span class="reduced_process" style="position: absolute;
        left: 0;
        width: 1000px;
        right: 0;"><span></span></div>';
}




function process_custom_post($song,$count=1,$num=1) {
    if($count=='-1'){
        loader();
    }

    $track =  (array_key_exists(0, $song) && $song[0] != "" ?  $song[0] : 'N/A');
    $artist = (array_key_exists(1, $song) && $song[1] != ""  ?  $song[1] : 'N/A');
    $length = (array_key_exists(2, $song) && $song[2] != ""  ?  $song[2] : 'N/A');
    $genre = (array_key_exists(3, $song) && $song[3] != ""  ?  $song[3] : 'N/A');
    $year = (array_key_exists(4, $song) && $song[4] != ""  ?  $song[4] : 'N/A');
    $month = (array_key_exists(5, $song) && $song[5] != ""  ?  $song[5] : 'N/A');
    $playlist = (array_key_exists(6, $song) && $song[6] != ""  ?  $song[6] : '');
    $custom_post = array();
    $custom_post['post_type'] = 'songs';
    $custom_post['post_status'] = 'publish';
    $custom_post['post_title'] = $track;
    //echo "Importing " . $artist  . " - " . $track . " <i> (" . $count ." items remaining)...</i><BR />";
    $post_id = wp_insert_post( $custom_post );
    $updated = update_post_meta($post_id, 'artist_name', $artist);
    $updated = update_post_meta($post_id, 'song_length', $length);
    $updated = update_post_meta($post_id, 'song_genre', $genre);
    $updated = update_post_meta($post_id, 'song_year', $year);
    $updated = update_post_meta($post_id, 'song_month', $month);
    $updated = update_post_meta($post_id, 'sample_playlist', $playlist);?>
    <script>
        var width = jQuery('#upload_loader span').attr('width');
        var count = '<?php echo $count+2; ?>';
        var count_all_songs = '<?php echo $num; ?>';
        var percent = (count*100)/count_all_songs;
        var perc_to_width =percent*10;
        var percent_show=    percent.toFixed(2);
        if(percent_show>99.8){
            percent_show =100;
        }
        jQuery('#upload_loader span').css('width',perc_to_width+'px');
        jQuery('#upload_loader span.reduced_process').css('width','1000px');
        jQuery('#upload_loader span.reduced_process').html('<b>Number of songs importing '+count_all_songs+' ('+percent_show+'%)</b>')

    </script>
    <?

    return true;
}
function reset_songs_posts($total_song_count) {

    $current_user = wp_get_current_user();
    $playlists= get_user_meta($current_user->ID, 'ms_playlists', true);

    if(!empty($playlists)){
        $pdf_url=get_user_meta($current_user->ID, 'ms_playlist_pdf_url', true);
        $result =update_user_meta($current_user->ID, 'ms_playlist_pdf_url', '');
        $url=str_replace(rtrim(get_site_url(),'/').'/', ABSPATH, $pdf_url);
        @unlink($url);
        update_user_meta($current_user->ID, 'ms_playlists', '');



    }


    set_time_limit(0);
    $count = 0;
    // while($total_song_count != 'done') {

    $my_query = new WP_Query(array( 'post_type' => 'songs', 'posts_per_page' => 2000));
    //  if( $my_query->have_posts() ) {
    while ($my_query->have_posts()) : $my_query->the_post();
        $current_ID = get_the_ID();
        echo "Deleting song ID: ". $current_ID . " <i>(Item #" . $count .")...</i><BR />";
        $currently_deleted = wp_delete_post($current_ID , true);
        $count++;
    endwhile;


    wp_reset_query();
    $total_song_count--;
    /* }else {
         $total_song_count = 'done';
     }*/
    //  }

    echo "Done!";?>

   <?php stats_update();
}
?>
