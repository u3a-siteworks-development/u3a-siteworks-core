<?php

// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
class U3aNotice // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
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

    // Limits on the max size of data input
    private const MAX_DATE = 10;
    // YYYY-MM-DD
    private const MAX_URL = 2000;
    // for all browsers

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
     */
    public function __construct($ID)
    {
        $ID           = (int) $ID;
        $this->ID     = $ID;
        $this->exists = false;
        if (is_int($ID) && $ID > 0) {
            if (get_post($ID) !== null) {
                // so a post with this ID exists
                $this->exists = true;
            }
        }
    }
    //end __construct()


    // $plugin_file is the value of __FILE__ from the main plugin file
    public static function initialise($plugin_file)
    {
        self::$plugin_file = $plugin_file;

        // Register Notice CPT
        add_action('init', [self::class, 'register_notices']);

        // Routine to run on plugin activation
        register_activation_hook($plugin_file, [self::class, 'on_activation']);

        // Set up the custom fields in a metabox (using free plugin from on metabox.io)
        add_filter('rwmb_meta_boxes', [self::class, 'add_metabox'], 10, 1);

        // Alter the columns that are displayed in the Notices list admin page
        add_filter('manage_' . U3A_NOTICE_CPT . '_posts_columns', [self::class, 'change_columns']);
        add_action('manage_' . U3A_NOTICE_CPT . '_posts_custom_column', [self::class, 'show_column_data'], 10, 2);
        add_filter('manage_edit-' . U3A_NOTICE_CPT . '_sortable_columns', [self::class, 'make_column_sortable']);
        add_action('pre_get_posts', [self::class, 'sort_column_data']);

        // Change prompt shown for post title
        add_filter('enter_title_here', [self::class, 'change_prompt']);

        // Register the blocks
        add_action('init', [self::class, 'register_blocks']);

        // Add action to restrict database field lengths
        add_action('save_post_u3a_notice', [self::class, 'validate_notice_fields'], 30, 2);
    }
    //end initialise()


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
    //end validate_notice_fields()


    public static function register_notices()
    {
        $args = [
            'public'       => true,
            'show_in_rest' => true,
            'supports'     => [
                'title',
                'editor',
                'author',
                'thumbnail',
                'excerpt',
            ],
            'rewrite'      => ['slug' => sanitize_title(U3A_NOTICE_CPT . 's')],
            'has_archive'  => false,
            'menu_icon'    => U3A_NOTICE_ICON,
            'labels'       => [
                'name'          => 'u3a Notices',
                'singular_name' => 'Notice',
                'add_new_item'  => 'Add Notice',
                'add_new'       => 'Add New Notice',
                'edit_item'     => 'Edit Notice',
                'all_items'     => 'All Notices',
                'view_item'     => 'View Notice',
                'update_item'   => 'Update Notice',
                'search_items'  => 'Search Notices',
            ],
        ];
        if (!(current_user_can('edit_others_pages'))) {
            $args += [
                'capabilities' => ['create_posts' => 'do_not_allow'],
                'map_meta_cap' => true,
            ];
        }

        register_post_type(U3A_NOTICE_CPT, $args);
    }
    //end register_notices()


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
    //end on_activation()


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
        $fields[] = [
            'type'      => 'date',
            'name'      => 'Notice Start Date',
            'id'        => 'notice_start_date',
            'desc'      => 'Date when this notice should start being displayed on the website',
            'size'      => 15,
            'std'       => date('Y-m-d'),
            'pattern'   => '[1-2][0-9][0-9][0-9]-[0-1][0-9]-[0-3][0-9]',
            'required'  => true,
            'maxlength' => self::MAX_DATE,
        ];
        $fields[] = [
            'type'      => 'date',
            'name'      => 'Notice End Date',
            'id'        => 'notice_end_date',
            'desc'      => 'Date when this notice should stop being displayed on the website',
            'size'      => 15,
            'std'       => date('Y-m-d'),
            'pattern'   => '[1-2][0-9][0-9][0-9]-[0-1][0-9]-[0-3][0-9]',
            'required'  => true,
            'maxlength' => self::MAX_DATE,
        ];
        $fields[] = [
            'type'  => 'divider',
            'after' => '<p>If you provide a Notice URL the content of this page will be ignored and the Notice 
                            List entry will link to the given URL instead.</p>
                            <p>The Notice Start and End dates will still be used to determine if this 
                            Notice should be included in the Notice List.</p>',
        ];
        $fields[] = [
            'type'      => 'url',
            'name'      => 'Notice URL',
            'id'        => 'notice_url',
            'desc'      => 'The URL should start with https://, or http:// for an unsecured website link.',
            'maxlength' => self::MAX_URL,
        ];

        return $fields;
    }
    //end field_descriptions()


    /**
     * Registers the block u3a/noticelist and its render callback.
     */
    public static function register_blocks()
    {
        wp_register_script(
            'u3anoticeblocks',
            plugins_url('js/u3a-notice-blocks.js', self::$plugin_file),
            [
                'wp-blocks',
                'wp-element',
                'wp-components',
                'wp-block-editor',
            ],
            U3A_SITEWORKS_CORE_VERSION,
            false,
        );
        wp_enqueue_script('u3anoticeblocks');

        register_block_type(
            'u3a/noticelist',
            [
                'editor_script'   => 'u3anoticeblocks',
                'render_callback' => [
                    self::class,
                    'display_noticelist',
                ],
            ]
        );
    }
    //end register_blocks()


    /**
     * Alter the columns that are displayed in the Posts list admin page to remove the standard
     * WordPress date column and add the Notice Start and End dates
     *
     * @param  array $columns
     * @return modified columns
     * @usedby filter 'manage_' . U3A_NOTICE_CPT . '_posts_columns'
     */
    public static function change_columns($columns)
    {
        unset($columns['date']);

        $columns['noticeStart'] = 'Start date';
        $columns['noticeEnd']   = 'End date';
        $columns['noticeType']  = 'Type';
        return $columns;
    }
    //end change_columns()


    /**
     * Alter what is shown for one row in the columns that are displayed in the events posts list admin page.
     * Notice Type is URL or Notice
     *
     * @param  str $column
     * @param  int $post_id the id of the post for the row
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
                $type    = (strncasecmp($alt_url, 'http', 4) === 0) ? 'URL' : 'Notice';
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                print $type;
        }//end switch
    }
    //end show_column_data()


    /**
     * Provide sorting mechanism for the Notice start and end date columns.
     *
     * @param  obj $query attributes of query, passed by ref
     * @usedby action 'pre_get_posts'
     */
    public static function sort_column_data($query)
    {
        // This is a very general purpose hook, so ...
        // query must be main query for an admin page with a query for u3a_notice post-type
        if (
            !( is_admin()
            && ($query->is_main_query())
            && ('u3a_notice' == $query->get('post_type')))
        ) {
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
    //end sort_column_data()


    /**
     * Makes Notice date columns sortable.
     *
     * @param  array $columns
     * @return modified array $columns
     * @usedby filter 'manage_edit-' . U3A_NOTICE_CPT . '_sortable_columns'
     */
    public static function make_column_sortable($columns)
    {
        $columns['noticeStart'] = 'noticeStart';
        $columns['noticeEnd']   = 'noticeEnd';
        return $columns;
    }
    //end make_column_sortable()


    /**
     * List Notices in publication order, most recent first.  Returns max 5 items.
     * Show the Excerpt if one is provided for the Notice (but don't fall back to displaying extract from content)
     * Could be extended to allow more user control
     *
     * @return string
     */
    public static function display_noticelist($atts, $content = '')
    {

        $display_args = [
            'title'     => 'Latest Notices',
            'showtitle' => true,
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

        $posts = get_posts(
            [
                'numberposts' => -1,
                'orderby'     => 'meta_value',
                'post_type'   => U3A_NOTICE_CPT,
                'order'       => 'DESC',
                'post_status' => 'publish',
                'meta_key'    => 'notice_start_date',
                'meta_query'  => [
                    'relation' => 'AND',
                    [
                        'key'     => 'notice_end_date',
                        'value'   => date("Y-m-d"),
                        'type'    => 'DATE',
                        'compare' => '>',
                    ],
                    [
                        'key'     => 'notice_start_date',
                        'value'   => date("Y-m-d"),
                        'type'    => 'DATE',
                        'compare' => '<=',
                    ]
                ],
            ]
        );

        if (!$posts) {
            return '<p>There are no current notices</p>';
        }

        $blockattrs = wp_kses_data(get_block_wrapper_attributes(['class' => 'u3a-notice-list']));
        $html       = "<div $blockattrs >\n";
        if ($display_args['showtitle']) {
            $html .= "<h3>" . $display_args['title'] . "</h3>\n";
        }

        foreach ($posts as $notice) {
            $title   = $notice->post_title;
            $alt_url = trim(get_post_meta($notice->ID, 'notice_url', true));
            $url     = (strncasecmp($alt_url, 'http', 4) === 0) ? $alt_url : get_permalink($notice->ID);
            $html   .= "<h4><a href=\"$url\">$title</a></h4>\n";
            if (has_excerpt($notice)) {
                $excerpt = get_the_excerpt($notice);
                $html   .= "<p>$excerpt</p>";
            }
        }

        $html .= "</div>\n";

        return $html;
    }
    //end display_noticelist()
}
//end class
