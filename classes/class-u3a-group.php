<?php

class U3aGroup
{
    use ModifyQuickEdit;
    use ChangePrompt;

    /**
     * The post_type for this class
     *
     * @var string 
     */
    public static $post_type = U3A_GROUP_CPT;

    /**
     * The term used for the title of these custom posts
     *
     * @var string 
     */
    public static $term_for_title = "name of the Group";

    // The names of the post metadata fields for this CPT
    // ..._ID means this field is the ID of the related post or term, or if not set the string ''
    // ..._NUM means this is an index number to a list of terms, or if not set the string ''
    // With neither suffix the field is plain text, or empty if not set
    // No CPT specific prefix is used, as each meta is related to a specific post, but is this recommended practice? TBD

    // this array is for reference only. It is not used.
    public static $cpt_fields = array(
        'coordinator_ID',   // Post ID (Contact CPT)
        'coordinator2_ID',  // Post ID (Contact CPT)
        'deputy_ID',        // Post ID (Contact CPT)
        'tutor_ID',         // Post ID (Contact CPT)
        'day_NUM',          // using $daylist
        'time',             // using $timelist
        'frequency',        // using $frequency list
        'when',
        'email',
        'email2',
        'venue_ID',         // Post ID (Venue CPT)
        'status_NUM',       // using $status list
        'cost',
        // Term ID (group taxonomy)   Note we store this as a value in wp_term_relationships
    );
    public static $day_list = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];
    public static $time_list = ['Morning' => 'Morning', 'Afternoon' => 'Afternoon', 'Evening' => 'Evening', 'All Day' => 'All day'];
    public static $frequency_list = ['Weekly' => 'Weekly', 'Fortnightly' => 'Fortnightly', 'Monthly' => 'Monthly'];
    public static $status_list = [
        1 => 'Active, open to new members',
        2 => 'Active, not currently accepting new members',
        3 => 'Active, full but can join waiting list',
        4 => 'Temporarily inactive',
        5 => 'No longer meeting'
    ];
    public static $status_list_short = [
        1 => 'Active',
        2 => 'Full',
        3 => 'Wait list only',
        4 => 'Suspended',
        5 => 'Closed'
    ];

    // $plugin_file is the value of __FILE__ from the main plugin file
    private static $plugin_file;

    /* Limits on the max size of data input */
    const MAX_TIME = 9; // Afternoon - need to increase if longer keys are added to time_list
    const MAX_FREQUENCY = 11; // fortnightly - need to increase if longer keys added to frequency_list
    const MAX_WHEN = 1024; // free text field
    const MAX_EMAIL = 320; // max_email is 64 chars plus @ plus 255S
    const MAX_COST = 1024; // free text field
    const MAX_DAY_NUM = 1; // should be single digit
    const MAX_STATUS = 1; // increase if there are more than 9 statuses in status_list

    /**
     * The id of this post
     *
     * @var string
     */
    public $ID;

    /**
     * Construct a new object for a u3a_group post.
     */
    public function __construct($ID)
    {
        $this->ID = $ID;
    }

    /**
     * Set up the actions and filters used by this class.
     *
     * @param $plugin_file the value of __FILE__ from the main plugin file 
     */
    public static function initialise($plugin_file)
    {
        self::$plugin_file = $plugin_file;

        // Register Group CPT and taxonomy
        add_action('init', array(self::class, 'register_groups'));

        // Routine to run on plugin activation
        register_activation_hook($plugin_file, array(self::class, 'on_activation'));

        // Register the blocks and add a shortcode
        add_action('init', array(self::class, 'register_blocks'));

        // Add default content to new posts of this type
        add_filter('default_content', array(self::class, 'add_default_content'), 10, 2);

        // Change prompt shown for post title
        add_filter('enter_title_here', array(self::class, 'change_prompt'));

        // Set up the custom fields in a metabox (using free plugin from on metabox.io)
        add_filter('rwmb_meta_boxes', [self::class, 'add_metabox'], 10, 1);

        // Customise the Quick Edit panel
        add_action('admin_head-edit.php', array(self::class, 'modify_quick_edit'));

        // Add custom filters to the admin posts list
        add_action('restrict_manage_posts', array(self::class, 'add_admin_filters'));

        // Add action to restrict database field lengths
        add_action('save_post_u3a_group', [self::class, 'validate_group_fields'], 30, 2);

        // Convert metadata fields to displayable text when rendered by the third party Meta Field Block
        add_filter('meta_field_block_get_block_content', array(self::class, 'modify_meta_data'), 10, 2);

    }

    // validate the lengths of fields on save
    public static function validate_group_fields($post_id, $post)
    {
        $value = get_post_meta($post_id, 'status_NUM', true);
        if (strlen($value) > self::MAX_STATUS) {
            update_post_meta($post_id, 'status_NUM', '');
        }
        $value = get_post_meta($post_id, 'day_NUM', true);
        if (strlen($value) > self::MAX_DAY_NUM) {
            update_post_meta($post_id, 'day_NUM', '');
        }
        $value = get_post_meta($post_id, 'time', true);
        if (strlen($value) > self::MAX_TIME) {
            update_post_meta($post_id, 'time', '');
        }
        $value = get_post_meta($post_id, 'frequency', true);
        if (strlen($value) > self::MAX_FREQUENCY) {
            update_post_meta($post_id, 'frequency', '');
        }
        $value = get_post_meta($post_id, 'when', true);
        if (strlen($value) > self::MAX_WHEN) {
            update_post_meta($post_id, 'when', substr($value,0,self::MAX_WHEN));
        }
        $value = get_post_meta($post_id, 'email', true);
        if (strlen($value) > self::MAX_EMAIL) {
            update_post_meta($post_id, 'email', '');
        }
        $value = get_post_meta($post_id, 'email2', true);
        if (strlen($value) > self::MAX_EMAIL) {
            update_post_meta($post_id, 'email2', '');
        }
        $value = get_post_meta($post_id, 'cost', true);
        if (strlen($value) > self::MAX_COST) {
            update_post_meta($post_id, 'cost', substr($value,0,self::MAX_COST));
        }
    }

    /**
     * Registers the custom post type and taxonomy for this class.
     */
    public static function register_groups()
    {
        $args = array(
            'public' => true,
            'show_in_rest' => true,
            'supports' => array('title', 'editor', 'author', 'thumbnail', 'color'),
            'rewrite' => array('slug' => sanitize_title(U3A_GROUP_CPT . 's')),
            'has_archive' => false,
            'menu_icon' => U3A_GROUP_ICON,
            'labels' => array(
                'name' => 'u3a Groups',
                'singular_name' => 'Group',
                'add_new_item' => 'Add Group',
                'add_new' => 'Add New Group',
                'edit_item' => 'Edit Group',
                'all_items' => 'All Groups',
                'view_item' => 'View Group',
                'update_item' => 'Update Group',
                'search_items' => 'Search Groups'
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
        register_post_type(U3A_GROUP_CPT, $args);

        // Retrieve preferred term for categories from u3a Settings

        $category_singular_term = ucfirst(get_option('u3a_catsingular_term', 'category'));
        $category_plural_term = ucfirst(get_option('u3a_catplural_term', 'categories'));

        register_taxonomy(U3A_GROUP_TAXONOMY, U3A_GROUP_CPT, array(
            'hierarchical'      => false,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => sanitize_title(U3A_GROUP_TAXONOMY)),
            'show_in_rest'      => true,
            'meta_box_cb'       => false,
            'labels'            => array(
                'name'              => "Group $category_singular_term",
                'singular_name'     => "Group $category_singular_term",
                'search_items'      => "Search Group $category_plural_term",
                'all_items'         => "All Group $category_plural_term",
                'view_item'         => "View Group $category_singular_term",
                'parent_item'       => "Parent Group $category_singular_term",
                'parent_item_colon' => "Parent Group $category_singular_term:",
                'edit_item'         => "Edit Group $category_singular_term",
                'update_item'       => "Update Group $category_singular_term",
                'add_new_item'      => "Add New Group $category_singular_term",
                'new_item_name'     => "New Group $category_singular_term Name",
                'not_found'         => "No Group $category_plural_term Found",
                'back_to_items'     => "Back to Group $category_plural_term",
                'menu_name'         => "Group $category_plural_term",
            )

        ));
    }

    /**
     * Do tasks that should only be done on activation
     *
     * Register post type, taxonomy and flush rewrite rules.
     * Add default categories for groups
     * Set default u3a group settings
     */
    public static function on_activation()
    {
        self::register_groups();
        delete_option('rewrite_rules');

        $newTerms = array(
            'General',
            'History',
            'Languages',
            'Literature',
            'Natural History',
            'Science and Technology',
            'Sport',
            'Walking'
        );
        // WP won't duplicate terms if they already exist so no point checking here
        foreach ($newTerms as $term) {
            wp_insert_term($term, U3A_GROUP_TAXONOMY);
        }

        // switch off less commonly used group fields
        foreach (array('field_coord2', 'field_deputy', 'field_tutor', 'field_groupemail2') as $option) {
            update_option($option, 9);
        }
    }

    /**
     * Registers the blocks u3a/groupdata and u3a/grouplist, and their render callbacks.
     * Add a shortcode that mimics the grouplist block.
     */
    public static function register_blocks()
    {
        wp_register_script(
            'u3agroupblocks',
            plugins_url('js/u3a-group-blocks.js', self::$plugin_file),
            array('wp-blocks', 'wp-element','wp-components','wp-editor'),
            U3A_SITEWORKS_CORE_VERSION,
            false,
        );
        wp_enqueue_script('u3agroupblocks');

        register_block_type('u3a/grouplist', array(
            'editor_script' => 'u3agroupblocks',
            'render_callback' => array(self::class, 'display_list_cb'),
        ));
        register_block_type('u3a/groupdata', array(
            'editor_script' => 'u3agroupblocks',
            'render_callback' => array(self::class, 'display_cb'),
        ));
        // also add a shortcode!!
        add_shortcode('u3agrouplist', array(self::class, 'display_list_cb'));
    }

    /**
     * Add default content to new posts of this type.
     *
     * @param $post the new post
     * @param $content ignored
     * @usedby filter 'default_content'
     */
    public static function add_default_content($content, $post)
    {
        if ($post->post_type == U3A_GROUP_CPT) {
            $content = '<!-- wp:u3a/groupdata /--><!-- wp:paragraph --><p></p><!-- /wp:paragraph --> ';
            if (post_type_exists('u3a_event')) $content .= ' <!-- wp:u3a/eventlist /-->';
            return $content;
        }
        return $content;
    }

    /**
     * Filter that adds a metabox for a post_type.
     *
     * @param array $metaboxes List of existing metaboxes.
     * Note:  static::field_descriptions() gets the rwmb info for the fields in the metabox.
     *
     * @return array $metaboxes With the added metabox
     * @usedby filter 'rwmb_meta_boxes'
     */
    public static function add_metabox($metaboxes)
    {
        $metabox = [
            'title'    => 'Group Information',
            'id'       => U3A_GROUP_CPT,
            'post_types' => [U3A_GROUP_CPT],
            'context'  => 'normal',
            'autosave' => true,
        ];
        $metabox['fields'] = self::field_descriptions();
        // add metabox to all input rwmb metaboxes
        $metaboxes[] = $metabox;
        return $metaboxes;
    }

    /*
     * Defines the fields for this class.
     *
     * @return array
     */
    public static function field_descriptions()
    {
        $category_singular_term = get_option('u3a_catsingular_term', 'category');
        $ucfirst_category_singular_term = ucfirst($category_singular_term);
        $coordinator_term = ucfirst(get_option('u3a_coord_term', 'coordinator'));

        $fields = [];
        // Now add all the fields to the $fields array in the order they will appear.
        // see https://docs.metabox.io/fields/
        // and https://docs.metabox.io/field-settings/ for details.
        $fields[] = [
            'type'    => 'select',
            'name'    => 'Status',
            'id'      => 'status_NUM',
            'desc'    => 'Whether the group is active, full, etc. Select the appropriate option',
            'options' => self::$status_list,
            'std'     => 'Active', // default value
            'required' => true,
        ];
        $fields[] = [
            'type'       => 'taxonomy',
            'name'       => $ucfirst_category_singular_term,
            'id'         => 'category',
            'taxonomy'   => U3A_GROUP_TAXONOMY,
            'multiple'   => true,
            'field_type' => 'select_advanced',
            'required' => true,
            'desc'    => "You may enter more than one $category_singular_term here.",
        ];
        $fields[] = [
            'type'    => 'heading',
            'name'    => 'When the group usually meets',
            'desc'    => 'Each of meeting day, time period and frequency, should be entered if known',
        ];
        $fields[] = [
            'type'    => 'select',
            'name'    => 'Meeting day',
            'id'      => 'day_NUM',
            'desc'    => 'Leave blank or the day on which regular meetings take place',
            'options' => array_merge([0 => '...'], self::$day_list),
            'std'     => 0, // default value
        ];
        $fields[] = [
            'type' => 'select',
            'name' => 'Time period',
            'id'   => 'time',
            'desc' => 'Leave blank or enter meeting time period',
            'options' => array_merge(['' => '...'], self::$time_list),
            'std'     => '', // default value
            'maxlength' => self::MAX_TIME,
        ];
        $fields[] = [
            'type'    => 'time',
            'name'    => 'Start time',
            'id'      => 'startTime',
            'desc' => 'Optional, input format e.g. 09:30 and 14:45',
            // TODO: Maybe no pattern needed as the picker restricts the value range?
            'pattern' => '[0-2][0-9]:[0-5][0-9]', // catches most bad input!
        ];
        $fields[] = [
            'type'    => 'time',
            'name'    => 'End time',
            'id'      => 'endTime',
            'desc' => 'Optional, input format e.g. 09:30 and 14:45',
            // TODO: Maybe no pattern needed as the picker restricts the value range?
            'pattern' => '[0-2][0-9]:[0-5][0-9]', // catches most bad input!
        ];
        $fields[] = [
            'type'    => 'select',
            'name'    => 'Frequency',
            'id'      => 'frequency',
            'desc'    => 'Leave blank or enter frequency',
            'options' => array_merge(['' => '...'], self::$frequency_list),
            'std'     => '', // default value
            'maxlength' => self::MAX_FREQUENCY,
        ];
        $fields[] = [
            'type'    => 'text',
            'name'    => 'When - additional text',
            'id'      => 'when',
            'desc'    => 'If necessary, enter any additional information about when the group meets',
            'std'     => '', // default value
            'maxlength' => self::MAX_WHEN,
        ];
        $fields[] = [
            'type'    => 'heading',
            'name'    => 'Where the group usually meets',
        ];
        $fields[] = [
            'type'       => 'post',
            'name'       => 'Venue (Optional)',
            'id'         => 'venue_ID',
            'desc'    => 'You can start typing the name to reduce the list of choices.',
            'post_type'  => U3A_VENUE_CPT,
            'query_args' => ['orderby' => 'title', 'order' => 'ASC'],
            'field_type' => 'select_advanced', // this is the default anyway
            'ajax'       => false,  // this seems like a good choice, but try switching it on, when there a lots of venues??
        ];
        $fields[] = [
            'type'    => 'heading',
            'name'    => 'Contact details',
            'desc'    => '',
        ];
        $fields[] = [
            'type'       => 'post',
            'post_type'  => U3A_CONTACT_CPT,
            'query_args' => ['orderby' => 'title', 'order' => 'ASC'],
            'name'       => 'Group ' . $coordinator_term,
            'id'         => 'coordinator_ID',
            'desc'       => "Select or leave blank",
            'ajax'       => false,  // this seems like a good choice, but try switching it on, when there a lots of contacts??
            'required' => false,
        ];
        if (get_option('field_coord2', '1') == '1') {
            $fields[] = [
                'type'       => 'post',
                'post_type'  => U3A_CONTACT_CPT,
                'query_args' => ['orderby' => 'title', 'order' => 'ASC'],
                'name'       => 'Group ' . $coordinator_term . ' 2',
                'id'         => 'coordinator2_ID',
                'desc'       => "Select or leave blank",
                'ajax'       => false,  // this seems like a good choice, but try switching it on, when there a lots of contacts??
                'required' => false,
            ];
        }
        if (get_option('field_deputy', '1') == '1') {
            $fields[] = [
                'type'       => 'post',
                'post_type'  => U3A_CONTACT_CPT,
                'query_args' => ['orderby' => 'title', 'order' => 'ASC'],
                'name'       => 'Deputy',
                'id'         => 'deputy_ID',
                'desc'       => "Select or leave blank",
                'ajax'       => false,  // this seems like a good choice, but try switching it on, when there a lots of contacts??
                'required' => false,
            ];
        }
        if (get_option('field_tutor', '1') == '1') {
            $fields[] = [
                'type'       => 'post',
                'post_type'  => U3A_CONTACT_CPT,
                'query_args' => ['orderby' => 'title', 'order' => 'ASC'],
                'name'       => 'Tutor',
                'id'         => 'tutor_ID',
                'desc'       => "Select or leave blank",
                'ajax'       => false,  // this seems like a good choice, but try switching it on, when there a lots of contacts??
                'required' => false,
            ];
        }
        if (get_option('field_groupemail', '1') == '1') {
            $fields[] = [
                'type' => 'email',
                'name' => 'Primary group email',
                'id'   => 'email',
                'desc' => 'Email address for group',
                'maxlength' => self::MAX_EMAIL,
            ];
        }
        if (get_option('field_groupemail2', '1') == '1') {
            $fields[] = [
                'type' => 'email',
                'name' => 'Secondary group email',
                'id'   => 'email2',
                'desc' => 'Alternate email address for group',
                'maxlength' => self::MAX_EMAIL,
            ];
        }
        if (get_option('field_cost', '1') == '1') {
            $fields[] = [
                'before'     => 'Optional Cost Info',
                'type'    => 'text',
                'name'    => 'Cost',
                'id'      => 'cost',
                'desc'    => 'You may enter a line of cost information here.',
                'std'     => '', // default value
                'maxlength' => self::MAX_COST,
            ];
        }
        return $fields;
    }

    /**
     * Add filter by group category to "all Groups" posts list
     * @param $post_type
     * @usedby action 'restrict_manage_posts'
     */
    public static function add_admin_filters($post_type)
    {
        if (U3A_GROUP_CPT !== $post_type) {
            return;
        }

        // Selector for event category
        $taxonomy_slug = U3A_GROUP_TAXONOMY;
        $select_title = 'All ' . ucfirst(get_option('u3a_catplural_term', 'categories'));

        // Retrieve taxonomy terms and genenerate select
        $terms = get_terms($taxonomy_slug);
        //phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped,WordPress.Security.NonceVerification.Recommended
        print "<select name='{$taxonomy_slug}' id='{$taxonomy_slug}' class='postform'>";
        print '<option value="">' . $select_title . '</option>';
        foreach ($terms as $term) {
            printf(
                '<option value="%1$s" %2$s>%3$s (%4$s)</option>',
                $term->slug,
                ((isset($_GET[$taxonomy_slug]) && ($_GET[$taxonomy_slug] == $term->slug)) ? ' selected="selected"' : ''),
                esc_html($term->name),
                $term->count
            );
        }
        print '</select>';
        //phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped,WordPress.Security.NonceVerification.Recommended
    }

    /*
     * Calls the display function for an object of this class.
     * This code is common to all our custom post types, so don't edit it!
     */
    public static function display_cb($atts, $content = '')
    {
        global $post;
        if (U3A_GROUP_CPT != $post->post_type) { // oops shouldn't be here
            return 'Error: only for use with items of type ' . U3A_GROUP_CPT;
        }
        $my_object = new self($post->ID); // an object of this class
        return $my_object->display($atts, $content);
    }

    /*
     * This is the callback function for block u3a/grouplist.
     * Calls the selected option to display a list of groups
     * Can be called either as a shortcode or as a render callback of a block.
     */
    public static function display_list_cb($atts, $content = '')
    {
        $u3a_grouplist_type = get_option('u3a_grouplist_type', 'sorted');
        if ('sorted' == $u3a_grouplist_type) {
            return self::group_list_sorted($atts, $content = '');
        } else { // 'filtered'
            return self::group_list_filtered($atts, $content = '');
        }
    }

    /*
     * List all groups sorted in some way.
     * Can be called either as a shortcode or as a render callback of a block.
     * Attributes will also be taken from the page's URL query parameters.
     * If present these query parameters will override parameters passed as arguments
     * 
     * @param arr $atts attributes with the following possible keys
     *  Attributes
     *  sort: either 'alpha' (default) for a listing in group name alphabet order
     *        or 'cat' for a listing grouped by u3a_group_category
     *        or 'day' for a listing grouped by meeting day
     *        or 'venue' for a listing grouped by venue
     *  status=y: to include each group's status (default=y)
     *  when=y: to include meeting day, time and frequency (default=y)
     *  Maybe in future add more options!!
     */
    public static function group_list_sorted($atts, $content = '')
    {
        global $wp;
        // valid display_args names and default values
        $display_args = [
            'cat'  => 'all',
            'sort' => 'alpha',
            'flow' => 'column',
            'status' => 'y',
            'when' => 'y',
        ];
        // set from page query or from call attributes
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        foreach ($display_args as $name => $default) {
            if (isset($_GET[$name])) {
                $display_args[$name] = sanitize_text_field($_GET[$name]);
         // phpcs:enable WordPress.Security.NonceVerification.Recommended
            } elseif (isset($atts[$name])) {
                $display_args[$name] = $atts[$name];
            }
        }
        $list_type = $display_args['sort'];
        $cat = $display_args['cat'];
        $category_singular_term = get_option('u3a_catsingular_term', 'category');
        $html = '';

        // set up some buttons to provide some built-in options
        // omit this if not displaying all groups
        if ('all' == $cat){
            $thispage = untrailingslashit(home_url($wp->request));
            $button_identifier = "list_button_anchor";
            $html = <<<END
            <div id=$button_identifier class="u3agroupbuttons">
                <a class="wp-element-button" href="$thispage?sort=alpha#$button_identifier">Alphabetical</a>
                <a class="wp-element-button" href="$thispage?sort=cat#$button_identifier">By $category_singular_term</a>
                <a class="wp-element-button" href="$thispage?sort=day#$button_identifier">By meeting day</a>
                <a class="wp-element-button" href="$thispage?sort=venue#$button_identifier">By venue</a>
            </div>
            END;
        }

        $list_flow = $display_args['flow'];
        if ('row' == $list_flow) {
            $html .= '<div class="u3agrouplist-row-first-flow">';
        } else {
            $html .= '<div class="u3agrouplist-column-first-flow">';
        }
        // we will close the <div> before returning!

        // set up basic query args
        $query_args = array(
            'post_type' => 'u3a_group',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        );

        // if only displaying groups for a selected category 
        if ('all' != $cat) {
            $query_args['tax_query'] = [[
                'taxonomy' => U3A_GROUP_TAXONOMY,
                'field'    => 'slug',
                'terms' => $cat,
            ]];
            $cat_name = get_term_by('slug', $cat, U3A_GROUP_TAXONOMY)->name;
            $html .= "<h3>Groups in $category_singular_term: $cat_name</h3>";
            $html .= self::display_selected_groups($query_args, $display_args);

        } elseif ('alpha' == $list_type) { // list all groups alphabetically
            $html .= '<h3>Groups listed alphabetically</h3>';
            $html .= self::display_selected_groups($query_args, $display_args);

        } elseif ('day' == $list_type) { // group the list by usual meeting day of week
            $html .= "<h3>Groups listed by meeting day</h3>\n";

            $weekdays = self::$day_list;
            $weekdays[0] = "Unspecified"; // append default value( 0 ) to list.
            foreach ($weekdays as $day_NUM => $weekday) {
                $query_args['meta_query'] = [
                        [
                            'key' => 'day_NUM',
                            'value'    => $day_NUM,
                        ],
                ];
                // Alter query for 'Unspecified' to select when day_NUM is zero or not defined
                // Only needed when database has been incorrectly loaded, as day_NUM should always be set.
                if (0 == $day_NUM) {
                    $query_args['meta_query'] = [
                        'relation' => 'OR',
                        [
                            'key' => 'day_NUM',
                            'value' => $day_NUM
                        ],
                        [
                            'key' => 'day_NUM',
                            'compare' => 'NOT EXISTS'
                        ]
                    ];
                }
                $day_html = self::display_selected_groups($query_args, $display_args, '');
                if (!empty($day_html)) {
                    $html .= <<< END
                    <h3>$weekday</h3>
                        $day_html
                    END;
                }
            } // endfor

        } elseif ('venue' == $list_type) { // group the list by venue
            $html .= "<h3>Groups listed by venue</h3>\n";
            $venue_query_args = [
                'post_type' => 'u3a_venue',
                'posts_per_page' => -1,
                'orderby' => 'title',
                'order' => 'ASC',
                'fields' => 'ids'
            ];
            $venue_IDs = get_posts($venue_query_args);
            $venue_IDs[] = 0; //  append default value( 0 ) to list.
            foreach ($venue_IDs as $venue_ID) {
                $query_args['meta_query'] = [
                        [
                            'key' => 'venue_ID',
                            'value'    => $venue_ID,
                        ],
                ];
                // Alter query for 'Unspecified' to select when venue_ID is zero or not defined
                if (0 == $venue_ID) {
                    $query_args['meta_query'] = [
                        'relation' => 'OR',
                        [
                            'key' => 'venue_ID',
                            'value' => $venue_ID
                        ],
                        [
                            'key' => 'venue_ID',
                            'compare' => 'NOT EXISTS'
                        ]
                    ];
                }
                $venue_html = self::display_selected_groups($query_args, $display_args, '');
                if (!empty($venue_html)) { // so ignore venues with no groups
                    if (0 == $venue_ID) {
                        $venue_header = "Unspecified venue";
                    } else {
                        $venue_object = new U3aVenue($venue_ID);
                        $venue_header = $venue_object->venue_name_with_link();
                    }
                    $html .= <<< END
                    <h3>$venue_header</h3>
                        $venue_html
                    END;
                }
            } // endfor

        } elseif ('cat' == $list_type) { // group the list by u3a_group_category
            $html  .= <<< END
            <h3>Groups listed by $category_singular_term</h3>
            END;
            $term_args = array(
                'taxonomy'      => U3A_GROUP_TAXONOMY,
                'hide_empty'    => true,  // ignore categories with no groups
                'orderby'       => 'name',
            );
            $term_query = new WP_Term_Query($term_args);
            foreach ($term_query->get_terms() as $category) {
                $cat_name = $category->name;
                $cat_slug = $category->slug;
                $html .= <<< END
                <h3>$cat_name</h3>
                END;
                $query_args = array(
                    'post_type' => 'u3a_group',
                    'posts_per_page' => -1,
                    'orderby' => 'title',
                    'order' => 'ASC',
                    //              'u3a_group_category' => $cat_slug, //deprecated since wp3.1
                    'tax_query' => [
                        [
                            'taxonomy' => U3A_GROUP_TAXONOMY,
                            'field'    => 'slug', // default is term_id
                            'terms' => $cat_slug,
                        ]
                    ],
                );
                $html .= self::display_selected_groups($query_args, $display_args);
            } // endfor
        } else {
            $html .= 'unknown sort attribute in u3a_groups_list';
        }
        $html .= "</div> <!-- end of u3agrouplist -->\n";
        return $html;
    }
    /**
     * List all groups, potentially filtered in some way.
     * Might update the code to make use of function display_selected_groups TBD
     */
    public static function group_list_filtered($atts, $content = '')
    {
        global $wp;
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $list_type = (isset($_GET['type'])) ? sanitize_text_field($_GET['type']) : "";
        $get_group_listing = false;
        // valid display_args names and default values
        $display_args = [
            'flow' => 'column',
            'status' => 'y',
            'when' => 'n',
        ];
        // set from page query or from call attributes
        foreach ($display_args as $name => $default) {
            // phpcs:disable WordPress.Security.NonceVerification.Recommended
            if (isset($_GET[$name])) {
                $display_args[$name] = sanitize_text_field($_GET[$name]);
            // phpcs:enable WordPress.Security.NonceVerification.Recommended
            } elseif (isset($atts[$name])) {
                $display_args[$name] = $atts[$name];
            }
        }
        // Add back to list
        $url = untrailingslashit(home_url($wp->request));
        $para_with_back_link = "<p><br><a class=\"wp-element-button\" href=\"$url\">Back to full group list</a></p>";
        $query_args = array(
            'post_type' => 'u3a_group',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'post_status' => 'publish',
        );
        $category_singular_term = get_option('u3a_catsingular_term', 'category');
        switch ($list_type) {
                // Select groups in the chosen category
            case 'par':
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $par = (isset($_GET['par'])) ? sanitize_text_field($_GET['par']) : '';
                $none_msg = "<p>No groups found in $category_singular_term $par</p>";
                if (!empty($par)) {
                    $query_args['tax_query'] = [['taxonomy' => U3A_GROUP_TAXONOMY, 'field' => 'name', 'terms' => $par]];
                    $list_heading = "Groups in $category_singular_term $par";
                    $get_group_listing = true; // so will populate $html later
                } else {
                    $html = $none_msg;
                }
                break;

            case 'day':
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $day = (isset($_GET['day'])) ? sanitize_text_field($_GET['day']) : '';
                $none_msg = "<p>No groups found that meet on $day</p>";
                if (!empty($day)) {
                    // $day_NUM = date("N", strtotime($day)) - 1;
                    $day_NUM = array_search($day, self::$day_list);
                    $query_args['meta_query'] = [['key' => 'day_NUM', 'value' => $day_NUM, 'compare' => '=']];
                    $list_heading = "Groups meeting on $day";
                    $get_group_listing = true; // so will populate $html later
                } else {
                    $html = $none_msg;
                }
                break;

            case 'letter':
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $letter = (isset($_GET['letter'])) ? sanitize_text_field($_GET['letter']) : '';
                $none_msg = "<p>No matching groups found</p>";
                if (!empty($letter)) {
                    global $wpdb;
                    // first do preliminary query
                    $results = $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT ID FROM %i WHERE post_type = %s AND SUBSTRING(post_title,1,1) = %s AND post_status = 'publish'; ",
                            $wpdb->prefix.'posts',
                            U3A_GROUP_CPT,
                            $letter,
                            ),
                        ARRAY_A
                    );
                    $post_ids = array_column($results, 'ID');
                    $post_ids = count($post_ids) ? $post_ids : array(-1);
                    //the above is a simple workaround as passing an empty array to post__in currently returns the most recent posts (see Ticket #28099).
                    $query_args['post__in'] = $post_ids;
                    $list_heading = "Groups starting with letter $letter";
                    $get_group_listing = true; // so will populate $html later
                } else {
                    $html = $none_msg;
                }
                break;

                // The default case is no values set in query string: display either the group list or the filters
            default:
                $numGroups = wp_count_posts(U3A_GROUP_CPT)->publish;
                $threshold = get_option('grouplist_threshold', 20);
                if ($numGroups > $threshold) {
                    $all_group_posts = get_posts($query_args);

                    $html = "<p>Find a group by its initial letter</p>";
                    $html .= self::get_letter_list($all_group_posts);

                    $html .= "<p>Find a group by its $category_singular_term</p>";
                    $html .= self::get_parent_list($all_group_posts);

                    $html .= "<p>Find a group by day of the week it operates<br> Groups with unspecified day will be omitted.</p>";
                    $html .= self::get_day_list($all_group_posts);
                } else {
                    $none_msg = "<p>No groups found.</p>";
                    $list_heading = "Showing all groups";
                    $get_group_listing = true; // so will populate $html later
                    $para_with_back_link = ''; // not needed in default case!!
                }
        } //end of switch

        if ($get_group_listing) {
            // List the selected groups
            $list_head = '<div class="u3agrouplist-column-first-flow">';
            $list_flow = $display_args['flow'];
            if ('row' == $list_flow) {
                $list_head = '<div class="u3agrouplist-row-first-flow">';
            }
            $group_list_HTML = self::display_selected_groups($query_args, $display_args, '');
            if (!empty($group_list_HTML)) {
                $html = <<< END
                $list_head
                <h3>$list_heading</h3>
                $group_list_HTML
                </div>
                $para_with_back_link
                END;
            } else {
                $html = $none_msg;
            }
        }
        return $html;
    }

    /** Return HTML markup for group letter selector.
     * Only include letters that have groups associated with them.
     ^
     * @param array $posts all published group posts.
     *
     * @return HTML <div> with a set of links to current page with added query parameter,
     *              specifying a letter, e,g ?type=letter&letter=b
     */
    public static function get_letter_list($posts)
    {
        global $wp;
        $letters_used = '';
        foreach ($posts as $post) {
            $letters_used .= strtoupper(substr($post->post_title, 0, 1));
        }
        $html = "<div class=\"u3agroupselector\">\n";
        foreach (range('A', 'Z') as $let) {
            if (strpos($letters_used, $let) === false) continue;
            $url = untrailingslashit(home_url($wp->request)) . "?type=letter&letter=" . $let;
            $html .= "<a class='wp-element-button' href='" . $url . "' style='text-align: center; display:inline-block;'>" . $let . "</a>\n";
        }
        $html .= "</div>\n";
        return $html;
    }

    /** Return HTML markup for group category selector.
     * Only include categories that have groups associated with them.
     *
     * @param array $posts all published group posts.
     *
     * @return HTML <div> with a set of links to current page with added query parameter,
     *              specifying a category, e,g ?type=par&par=Arts
     */
    public static function get_parent_list($posts)
    {
        global $wp;
        $catsUsed = array();
        foreach ($posts as $post) {
            $categories = get_the_terms($post->ID, U3A_GROUP_TAXONOMY);
            if ((false !== $categories) && !is_wp_error($categories)) {
                foreach ($categories as $cat) {// allows for a group to be in multiple categories
                    $catsUsed[] = $cat->name;
                }
            }
        }
        $uniqueCats = array_unique($catsUsed);
        $html = "<div class=\"u3agroupselector\">\n";
        $url = untrailingslashit(home_url($wp->request)) . "?type=par&par=";
        foreach ($uniqueCats as $catName) {
            $html .= "<a class='wp-element-button' href='" . $url . $catName . "' style='display:inline-block;'>" . $catName . "</a>";
        }
        $html .= "</div>\n";

        return $html;
    }

    /** Return HTML markup for group day selector.
     * Only include days that have groups associated with them.
     *
     * @param array $posts all published group posts.
     *
     * @return HTML <div> with a set of links to current page with added query parameter,
     *              specifying a category, e,g ?type=day&day=Tuesday
     */
    public static function get_day_list($posts)
    {
        global $wp;
        $daysUsed = array();
        foreach ($posts as $post) {
            $day_NUM = get_post_meta($post->ID, 'day_NUM', true);
            if (!empty($day_NUM)) $daysUsed[] = $day_NUM;
        }
        $uniqueDays = array_unique($daysUsed);
        asort($uniqueDays);
        $url = untrailingslashit(home_url($wp->request)) . "?type=day&day=";
        $html = "<div class=\"u3agroupselector\">";
        foreach ($uniqueDays as $day_NUM) {
            $html .= "<a class='wp-element-button' style='display:inline-block;' href='" . $url . self::$day_list[$day_NUM] . "'>" . self::$day_list[$day_NUM] . "</a>";
        }
        $html .= "</div>\n";

        return $html;
    }

    /**
     * Returns HTML for the groups defined by $query_args, with displayed items defined by $display_args.
     *
     * @param array $query_args parameters for WP_Query
     * @param array $display_args options for what info is displayed for each group
     *        possble args:
     *        'status' = y
     *        'when' = y
     * @param str $none_msg output if no matching groups found.
     * @return HTML <ul> with a list item for each group found.     
     */
    public static function display_selected_groups($query_args, $display_args, $none_msg = 'No groups.')
    {
        $show_status = $display_args['status'];
        $show_when = $display_args['when'];

        $groups = new WP_Query($query_args);
        if ($groups->have_posts()) :
            $html = "<ul>\n";

            while ($groups->have_posts()) : $groups->the_post();
                $the_group = new U3aGroup(get_the_ID());
                $permalink = get_the_permalink();
                $title =  get_the_title();
                $html .= <<< END
            <li>
                <span class="u3a_group_title"><a href="$permalink">$title</a></span>
            END;
                if ('y' == $show_status) {
                    $status = $the_group->status_text_short();
                    $html .= "<br><span class=\"u3a_group_status\">Status: $status </span>\n";
                }
                if ('y' == $show_when) {
                    $when = $the_group->when_text();
                    if (!empty($when)) {
                        $html .= "<br><span class=\"u3a_group_when\">$when </span>\n";
                    }
                }
                // later versions will make display depend other display_args, maybe! TBD.
                $html .= "</li>";
            endwhile;

            $html .= "</ul>\n";

        else :
            $html = $none_msg;

        endif;
        wp_reset_postdata(); // ensures that the global $post has been restored to the current post in the main query
        return $html;
    }


    // Below here are object methods.

    /*
     * Returns the HTML for this object's custom data.
     * This simple version ignores any attributes in $atts.
     * Also assumes that $content is unused by the caller.
     *
     * @return str The HTML.
     */
    public function display($atts, $content = '')
    {
        $coordinator_term = ucfirst(get_option('u3a_coord_term', 'coordinator'));

        $status = $this->status_text();

        // Group leader (or whatever term is set) as the principal (or only) contact
        $contacttext = $this->contact_text('coordinator_ID', 'Group ' . $coordinator_term);

        // now all the other optional contact data, if required
        $extrahtml = '';
        if (get_option('field_coord2', '1') == '1') {
            // Second Group leader (or whatever term is set)
            $extrahtml .= $this->contact_text('coordinator2_ID', 'Group ' . $coordinator_term);
        }
        if (get_option('field_deputy', '1') == '1') {
            // Deputy leader (or whatever term is set)
            $extrahtml .= $this->contact_text('deputy_ID', 'Deputy ' . $coordinator_term);
        }
        if (get_option('field_tutor', '1') == '1') {
            // Tutor
            $extrahtml .= $this->contact_text('tutor_ID', 'Tutor');
        }
        $extrahtml .= $this->extra_emailstext();

        // When
        $when = $this->when_text();
        $when_row = (!empty($when)) ? "<tr><td>When:</td> <td>$when</td></tr>" : '';

        // Where
        $venue_object = new U3aVenue(get_post_meta($this->ID, 'venue_ID', true));
        $venue_HTML = $venue_object->venue_name_with_link();
        $venue_row = (!empty($venue_HTML)) ? "<tr><td>Venue:</td> <td>$venue_HTML</td></tr>" : '';

        // Cost
        $cost = esc_html(get_post_meta($this->ID, 'cost', true));
        $cost_row = (!empty($cost)) ? "<tr><td>Cost:</td> <td>$cost</td></tr>" : '';

        // compose output
        $html = <<< END
        <!-- Custom Single Group View -->
        <table class="u3a_group_table">
            <tr><td>Status:</td><td>$status</td></tr>
            $contacttext
            $extrahtml
            $when_row
            $venue_row
            $cost_row
        </table>
        END;
        return $html;
    }

    /**
     * Returns the short text version of the group's status
     * @return str
     */
    public function status_text_short()
    {
        $status_NUM = get_post_meta($this->ID, 'status_NUM', true);
        return (!empty($status_NUM)) ? self::$status_list_short[$status_NUM] : 'ERROR: no status entered';
    }

    /**
     * Returns the long text version of the group's status
     * @return str
     */
    public function status_text()
    {
        $status_NUM = get_post_meta($this->ID, 'status_NUM', true);
        return (!empty($status_NUM)) ? self::$status_list[$status_NUM] : 'ERROR: no status entered';
        // or could return both long and short values in form "short = long" TBD
    }

    /**
     * Returns one line describing when the group meets, based on day, time and frequency
     * and, if present, a second line with free text.
     * The text is structured to be grammatical, even if some fields are missing.
     *
     * @return str with <br> between the lines.
     */
    public function when_text()
    {
        $day_NUM = get_post_meta($this->ID, 'day_NUM', true);
        // set valid weekday or empty string (use ?? operator)
        $weekday = (!empty($day_NUM)) ? (self::$day_list[$day_NUM] ?? '') : '';
        $daytext = ($weekday != '') ? 'on ' . $weekday : '';

        $time = get_post_meta($this->ID, 'time', true);
        // set valid time period value or empty string (use ?? operator)
        $time = (!empty($time)) ? (self::$time_list[$time] ?? '') : '';
        $time = strtolower($time);
        $timetext = ($time == '' || $time == 'all day') ? $time : $time . 's';  // usually add 's'!

        $start = get_post_meta($this->ID, 'startTime', true);  // in NN:NN format
        $start = (!empty($start)) ? date('g:i',strtotime($start)) : '';// e.g. convert to 3:30 
        $connector = '-';  // without spaces to enforce (simply) no break of line
        $end = get_post_meta($this->ID, 'endTime', true);
        $end = (!empty($end)) ? $connector . date('g:i',strtotime($end)) : '';// e.g. convert to -5:30
        $fromtotext = $start . $end;

        $daytext .= ($weekday != '' && $time == '') ? 's' : '';  // usually add 's' if time is blank

        $frequency = get_post_meta($this->ID, 'frequency', true);
        // set valid frequency value or empty string (use ?? operator)
        $frequency = (!empty($frequency)) ? (self::$frequency_list[$frequency] ?? '') : '';

        // trim to ensure content free string is empty!
        $when_main_text = trim("$frequency $daytext $timetext $fromtotext");
        // e.g. Monthly on Tuesday Mornings 9:00-12:30
        $when_extra_text = esc_html(get_post_meta($this->ID, 'when', true));
        // return both values with <br> only if both items are not empty
        return implode('<br>', array_filter([$when_main_text, $when_extra_text]));
    }


    /**
     * Returns HTML table row with contact details.
     * @param str $role_field the name of the post-meta field of a contact
     * @param str $rolename the display name for this role
     *
     * @return str HTML complete <tr> item.
     */
    public function contact_text($role_field, $rolename)
    {
        $html = '';
        $contact = new U3aContact(get_post_meta($this->ID, $role_field, true));
        $contact_info = $contact->contact_text();
        if ($contact_info) {
            $html .= "<tr><td>$rolename:</td><td>$contact_info</td></tr>";
        }
        return $html;
    }

    /**
     * Returns HTML table row with email details for one or two group email fields.
     * The email addresses may be cloaked, depending on option settings.
     * 
     * @return str HTML complete <tr> item.
     */
    public function extra_emailstext()
    {
        $html = '';
        $email = '';
        $email2 = '';
        $group_name = get_the_title($this->ID);
        // strip out any trailing word "group" as we are about to suffix it with " group".
        if (' group' == strtolower(substr($group_name, -6))) {
            $group_name = substr($group_name, 0, strlen($group_name) - 6);
        }
        if (get_option('field_groupemail', '1') == '1') {
            $email = U3aContact::cloak_email(get_post_meta($this->ID, 'email', true), "$group_name group");
        }
        if (get_option('field_groupemail2', '1') == '1') {
            $email2 = U3aContact::cloak_email(get_post_meta($this->ID, 'email2', true), "$group_name group");
        }
        if (empty($email) && !empty($email2)) {
            // just in case the option to show second group email is selected but group email is not!
            $email = $email2;
            $email2 = '';
        }
        if (!empty($email)) {
            $html .= "<tr><td>Group email:</td>";
            $html .= empty($email2) ? "<td> $email</td>" : "<td> $email or $email2</td>";
            $html .= "</tr>";
        }
        return $html;
    }

    /** 
     * Convert event metadata to displayable text when rendered by the third party Meta Field Block.
     * Ref https://wordpress.org/plugins/display-a-meta-field-as-block/
     * (WP won't have a problem if the block isn't present)
     * Where metadata is stored as references/codes return the associated text string
     * Where metadata is already in text form leave alone
     * @usedby filter 'meta_field_block_get_block_content'
     */
    public static function modify_meta_data($content, $attributes)
    {
        if ($content != '' ) {
            switch ($attributes['fieldName']) {
                case 'status_NUM':
                    $content = self::$status_list[$content];
                    break;
                case 'day_NUM':
                    $content = ($content == 0) ? '' : self::$day_list[$content];
                    break;
                case 'venue_ID':
                case 'coordinator_ID':
                case 'coordinator2_ID':
                case 'deputy_ID':
                case 'tutor_ID':
                    $content = get_the_title($content);
            }
        }
        return $content;
    }
}
