<?php
if (!class_exists('zc_ms_search')) {
    class zc_ms_search {
        function __construct() {
        }
        function songs_search_join ($join){
            global $pagenow, $wpdb;
            // I want the filter only when performing a search on edit page of Custom Post Type named "songs"
            if ( is_admin() && $pagenow=='edit.php' && isset($_GET['post_type']) && $_GET['post_type']=='songs' && isset($_GET['s']) && $_GET['s'] != '') {
                $join .='LEFT JOIN '.$wpdb->postmeta . ' p ON '. $wpdb->posts . '.ID = p.post_id ';
            }
            return $join;
        }
        function songs_search_where( $where ){
            global $pagenow, $wpdb;
            // I want the filter only when performing a search on edit page of Custom Post Type named "songs"
            if ( is_admin() && $pagenow=='edit.php' && isset($_GET['post_type']) && $_GET['post_type']=='songs' && isset($_GET['s']) && $_GET['s'] != '') {
                $where = preg_replace(
                    "/\(\s*".$wpdb->posts.".post_title\s+LIKE\s*(\'[^\']+\')\s*\)/",
                    "(".$wpdb->posts.".post_title LIKE $1) OR (p.meta_value LIKE $1)", $where );
            }
            return $where;
        }
        function songs_limits($groupby) {
            global $pagenow, $wpdb;
            if ( is_admin() && $pagenow == 'edit.php' && isset($_GET['post_type']) && $_GET['post_type']=='songs' && isset($_GET['s']) && $_GET['s'] != '' ) {
                $groupby = "$wpdb->posts.ID";
            }
            return $groupby;
        }


        /**
         * Create the dropdown menu
         */
        function wpse45436_admin_posts_filter_restrict_manage_posts(){
            $type = 'songs';
            if (isset($_GET['post_type'])) {
                $type = $_GET['post_type'];
            }
            //only add filter to post type you want
            if ('songs' == $type){
                //change this to the list of values you want to show
                //in 'label' => 'value' format
                $values = array(
                    'Print Version' => 'sample_playlist',
                    'Title'=>'title',
                    'Artist'=>'artist_name',
                    'Genre'=>'song_genre',
                    'Year'=>'song_year',
                    'Month'=>'song_month'
                );
                ?>
                <select name="ADMIN_FILTER_FIELD_VALUE">
                    <option value=""><?php _e('Filter By ', 'wose45436'); ?></option>
                    <?php
                    $current_v = isset($_GET['ADMIN_FILTER_FIELD_VALUE'])? $_GET['ADMIN_FILTER_FIELD_VALUE']:'';
                    foreach ($values as $label => $value) {
                        printf
                        (
                            '<option value="%s"%s>%s</option>',
                            $value,
                            $value == $current_v? ' selected="selected"':'',
                            $label
                        );
                    }
                    ?>
                </select>
            <?php
            }
        }
        /**
         * Create the dropdown menu
         */
        function wpse45437_admin_posts_filter_restrict_manage_posts(){
            $type = 'songs';
            if (isset($_GET['post_type'])) {
                $type = $_GET['post_type'];
            }
            //only add filter to post type you want
            if ('songs' == $type){
                //change this to the list of values you want to show
                //in 'label' => 'value' format
                $values = array(
                    'Print Version' => 'sample_playlist',

                );
                ?>



                <?php
                $current_v = isset($_GET['serch_by_text'])? $_GET['serch_by_text']:'';


                echo   '<input name="serch_by_text" type="text" value="'.$current_v.'">';



                ?>

            <?php
            }
        }

        function wpse45437_admin_posts_per_page_limit(){
            $type = 'songs';
            if (isset($_GET['post_type'])) {
                $type = $_GET['post_type'];
            }
            //only add filter to post type you want
            if ('songs' == $type){
                //change this to the list of values you want to show
                //in 'label' => 'value' format

                $values = array(
                    '20' => '20',
                    '50'=>'50',
                    '100'=>'100',
                    '250'=>'250',
                    '500'=>'500',

                );
                ?>
                <select name="set_per_page" style="float: right">
                    <option value=""><?php _e('Per Page ', ''); ?></option>
                    <?php
                    $current_v = isset($_GET['set_per_page'])? $_GET['set_per_page']:'';
                    foreach ($values as $label => $value) {
                        printf
                        (
                            '<option value="%s"%s>%s</option>',
                            $value,
                            $value == $current_v? ' selected="selected"':'',
                            $label
                        );
                    }
                    ?>
                </select>
            <?php
            }
        }








        /**
         * if submitted filter by post meta
         */
        function wpse45436_posts_filter( $query ){

            global $pagenow;
            $type = 'songs';
            if (isset($_GET['post_type'])) {
                $type = $_GET['post_type'];
            }

            if ( 'songs' == $type && is_admin() && $pagenow=='edit.php') {




                if(isset($_GET['ADMIN_FILTER_FIELD_VALUE']) && empty($_GET['ADMIN_FILTER_FIELD_VALUE'])  && isset($_GET['serch_by_text']) && !empty($_GET['serch_by_text'])){

                    $query->query_vars['meta_value'] = $_GET['serch_by_text'];
                    $query->query_vars['meta_compare'] ='LIKE';

                    if(isset($_GET['set_per_page']) && !empty($_GET['set_per_page'])){

                        $query->query_vars['posts_per_page'] =$_GET['set_per_page'];

                    }



                }elseif(isset($_GET['ADMIN_FILTER_FIELD_VALUE']) && !empty($_GET['ADMIN_FILTER_FIELD_VALUE']) && isset($_GET['serch_by_text']) && !empty($_GET['serch_by_text'])){

                    if($_GET['ADMIN_FILTER_FIELD_VALUE']=='sample_playlist' ){


                        $query->query_vars['meta_key'] = $_GET["ADMIN_FILTER_FIELD_VALUE"];
                        $query->query_vars['meta_value'] = 'on';
                        if(isset($_GET['set_per_page']) && !empty($_GET['set_per_page'])){
                            $query->query_vars['posts_per_page'] =$_GET['set_per_page'];

                        }
                        $metaquery = array(
                            array( 'value'     => $_GET['serch_by_text'],
                                'compare'   => 'LIKE'),



                        );

                        $query->set( 'meta_query', $metaquery );
                    }



                    else{
                        $query->query_vars['meta_key'] = $_GET["ADMIN_FILTER_FIELD_VALUE"];
                        $query->query_vars['meta_value'] = $_GET['serch_by_text'];
                        $query->query_vars['meta_compare'] ='LIKE';
                        if(isset($_GET['set_per_page']) && !empty($_GET['set_per_page'])){
                            $query->query_vars['posts_per_page'] =$_GET['set_per_page'];

                        }

                    }






                }elseif(isset($_GET['ADMIN_FILTER_FIELD_VALUE']) && empty($_GET['ADMIN_FILTER_FIELD_VALUE']) && isset($_GET['serch_by_text']) && empty($_GET['serch_by_text'])){

                    if(isset($_GET['set_per_page']) && !empty($_GET['set_per_page'])){
                        $query->query_vars['posts_per_page'] =$_GET['set_per_page'];

                    }
                }
                elseif(isset($_GET['ADMIN_FILTER_FIELD_VALUE']) && !empty($_GET['ADMIN_FILTER_FIELD_VALUE']) && isset($_GET['serch_by_text']) && empty($_GET['serch_by_text'])){

                    if($_GET['ADMIN_FILTER_FIELD_VALUE']=='sample_playlist' ){


                        $query->query_vars['meta_key'] = $_GET["ADMIN_FILTER_FIELD_VALUE"];
                        $query->query_vars['meta_value'] = 'on';
                        if(isset($_GET['set_per_page']) && !empty($_GET['set_per_page'])){
                            $query->query_vars['posts_per_page'] =$_GET['set_per_page'];

                        }

                    }








                }


            }
        }
    }
}
?>