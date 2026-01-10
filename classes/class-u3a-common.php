<?php
class U3aCommon
{

    // $plugin_file is the value of __FILE__ from the main plugin file
    private static $plugin_file;

    // list of all our custom post types (CPTs) 
    private static $CPT_array = [U3A_EVENT_CPT, U3A_GROUP_CPT, U3A_CONTACT_CPT, U3A_VENUE_CPT, U3A_NOTICE_CPT];

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

        // Add a fix for long standing WordPress bug
        self::fix_metaboxcb_is_false_gutenberg_bug();

        /* Three hooks common to all our CPTs */
        
        // Remove the Dates filter for this plugin's post types
        add_filter('months_dropdown_results', array(self::class, 'remove_date_filter'), 10, 2);

        // Load admin javascript to ensure title is set for custom posts
        add_action('admin_enqueue_scripts', array(self::class, 'load_ensure_title_script'), 10, 1);
        
        // Customise the Quick Edit panel
        add_action('admin_head-edit.php', array(self::class, 'modify_quick_edit'));
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
     * Remove the default Dates filter for our CPTs.
     * @param array $months Array of the months drop-down query results
     * @param $post_type 
     * @return empty array for selected post_types
     * @usedby filter 'months_dropdown_results'
     */
    public static function remove_date_filter($months, $post_type)
    {
        if (in_array($post_type, self::$CPT_array)) {
            return array();
        }
        return $months;
    }

    /**
     * Load script that prevents user saving a CPT post with no title.
     * @param $hook screen that invokes the action.
     * @usedby action 'admin_enqueue_scripts'
     */
    public static function load_ensure_title_script($hook)
    {
        global $post;
        if ($hook == 'post-new.php' || $hook == 'post.php') {
            if (in_array($post->post_type, self::$CPT_array)) {
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

    /**
     * Removes the Date, Password and Category input areas from the Quick Edit panel for all our CPTs
     * NB Function uses the heading text in spans to identify panels to remove.  If text changes this function will need corresponding changes.
     *
     * @ref https://wordpress.stackexchange.com/questions/59871/remove-specific-items-from-quick-edit-menu-of-a-custom-post-type
     *
     * @usedby action 'admin_head-edit.php'
     * @return void
     */
    public static function modify_quick_edit()
    {
        global $current_screen;
        if (!in_array($current_screen->post_type, self::$CPT_array)) {
            return;
        }
        ?>
<script type="text/javascript">
    jQuery(document).ready(function($) {

        $('span:contains("Password")').each(function(i) {
            $(this).parent().parent().remove();
        });
        $('span:contains("Date")').each(function(i) {
            $(this).parent().remove();
        });
        $('span:contains("Category")').each(function(i) {
            $(this).parent().parent().remove();
        });
        $('span.title:contains("Group")').each(function(i) {
            $(this).parent().parent().remove();
        });
        $('.inline-edit-date').each(function(i) {
            $(this).hide(); // hide NOT remove so that other fields that need it know the publish date.
        });
    });
</script>
        <?php
    }
}

