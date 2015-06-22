<?php global $zc_ms;

$current_user = wp_get_current_user();
$all_stats = $zc_ms->get_plugin_option( 'stats' );
$total_song_count = $all_stats['total_song_count'];
$available_genres = $all_stats['available_genres'];
$playlist_count = $all_stats['sample_playlist_count'];
$all_settings = $zc_ms->get_plugin_option( 'settings' );
$page_width = $all_settings['page_width'];
$show_powered_by= $all_settings['show_powered_by'];



?>

<div  class=" <?php if($page_width==0){ echo "full_screen_width";} ?>" style="margin:auto;position:relative; <?php if(!empty($page_width) && $page_width>=600){
    echo "width:".$page_width.'px';
}elseif($page_width=='0'){
    echo "width:100%";

} ?>">
<?php $wp_upload = wp_upload_dir();?>
<input type="hidden" class="pdf_url" value="<?=$wp_upload['baseurl'] . '/EMP/'?>">
<?php
if ( 0 == $current_user->ID ) {
    $user_playlists = get_user_meta($current_user->ID, 'ms_playlists');
    $wp_upload = wp_upload_dir();
    $upload_dir = $wp_upload['baseurl'] . '/EMP/';?><script>var userPlaylists = '<?php echo json_encode($user_playlists); ?>';
        var availableGenres = '<?php echo json_encode($available_genres); ?>';
        var username = '<?php echo $current_user->user_login; ?>';
        var pdfdir = "<?php echo $upload_dir ?>";
    </script><?php


} else {

    $wp_upload = wp_upload_dir();
    $upload_dir = $wp_upload['baseurl'] . '/EMP/';?><script>var userPlaylists = '<?php echo json_encode($user_playlists); ?>';
        var availableGenres = '<?php echo json_encode($available_genres); ?>';
        var username = '<?php echo $current_user->user_login; ?>';
        var pdfdir = "<?php echo $upload_dir ?>";
    </script><?php
}

?>




<div id="master-playlist">
    <span class="playlist_loading" datasrc="<?=plugins_url('emp-song-selector/images/loading.gif')?>" style="  position: absolute;
 "></span>
    <table id="music_selector_table" >
        <thead class="music_selector_table_head">
        <tr class="music_selector_tr">
            <div id="filter-menu"><div style="display: block;float:left;padding-top: 8px;color: #666;"><b>Search By</div></b><select id="search-by" >
                    <option value='title'>Title</option>
                    <option value='artist_name'>Artist</option>
                    <option value='song_genre'>Genre</option>
                    <option value='song_year'>Year</option>
                </select><span  style="float: left;display: block;margin-top: 6px">:</span><input name="search-term" id="search-term"/><input type="button" name="search-songs" id="search-songs" class="" value="Search" /><div id="limit-results"><b style="padding-top: 8px;
  display: block;
  float: left;">Per Page</b><select id="limit-results-selection">
                        <option value=10>10</option>
                        <option value=25>25</option>
                        <option value=50 selected="selected">50</option>
                        <option value=100>100</option>
                    </select>
                </div>
            </div>
        </tr>
        <tr>

            <div class="music_selector_tfoot_item">
                <?php if ( 0 !== $current_user->ID ) {
                    $count_pages = round($playlist_count/50);
                } else {
                    $count_pages = round($total_song_count/50);
                }

                ?>

            </div>

            <div id="the_action_buttons" class="music_selector_tfoot_item">


                <select id="playlist-selection">

                    <?php
                    $args=array();
                    $args['playlistID']='sample';
                    $args['posts_per_page']='-1';
                    $args['offset']='0';
                    $args['playlist_selection']='sample';
                    $unsanitized_args = $args;
                    $current_user = wp_get_current_user();
                    $sanitized_data = $this->sanitize_query($unsanitized_args);
                    $args = $sanitized_data['args'];
                    $query_songs = new WP_Query($args);
                    $query_count_sample = $query_songs->found_posts;
                    ?>

                    <option id="pdf-0" value="sample" <?php if ( 0 === $current_user->ID ) { echo " selected='selected'";}?>>Print Version (Total: <?php  echo $query_count_sample; ?>)</option>


                    <option value="selected">Current Selection</option>
                </select>


                    <a href="<?php echo $upload_dir . 'song-list.pdf'; ?>" class="avail-playlist-pdf icon" id='sample' target="_blank" title="" style="<?php if(@fopen($upload_dir . 'song-list.pdf', "r")){echo "display:block; ";}else{"display:none; ";} ?>"> <span class="pdf_text">PRINT</span></a>







            </div>
        </tr>
        <tr class="music_selector_thead_tr">
            <th class="music_selector_thead_th song-select"><input type="checkbox" id="select-all-songs"  value=1 /></th>
            <th class="music_selector_thead_th song-title" data-sort="string"><a href="javascript:void(0) ">TITLE </a> - <a href="javascript:void(0) ">ARTIST</a></th>
            <!-- <th class="music_selector_thead_th song-artist" data-sort="string"><a href="javascript:void(0) ">ARTIST</a></th>-->
            <th class="music_selector_thead_th song-genre" data-sort="string"><a href="javascript:void(0) ">GENRE</a></th>
            <th class="music_selector_thead_th song-year" data-sort="int"><a href="javascript:void(0) ">YEAR</a></th>
            <th class="music_selector_thead_th song-length" data-sort="int"><a href="javascript:void(0) ">LENGTH</a></th>

        </tr>
        </thead>
        <tfoot class="music_selector_tfoot">

        <tr class="music_selector_tfoot_tr" >

            <td  class="music_selector_tfoot_td">


                <div class="music_selector_tfoot_item">
                    <a href="javascript:void(0)"><input type="button" id="current-selection-q" class="submit" value="Show Selection" /></a>
                </div>



                <div id="song-nav">
                    <div id="song-pagination">
                        <a href="javascript:void(0) "><span id="prev-batch"></span></a>
                        <input type="text" id="current-page" value="1"  /> /
                        <span id="total-pages"> <?php echo $count_pages; ?> </span>
                        <a href="javascript:void(0) "><span id="next-batch"></span></a>
                    </div>

                </div>

            </td>
        </tr>

        </tfoot>
        <tbody id="music_selector_results">

        </tbody>

    </table>

</div>
<div id="stats-window">
    <div class="user-playlist-stats">
        <div>Songs Selected: <span id="song-count"> 0</span></div>
        <div>Playlist Length: <span id="playlist-length"> 0h 00m 00s</span></div>
    </div>
    <a href="#" target="_blank"><div id="playlist-pdf"></div></a>
   <?php if($show_powered_by=='1'){ ?>
    <div id="poweredby">Powered By <a href="http://www.eventmasterpro.com/wp-plugins" target="_blank">Event Master Pro</a></div>
    <?php } ?>
</div>
</div>
