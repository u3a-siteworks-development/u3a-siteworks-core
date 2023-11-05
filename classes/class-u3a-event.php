<?php

/**
 * This class manages these metadata fields:
 * eventDate        event date YYYY-MM-DD
 * eventTime        time of event HH:MM
 * eventDays        event duration in days (integer)
 * eventGroup_ID    ID of group
 * eventVenue_ID    ID of venue
 * eventOrganiser_ID  ID of contact
 *  also each event is assigned to an event category.
 */
class U3aEvent
{
    use ModifyQuickEdit;
    use ChangePrompt;

    /**
     * The post_type for this class
     *
     * @var string 
     */
    public static $post_type = U3A_EVENT_CPT;

    /**
     * The term used for the title of these custom posts
     *
     * @var string 
     */
    public static $term_for_title = "title for event";

    // $plugin_file is the value of __FILE__ from the main plugin file
    private static $plugin_file;

    /**
     * The ID of this post
     *
     * @var string
     */
    public $ID;

    /* Limits on the max size of data input */
    const MAX_COST = 1024;
    const MAX_DATE = 10; // yyyy-mm-dd
    const MAX_TIME = 5; // hh:mm

    /*
     * Construct a new object for a u3a_group post.
     *
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

        // Register Event CPT and taxonomy
        add_action('init', array(self::class, 'register_events'));

        // Routine to run on plugin activation
        register_activation_hook($plugin_file, array(self::class, 'on_activation'));

        // Register the blocks and add matching shortcode
        add_action('init', array(self::class, 'register_blocks'));

        // Set up the custom fields in a metabox (using free plugin from on metabox.io)
        add_filter('rwmb_meta_boxes', [self::class, 'add_metabox'], 10, 1);

        // Add action to create/update eventEndDate meta field
        add_action('save_post_u3a_event', [self::class, 'set_eventEndDate'], 20, 2);

        // Add action to restrict database field lengths
        add_action('save_post_u3a_event', [self::class, 'validate_event_fields'], 30, 2);
    
        // Add default content to new posts of this type
        add_filter('default_content', array(self::class, 'add_default_content'), 10, 2);

        // Change prompt shown for post title
        add_filter('enter_title_here', array(self::class, 'change_prompt'));

        // Modify the query when a Query Block is used to display posts of this type
        // so that when user selects sort in date order, the event date is used instead of the post date
        add_filter('query_loop_block_query_vars', array(self::class, 'filter_events_query'), 10, 1);

        // Alter the columns that are displayed in the Posts list admin page
        add_filter('manage_' . U3A_EVENT_CPT . '_posts_columns', array(self::class, 'change_columns'));
        add_action('manage_' . U3A_EVENT_CPT . '_posts_custom_column', array(self::class, 'show_column_data'), 10, 2);
        add_filter('manage_edit-' . U3A_EVENT_CPT . '_sortable_columns', array(self::class, 'make_column_sortable'));
        add_action('pre_get_posts', array(self::class, 'sort_column_data'));

        // Add custom filters to the admin posts list
        add_filter('parse_query', array(self::class, 'filter_posts'));
        add_action('restrict_manage_posts', array(self::class, 'add_admin_filters'));

        // Customise the Quick Edit panel
        add_action('admin_head-edit.php', array(self::class, 'modify_quick_edit'));

        // Convert event date to system format when rendered by the third party Meta Field Block
        add_filter('meta_field_block_get_block_content', array(self::class, 'modify_meta_date'), 10, 2);
    
    }

        // validate the lengths of fields on save
    public static function validate_event_fields($post_id, $post)
    {
        // shorten values if they did not come in from the client.
        // other fields are restricted by being of type 'post' (20).
        // Still have to protect the ones which are formatted by pattern.
        $value = get_post_meta($post_id, 'cost', true);
        if (strlen($value) > self::MAX_COST) {
            update_post_meta($post_id, 'cost', substr($value, 0 , self::MAX_COST));
        }
        $value = get_post_meta($post_id, 'eventDate', true);
        if (strlen($value) > self::MAX_DATE) {
            update_post_meta($post_id, 'eventDate', 0);
        }
        $value = get_post_meta($post_id, 'eventTime', true);
        if (strlen($value) > self::MAX_TIME) {
            update_post_meta($post_id, 'eventTime', 0);
        }
    }
    
    /**
     * Registers the custom post type and taxonomy for this class.
     */
    public static function register_events()
    {
        $args = array(
            'public' => true,
            'show_in_rest' => true,
            'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt'),
            'rewrite' => array('slug' => sanitize_title(U3A_EVENT_CPT . 's')),
            'has_archive' => false,
            'menu_icon' => U3A_EVENT_ICON,
            'labels' => array(
                'name' => 'u3a Events',
                'singular_name' => 'Event',
                'add_new_item' => 'Add Event',
                'add_new' => 'Add New Event',
                'edit_item' => 'Edit Event',
                'all_items' => 'All Events',
                'view_item' => 'View Event',
                'update_item' => 'Update Event',
                'search_items' => 'Search Events'
            )
        );

        register_post_type(U3A_EVENT_CPT, $args);

        register_taxonomy(U3A_EVENT_TAXONOMY, U3A_EVENT_CPT, array(
            'hierarchical'      => false,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'event-category'),
            'show_in_rest'      => true,
            'meta_box_cb'       => false,
            'labels'            => array(
                'name'              => 'Event Category',
                'singular_name'     => 'Event Category',
                'search_items'      => 'Search Event Categories',
                'all_items'         => 'All Event Categories',
                'view_item'         => 'View Event Category',
                'parent_item'       => 'Parent Event Category',
                'parent_item_colon' => 'Parent Event Category:',
                'edit_item'         => 'Edit Event Category',
                'update_item'       => 'Update Event Category',
                'add_new_item'      => 'Add New Event Category',
                'new_item_name'     => 'New Event Category Name',
                'not_found'         => 'No Event Categories Found',
                'back_to_items'     => 'Back to Event Categories',
                'menu_name'         => 'Event Categories',
            )

        ));
    }

    /**
     * Do tasks that should only be done on activation
     *
     * Register post type, taxonomy and flush rewrite rules.
     * Add default categories for events
     */
    public static function on_activation()
    {
        self::register_events();
        delete_option('rewrite_rules');

        $newTerms = array(
            'Meeting',
            'Outing',
            'Study Day',
            'Social',
            'Summer School',
            'Holiday',
            'Other',
            'Workshop',
        );
        foreach ($newTerms as $term) {
            wp_insert_term($term, U3A_EVENT_TAXONOMY);
        }
    }

    /**
     * Filter that adds a metabox for a post_type.
     *
     * @param array $metaboxes List of existing metaboxes.
     * Note:  static::field_descriptions() gets the rwmb info for the fields in the metabox.
     *
     * @return array $metaboxes With the added metabox
     */
    public static function add_metabox($metaboxes)
    {
        $metabox = [
            'title'    => 'Event Information',
            'id'       => U3A_EVENT_CPT,
            'post_types' => [U3A_EVENT_CPT],
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
        $fields = [];
        // Now add all the fields to the $fields array in the order they will appear.
        // see https://docs.metabox.io/fields/
        // and https://docs.metabox.io/field-settings/ for details.
        $fields[] = [
            'type'       => 'taxonomy',
            'name'       => 'Event category',
            'id'         => 'category',
            'taxonomy'   => U3A_EVENT_TAXONOMY,
            'field_type' => 'select_advanced',
            'required' => true,
        ];
        $fields[] = [
            'type'    => 'date',
            'name'    => 'Event date',
            'id'      => 'eventDate',
            'required' => true,
            // TODO: Maybe no pattern needed as the picker restricts the value range?
            'pattern' => '[1-2][0-9][0-9][0-9]-[0-1][0-9]-[0-3][0-9]', // catches most bad input!
       ];
        $fields[] = [
            'type'    => 'time',
            'name'    => 'Start time',
            'id'      => 'eventTime',
            'desc' => 'Optional',
            // TODO: Maybe no pattern needed as the picker restricts the value range?
            'pattern' => '[0-2][0-9]:[0-5][0-9]', // catches most bad input!
        ];
        $fields[] = [
            'type'    => 'number',
            'name'    => 'Duration (days)',
            'id'      => 'eventDays',
            'min'     => 1,
            'max'     => 100,
            'desc' => 'Optional',
        ];
        if (!current_user_can('edit_others_posts')) {  // ie Editor or above
            $user = wp_get_current_user();
            $group_post_query_args = ['author' => $user->ID, 'orderby' => 'title', 'order' => 'ASC'];
        } else {
            $group_post_query_args = ['orderby' => 'title', 'order' => 'ASC'];
        }
        $fields[] = [
            'type'       => 'post',
            'name'       => 'Group',
            'id'         => 'eventGroup_ID',
            'desc'    => 'Only if the event is for a specific group.',
            'post_type'  => U3A_GROUP_CPT,
            'query_args' => $group_post_query_args,
            'field_type' => 'select_advanced', // this is the default anyway
            'ajax'       => false,  // this seems like a good choice, but try switching it on, when there a lots of groups??
            'required' => current_user_can('edit_others_posts') ? false : true,  // 'Author' must select a group
        ];
        $fields[] = [
            'type'       => 'post',
            'name'       => 'Venue',
            'id'         => 'eventVenue_ID',
            'desc'    => 'Optional',
            'post_type'  => U3A_VENUE_CPT,
            'query_args' => ['orderby' => 'title', 'order' => 'ASC'],
            'field_type' => 'select_advanced',
            'ajax'       => false,  // this seems like a good choice, but try switching it on, when there a lots of venues??
        ];
        $fields[] = [
            'type'       => 'post',
            'post_type'  => U3A_CONTACT_CPT,
            'query_args' => ['orderby' => 'title', 'order' => 'ASC'],
            'name'       => 'Organiser',
            'id'         => 'eventOrganiser_ID',
            'desc'       => "Select or leave blank",
            'ajax'       => false,  // this seems like a good choice, but try switching it on, when there a lots of contacts??
            'required' => false,
        ];
        $fields[] = [
            'type'       => 'text',
            'name'       => 'Cost',
            'id'         => 'eventCost',
            'desc'       => 'You may include cost information here.',
            'std'        => '', // default value,
            'maxlength'  => self::MAX_COST,
        ];
        $fields[] = [
            'type'       => 'checkbox',
            'name'       => 'Booking Required?',
            'id'         => 'eventBookingRequired',
            'desc'       => "Tick if advance booking is required.",
            'std'        => 0, // default 0 = false
        ];
        return $fields;
    }

    /**
     * Registers the blocks u3a/eventdata and u3a/eventlist, and their render callbacks.
     * Add a shortcode that mimics the eventlist block.
     */
    public static function register_blocks()
    {
        wp_register_script(
            'u3aeventblocks',
            plugins_url('js/u3a-event-blocks.js', self::$plugin_file),
            array('wp-blocks',
                    'wp-element',
                    'wp-i18n',
                    'wp-components',
                    'wp-editor'),
            U3A_SITEWORKS_CORE_VERSION,
            false,
        );
        wp_enqueue_script('u3aeventblocks');

        register_block_type(
            'u3a/eventlist',
            array(
                'editor_script' => 'u3aeventblocks',
                'render_callback' => array(self::class, 'display_eventlist')
            )
        );

        register_block_type(
            'u3a/eventdata',
            array(
                'editor_script' => 'u3aeventblocks',
                'render_callback' => array(self::class, 'display_cb')
            )
        );
        // also add a shortcode!!
        add_shortcode('u3aeventlist', array(self::class, 'display_eventlist'));
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
        if ($post->post_type == U3A_EVENT_CPT) {
            $content = '<!-- wp:u3a/eventdata /--><!-- wp:paragraph --><p></p><!-- /wp:paragraph -->  ';
            return $content;
        }
        return $content;
    }

    /**
     * Alter the columns that are displayed in the events posts list admin page.
     * @param array $columns
     * @return modified columns
     * @usedby filter 'manage_' . U3A_EVENT_CPT . '_posts_columns'
     */
    public static function change_columns($columns)
    {
        unset($columns['date']);
        $columns['eventDate'] = 'Event date';
        $columns['eventGroup'] = 'Group';
        $columns['eventVenue'] = 'Venue';
        $columns['eventOrganiser'] = 'Organiser';
        return $columns;
    }

    /**
     * Alter what is shown fo one row in the columns that are displayed in the events posts list admin page.
     * @param str $column
     * @param int $post_id  the id of the post for the row 
     * @usedby action 'manage_' . U3A_EVENT_CPT . '_posts_custom_column'
     */
    public static function show_column_data($column, $post_id)
    {
        switch ($column) {
            case 'eventDate':
                $date = get_post_meta($post_id, 'eventDate', true);
                if (!empty($date)) {
                    //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- date() is safe
                    print date(get_option('date_format'), strtotime($date));
                } else {
                    print 'not set';
                }
                break;
            case 'eventGroup':
                $eventGroup_ID = get_post_meta($post_id, 'eventGroup_ID', true);
                if (is_numeric($eventGroup_ID)) print esc_HTML(get_the_title($eventGroup_ID));
                break;
            case 'eventVenue':
                $eventVenue_ID = get_post_meta($post_id, 'eventVenue_ID', true);
                if (is_numeric($eventVenue_ID)) print esc_HTML(get_the_title($eventVenue_ID));
                break;
            case 'eventOrganiser':
                $eventOrganiser_ID = get_post_meta($post_id, 'eventOrganiser_ID', true);
                if (is_numeric($eventOrganiser_ID)) print esc_HTML(get_the_title($eventOrganiser_ID));
                break;
        }
    }

    /**
     * Provide sorting mechanism for the event date column.
     *
     * @param array $query attributes of query
     * @usedby action 'pre_get_posts'
     */
    public static function sort_column_data($query)
    {
        if (!is_admin() /* || !$query->is_main_query() */) {
            return;
        }
        if ('eventDate' === $query->get('orderby')) {
            $query->set('orderby', 'meta_value');
            $query->set('meta_key', 'eventDate');
        }
    }

    /**
     * Makes event date column sortable.
     * 
     * @param array $columns
     * @return modified array $columns
     * @usedby filter 'manage_edit-' . U3A_EVENT_CPT . '_sortable_columns'
     */
    public static function make_column_sortable($columns)
    {
        $columns['eventDate'] = 'eventDate';
        return $columns;
    }

    /**
     * Add filter by event category to "all Events" posts list
     * @param $post_type
     * @usedby action 'restrict_manage_posts'
     */
    public static function add_admin_filters($post_type)
    {
        if ('u3a_event' !== $post_type) {
            return;
        }

        // Selector for event category
        $taxonomy_slug = U3A_EVENT_TAXONOMY;
        $select_title = 'All event categories';

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
                $term->name,
                $term->count
            );
        }
        print '</select>';

        // Selector for group
        $groups = self::get_posts_array('u3a_group');
        $selected = isset($_GET['groupID']) ? $_GET['groupID'] : '';
        if ($groups) {
            print '<select name="groupID"><option value="">All groups</option>';
            foreach ($groups as $id => $text) {
                $sel = ($id == $selected) ? '" selected>' : '">';
                print '<option value="' . $id . $sel . esc_HTML($text) . '</option>';
            }
            print "</select>\n";
        }
        //phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped,WordPress.Security.NonceVerification.Recommended
    }

    /**
     * Get all posts for a custom post type.
     * @param $cpt the custom post type
     * @return associative array in the form Post_ID => Post_title
     */
    public static function get_posts_array($cpt)
    {
        $all_posts = get_posts(array('post_type' => $cpt, 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC'));
        if ($all_posts) {
            $posts = array();
            foreach ($all_posts as $cur_post) {
                $posts[$cur_post->ID] = $cur_post->post_title;
            }
            return $posts;
        }
        return null;
    }

    /** 
     * Filter posts if the groupID filter is set (ie $_GET contains groupID).
     * Only apply for the u3a_event post type
     * 
     * This filter is used when generating the admin page listing u3a Events.  If the filter-by-group control
     * is used, this filter alters the main query so that only events for the chosen group are shown.
     * 
     * @usedby filter 'parse_query'
     */
    public static function filter_posts($query)
    {
        //modify the query only if it is admin and main query.
        if (!(is_admin() && $query->is_main_query())) {
            return $query;
        }
        //only modify query if filter is set for this post type
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (!(($query->query['post_type'] === U3A_EVENT_CPT) && (isset($_GET['groupID']) && !empty($_GET['groupID'])))) {
            return $query;
        }

        //modify the query_vars for group selection
        $query->query_vars['meta_key'] = 'eventGroup_ID';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $query->query_vars['meta_value'] = sanitize_text_field($_GET['groupID']);
        $query->query_vars['meta_compare'] = '=';
        return $query;
    }

    /** 
     * Modify the query when a Query Block is used to display posts of this type.
     * @param array $query
     * @used by filter 'query_loop_block_query_vars'
     * TBD Usage needs explanation
     */
    public static function filter_events_query($query)
    {
        // ignore if the query block is not using this post type
        if ($query['post_type'] != U3A_EVENT_CPT) return $query;

        // always exclude events with dates in the past
        $query['meta_key'] = 'eventDate';
        $query['meta_value'] = date("Y-m-d");
        $query['meta_compare'] = '>=';

        // If date order is chosen in the block settings, change to use the Event date instead of Post date
        if ($query['orderby'] == 'date') $query['orderby'] = 'meta_value';

        return $query;
    }

    /** 
     * Convert event date to system format when rendered by the third party Meta Field Block.
     * (WP won't have a problem if the block isn't present)
     * @usedby filter 'meta_field_block_get_block_content'
     */
    public static function modify_meta_date($content, $attributes)
    {
        if (($attributes['fieldName'] == 'eventDate') && ($content != '')) {
            return date(get_option('date_format'), strtotime($content));
        }
        return $content;
    }

    /** 
     * Update all events so they have an eventEndDate.
     * @usedby 'u3a_core_update_storage_2_to_3'
     * There is no error returned here, so a failed setEventEndDate is potentially ignored.
     */
    public static function update_allEventsEndDate()
    {
        $all_posts = get_posts(array('post_type' => 'u3a_event', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC'));
        if ($all_posts) {
            foreach ($all_posts as $cur_post) {
                self::set_eventEndDate($cur_post->ID, $cur_post);
            }
        }
    }

    // create/update meta field eventEndDate
    public static function set_eventEndDate($post_id, $post)
    {
        if ($post->post_status != 'publish') { // apparently this is called when making a draft post
            return;
        }
        $eventStartDate = get_post_meta($post_id, 'eventDate', true);
        if (empty($eventStartDate)) { // shouldn't happen
            return;
        }
        $duration = get_post_meta($post_id, 'eventDays', true);
        if (!empty($duration) && $duration > 1) {
            $eventEndDate = date("Y-m-d", strtotime($eventStartDate) + 86400 * ($duration - 1));
        } else {
            $eventEndDate = $eventStartDate;
        }
        update_post_meta($post_id, 'eventEndDate', $eventEndDate); // will create/update the field
    }

    /**
     * List events in date order, selected according to parameters.
     *
     * Can be called either as a shortcode or as a render callback of a block.
     * Attributes will also be taken from the page's URL query parameters.
     * If present these query parameters will override parameters passed as arguments
     *
     * @param array $atts Valid attributes are:
     *    when = 'past'/'future' (default future)
     *    order = 'asc'/'desc' (defaults to asc for future and desc for past)
     *    cat = which event category to display (default all)
     *    groups = 'y'/'n which will override the value in option settings
     *    limitnum (int) = limits how many events to be displayed
     *    limitdays (int) = limits how many day in the future or past to show events
     *    layout = has no effect as yet!
     *
     * @return HTML
     */
    public static function display_eventlist($atts, $content = '')
    {
        global $post;
        
        // valid display_args names and default values
        $display_args = [
            'when' => 'future',
            'order' => '',
            'cat' => 'all',
            'groups' => '',
            'limitdays' => 0,
            'limitnum' => 0,
            'layout' => 'list',
        ];
        // set from page query or from call attributes, page query parameters take priority
        foreach ($display_args as $name => $default) {
            // phpcs:disable WordPress.Security.NonceVerification.Recommended
            if (isset($_GET[$name])) {
                $display_args[$name] = strtolower(sanitize_text_field($_GET[$name]));
            // phpcs:enable WordPress.Security.NonceVerification.Recommended
            } elseif (isset($atts[$name])) {
                $display_args[$name] = strtolower($atts[$name]);
            }
        }
        // validate all args
        // do this by treating each arg explicitly
        $error = '';
        $when = $display_args['when'];
        if ('past' != $when && 'future' != $when) {
            $error .= 'bad parameter: when=' . esc_html($when) . '<br>';
            $when = 'future'; // default
        }

        $order = strtoupper($display_args['order']);
        if ('ASC' != $order && 'DESC' != $order &&  '' != $order) {
            $error .= 'bad parameter: order=' . esc_html($order) . '<br>';
            $order = '';  // default
        }
        if ('' == $order) { // set order depending on past/future
            $order = ('future' == $when) ? 'ASC' : 'DESC';
        }

        $cat = sanitize_text_field($display_args['cat']);

        $include_groups = $display_args['groups'];
        if ('y' != $include_groups && 'n' != $include_groups &&  '' != $include_groups) {
            $error .= 'bad parameter: groups=' . esc_html($include_groups) . '<br>';
            $include_groups = '';
        }
        if ('' == $include_groups) { // set order depending on option setting
            $exclude_groups = get_option('events_nogroups', '1') == 1 ? true : false;
        } elseif ('n' == $include_groups) {
            $exclude_groups = true;
        } else {
            $exclude_groups = false;
        }

        if (!is_numeric($display_args['limitdays'])) {
            $error .= 'bad parameter: limitdays=' . esc_html($display_args['limitdays']) . '<br>';
        }
        $limitdays = intval($display_args['limitdays']); // result is always an int

        if (!is_numeric($display_args['limitnum'])) {
            $error .= 'bad parameter: limitnum=' . esc_html($display_args['limitnum']) . '<br>';
        }
        $limitnum = intval($display_args['limitnum']); // result is always an int

        $layout = $display_args['layout']; //not currently used!

        $numposts = ($limitnum > 0) ? $limitnum : -1; // if unlimited display all selected events
        $query_args = [
            'post_type' => U3A_EVENT_CPT,
            'post_status' => 'publish',
            'posts_per_page' => $numposts,
            'meta_key' => 'eventDate',
            'orderby' => 'meta_value',
            'order'    => $order,
        ];

        // set eventDate and eventEndDate part of meta_query
        $now = date("Y-m-d");
        if ($limitdays > 0) {
            if ('past' == $when) {
                $limitdays = -$limitdays;
                $limit_date = date("Y-m-d", time() + 86400 * $limitdays);
                $date_query = [
                    'relation' => 'AND', ['key' => 'eventDate', 'value' => $now, 'compare' => '<'],
                    ['key' => 'eventEndDate', 'value' => $limit_date, 'compare' => '>=']
                ];
            } else {
                $limit_date = date("Y-m-d", time() + 86400 * $limitdays);
                $date_query = [
                    'relation' => 'AND', ['key' => 'eventEndDate', 'value' => $now, 'compare' => '>='],
                    ['key' => 'eventDate', 'value' => $limit_date, 'compare' => '<']
                ];
            }
        } else {
            if ('past' == $when) {
                $date_query = ['key' => 'eventDate', 'value' => $now, 'compare' => '<'];
            } else {
                $date_query = ['key' => 'eventEndDate', 'value' => $now, 'compare' => '>='];
            }
        }

        // set eventGroup_ID part of query
        // if called from a u3a_group post, we'll select only events for this group
        $on_group_page = false;
        $group_query = null;
        if (U3A_GROUP_CPT == $post->post_type) {
            $on_group_page = true;
            $group_ID = $post->ID;
            $group_query = ['key' => 'eventGroup_ID', 'value' => $group_ID, 'compare' => '='];
        } elseif ($exclude_groups) {  // exclude group events from normal list
            // eventGroup_ID is UNSET when event is not associated to any group!!
            // but include possibility eventGroup_ID is set to empty string, as a precaution!!
            $group_query = [
                'relation' => 'OR',
                ['key' => 'eventGroup_ID', 'value' => ''],
                ['key' => 'eventGroup_ID', 'compare' => 'NOT EXISTS'],
            ];
        }
        // now set the whole meta_query
        if (!empty($group_query)) {
            $query_args['meta_query'] = [$date_query, $group_query];
        } else {
            $query_args['meta_query'] = [$date_query];
        }

        // set taxonomy query
        if (!empty($cat)  && 'all' != $cat) {
            $query_args['tax_query'] = [[
                'taxonomy' => U3A_EVENT_TAXONOMY,
                'field'    => 'slug',
                'terms' => $cat, // could provide an aray of cats here!!
            ]];
        }
        $posts = get_posts($query_args);

        // Generate table from array of posts
        // no need to show the event's group if we are on the group page!
        $show_group_info = !($on_group_page);
        if ($posts)  return self::display_event_listing($posts,  $when, $show_group_info);
        else return '';
    }

    /* Return the HTML code for selected events.
     *
     * @param array $posts the selected posts of type u3a_event
     * @param str $when 'past' / 'future'
     * @param boolean $show_group to display the group with which the event is associated.
     *
     * @return HTML <h3><div> with a pair of <div>s for each event </div>
     *              or empty string ''
     */
    public static function display_event_listing($posts, $when, $show_group = true)
    {
        if (!$posts) return '';

        $when_text = ('past' == $when) ? 'Previous' : 'Forthcoming';
        $html = "<h3>$when_text Events</h3>\n<div class=\"u3aeventlist\">\n";
        foreach ($posts as $event) {
            $my_event = new self($event->ID); // an object of this class
            list($date, $time) = $my_event->event_date_and_time();
            $title = $event->post_title;
            $permalink = get_the_permalink($event);
            //$eventpagelink = get_site_url() . '/u3a-events/' . $event->post_name;
            $event_category = '';
            $terms = get_the_terms($event, U3A_EVENT_TAXONOMY); // an array of terms or null
            if ((false !== $terms) && !is_wp_error($terms)) {
                // assumes only one category permitted for now, may allow multiple categories in future.
                $term = $terms[0];
                $event_category = $term->name;
            }
            $group_line = '';
            if ($show_group) {
                list($groupName, $group_link) = $my_event->event_group_title_and_permalink();
                $group_line = ($groupName) ? "<p>(Group: <a href=\"$group_link\">$groupName</a>)</p>" : '';
            }
            add_filter( 'excerpt_length', function ($length ) { return 30; } );
            $extract = get_the_excerpt($event->ID);
            // $extract = wp_strip_all_tags($event->post_content, true);
            // if (strlen($extract) > 100) $extract = substr($extract, 0, 96) . ' ...';
            if (!empty($extract)) {
                $extract .= '<br>';
            }

            $time_text = ($time) ? '<br>' . $time : '';
            $the_venue = new U3aVenue(get_post_meta($event->ID, 'eventVenue_ID', true));
            $venue_name_with_link = $the_venue->venue_name_with_link();
            $venue_line = '';
            if (!empty($venue_name_with_link)) {
                $venue_line = "Venue: $venue_name_with_link";
            }
            $cost_line = '';
            $cost = get_post_meta($event->ID, 'eventCost', true);
            if (!empty($cost)) {
                $cost_line = "<br>Cost: $cost";
            }
            $booking_required_line = '';
            $booking_required = get_post_meta($event->ID, 'eventBookingRequired', true);
            if (!empty($booking_required)) {  // default value of 0 is empty!
                $booking_required_line = "<br><strong>Booking Required</strong>";
            }

            $date_start = "<strong>$date</strong> $time_text";
            $date_end = '';
            $days = get_post_meta($event->ID, 'eventDays', true);
            if ($days > 1) {
                $enddate = $my_event->event_end_date();
                $date_end = "<br>to $enddate";
            }

            // Assemble the components
            $html .= <<< END
            <div>
            <p>$date_start 
            $date_end
            <br>$event_category</p>
            </div>
            <div>
            <p class="u3aeventtitle"><a href="$permalink">$title</a></p>
            $group_line
            <p>$extract $venue_line $cost_line $booking_required_line</p>
            </div>
END;
        }
        $html .= "</div>\n";
        return $html;
    }

    /*
     * Calls the display function for an object of this class
     * This code is common to all our custom post types, so don't edit it!
     */
    public static function display_cb($atts, $content = '')
    {
        global $post;
        if (U3A_EVENT_CPT != $post->post_type) { // oops shouldn't be here
            return 'Error: only for use with items of type ' . U3A_EVENT_CPT;
        }
        $my_object = new self($post->ID); // an object of this class
        return $my_object->display($atts, $content);
    }

    // Below here are object methods.
    // Note they all assume that the object is the current post 'in the loop'.

    /*
     * Returns the HTML for this object's custom data.
     *
     * @return string The HTML.
     */
    public function display($atts, $content)
    {
        $html = "<div class=\"u3aeventdata\">\n";
        // event category
        $terms = get_the_terms($this->ID, U3A_EVENT_TAXONOMY); // an array of terms or null
        if ((false !== $terms) && !is_wp_error($terms)) {
            // assumes only one category permitted for now, may allow multiple categories in future.
            $term = $terms[0];
            $event_category = $term->name;
            $html .= "<div> Event type: </div> <div>$event_category</div>";
        }
        // date, time, duration
        $date_time = $this->event_date_and_time();
        $date = $date_time[0];
        $time = $date_time[1];
        if (!empty($date)) {
            $html .= "<div> Date: </div> <div>$date</div>";
        }
        if (!empty($time)) {
            $html .= "<div> Time: </div> <div>$time</div>";
        }
        $duration = get_post_meta($this->ID, 'eventDays', true);
        if (!empty($duration) && $duration > 1) {
            $html .= "<div> Duration: </div> <div>$duration days</div>";
            $enddate = $this->event_end_date();
            if (!empty($enddate)) {
                $html .= "<div> Until: </div> <div>$enddate</div>";
            }
        }

        // Group

        list($group_title, $group_permalink) = $this->event_group_title_and_permalink();
        if (!empty($group_title)) {
            $group_text = "<a href='$group_permalink'>$group_title</a>";
            $html .= "<div> Group: </div> <div>$group_text</div>";
        }

        // Venue
        $the_venue = new U3aVenue(get_post_meta($this->ID, 'eventVenue_ID', true));
        $venue_name_with_link = $the_venue->venue_name_with_link();
        if (!empty($venue_name_with_link)) {
            $html .= "<div> Venue: </div> <div>$venue_name_with_link</div>";
        }

        // Organiser
        $contact = new U3aContact(get_post_meta($this->ID, 'eventOrganiser_ID', true));
        $contact_info = $contact->contact_text();
        if ($contact_info) {
            //note: $contact_info has a containing div which impacts on css ".u3aeventlist div:nth-child(odd)" 
            $html .= "<div> Organiser: </div>$contact_info";
        }

        //Cost
        $cost = get_post_meta($this->ID, 'eventCost', true);
        if (!empty($cost)) {
            $html .= "<div> Cost: </div> <div>$cost</div>";
        }

        //Booking Required
        $booking_required = get_post_meta($this->ID, 'eventBookingRequired', true);
        if (!empty($booking_required)) {  // default value of 0 is empty!
            $html .= "<div> Booking:</div> <div>Note that booking is required.</div>";
        }

        $html .= "</div>";
        return $html;
    }

    /** 
     * Formats the event date and time.
     * @return array [formatted date,formatted time]
     */
    public function event_date_and_time()
    {
        $date = get_post_meta($this->ID, 'eventDate', true);
        if (empty($date)) {
            return ['', ''];
        }
        $time = get_post_meta($this->ID, 'eventTime', true);
        $time = (!empty($time)) ? $time : '';
        $temp = strtotime($date . ' ' . $time);

        // Use the date and time format settings from Event tab on u3a Settings page
        $events_dateformat = get_option('events_dateformat', 'system');
        switch ($events_dateformat) {
            case 'system':
                $dateformat = get_option('date_format', 'jS F Y');
                break;
            case 'short':
                $dateformat = 'D M jS';
                break;
            default:
                $dateformat = 'l jS F Y';
        }
        $events_timeformat = get_option('events_timeformat', 'system');
        switch ($events_timeformat) {
            case 'system':
                $timeformat = get_option('time_format', 'g:i a');
                break;
            case '12hr':
                $timeformat = 'g:ia';
                break;
            default:
                $timeformat = 'H:i';
        }

        if ('' != $time) {
            return [date($dateformat, $temp), date($timeformat, $temp)];
        } else {
            return [date($dateformat, $temp), ''];
        }
    }

    /**
     * Formats the event end date.
     * @return string formatteddate
     */
    public function event_end_date()
    {
        $date = get_post_meta($this->ID, 'eventEndDate', true);
        if (empty($date)) {
            return $date;
        }
        // Use the date and time format settings from Event tab on u3a Settings page
        $events_dateformat = get_option('events_dateformat', 'system');
        switch ($events_dateformat) {
            case 'system':
                $dateformat = get_option('date_format', 'jS F Y');
                break;
            case 'short':
                $dateformat = 'D M jS';
                break;
            default:
                $dateformat = 'l jS F Y';
        }

        return date($dateformat, strtotime($date));
    }

    /**
     * Gets the title and permalink of the group related to this event.
     *
     * @return array [title,permalink]
     */
    public function event_group_title_and_permalink()
    {
        $group_ID = get_post_meta($this->ID, 'eventGroup_ID', true);
        if (!empty($group_ID) && is_numeric($group_ID)) {
            return [get_post($group_ID)->post_title, get_permalink($group_ID, false)];
        } else {
            return ['', ''];
        }
    }
}
