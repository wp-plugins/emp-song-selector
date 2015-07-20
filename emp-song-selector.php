<?php
defined( 'ABSPATH' ) OR exit;
/*
Plugin Name: EMP Song Selector Module (Lite Version)
Plugin URI:http://www.eventmasterpro.com/wp-plugins
Description: Song Selector (Lite Version)
Version: 2.1.3
Author: Event Master Pro
Author URI:http://www.eventmasterpro.com/wp-plugins
License:GPLv2
*/

global $wp;
$wp_upload_dir = wp_upload_dir();
// Define constants
define( 'ZC_MS_Version' , 0 );
define( 'ZC_MS_ROOT' , dirname(__FILE__) );
define( 'ZC_MS_FILE_PATH' , ZC_MS_ROOT . '/' . basename(__FILE__) );
define( 'ZC_MS_URL' , plugins_url(plugin_basename(dirname(__FILE__)).'/') );
define( 'ZC_MS_SETTINGS_PAGE' , 'edit.php?post_type=songs' );
define( 'ZC_MS_UPLOAD_DIR' , $wp_upload_dir['basedir'] .'/EMP/' );
define( 'ZC_MS_UPLOAD_URL' , $wp_upload_dir['baseurl'] .'/EMP/' );


$plugin = plugin_basename(__FILE__);
register_uninstall_hook( __FILE__, array( 'zc_ms', 'uninstall_plugin' ) );
$plugin_slug = basename(dirname(__FILE__));
include_once( ZC_MS_ROOT . '/php/music-sel-import.php' );
// Core class
if (!class_exists('zc_ms')) {
    class zc_ms{
        // Unique identifier added as a prefix to all of the options
        var $options_group = 'zc_ms_';
        // The plugin options
        var $plugin_options = array(
            "version" => 0,
            "stats" => array(
                'total_song_count' => 0,
                'sample_playlist_count' => 0,
                'master_playlist_url' => '',
                'sample_playlist_url' => '',
                'available_genres' => array(),
                'last_update_stats' => array(
                    'date' => '',
                    'count' => '',
                    'filename' => ''
                )
            ),
            "settings" =>array(
                'branding'=> array(
                    'logo_url' => '',

                    'headline_font_size' => '14px',
                    'headline_font_color' => '#000000',
                ),

                'user_role' => 'Subscriber',
                'page_width'=>'600',
                'show_powered_by'=>'1'

            ),


        );
        /*
        * Initialize the core of the plugin
        */
        function __construct() {
            global $wpdb;

            $this->load_options();
            add_action('init', array(&$this,'register_songs_cpt'), 1);
            // Load the settings page in admin area
            if(is_admin()) {
                add_filter("plugin_action_links", array(&$this, 'zc_ms_settings_link'), 10, 2 );
                add_action( 'admin_init', array( &$this, 'admin_init' ) );
                add_action('wp_ajax_ms_ajax_handler', array(&$this, 'ms_ajax_handler'));
                add_action( 'init', array( &$this, 'admin_menu_init' ) );
                add_action('admin_enqueue_scripts', array(&$this, 'add_admin_scripts'));
                add_filter( 'attribute_escape', array(&$this, 'rename_second_menu_name'), 10, 2 );
                add_action( 'init', array( &$this, 'setup_frontend_ajax' ) );

            } else if(!is_admin()){
                add_shortcode( 'song-selector', array( &$this, 'setup_frontend' ) );
                remove_filter( 'the_content', 'wpautop' );
                add_filter( 'the_content', 'wpautop' , 12);

            }

        }

        public function fe_ajax(){
            $nonce = $_REQUEST['postNonce'];



            if ( ! wp_verify_nonce( $nonce, 'fe-ajax-post-nonce' ) ){
                die ( 'Busted!');
            }
            switch($_REQUEST['fn']){
                case 'query':
                    $unsanitized_args = $_REQUEST['args'];
                    $current_user = wp_get_current_user();
                    $sanitized_data = $this->sanitize_query($unsanitized_args);

                    $args = $sanitized_data['args'];

                    $search_by = $sanitized_data['search_by'];
                    $search_term = $sanitized_data['search_term'];
                    $selected_playlist = $sanitized_data['selected_playlist'];

                    $args['order']='ASC';
                    $args['orderby']=$search_by;
                    $args['search_term']=$search_term;
                    if($args['meta_query'][0]['key']=='sample_playlist'){
                        $args['playlistID'] ='sample';
                    }



                    $playlist_id = $args['playlistID'];
                    $upload_dir = wp_upload_dir();
                    $upload_basedir = $upload_dir['basedir'];
                    $html ="<tbody id='music_selector_results'>";
                    $query_songs = new WP_Query($args);
                    $query_count = $query_songs->found_posts;




                    if( $query_songs->have_posts()) {

                        $html .= $this->generate_html($query_songs, false);


                    } else {
                        if ($search_term == 'empty')
                            $html .= '<tr class="music_selector_tr"><td class="music_selector_td"><center><i>' . $search_by . '</i></center></td></tr>';
                        else
                            $html .= '<tr class="music_selector_tr"><td class="music_selector_td"><center><i><b>' . $search_term . ' </b>was not found while searching by<b> ' . $search_by . '</b></i> in <u>' . $selected_playlist . '</u></center></td></tr>';
                    }

                    $filename = ($playlist_id == 'sample' ? 'song-list.pdf' : $current_user->user_login . '-' . $playlist_id . '.pdf');

                    if(($playlist_id=='sample' && $search_term == 'empty') ||  $unsanitized_args['playlist_selection']=='selected'){
                      //  print_r($args);

                        $wp_upload = wp_upload_dir();
                       if(!empty($unsanitized_args['post__in']) || $unsanitized_args['playlist_selection']=='selected'){


                           $file_url = $wp_upload['baseurl'] . '/EMP/-selected.pdf';
                           $playlist_id ='selected';

                       }else{
                           $file_url = $wp_upload['baseurl'] . '/EMP/song-list.pdf';
                       }
                        if(@fopen($file_url, "r") && $playlist_id=='sample'){

                        }else{


                            $args['posts_per_page']='-1';
                            $html1 ="";
                            //_log($args);
                            $query_songs = new WP_Query($args);
                            $query_count = $query_songs->found_posts;

                            if( $query_songs->have_posts() ) {

                                $html1 .= $this->generate_html($query_songs, true);
                            }
                            $this->generate_pdf($html1, false, $playlist_id);
                        }

                    }


                    $output = json_encode(array('html' =>$html, 'count' => $query_count));
                    break;
                case 'save-playlist':
                    $current_user = wp_get_current_user();
                    $output = false;

                    if (isset( $_REQUEST['playlist'])) {

                        $unsanitized_playlist = $_REQUEST['playlist'];
                        $unsanitized_playlist_id = $_REQUEST['playlist_id'];
                        $playlist = $this->sanitize_playlist($unsanitized_playlist);
                        $playlist_id = sanitize_text_field($unsanitized_playlist_id);
                        $playlists = get_user_meta($current_user->ID, 'ms_playlists', true);
                        $playlists[$playlist_id] = $playlist;
                        $result = update_user_meta($current_user->ID, 'ms_playlists', $playlists);
                        $args = array('post_type' => 'songs','posts_per_page' =>-1, 'post__in'=>$playlist);
                        $query = new WP_Query($args);
                        $html = $this->generate_html($query,true);


                        $pdf_url = $this->generate_pdf($html, $current_user, $playlist_id);



                        $result =update_user_meta($current_user->ID, 'ms_playlist_pdf_url', $pdf_url);
                    }
                    $output = $pdf_url;
                    break;
                case 'delete-playlist':
                    $current_user = wp_get_current_user();
                    $output = false;
                    if (isset( $_REQUEST['playlist'])) {

                        $unsanitized_playlist = $_REQUEST['playlist'];

                        $unsanitized_playlist_id = $_REQUEST['playlist_id'];

                        $playlist = $this->sanitize_playlist($unsanitized_playlist);
                        $playlist_id = sanitize_text_field($unsanitized_playlist_id);
                        $playlists = get_user_meta($current_user->ID, 'ms_playlists', true);



                        $playlists[$playlist_id] = $playlist;
                        if(count($playlists)=='1'){
                            $result = delete_user_meta($current_user->ID,'ms_playlists' );
                        }else{
                            unset($playlists[$playlist_id]);
                            $result = update_user_meta($current_user->ID, 'ms_playlists', $playlists);
                        }

                        $args = array('post_type' => 'songs','posts_per_page' =>-1, 'post__in'=>$playlist);
                        $query = new WP_Query($args);

                        $html = $this->generate_html($query);
                        $pdf_url = $this->generate_pdf($html, $current_user, $playlist_id);
                        if(count($playlists)=='1'){
                            if(is_file($pdf_url)){

                                $url=str_replace(rtrim(get_site_url(),'/').'/', ABSPATH, $pdf_url);
                                @unlink($url);

                            }
                            $result = delete_user_meta($current_user->ID,'ms_playlist_pdf_url', $pdf_url );
                        }else{
                            $result =update_user_meta($current_user->ID, 'ms_playlist_pdf_url', $pdf_url);
                        }
                        $url=str_replace(rtrim(get_site_url(),'/').'/', ABSPATH, $pdf_url);
                        @unlink($url);
                    }
                    $output = $pdf_url;

                    break;
                case 'delete-sample-playlist':

                    if (isset( $_REQUEST['playlist_id'])) {
                        $wp_upload = wp_upload_dir();
                        $playlist_id = $_REQUEST['playlist_id'];
                        $file_url = $wp_upload['baseurl'] . '/EMP/'.$playlist_id.'.pdf';

                        $url=str_replace(rtrim(get_site_url(),'/').'/', ABSPATH, $file_url);
                        if(@fopen($file_url, "r")){

                            @unlink($url);
                        }

                    }



                    break;
                default:
                    $output = 'No function specified, check your jQuery.ajax() call';
                    break;
            }
            echo $output;
            die;
        }

        function sanitize_playlist($unsanitized_playlist) {
            if(is_array($unsanitized_playlist) || is_object($unsanitized_playlist)) {
                foreach($unsanitized_playlist as $ukey => $uvalue) {
                    $skey = esc_attr($ukey);
                    $svalue = filter_var($uvalue, FILTER_SANITIZE_NUMBER_INT);
                    $playlist[$skey] = $svalue;
                }
            } else {
                $playlist = filter_var($unsanitized_playlist, FILTER_SANITIZE_NUMBER_INT);
            }
            return $playlist;
        }

        function sanitize_query($user_args) {
            $current_user = wp_get_current_user();

            $args=array(
                'post_status' => 'publish',
                'order' => 'DESC'
            );

            if (isset($user_args['search_by'])){

                $args['meta_query'][] = array('key' =>$user_args['search_by'], 'value'=> $user_args['search_term']);
                $search_by = str_replace( 'song', '', $user_args['search_by']);
                $search_by = str_replace( '_', ' ',  $search_by);
                $search_term = $user_args['search_term'];
                unset($user_args['search_by']);
                unset($user_args['search_term']);
            } else if(isset($user_args['s'])) {

                $search_by = 'song title';
                $search_term = $user_args['s'];
            } else {

                $search_term = 'empty';
                $search_by = 'Your current playlist is empty';
            }


            foreach ($user_args as $key=> $value) {

                $args[$key] = $value;
            }
            // logged in requesting full playlist
            if (isset($user_args['playlist_selection']) && $user_args['playlist_selection'] == 'full' && $current_user->ID !== 0 ) {

                unset ($args['playlist_selection']);
                if (isset($args['post__in'])) { unset($args['post__in']); }
                $selected_playlist = 'the master playlist';
                // logged in requesting user defined playlist
            } else if ($current_user->ID !== 0 && array_key_exists('playlist_selection', $user_args) && !in_array($user_args['playlist_selection'], array('sample', 'full', 'selected'))) {

                $playlists = get_user_meta($current_user->ID, 'ms_playlists');
                $selected_playlist = $args['playlist_selection'];
                //_log($playlists[0]);
                //_log($args['playlist_selection']);
                $args['post__in'] = $playlists[0][$args['playlist_selection']];
                unset ($args['playlist_selection']);
                // requesting sample playlist
            }else if (isset($user_args['playlist_selection']) && $user_args['playlist_selection'] == 'sample'){

                unset ($args['playlist_selection']);
                $args['meta_query'][] = array('key' =>'sample_playlist', 'value'=> 'on');
                $args['meta_query'][] = array('key' =>$user_args['search_by'], 'value'=> $user_args['search_term']);//added

                $selected_playlist = 'the sample playlist';
            } else if (isset($user_args['playlist_selection']) && $user_args['playlist_selection'] == 'selected'){


                unset ($args['playlist_selection']);
                if(!isset($args['post__in']) || $args['post__in'] == 0) {

                    $args['post__in'] = array(-1);
                }
                if ($current_user->ID === 0) {

                    $args['meta_query'][0] = array('key' =>'sample_playlist', 'value'=> 'on');
                }
                $selected_playlist = 'your current selection';
                // everything else
            } else {
                $args['meta_query'][0] = array('key' =>'sample_playlist', 'value'=> 'on');
                if (!isset($user_args['playlist_selection'])){
                    if(!isset($args['post__in']) || $args['post__in'] == 0) {
                        // _log('reset post__in');
                        $args['post__in'] = array(-1);
                    }
                }
                $selected_playlist = (isset($user_args['playlist_selection']) && $user_args['playlist_selection'] !== 'selected' ? 'the sample playlist': 'your current selection');
            }
            $args['post_type'] = 'songs';
            //_log($search_by);
            if($search_by != " year") {
                $args['meta_query'][0]['compare'] = 'LIKE';
            }

            $data = array(
                'args'=>$args,
                'search_by' =>$search_by,
                'search_term' => $search_term,
                'selected_playlist' => $selected_playlist
            );

            return $data;
        }
        public function array_concat(Array $array1array, Array $array2array) {
            foreach ($array2array as $key => $value) {
                $strip_val =strip_tags($value);
                $newval = "";
                if (isset($array1array[$key])) {
                    $newval = $array1array[$key] ;
                    $strip_newval =strip_tags($newval);
                }
                if(!empty($strip_val) && !empty($strip_newval)){
                    $array1array[$key] =  $value.$newval;
                }elseif(empty($strip_val)){
                    $array1array[$key] =  $newval.'<td class="song-select-single" ></td><td class="song-title"></td>';
                }


            }
            return $array1array;
        }
        public function generate_pdf($songs_html, $current_user = false, $playlist_id = 'sample') {


            $all_settings = $this->get_plugin_option( 'settings' );
            $branding = $all_settings['branding'];

            $font_color = (isset($all_settings['branding']['headline_font_color']) ? $all_settings['branding']['headline_font_color'] : "#000000");
            $font_size = (isset($all_settings['branding']['headline_font_size']) ? $all_settings['branding']['headline_font_size'] : "15px");
            $logo_url =$branding['logo_url'];

            $html = "";
            if($branding['logo_url'] !== "") {
                $html = '<img src="' . $branding['logo_url'] . '" style="display:inline; float:left;" />&nbsp;';
            }



            $all_before_order = json_decode($songs_html);



            $all_before_order = (array)$all_before_order;

            $seconds=  $all_before_order['seconds'] ;
            unset($all_before_order['seconds']);
            $vals = array();
            $ordering_array = array();
            foreach($all_before_order as $key=>$val){
                foreach($val as $t){

                    $vals[$key][] = strip_tags($t);
                }
            }
            foreach($vals as $key=>$v){

                asort($v);
                $ordering_array[$key] = $v;
            }
            $all = array();
            foreach($ordering_array as $key=>$val){
                foreach($val as $k=>$v){
                    $all[$key][]=$all_before_order[$key][$k];//$all_before_order[$key][$k];
                }
            }
            ksort($all);

            $all_ordering =array();

            foreach($all as $key=>$val){

                array_push($all_ordering,'<tr class="music_selector_tr"><td class="song-select-single"> </td><td class="music_selector_td song-title" style="font-weight: bold; text-decoration: underline;">'.$key.'</td></tr>');

                foreach($val as $k){
                    array_push($all_ordering,$k);
                }
            }

            $html_ar = array_chunk($all_ordering, 36, false);

            $placeholders = array('<tr class="music_selector_tr">', '</tr>');

            $vals_1 = array(' ', ' ');
            $all_rows_array = array();
            foreach ($html_ar as $key => $value) {
                foreach($value as $key1=>$v){
                    if(!empty($v)){

                        $k= str_replace($placeholders, $vals_1, $v);
                        $all_rows_array[$key][]=$k;
                    }
                }
            }

            $songs_block = array();
            $count_blocks= count($all_rows_array);

            foreach($all_rows_array as $key=>$value){
                if(($key % 2 != 0 && $count_blocks>1)){

                    array_push($songs_block,$this->array_concat($value,$all_rows_array[$key-1])) ;
                }elseif((count($value)<=36 && $count_blocks=='1') || ($key % 2 == 0 && $key==$count_blocks-1)){
                    $value_array = array();

                    foreach($value as $val){
                        array_push($value_array,$val.'<td class="song-select-single" ></td><td class="song-title"></td>');
                    }
                    array_push($songs_block,$value_array) ;
                }

            }

            $all_rows_tr = array();

            foreach($songs_block as $key=>$block){
                foreach($block as $k=>$val){
                    array_push($all_rows_tr,'<tr  class="music_selector_tr">'.$val.'</tr>');
                }
            }
            $blocks_array = array_chunk($all_rows_tr, 36, false);

            $all_html = array();
            $blocks_count =count($blocks_array);
            foreach($blocks_array as $key=>$block){
                $text ='';
                $html ='';

                foreach($block as $k=>$val){
                    $text .= $val;
                }

                if($playlist_id=='sample'){
                    $playlist_name ='<h3  style="line-height: 69px;height: 69px;margin:0; text-decoration: underline;" id="pdf-headline">Print Version Playlist </h3>';
                }else{
                    $playlist_name ='<h3  style="line-height: 69px;height: 69px;margin:0; text-decoration: underline;" id="pdf-headline">'.$playlist_id.' Playlist </h3>';
                }


                $html .= '<span style="display: block;overflow: hidden;width: 100%;height: 69px;text-align: center;position: relative;"><img src="'.$logo_url.'" style="height:69px;float:left;margin-right:-200px"/>'.$playlist_name.'</span><table style="width:100%;padding: 0"><thead>';
                $html .= '<tr>';
                $html .= '<th class="song-select"></th><th></th>';
                $html .= '</tr></thead>';
                $html .= '<tbody>';
                $html .= $text;
                $html .= '</tbody>';
                $html .=  '</table>';
                $html .=  '<h3 style="margin-top: 70px"  id="pdf-headline">Page '. ($key+1).' of '.  $blocks_count.'<h3>';
                if($key==$blocks_count-1){
                    $html .=  '<p style="font-size: 11px;font-weight: normal;font-family: verdana">'.$seconds.'</p>';
                }
                array_push($all_html,$html);

            }
            $styles = 'td, th { padding: .3em;padding-right:0; border: 1px #ccc solid;font-size:11px;}
            p{ font-family:verdana !important;}
            tr td{ font-family:verdana !important;
            text-align:left;
            float:left;
            text-overflow: ellipsis;

            }
            .song-select-single{
            width:31px;
            }
             .song-title{
             width:47%
                    }
             td.song-select:nth-child(even){
             padding-right:0 !important;
             margin-right:0 !important;
             }
             td.song-title:nth-child(odd){
             padding-left:0 !important;
             margin-left:0 !important;
             }

             body {font-family: "verdana";margin:0;}
             #pdf-headline { text-align:center; color:' . $font_color . '; font-size:' . $font_size . ';}
             @page *{ padding:0;margin:20px;margin-left:55px;margin-right:0 !important;margin-top:0 !important;margin-bottom:10px !important}
            .song-title  { overflow: hidden; text-overflow: ellipsis;}';
            $upload_dir = wp_upload_dir();
            $upload_basedir = $upload_dir['basedir'];
            require_once('php/mpdf/mpdf.php');
            $mpdf = new mPDF();
            $mpdf->WriteHTML($styles,1);
            $mpdf -> SetTopMargin(10);
            $mpdf -> SetAutoPageBreak(10);


            //_log('wrote styles, writing html');
            foreach($all_html as $val){
                $mpdf->AddPage();
                $mpdf->WriteHTML($val,2);
            }

            //_log('wrote html, outputting');

            if($playlist_id == 'sample'){
                $filename  ='song-list.pdf';
            }elseif($playlist_id == 'selected'){


                $filename  ='-selected.pdf';
            }else{
               $filename =$current_user->user_login . '-' . $playlist_id . '.pdf';
            }

            $mpdf->Output($upload_basedir . '/EMP/' . $filename,'F');
            //_log('output FINISHED');
            $url = $upload_dir['baseurl'] . '/EMP/' . $filename;
            return $url;
        }
        function encodeURIComponent($str) {
            $revert = array('%21'=>'!', '%2A'=>'*', '%27'=>"'", '%28'=>'(', '%29'=>')');
            return strtr(rawurlencode($str), $revert);
        }
        public function generate_html($query_songs, $pdf = true) {

            $current_count =0;
            $html ="";
            $all_time=0;
            $genre_names =array();


            while ($query_songs->have_posts()) : $query_songs->the_post();
                $current_count++;
                $current_ID = get_the_ID();


                $current_title = get_the_title();
                $current_artist = get_post_meta( $current_ID, 'artist_name', true );
                $prev_genre = (isset($current_genre) ? $current_genre : null);
                $current_year= get_post_meta( $current_ID, 'song_year', true );
                $current_length = get_post_meta( $current_ID, 'song_length', true );
                $current_genre = get_post_meta( $current_ID, 'song_genre', true );
                sscanf($current_length, "%d:%d:%d", $hours, $minutes, $seconds);

                $time_seconds = isset($seconds) ? $hours * 3600 + $minutes * 60 + $seconds : $hours * 60 + $minutes;
                $all_time +=$time_seconds;

                if ($pdf) {
                    if(strlen ($current_title . ' - '. $current_artist )>51){
                        $artist_title =  substr($current_title . ' - '. $current_artist, 0, 48).'...';
                    }else{
                        $artist_title =  $current_title . ' - '. $current_artist;
                    }
                    // $html_pdf .= '<td class="music_selector_td song-title">'.$artist_title.'</td>';
                    $html_arr = '<tr class="music_selector_tr"><td class="music_selector_td song-select-single "><input class="select-single-song" type="checkbox" id="song-' . $current_ID . '" value="' . $current_ID . '" name="' . $time_seconds . '"></td>
                    <td class="music_selector_td song-title">'.$artist_title.'</td></tr>';
                    $genre_names[$current_genre][] = $html_arr;
                }
                if (!$pdf) {
                    $html .= '<tr class="music_selector_tr">';
                    $html .= '<td class="music_selector_td song-select-single "><input class="select-single-song" type="checkbox" id="song-' . $current_ID . '" value="' . $current_ID . '" name="' . $time_seconds . '"></td>';
                    $html .= '<td class="music_selector_td song-title">' . $current_title . ' - '. $current_artist .'</td>';

                    $html .= '<td class="music_selector_td song-genre">' . $current_genre . '</td>';
                    $html .= '<td class="music_selector_td song-year">' . $current_year . '</td>';
                    $html .= '<td class="music_selector_td song-length" data-sort-value=' . $time_seconds . '>' . $current_length . '</td>';

                    $html .= '</tr>';
                }
            endwhile;
            if($pdf){
                $all_songs_hours = floor($all_time / 3600);
                $mins = floor(($all_time - ($all_songs_hours*3600)) / 60);
                $secs = floor($all_time % 60);
                $genre_names['seconds']='Playlist Length : '.$all_songs_hours.'h '. $mins.'m '. $secs.'s '.'    ||   '.$current_count.' Songs Selected';
                return json_encode($genre_names);
            }else{
                return $html;
            }
        }
        public function setup_frontend_ajax(){

            add_action('wp_ajax_nopriv_fe-ajax', array(&$this, 'fe_ajax'));
            add_action('wp_ajax_fe-ajax',array(&$this,  'fe_ajax'));
        }
        function setup_frontend() {
            add_action('wp_footer', array(&$this, 'fe_scripts'));
            add_action('wp_footer', array(&$this, 'fe_ajax_scripts'));
            include('php/music-sel-fe.php');

        }
        function fe_scripts(){
            wp_register_style('music_selector_style', (ZC_MS_URL . 'css/music-sel-fe.css'));
            wp_enqueue_style('music_selector_style');
        }
        function fe_ajax_scripts(){

            wp_enqueue_style( 'jquery-ui-smoothness' , 'http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css' );
            wp_enqueue_script('jquery-ui-autocomplete');
            wp_register_script('music_selector_script', ( ZC_MS_URL . 'js/music-sel-fe.js'), false);

            wp_enqueue_script('music_selector_script');
            wp_register_script('table_sort_script', ( ZC_MS_URL . 'js/table-sort/table-sort.js'), false);
            wp_enqueue_script('table_sort_script');
            wp_localize_script( 'music_selector_script', 'PlaylistAjax', array(
                    'ajaxurl' => admin_url( 'admin-ajax.php'),
                    'postNonce' => wp_create_nonce( 'fe-ajax-post-nonce' ))

            );
        }
        /*
        *
        */
        function register_songs_cpt() {
            require_once 'php/songs-cpt.php';
        }
        function manage_songs_posts_columns($post_columns) {
            $post_columns = array(
                'cb' => $post_columns['cb'],
                'title' => 'Song',
                'artist_name' => 'Artist',
                'song_length' => 'Length',
                'song_genre' => 'Genre',
                'song_year' => 'Year',
                'song_month' => 'Month',

                'sample_playlist' => '<span style="text-align: center;width: 100%;display: block;color: #0073aa;">Print Version</span><p style="text-align: center;margin-bottom: 0"></p>'
            );
            return $post_columns;
        }
        function songs_column_register_sortable( $post_columns ) {
            $post_columns = array(
                'title' => 'title',
                'artist_name' => 'artist_name',
                'song_length' => 'song_length',
                'song_genre' => 'song_genre',
                'song_year' => 'song_year',
                'song_month' => 'song_month',
            );
            return $post_columns;
        }
        function sort_posts_by_meta_value($query) {
            global $pagenow;
            if (is_admin() && $pagenow=='edit.php' &&
                isset($_GET['post_type']) && $_GET['post_type']=='songs' &&
                isset($_GET['orderby'])  && $_GET['orderby'] !='None')  {
                if ($_GET['orderby'] == "title") {
                    $query->query_vars['orderby'] = 'title';
                } else {
                    $query->query_vars['orderby'] = 'meta_value';
                    $query->query_vars['meta_key'] = $_GET['orderby'];
                }
            }
        }
        function manage_songs_custom_column($column_key,$post_id) {
            global $pagenow;
            $post = get_post($post_id);
            if ($post->post_type=='songs' && is_admin() && $pagenow=='edit.php')  {
                if ($column_key == 'sample_playlist') {
                    $checked = (get_post_meta($post_id,$column_key,true) == "on" ? "checked='checked'" : " ");
                    echo "<input id='playlist-" . $post_id . "' class='save-to-playlist' value='" . $post_id ."' type='checkbox'" . $checked ."/>";
                } else {
                    echo ( get_post_meta($post_id,$column_key,true) ) ? get_post_meta($post_id,$column_key,true) : "Undefined";
                }
            }
        }


        function rename_second_menu_name( $safe_text, $text ) {
            if ( 'Manage Songs' !== $text ) {
                return $safe_text;
            }
            // We are on the main menu item now. The filter is not needed anymore.
            remove_filter( 'attribute_escape', 'rename_second_menu_name' );
            return 'EMP Song Selector';
        }
        /*
        * Add the Song Selector link on plugin page
        */
        function zc_ms_settings_link($links, $file) {
            static $this_plugin;
            if (!$this_plugin) {
                $this_plugin = plugin_basename(__FILE__);
            }
            // check to make sure this is the correct plugin
            if ($file == $this_plugin) {
                $settings_link = '<a href="' . ZC_MS_SETTINGS_PAGE .'">Song Selector</a>';
                array_unshift($links, $settings_link);
            }
            return $links;
        } // END: zc_ms_settings_link()
        /**
         * Initialize the plugin settings for the admin
         */
        function admin_init() {


            // Search and filter songs custom post type by custom field values
            include 'php/search.php';
            $zc_ms_search = new zc_ms_search();

            add_filter('posts_join', array( &$zc_ms_search, 'songs_search_join' ) );
            add_filter( 'posts_where', array( &$zc_ms_search, 'songs_search_where' ) );
            add_filter( 'posts_groupby', array( &$zc_ms_search, 'songs_limits' ) );
            add_action( 'restrict_manage_posts', array( &$zc_ms_search, 'wpse45436_admin_posts_filter_restrict_manage_posts' ));
            add_action( 'restrict_manage_posts', array( &$zc_ms_search, 'wpse45437_admin_posts_filter_restrict_manage_posts' ));
            add_filter( 'parse_query', array( &$zc_ms_search, 'wpse45436_posts_filter' ) );
            add_action( 'restrict_manage_posts', array( &$zc_ms_search, 'wpse45437_admin_posts_per_page_limit' ));

            // Song Custom Post Type Page
            add_action('manage_songs_posts_columns', array( &$this, 'manage_songs_posts_columns' ) );
            add_filter( 'manage_edit-songs_sortable_columns', array( &$this, 'songs_column_register_sortable' ) );
            add_filter( 'parse_query',  array( &$this, 'sort_posts_by_meta_value' ) );
            add_action('manage_posts_custom_column', array( &$this, 'manage_songs_custom_column'),10,2);
            add_action('save_post', array( &$this, 'songs_save_data') );
            add_filter('post_type_link', array( &$this,"change_link"),10,2);
            add_action( "post_row_actions", array( &$this, 'wpse32093_link_target_blank'), 20, 2 );
            add_filter('post_row_actions', array( &$this,'remove_quick_edit'),10,2);
            add_action( 'admin_init', array( &$this, 'wpse151723_remove_yoast_seo_posts_filter'), 20 );
            add_action('admin_footer-edit.php', array(&$this, 'custom_bulk_admin_songs'));

            // Register all plugin settings with wp-options

            register_setting($this->options_group, $this->get_plugin_option_fullname('settings'), array( &$this , 'zc_ms_options_validate'));
        } // END: admin_init()



        /**
         * Initialize the admin page
         */
        function admin_menu_init() {
            if(is_admin()) {
                //Add the necessary pages for the plugin

                add_action('admin_menu', array(&$this, 'add_menu_items'));
            }
        }

        function wpse151723_remove_yoast_seo_posts_filter() {
            global $wpseo_metabox;

            if ( $wpseo_metabox ) {
                remove_action( 'restrict_manage_posts', array( $wpseo_metabox, 'posts_filter_dropdown' ) );
            }
        }

        function custom_bulk_admin_songs() {

            global $post;

            if($post->post_type == 'songs') {
                ?>
                <script type="text/javascript">
                    jQuery(document).ready(function() {

                        jQuery('<option>').val('print_version').text('<?php _e('Make Print Version')?>').appendTo("select[name='action']");
                        jQuery('<option>').val('print_version').text('<?php _e('Make Print Version')?>').appendTo("select[name='action2']");
                        jQuery('<option>').val('un_print_version').text('<?php _e('Un-Select Print Version')?>').appendTo("select[name='action']");
                        jQuery('<option>').val('un_print_version').text('<?php _e('Un-Select Print Version')?>').appendTo("select[name='action2']");

                    });
                </script>
            <?php
            }

        }

        /**
         * Add admin menu page for the plugin
         */
        function add_menu_items() {
            global $meta_box;
            // Add To Settings Admin Menu
            include_once( ZC_MS_ROOT . '/php/settings.php' );
            include( ZC_MS_ROOT . '/php/songs-cf.php' );
            foreach($meta_box as $post_type => $value) {
                add_meta_box($value['id'], $value['title'],  array(&$this, 'songs_format_box'), $post_type, $value['context'], $value['priority']);
            }
            // include_once( ZC_MS_ROOT . '/php/import.php' );
            include_once( ZC_MS_ROOT . '/php/settings.php' );
            $zc_ms_settings = new ZC_MS_Settings();
            // add_submenu_page( 'edit.php?post_type=songs', 'Import Songs', 'Import Songs', 'manage_options', 'import-songs', array( &$zc_ms_import , 'import_page' ) );
            add_submenu_page( 'edit.php?post_type=songs', 'Import Songs', 'Import Songs', 'manage_options', 'import-songs', 'import_page' );
            add_submenu_page( 'edit.php?post_type=songs', 'Settings', 'Settings', 'manage_options', 'ms-settings', array(&$zc_ms_settings , 'settings_page' ) );

            //$this->settings = new ZC_MS_Settings();
        } // END: add_menu_items()

        //Format meta boxes
        function songs_format_box() {
            global $meta_box, $post;
            $nonce = wp_create_nonce(basename(__FILE__));
            // Use nonce for verification
            echo '<input type="hidden" name="songs_meta_box_nonce" value="' . $nonce . '" />';
            echo '<table class="form-table">';
            foreach ($meta_box[$post->post_type]['fields'] as $field) {
                // get current post meta data
                $meta = get_post_meta($post->ID, $field['id'], true);
                echo '<tr class="music_selector_tr">'.
                    '<th style="width:20%"><label for="'. $field['id'] .'">'. $field['name']. '</label></th>'.
                    '<td class="music_selector_td">';
                switch ($field['type']) {
                    case 'text':
                        echo '<input type="text" name="'. $field['id']. '" id="'. $field['id'] .'" value="'. ($meta ? $meta : $field['default']) . '" size="30" style="width:97%" />'. '<br />'. $field['desc'];
                        break;
                    case 'textarea':
                        echo '<textarea name="'. $field['id']. '" id="'. $field['id']. '" cols="60" rows="4" style="width:97%">'. ($meta ? $meta : $field['default']) . '</textarea>'. '<br />'. $field['desc'];
                        break;
                    case 'select':
                        echo '<select name="'. $field['id'] . '" id="'. $field['id'] . '">';
                        foreach ($field['options'] as $option) {
                            echo '<option '. ( $meta == $option ? ' selected="selected"' : '' ) . '>'. $option . '</option>';
                        }
                        echo '</select>';
                        break;
                    case 'radio':
                        foreach ($field['options'] as $option) {
                            echo '<input type="radio" name="' . $field['id'] . '" value="' . $option['value'] . '"' . ( $meta == $option['value'] ? ' checked="checked"' : '' ) . ' />' . $option['name'];
                        }
                        break;
                    case 'checkbox':
                        echo '<input type="checkbox" name="' . $field['id'] . '" id="' . $field['id'] . '"' . ( $meta ? ' checked="checked"' : '' ) . ' />';
                        break;
                }
                echo '<td class="music_selector_td">'.'</tr>';
            }
            echo '</table>';
        }
        // Save data from meta box
        function songs_save_data($post_id) {
            global $meta_box;
            global $post;
            //Verify nonce
            if (!isset($_POST['songs_meta_box_nonce']) || !wp_verify_nonce($_POST['songs_meta_box_nonce'], basename(__FILE__))) {
                return $post_id;
            }
            //Check autosave
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return $post_id;
            }
            //Check permissions
            if ('page' == $_POST['post_type']) {
                if (!current_user_can('edit_page', $post_id)) {
                    return $post_id;
                }
            } elseif (!current_user_can('edit_post', $post_id)) {
                return $post_id;
            }
            foreach ($meta_box[$post->post_type]['fields'] as $field) {
                @$old = get_post_meta($post_id, $field['id'], true);
                @$new = $_POST[$field['id']];
                if ($new && $new != $old) {
                    update_post_meta($post_id, $field['id'], $new);
                    // TODO: Increment sample playlist if playlisted
                } elseif ('' == $new && $old) {
                    // TODO: decrement sample playlist if playlisted
                    delete_post_meta($post_id, $field['id'], $old);
                }
            }
        }
        function change_link( $permalink, $post ) {
            global $current_screen;
            $custom_post = get_post_custom($post->ID);
            if( $post->post_type == 'songs' && isset($custom_post['artist_name'][0]) && $current_screen != null && $current_screen->action != 'add' ) {
                $cf = get_post_custom($post->ID);
                $artist_name = $cf['artist_name'][0];
                $query = $artist_name . "+" . $post->post_title;
                $query = str_replace(array( " ", "-", "_", ":", "|", "~", "'",'"', "&", "$", "%", "#", "@", "*", "/"), "+", $query);
                $permalink = "http://www.youtube.com/results?search_query=" . $query;
            }
            return $permalink;
        }
        function wpse32093_link_target_blank( $actions, $post ){
            if ($post->post_type=='songs')  {
                $actions = array_map( 'links_add_target', $actions, array("","",'_target'));
            }
            $actions['2'] = preg_replace('@>(.*)<@', '>View in YouTube<', $actions['2']);

            return $actions;
        }
        function remove_quick_edit( $actions, $post ) {
            if( $post->post_type == 'songs' ) {
                unset($actions['inline hide-if-no-js']);
            }
            return $actions;
        }

        /**
         * Output the necessary Javascript and CSS to music-selector admin page
         */
        function add_admin_scripts() {

            if (is_admin()) {
                if (array_key_exists ( 'post_type' , $_REQUEST ) && $_REQUEST['post_type'] == 'songs') {
                    wp_enqueue_script('jquery');
                }
                if (!array_key_exists ('page', $_REQUEST)) {
                    wp_enqueue_style( 'song-list-css', ZC_MS_URL . '/css/songs-list.css' );
                    wp_enqueue_script( 'library-js', ZC_MS_URL . '/js/library.js' );
                    wp_localize_script( 'library-js', 'MusicSelectorAjax', array(
                            'ajaxurl' => admin_url( 'admin-ajax.php' ),
                            'postNonce' => wp_create_nonce( 'ms-ajax-post-nonce' ))
                    );
                } else if (isset($_REQUEST['page']) && $_REQUEST['page'] == 'import-songs') {
                    wp_enqueue_style( 'ms-import-css', ZC_MS_URL . '/css/ms-import.css' );
                    wp_enqueue_script('jquery');
                    wp_enqueue_script('jquery-ui');
                    wp_enqueue_script( 'import-js', ZC_MS_URL . '/js/import.js' );
                } else if (isset($_REQUEST['page']) && $_REQUEST['page'] == 'ms-settings') {
                    wp_enqueue_style( 'settings-css', ZC_MS_URL . '/css/settings.css' );
                    wp_enqueue_script( 'ms-settings-js', ZC_MS_URL . '/js/settings.js' );
                    wp_localize_script( 'ms-settings-js', 'MusicSelectorAjax', array(
                            'ajaxurl' => admin_url( 'admin-ajax.php'),
                            'postNonce' => wp_create_nonce( 'ms-ajax-post-nonce' ))
                    );
                }
            }
        }// END: function add_admin_scripts()
        /*
        * Enable the plugin
        */
        function activate_plugin() {
            global $wpdb;
            // Create the writable temp directories
            $pdf_dir = ZC_MS_UPLOAD_DIR .'ms-pdfs/';
            if (!file_exists($pdf_dir)) {
                mkdir($pdf_dir, 0775, true);
            }
        }
        /**
         * Delete the temp directories used by the plugin when plugin is deleted.
         */
        public static function uninstall_plugin() {
            if ( ! current_user_can( 'activate_plugins' ) )
                return;
            check_admin_referer( 'bulk-plugins' );
            // Important: Check if the file is the one that was registered during the uninstall hook.
            if ( __FILE__ != ZC_MS_FILE_PATH )
                return;
            $plugin_upload_dir = ZC_MS_UPLOAD_DIR;
            function rrmdir($dir) {
                if (is_dir($dir)) {
                    $objects = scandir($dir);
                    foreach ($objects as $object) {
                        if ($object != "." && $object != "..") {
                            if (filetype($dir."/".$object) == "dir")
                                rrmdir($dir."/".$object);
                            else unlink   ($dir."/".$object);
                        }
                    }
                    reset($objects);
                    rmdir($dir);
                }
            }
            rrmdir($plugin_upload_dir);
            rrmdir($plugin_upload_dir);
        }// END: function uninstall_plugin()
        function deactivate_plugin() {
            //function uninstall_plugin () {
            //$this->delete_options($this->plugin_options);
        } // END: deactivate_plugin
        /*
         * Loads options for the plugin.
         * If option doesn't exist in database, it is added
         *
         * Note: default values are stored in the $this->plugin_options array
         * Note: a prefix unique to the plugin is appended to all the options. Prefix is stored in $this->options_group
         */
        function load_options ( ) {
            $new_options = array();


            foreach($this->plugin_options as $option => $value) {
                $name = $this->get_plugin_option_fullname($option);
                $return = get_option($name);
                if($return === false) {
                    add_option($name, $value);
                } else {
                    $new_array[$option] = $return;
                }
            }


            $this->plugin_options = $new_array;
        } // END: load_options
        /*
        * Delete all the options in the database
        *
        * TODO: Confirmation
        */
        function delete_options($my_options) {
            if (!is_string($my_options)){
                foreach (array_keys($my_options) as $value) {
                    $name = $this->get_plugin_option_fullname($value);
                    delete_option($name);
                }
            }
        } // end delete_options()
        /*
        * Appends the option prefix and returns the full name of the option as it is stored in the wp_options db
        */
        function get_plugin_option_fullname($name) {
            return $this->options_group . $name;
        }
        /**
         * get_plugin_option ()
         *
         * Returns option for the plugin specified by $name, e.g. show_on_load
         * Note: The plugin option prefix does not need to be included in $name
         * @param string name of the option
         * @return option|null if not found
         */
        function get_plugin_option ( $name ) {
            if (is_array($this->plugin_options) && $option = $this->plugin_options[$name])
                return $option;
            else
                return null;
        }
        /**
         * Updates option for the plugin specified by $name, e.g. show_on_load
         *
         * Note: The plugin option prefix does not need to be included in $name
         *
         * @param string name of the option
         * @param string value to be set
         *
         */
        function update_plugin_option( $name, $new_value ) {


            if( is_array($this->plugin_options) /* && !empty( $this->options[$name] ) */ ) {
                $this->plugin_options[$name] = $new_value;
                update_option( $this->get_plugin_option_fullname( $name ), $new_value );
            }
        } // END: update_plugin_option
        function update_sub_option( $option, $sub_option, $new_value ) {

            //$all_instances = $this-> get_plugin_option('instance');
            $all_options = $this->plugin_options[$option];

            $all_options[$sub_option] = $new_value;
            $this->update_plugin_option( $option, $all_options );
            return true;
        }
        function get_sub_option( $option, $sub_option ){
            $all_options = $this->plugin_options[$option];
            return $all_options[$sub_option] ;
        }
        function zc_ms_options_validate() {
            $all_opt = $this->get_plugin_option('settings');

            if ( array_key_exists( 'new_opt', $_POST ) ) {
                $new_opt = $_POST['new_opt'];
                foreach ( $new_opt as $setting => $option ) {
                    foreach ( $option as $key => $value) {
                        $all_opt[$key] = $value;
                    }
                }
            }
            return $all_opt;

        }
        /**
         * Backed AJAX controller
         */
        function ms_ajax_handler() {
            $nonce = $_REQUEST['postNonce'];
            if ( ! wp_verify_nonce( $nonce, 'ms-ajax-post-nonce' ) )
                die ( 'Busted!');
            if ( array_key_exists ( 'page' , $_REQUEST ) && $_REQUEST['page'] == 'music-selector' )
                die ( 'Busted!');
            switch($_REQUEST['fn']){
                case 'get-music-cp':
                    //$output = $this->update_plugin_option("front_page", $_REQUEST['campaign_id']);
                    $output = true;
                    break;
                case 'calculate-counts':
                    $args = array('post_type' => 'songs','post_status' => 'publish' ,  'fields' => 'ids', 'posts_per_page' => -1);
                    $args['meta_query'][] = array('key' =>'sample_playlist', 'value'=> 'on');
                    $query = new WP_Query($args);
                    $sample_playlist_count = $query->found_posts;
                    $this->update_sub_option('stats', 'sample_playlist_count', $sample_playlist_count);
                    unset($args['meta_query']);
                    $query = new WP_Query($args);
                    $master_playlist_count = $query->found_posts;
                    $this->update_sub_option('stats', 'total_song_count', $master_playlist_count);
                    $output = "<div>Master Playlist Count: " . $master_playlist_count . "</div><div>Print Version Count: " . $sample_playlist_count . "</div>";
                    break;
                case 'save-to-playlist':
                    $post_id = $_REQUEST['post_id'];
                    $value = $_REQUEST['value'];

                    $all_stats = $this->get_plugin_option( 'stats' );
                    $playlist_count = $all_stats['sample_playlist_count'];
                    if($value == 'on') {
                        $playlist_count++;
                    } else {
                        $playlist_count--;
                    }
                    $this->update_sub_option('stats', 'sample_playlist_count', $playlist_count);
                    $output = update_post_meta($post_id, 'sample_playlist', $value);
                    break;
                case 'save-print-version':

                    $print_version_posts = $_REQUEST['print_version_posts'];
                    foreach($print_version_posts as $post_id){

                        if(is_numeric($post_id)){

                            update_post_meta($post_id, 'sample_playlist','on');
                        }

                    }
                    break;
                case 'un-print-version':

                    $print_version_posts = $_REQUEST['un_print_version_posts'];
                    foreach($print_version_posts as $post_id){

                        if(is_numeric($post_id)){

                            update_post_meta($post_id, 'sample_playlist','0');
                        }

                    }
                    break;
                case 'reset-genres':
                    $this->update_sub_option('stats', 'available_genres', array());
                    $output = "Deleted all the available genres";
                    break;
                case 'update-genres':
                    set_time_limit(0);
                    $current_genre_count = 0;
                    $current_post_count = 0;
                    $all_stats = $this->get_plugin_option( 'stats' );
                    $available_genres = $all_stats['available_genres'];
                    $args = array('post_type' => 'songs','post_status' => 'publish',  'fields' => 'ids', 'posts_per_page' => -1 );//, 'offset' => $x * 1000);
                    $query = new WP_Query($args);
                    if( $query->have_posts() ) {
                        $total = $query->post_count;
                        while( $current_post_count !== $total ) {
                            $current_ID = $query->posts[$current_post_count];
                            $current_post_count++;
                            $current_genre = get_post_meta( $current_ID, 'song_genre', true );
                            if(!in_array($current_genre, $available_genres)) {
                                $available_genres[] = $current_genre;
                                $current_genre_count++;
                            }
                        }
                    }
                    $this->update_sub_option('stats', 'available_genres', $available_genres);
                    $output = "<div>Found " . $current_genre_count . " new, unique genres.";
                    break;
                case 'generate-pdf':
                    set_time_limit(0);
                    ini_set('memory_limit', '-1');
                    $playlist =$_REQUEST['playlist'];
                    $args = array('post_type' => 'songs','post_status' => 'publish', 'posts_per_page' =>20000);
                    if($playlist == 'sample') {
                        $args['meta_query'][] = array('key' =>'sample_playlist', 'value'=> 'on');
                    }
                    $query = new WP_Query($args);
                    //TODO: update options for total counts here
                    $html = $this->generate_html($query);
                    $pdf_url = $this->generate_pdf($html);
                    //$pdf_url = $this->generate_fpdf($query);
                    $output = $pdf_url;
                    break;
                default:
                    $output = 'No function specified, check your jQuery.ajax() call';
                    break;
            }
            echo $output;
            die;
        }
        function generate_fpdf($query_songs) {
            $current_count =0;
            while ($query_songs->have_posts()) : $query_songs->the_post();
                $current_ID = get_the_ID();
                $current_title = get_the_title();
                $current_artist = get_post_meta( $current_ID, 'artist_name', true );
                $current_genre = get_post_meta( $current_ID, 'song_genre', true );
                $current_year= get_post_meta( $current_ID, 'song_year', true );
                $current_length = get_post_meta( $current_ID, 'song_length', true );
                $data[$current_count] = array($current_title, $current_artist, $current_genre, $current_year, $current_length);
                $current_count++;
            endwhile;
            $upload_dir = wp_upload_dir();
            $upload_basedir = $upload_dir['basedir'];
            // define('FPDF_FONTPATH','php/fpdf/font/');
            require('php/fpdf/fpdf.php');

            $tpdf=new fPDF();
            //Column titles
            $header=array('TITLE','ARTIST','GENRE','YEAR', 'LENGTH');
            $w=array(50,40,30,18,22);
            $align=array("L","L","C","C","C");
            //Data loading
            $tpdf->SetFont('Verdana','',14);
            $tpdf->AddPage();
            //  $tpdf->ImprovedTable($header,$data,$w);

            for($i=0;$i<count($header);$i++)
                $tpdf->Cell($w[$i],7,$header[$i],1,0,'C');
            $tpdf->Ln();
            //Data

            foreach($data as $rows)
            {
                //$tpdf->Cell($w[0],6,$row[0],'LR',0,'L');
                //$tpdf->Cell($w[1],6,$row[1],'LR',0,'L');
                //$tpdf->Cell($w[2],6,$row[2],'LR',0,'L');
                //$tpdf->Cell($w[3],6,$row[3],'LR',0,'C');
                //$tpdf->Cell($w[4],6,$row[4],'LR',0,'C');

                foreach($rows as $key => $row) {
                    $current_y = $tpdf->GetY();
                    $current_x = $tpdf->GetX();
                    $total_string_width = $tpdf->GetStringWidth($row);
                    $number_of_lines = ceil( $total_string_width / ($w[$key] - 1) );

// Determine the height of the resulting multi-line cell.
                    $line_height = 5;
                    $height_of_cell = ceil( $number_of_lines * $line_height );

                    $tpdf->MultiCell($w[$key],$height_of_cell,$row,1,$align[$key]);
                    $tpdf->SetXY($current_x + $w[$key], $current_y);
                    $current_x = $tpdf->GetX();

                    //$tpdf->MultiCell($w[1],6,$row[1],1,'L');
                    //$tpdf->MultiCell($w[2],6,$row[2],1,'C');
                    //$tpdf->MultiCell($w[3],6,$row[3],1,'C');
                    //$tpdf->MultiCell($w[4],6,$row[4],1,'C');
                }
                //$tpdf->Ln();
                $current_y+=$height_of_cell;
                $tpdf->SetY($current_y);

            }
            //Closure line
            $tpdf->Cell(array_sum($w),0,'','T');
            $tpdf->Output($upload_basedir . '/EMP/filename.pdf','F');
            // _log('output FINISHED');

            $url = $upload_dir['baseurl'] . '/EMP/filename.pdf';
            return $url;
        }

    } // END: class zc_ms
}
/**
 * Create new instance of the zc_ms object
 */
global $zc_ms;
$zc_ms = new zc_ms();

// Hook to perform action when plugin activated
register_activation_hook( ZC_MS_FILE_PATH, array(&$zc_ms, 'activate_plugin'));
register_deactivation_hook( ZC_MS_FILE_PATH, array(&$zc_ms, 'deactivate_plugin'));

/**
 * zc_ms_loaded()
 * Allow plugins and core actions to attach themselves safely
 */
function zc_ms_loaded() {
    do_action( 'zc_ms_loaded' );
}
add_action( 'plugins_loaded', 'zc_ms_loaded', 10 );
