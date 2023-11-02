<?php
class U3aCommon
{

    // $plugin_file is the value of __FILE__ from the main plugin file
    private static $plugin_file;

    /**
     * This caries out initialisation that would otherwise be done directly by the main plugin file code.
     *
     * @param $plugin_file the value of __FILE__ from the main plugin file 
     */
    public static function initialise($plugin_file)
    {
        self::$plugin_file = $plugin_file;

        // Load CSS for plugin
        add_action('wp_enqueue_scripts', array(self::class, 'load_styles'));
        
        // Remove the Dates filter for this plugin's post types
        add_filter('months_dropdown_results', array(self::class, 'remove_date_filter'), 10, 2);
        self::fix_metaboxcb_is_false_gutenberg_bug();

        // Load admin javascript to ensure title is set for custom posts
        add_action('admin_enqueue_scripts', array(self::class, 'load_ensure_title_script'), 10, 1);
    }


    /** 
     * Loads CSS for the groups and venues.
     * @usedby action 'wp_enqueue_scripts'
     */
    public static function load_styles()
    {
        wp_enqueue_style(
            'u3agroupstyle',
            plugins_url('css/u3a-custom-post-types.css', self::$plugin_file),
            array(),
            U3A_SITEWORKS_CORE_VERSION,
            false,
        );
    }

    /**
     * Setting 'meta_box_cb' => false does not work with the Gutenberg editor: see below for a temporary fix
     * https://wordpress.stackexchange.com/questions/337087/custom-content-type-meta-box-cb-does-not-run
     * https://github.com/WordPress/gutenberg/issues/13816   see ArnaudBan comment
     * or https://ilikekillnerds.com/2022/10/how-to-hide-meta-boxes-in-wordpress-gutenberg/
     */
    public static function fix_metaboxcb_is_false_gutenberg_bug()
    {
        add_filter( 'rest_prepare_taxonomy', function ($response, $taxonomy, $request ) {
            $context = ! empty( $request['context'] ) ? $request['context'] : 'view';
            // Context is edit in the editor
            if( $context === 'edit' && $taxonomy->meta_box_cb === false ){
                $data_response = $response->get_data();
                $data_response['visibility']['show_ui'] = false;
                $response->set_data( $data_response );
            }
            return $response;
        }, 10, 3 );
    }

    /**
     * Remove the default Dates filter for all the post types in this plugin.
     * @param array $months Array of the months drop-down query results
     * @param $post_type 
     * @return empty array for selected post_types
     * @usedby filter 'months_dropdown_results'
     */
    public static function remove_date_filter($months, $post_type)
    {
        if (in_array($post_type, [U3A_EVENT_CPT, U3A_GROUP_CPT, U3A_VENUE_CPT, U3A_CONTACT_CPT])) {
            return array();
        }
        return $months;
    }

    /**
     * Load script that prevents user saving a post with no title.
     * @param $hook sceen that invokes the action.
     * @usedby action 'admin_enqueue_scripts'
     */
    public static function load_ensure_title_script($hook)
    {
        global $post;
        if ($hook == 'post-new.php' || $hook == 'post.php') {
            if (in_array($post->post_type, [U3A_EVENT_CPT, U3A_GROUP_CPT, U3A_VENUE_CPT, U3A_CONTACT_CPT])) {
                wp_enqueue_script(
                    'ensure_title_script', 
                    plugins_url('js/u3a-cpt-ensure-title.js', self::$plugin_file), 
                    array('jquery', 'wp-data', 'wp-editor', 'wp-edit-post'),
                    U3A_SITEWORKS_CORE_VERSION,
                    false,
                );
            }
        }
    }
}
