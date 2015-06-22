<?php
if (!class_exists('ZC_MS_Settings')) {
    class ZC_MS_Settings {
        function __construct() {
        }
        function settings_page () {
            global $zc_ms;
            $all_settings = $zc_ms->get_plugin_option( 'settings' );

            // print_r($all_settings);

            $branding = $all_settings['branding'];
            $set_role = $all_settings['user_role'];
            $page_width= $all_settings['page_width'];
            $show_powered_by= $all_settings['show_powered_by'];
            ?>
            <div class="wrap">
               <?php
                wp_enqueue_media();?>
                <div id="overlay" class="hidden"></div>
                <div id="icon-edit" class="icon32 icon32-posts-songs"></div>
                <h2>Song Selector Settings
                </h2>
                <form action="options.php" onsubmit="" method="post">
                    <?php settings_fields($zc_ms->options_group);

                    ?>
                    <span><p>Please support this free plugin by leaving your review at wordpress.org <a href="http://bit.ly/1Iu4Ngn">http://bit.ly/1Iu4Ngn</a></p><span>
                    <table class="form-table">


                        <tr>
                            <th scope="row"><strong>Shortcode</strong></th>
                            <td>
                                <p id="shortcode">[song-selector]</p>
                                <p><em>Use above shortcode to display the song selector on the front end.</em>
                                </p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><strong>User Role Settings</strong></th>
                            <td>
                                <p>Define user role for new users:</p>
                                <p>
                                    <select id="user-role" name="new_opt[settings][user_role]">
                                        <?php  global $wp_roles; $roles = $wp_roles->get_names();?>
                                        <?php foreach($roles as $role) { ?>
                                            <option value="<?php echo $role;?>" <?php if ($role == $set_role) { echo 'selected="selected"';}?>><?php echo $role;?></option>
                                        <?php }//end foreach ?>
                                    </select>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><strong>PDF Settings</strong></th>
                            <td>

                                <p>Playlist Heading Font Colour:<input type="text" name="new_opt[settings][branding][headline_font_color]" value="<?php echo $branding['headline_font_color'] ?>" id="headline-color" title="Specify color of the headline text"/></p>
                                <p>Playlist Heading Font Size:<input type="text" name="new_opt[settings][branding][headline_font_size]" value="<?php echo $branding['headline_font_size'] ?>" id="headline-size" title="Specify the size of the headline text"/></p>

                                <label for="image_url">PDF Logo Image:</label>
                                <input type="text" name="new_opt[settings][branding][logo_url]" id="image_url" class="regular-text" value="<?php echo $branding['logo_url'] ?>">
                                <input type="button" name="upload-btn" id="upload-btn" class="button-secondary" value="Upload Image">




                                <p>Generate Print Version PDF:</p>
                                <?php $wp_upload = wp_upload_dir();?>
                                <p><input type="button" id="sample-pdf" class="button-secondary generate-pdf" value="Generate Print Version PDF" title="Export the print version to pdf" name="sample" /><a href="<?php echo $wp_upload['baseurl'] . '/EMP/sample.pdf'; ?>" id="s-pdf-link" class="pdf-link"></a></p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><strong>Song counts</strong></th>
                            <td>
                                <p> Update Playlists counts </p>
                                <p><input type="button" id="playlists-counts" class="button-secondary" value="Update counts" title="Recalculate playlist counts for sample and master playlists" /></p>
                                <label><i>Re-Calculate playlist counts for print and master playlists</i></label>

                                <div id='counts-stats'>


                                </div>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><strong>Available genres</strong></th>
                            <td>
                                <p> Update the available genres List</p>
                                <p><input type="button" id="available-genres" class="button-secondary" value="Update available genres" title="Populate the available genres list" /></p>
                                <div id='genres-stats'></div>
                            </td>
                        </tr>


                        <tr valign="top">
                            <th scope="row"><strong>Page Width</strong><br><i>We can make Page Width full screen if '0'</i></th>

                            <td>
                                <input type="text" value="<?php echo $page_width;?>" name="new_opt[settings][page_width]" />
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><strong>Show 'Powered By' Link ( Please support this free plugin by leaving this on )</strong></th>

                            <td>
                                <input type="hidden" name="new_opt[settings][show_powered_by]" value="0" />
                                <input type="checkbox" value="1" name="new_opt[settings][show_powered_by]"   <?php if($show_powered_by==1 || ($show_powered_by!=0 && $show_powered_by!=1)  ){echo 'checked';} ?>/>
                            </td>
                        </tr>

                    </table>
                    <input name="info_update" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
                </form>
            </div>

        <?php
        }
        function make_query($sample_playlist = false) {
            $args = array('post_type' => 'songs','posts_per_page' =>-1, 'post__in'=>$playlist);
            if ($sample_playlist) { $args['meta_query'][] = array('key' =>'sample_playlist', 'value'=> 'on'); }
            $query = new WP_Query($args);
            $html = $this->generate_html($query);
            $pdf_url = $this->generate_pdf($html); //need extra variable to output to multiple pdf files

            //TODO AJAX controll this function return URL and append to a PDF icon next to button for live preview
            // Set url in settings
        }
    }
}
?>
