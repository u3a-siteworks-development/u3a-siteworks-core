<?php

class U3aNotice
{
    use ChangePrompt;
    use AddMetabox;

    /**
     * The post_type for this class
     *
     * @var string 
     */
    public static $post_type = U3A_NOTICE_CPT;

    /**
     * The term used for the title of these custom posts
     *
     * @var string 
     */
    public static $term_for_title = "title for the Notice";

    /**
     * The metabox title of these custom posts
     *
     * @var string 
     */
    public static $metabox_title = "Notice Settings";

    /**
     * The short name for this class
     *
     * @var string 
     */
    public static $post_type_name = 'notice';

    /* Limits on the max size of data input */
    const MAX_DATE = 10; // YYYY-MM-DD
    const MAX_URL = 2000; // for all browsers

    /**
     * The ID of this post
     *
     * @var string
     */
    public $ID;

    /**
     * If there is a post with this ID
     *
     * @var boolean
     */
    public $exists;

    // $plugin_file is the value of __FILE__ from the main plugin file
    private static $plugin_file;

    /**
     * Construct a new object for a u3a_group post.
     *
     */
    public function __construct($ID)
    {
        $ID = (int) $ID;
        $this->ID = $ID;
        $this->exists = false;
        if (is_int($ID) && $ID > 0) {
            if (get_post($ID) !== null) { // so a post with this ID exists
                $this->exists = true;
            }
        }
    }

    // $plugin_file is the value of __FILE__ from the main plugin file
    public static function initialise($plugin_file)
    {
        self::$plugin_file = $plugin_file;

        // Register Notice CPT
        add_action('init', array(self::class, 'register_notices'));

        // Routine to run on plugin activation
        register_activation_hook($plugin_file, array(self::class, 'on_activation'));

        // Set up the custom fields in a metabox (using free plugin from on metabox.io)
        add_filter('rwmb_meta_boxes', [self::class, 'add_metabox'], 10, 1);

        // Alter the columns that are displayed in the Notices list admin page
        add_filter('manage_' . U3A_NOTICE_CPT . '_posts_columns', array(self::class, 'change_columns'));
        add_action('manage_' . U3A_NOTICE_CPT . '_posts_custom_column', array(self::class, 'show_column_data'), 10, 2);
        add_filter('manage_edit-' . U3A_NOTICE_CPT . '_sortable_columns', array(self::class, 'make_column_sortable'));
        add_action('pre_get_posts', array(self::class, 'sort_column_data'));

        // Change prompt shown for post title
        add_filter('enter_title_here', array(self::class, 'change_prompt'));

        // Modify the query when a Query Block is used to display posts of this type
        // so that when user selects sort in date order, the event date is used instead of the post date
        add_filter('query_loop_block_query_vars', array(self::class, 'filter_events_query'), 10, 1);

        // Register the blocks
        add_action('init', array(self::class, 'register_blocks'));

        // Add action to restrict database field lengths
        add_action('save_post_u3a_notice', [self::class, 'validate_notice_fields'], 30, 2);
    }

    // validate the lengths of fields on save
    public static function validate_notice_fields($post_id, $post)
    {
        $value = get_post_meta($post_id, 'notice_start_date', true);
        if (strlen($value) > self::MAX_DATE) {
            update_post_meta($post_id, 'notice_start_date', '');
        }
        $value = get_post_meta($post_id, 'notice_end_date', true);
        if (strlen($value) > self::MAX_DATE) {
            update_post_meta($post_id, 'notice_end_date', '');
        }
        $value = get_post_meta($post_id, 'notice_url', true);
        if (strlen($value) > self::MAX_URL) {
            update_post_meta($post_id, 'notice_url', '');
        }
    }
    public static function register_notices()
    {
        $args = array(
            'public' => true,
            'show_in_rest' => true,
            'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt'),
            'rewrite' => array('slug' => sanitize_title(U3A_NOTICE_CPT . 's')),
            'has_archive' => false,
            'menu_icon' => U3A_NOTICE_ICON,
            'labels' => array(
                'name' => 'u3a Notices',
                'singular_name' => 'Notice',
                'add_new_item' => 'Add Notice',
                'add_new' => 'Add New Notice',
                'edit_item' => 'Edit Notice',
                'all_items' => 'All Notices',
                'view_item' => 'View Notice',
                'update_item' => 'Update Notice',
                'search_items' => 'Search Notices'
            )
        );
        if (!(current_user_can('edit_others_pages'))) {
            $args += array(
                'capabilities' => array(
                    'create_posts' => 'do_not_allow'
                ),
                'map_meta_cap' => true
            );
        }
        register_post_type(U3A_NOTICE_CPT, $args);
    }

    /**
     * Do tasks that should only be done on activation
     *
     * Register post type and flush rewrite rules.
     * TODO Add default categories for notices
     */
    public static function on_activation()
    {
        self::register_notices();
        delete_option('rewrite_rules');
    }

    /**
     * Defines the fields for this class.
     *
     * @return array
     */
    public static function field_descriptions()
    {
        $fields = [];
        // Now add all the fields to the $fields array in the order they will appear.
        // see https://docs.metabox.io/fields/
        // and https://docs.metabox.io/field-settings/ for details.

        $fields[] =
            [
                'type'      => 'date',
                'name'      => 'Notice Start Date',
                'id'        => 'notice_start_date',
                'desc'      => 'Date when this notice should start being displayed on the website',
                'size'      => 15,
                'std'       => date('Y-m-d'),
                'pattern' => '[1-2][0-9][0-9][0-9]-[0-1][0-9]-[0-3][0-9]',
                'required'  => true,
                'maxlength' => self::MAX_DATE,
            ];
        $fields[] =
            [
                'type'      => 'date',
                'name'      => 'Notice End Date',
                'id'        => 'notice_end_date',
                'desc'      => 'Date when this notice should stop being displayed on the website',
                'size'      => 15,
                'std'       => date('Y-m-d'),
                'pattern' => '[1-2][0-9][0-9][0-9]-[0-1][0-9]-[0-3][0-9]',
                'required' => true,
                'maxlength' => self::MAX_DATE,
            ];
        $fields[] =
            [
                'type' => 'divider',
                'after' => '<p>If you provide a Notice URL the content of this page will be ignored and the Notice List entry will link to the given URL instead.</p>
                            <p>The Notice Start and End dates will still be used to determine if this Notice should be included in the Notice List.</p>',
            ];
        $fields[] =
            [
                'type' => 'url',
                'name' => 'Notice URL',
                'id'   => 'notice_url',
                'desc' => 'The URL should start with https://, or http:// for an unsecured website link.',
                'maxlength' => self::MAX_URL,
            ];

        return $fields;
    }

    /**
     * Registers the block u3a/noticelist and its render callback.
     */
    public static function register_blocks()
    {
        wp_register_script(
            'u3anoticeblocks',
            plugins_url('js/u3a-notice-blocks.js', self::$plugin_file),
            array('wp-blocks', 'wp-element','wp-components','wp-block-editor'),
            U3A_SITEWORKS_CORE_VERSION,
            false,
        );
        wp_enqueue_script('u3anoticeblocks');

        register_block_type(
            'u3a/noticelist',
            array(
                'editor_script' => 'u3anoticeblocks',
                'render_callback' => array(self::class, 'display_noticelist')
            )
        );
    }

    /**
     * Alter the columns that are displayed in the Posts list admin page to remove the standard 
     * WordPress date column and add the Notice Start and End dates
     * @param array $columns
     * @return modified columns
     * @usedby filter 'manage_' . U3A_NOTICE_CPT . '_posts_columns'
     */
    public static function change_columns($columns)
    {
        unset($columns['date']);

        $columns['noticeStart'] = 'Start date';
        $columns['noticeEnd'] = 'End date';
        $columns['noticeType'] = 'Type';
        return $columns;
    }

    /**
     * Alter what is shown for one row in the columns that are displayed in the events posts list admin page.
     * Notice Type is URL or Notice
     * @param str $column
     * @param int $post_id  the id of the post for the row 
     * @usedby action 'manage_' . U3A_NOTICE_CPT . '_posts_custom_column'
     */
    public static function show_column_data($column, $post_id)
    {
        switch ($column) {
            case 'noticeStart':
                $date = get_post_meta($post_id, 'notice_start_date', true);
                if (!empty($date)) {
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- date function is safe
                    print date(get_option('date_format'), strtotime($date));
                } else {
                    print 'not set';
                }
                break;
            case 'noticeEnd':
                $date = get_post_meta($post_id, 'notice_end_date', true);
                if (!empty($date)) {
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- date function is safe
                    print date(get_option('date_format'), strtotime($date));
                } else {
                    print 'not set';
                }
                break;
            case 'noticeType':
                $alt_url = trim(get_post_meta($post_id, 'notice_url', true));
                $type = (strncasecmp($alt_url, 'http', 4) === 0) ? 'URL' : 'Notice';
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                print $type;
        }
    }

    /**
     * Provide sorting mechanism for the Notice start and end date columns.
     *
     * @param obj $query attributes of query, passed by ref
     * @usedby action 'pre_get_posts'
     */
    public static function sort_column_data($query)
    {
        // This is a very general purpose hook, so ...
        // query must be main query for an admin page with a query for u3a_notice post-type
        if (!( is_admin()
               && ($query->is_main_query())
               && ('u3a_notice' == $query->get('post_type'))
             )){
            return;
        }
        // also check that we are on the All Notices page
        // Note: get_current_screen() may not exist at the start of this function 
        $screen = get_current_screen(); 
        if ('edit-u3a_notice' !== $screen->id) {
            return;
        }
        if ('noticeStart' === $query->get('orderby')) {
            $query->set('orderby', 'meta_value');
            $query->set('meta_key', 'notice_start_date');
        }
        if ('noticeEnd' === $query->get('orderby')) {
            $query->set('orderby', 'meta_value');
            $query->set('meta_key', 'notice_end_date');
        }
    }

    /**
     * Makes Notice date columns sortable.
     * 
     * @param array $columns
     * @return modified array $columns
     * @usedby filter 'manage_edit-' . U3A_NOTICE_CPT . '_sortable_columns'
     */
    public static function make_column_sortable($columns)
    {
        $columns['noticeStart'] = 'noticeStart';
        $columns['noticeEnd'] = 'noticeEnd';
        return $columns;
    }
 
    /** 
     * Modify the query when a Query Block is used to display posts of this type.
     * @param array $query
     * @used by filter 'query_loop_block_query_vars'
     */
    public static function filter_events_query($query)
    {
        // ignore if the query block is not using this post type
        if ($query['post_type'] != U3A_NOTICE_CPT) return $query;

        // always exclude notices with dates in the past
        $query['meta_key'] = 'notice_end_date';
        $query['meta_value'] = date("Y-m-d");
        $query['meta_compare'] = '>=';

        // If date order is chosen in the block settings, change to use the Event date instead of Post date
        if ($query['orderby'] == 'date') $query['orderby'] = 'meta_value';

        return $query;
    }

    /**
     * List Notices in date order, selected according to parameters.
     * Show the Excerpt if one is provided for the Notice (but don't fall back to displaying extract from content)
     * Could be extended to allow more user control
	 *
     * Attributes will also be taken from the page's URL query parameters.
     * If present these query parameters will override parameters passed as arguments
     *
     * @param array $atts Valid attributes are:
	 *    title
	 *    showtitle = true/false
     *    startorend = 'start'/'end' (default start)
     *    order = 'asc'/'desc' (default desc)
	 *    maxnumber = 1-7 or -1 (default 5)
     *
     * @return HTML
     */
    public static function display_noticelist($atts, $content = '')
    {

        // valid display_args names and default values
        $display_args = [
            'title' => 'Latest Notices',
            'showtitle' => true,
			'startorend' => 'start',
            'order' => 'desc',
			'maxnumber' => -1,
        ];
        foreach ($display_args as $name => $default) {
            if (isset($_GET[$name])) {
                $display_args[$name] = sanitize_text_field($_GET[$name]);
            // phpcs:enable WordPress.Security.NonceVerification.Recommended
            } elseif (isset($atts[$name])) {
                $display_args[$name] = $atts[$name];
            }
        }
        if ($display_args['title'] == "") {
            $display_args['title'] = "Latest Notices";
        }
        // validate other args
        $error = ''; // NB not displayed?
        $startorend = $display_args['startorend'];
        if ('start' != $startorend && 'end' != $startorend) {
            $error .= 'bad parameter: startorend=' . esc_html($startorend) . '<br>';
            $startorend = 'start'; // default
        }

        $order = strtoupper($display_args['order']);
        if ('ASC' != $order && 'DESC' != $order &&  '' != $order) {
            $error .= 'bad parameter: order=' . esc_html($order) . '<br>';
            $order = '';  // default
        }
        if ('' == $order) {
            $order = 'DESC';
        }
		
		// maxnumber must be between 1 and 7 (arbitrary) or -1 (meaning all)
        $maxnumber = intval($display_args['maxnumber']); // result is always an int
        if (-1 != $display_args['maxnumber'] && !( $display_args['maxnumber'] >= 1 && $display_args['maxnumber'] <= 7 )){
            $error .= 'bad parameter: maxnumber=' . esc_html($display_args['maxnumber']) . '<br>';
			$maxnumber = -1;
        }

        $posts = get_posts(array(
            'numberposts' => $maxnumber,
            'orderby' => 'meta_value',
            'post_type' => U3A_NOTICE_CPT,
            'order' => strtoupper($display_args['order']),
            'post_status' => 'publish',
            'meta_key' => "notice_{$startorend}_date",
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => 'notice_end_date',
                    'value' => date("Y-m-d"),
                    'type' => 'DATE',
                    'compare' => '>',
                ),
                array(
                    'key' => 'notice_start_date',
                    'value' => date("Y-m-d"),
                    'type' => 'DATE',
                    'compare' => '<=',
                )
            )
        ));

        if (!$posts) return '<p>There are no current notices</p>';
        
        $blockattrs = wp_kses_data(get_block_wrapper_attributes(['class' => 'u3a-notice-list']));
        $html = "<div $blockattrs >\n";
        if ($display_args['showtitle']) {
            $html .= "<h3>" . $display_args['title'] . "</h3>\n";
        }
        foreach ($posts as $notice) {
            $title = $notice->post_title;
            $alt_url = trim(get_post_meta($notice->ID, 'notice_url', true));
            $url = (strncasecmp($alt_url, 'http', 4) === 0) ? $alt_url : get_permalink($notice->ID);
            $html .= "<h4><a href=\"$url\">$title</a></h4>\n";
            if (has_excerpt($notice)) {
                $excerpt = get_the_excerpt($notice);
                $html .= "<p>$excerpt</p>";
            }
        }
        $html .= "</div>\n";

        return $html;
    }
}
