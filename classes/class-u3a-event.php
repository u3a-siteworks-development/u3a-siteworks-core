<?php // phpcs:ignore Generic.Files.LineEndings.InvalidEOLChar

// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class U3aEvent
{
    use ChangePrompt;
    use AddMetabox;

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

    /**
     * The metabox title of these custom posts
     *
     * @var string
     */
    public static $metabox_title = "Event Information";

    // $plugin_file is the value of __FILE__ from the main plugin file
    private static $plugin_file;

    private static $CAL_EOL = "\r\n";

    private static $allowed_tags = array(
        'a' => array(
            'href' => array(),
            'title' => array(),
            'rel' => array()
        ),
        'blockquote' => array('cite' => array()),
        'br' => array(),
        'p' => array(),
        'code' => array(),
        'pre' => array(),
        'em' => array(),
        'strong' => array(),
        'ul' => array(),
        'ol' => array(),
        'li' => array(),
        'h3' => array(),
        'h4' => array()
    );

    /**
     * This class manages these metadata fields:
     * eventDate        event date YYYY-MM-DD
     * eventTime        time of event HH:MM
     * eventEndTime     end time of event HH:MM
     * eventDays        event duration in days (integer)
     * eventEndDate     event end date YYY-MM-DD (set automatically)
     * eventGroup_ID    ID of group
     * eventVenue_ID    ID of venue
     * eventOrganiser_ID  ID of contact
     * eventCost        text string
     * eventBookingRequired Boolean  stored as 0/1
     *  also each event is assigned to one or more event category.
     */
     // array of meta field key and object property name
    public static $meta_data = array(
        'eventDate' => 'date',
        'eventTime' => 'starttime',
        'eventEndTime' => 'endtime',
        'eventDays' => 'days_duration',
        'eventEndDate' => 'enddate',
        'eventGroup_ID' => 'group_ID',
        'eventVenue_ID' => 'venue_ID',
        'eventOrganiser_ID' => 'organiser_ID',
        'eventCost' => 'cost',
        'eventBookingRequired' => 'booking_required',
        );

    /**
     * The ID of the post for this event
     *
     * @var string
     */
    public $ID;

    // the event's title
    public $title;

    // meta data
    public $date;
    public $starttime;
    public $endtime;
    public $days_duration;
    public $enddate;
    public $group_ID;
    public $venue_ID;
    public $organiser_ID;
    public $cost;
    public $booking_required;

    /*
     * Construct a new object for a u3a_event post.
     *
     */
    public function __construct($ID)
    {
        $this->ID = $ID;
        $this->title = get_the_title($ID);
        // get all meta data and set property values
        // Note: unset meta data will have property value set to ''
        foreach (self::$meta_data as $key => $prop) {
            $this->$prop = get_post_meta($ID, $key, true);
        }
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
        add_action('pre_get_posts', array(self::class, 'add_groupID_to_query'));
        add_action('restrict_manage_posts', array(self::class, 'add_admin_filters'));

        // Generate an ICS file for events when any event changes
        add_action('save_post_u3a_event', array(self::class, 'generate_ics_calendar'), 99, 3);

        // Convert metadata fields to displayable text when rendered by the third party Meta Field Block
        add_filter('meta_field_block_get_block_content', array(self::class, 'modify_meta_data'), 10, 2);

        // Add row action for Repeat Event 
        add_filter('post_row_actions', array(self::class, 'repeat_event_row_action'), 10, 2);
        // Add admin page for Repeat Event
        add_action('admin_menu', array(self::class, 'repeat_event_admin_page'));
        // Function to process the Repeat Event form submission
        add_action('admin_post_u3a_repeat_event', array(self::class, 'repeat_event_save'));
        // Load CSS and JavaScript for the repeat event page
        add_action('admin_enqueue_scripts', array(self::class, 'repeat_event_scripts'));
        // Add Repeat Event to admin toolbar
        add_action('admin_bar_menu', array(self::class, 'repeat_event_admin_toolbar'), 99);        
    }


    /**
     * Registers the custom post type and taxonomy for this class.
     */
    public static function register_events()
    {
        $args = array(
            'public' => true,
            'show_in_rest' => true,
            'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'color'),
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

    /*
     * Defines the fields for this class.
     *
     * @return array
     */
    public static function field_descriptions()
    {
        $category_singular_term = get_option('u3a_catsingular_term', 'category');
        $ucfirst_category_singular_term = ucfirst($category_singular_term);
        $fields = [];
        // Now add all the fields to the $fields array in the order they will appear.
        // see https://docs.metabox.io/fields/
        // and https://docs.metabox.io/field-settings/ for details.
        $fields[] = [
            'type'       => 'taxonomy',
            'name'       => $ucfirst_category_singular_term,
            'id'         => 'category',
            'taxonomy'   => U3A_EVENT_TAXONOMY,
            'multiple'   => true,
            'field_type' => 'select_advanced',
            'required' => true,
            'desc'    => "You may enter more than one $category_singular_term here.",
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
            'type'    => 'time',
            'name'    => 'End time',
            'id'      => 'eventEndTime',
            'desc' => 'Optional',
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
            'ajax'       => false,  // this seems like a good choice, but try switching it on,
            //when there a lots of groups??
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
            'ajax'       => false,  // this seems like a good choice, but try switching it on,
            // when there a lots of venues??
        ];
        $fields[] = [
            'type'       => 'post',
            'post_type'  => U3A_CONTACT_CPT,
            'query_args' => ['orderby' => 'title', 'order' => 'ASC'],
            'name'       => 'Organiser',
            'id'         => 'eventOrganiser_ID',
            'desc'       => "Select or leave blank",
            'ajax'       => false,  // this seems like a good choice, but try switching it on,
            // when there a lots of contacts??
            'required' => false,
        ];
        $fields[] = [
            'type'       => 'text',
            'name'       => 'Cost',
            'id'         => 'eventCost',
            'desc'       => 'You may include cost information here.',
            'std'        => '', // default value,
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
            array('wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-data'),
            U3A_SITEWORKS_CORE_VERSION,
            false,
        );
        wp_enqueue_script('u3aeventblocks');

        register_block_type(
            'u3a/eventlist',
            array(
                'render_callback' => array(self::class, 'display_eventlist')
            )
        );

        register_block_type(
            'u3a/eventdata',
            array(
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
     * @return $columns
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
     * @param $column
     * @param $post_id  the id of the post for the row
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
                if (is_numeric($eventGroup_ID)) {
                    print esc_HTML(get_the_title($eventGroup_ID));
                }
                break;
            case 'eventVenue':
                $eventVenue_ID = get_post_meta($post_id, 'eventVenue_ID', true);
                if (is_numeric($eventVenue_ID)) {
                    print esc_HTML(get_the_title($eventVenue_ID));
                }
                break;
            case 'eventOrganiser':
                $eventOrganiser_ID = get_post_meta($post_id, 'eventOrganiser_ID', true);
                if (is_numeric($eventOrganiser_ID)) {
                    print esc_HTML(get_the_title($eventOrganiser_ID));
                }
                break;
        }
    }

    /**
     * Provide sorting mechanism for the event date column.
     *
     * @param $query attributes of query, passed by ref
     * @usedby action 'pre_get_posts'
     */
    public static function sort_column_data($query)
    {
        // This is a very general purpose hook, so ...
        // query must be main query for an admin page with a query for u3a_event post-type
        if (
            !(is_admin()
                && ($query->is_main_query())
                && ('u3a_event' == $query->get('post_type'))
            )
        ) {
            return;
        }
        // also check that we are on the All Events page
        // Note: get_current_screen() may not exist at the start of this function
        $screen = get_current_screen();
        if ('edit-u3a_event' !== $screen->id) {
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
     * @return array $columns
     * @usedby filter 'manage_edit-' . U3A_EVENT_CPT . '_sortable_columns'
     */
    public static function make_column_sortable($columns)
    {
        $columns['eventDate'] = 'eventDate';
        return $columns;
    }

    /**
     * Add filter by event category and by group to "all Events" posts list
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
        $selected = isset($_GET[$taxonomy_slug]) ? $_GET[$taxonomy_slug] : '';

        // Retrieve taxonomy terms and genenerate select
        $terms = get_terms($taxonomy_slug);

        //phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
        //phpcs:disable WordPress.Security.NonceVerification.Recommended
        print "<select name='{$taxonomy_slug}' id='{$taxonomy_slug}' class='postform'>";
        print '<option value="">' . $select_title . '</option>';
        foreach ($terms as $term) {
            $sel = ($term->slug == $selected) ? ' selected' : '';
            printf(
                '<option value="%1$s" %2$s>%3$s (%4$s)</option>',
                $term->slug,
                $sel,
                esc_html($term->name),
                $term->count
            );
        }
        print '</select>';

        // Selector for group
        $name = 'groupID'; // used to identify this filter when adding criterion to query.
        $groups = get_posts(array(
            'post_type' => 'u3a_group',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        if ($groups) {
            $selected = isset($_GET[$name]) ? $_GET[$name] : '';
            print "<select name='$name'><option value=''>All groups</option>";
            foreach ($groups as $group) {
                $id = $group->ID;
                $sel = ($id == $selected) ? ' selected' : '';
                printf(
                    '<option value="%1$s" %2$s>%3$s </option>',
                    $id,
                    $sel,
                    esc_HTML($group->post_title)
                );
            }
            print "</select>\n";
        }
        //phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped,WordPress.Security.NonceVerification.Recommended
    }

    /**
     * Adds filtering of posts by eventGroup_ID.
     *
     * This filtering is an option on the admin page listing u3a Events,
     * as set up by the function add_admin_filters().
     * If in use, this function alters the main query so that only events for the chosen group are shown.
     * @param object $query
     * @usedby filter 'pre_get_posts'
     */
    public static function add_groupID_to_query($query)
    {
        // This is a very general purpose hook, so ...
        // query must be main query for an admin page with a query for u3a_event post-type
        if (
            !(is_admin()
                && ($query->is_main_query())
                && ('u3a_event' == $query->get('post_type'))
            )
        ) {
            return;
        }
        // also check that we are on the All Events page
        // Note: get_current_screen() may not exist at the start of this function
        $screen = get_current_screen();
        if ('edit-u3a_event' !== $screen->id) {
            return;
        }
        //only modify query if filtering for groupID is set
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (!(isset($_GET['groupID']) && !empty($_GET['groupID']))) {
            return $query;
        }
        // add a meta_query for group selection
        $meta_query[] = array(
            'key' => 'eventGroup_ID',
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            'value' => sanitize_text_field($_GET['groupID']),
            'compare' => '=',
            'type' => '',
        );
        $query->set('meta_query', $meta_query);

        return $query;
    }

    /**
     * Modify the query when a Query Block is used to display posts of this type.
     * Events in the past will be omitted and if ordered by date will use eventDate not post_date
     *  
     * @param array $query
     * @used by filter 'query_loop_block_query_vars'
     */
    public static function filter_events_query($query)
    {
        // ignore if the query block is not using this post type
        if ($query['post_type'] != U3A_EVENT_CPT) {
            return $query;
        }

        // always exclude events with dates in the past
        $query['meta_key'] = 'eventDate';
        $query['meta_value'] = date("Y-m-d");
        $query['meta_compare'] = '>=';

        // If date order is chosen in the block settings, change to use the Event date instead of Post date
        if ($query['orderby'] == 'date') {
            $query['orderby'] = 'meta_value';
        }

        return $query;
    }

    /**
     * Convert event metadata to displayable text when rendered by the third party Meta Field Block.
     * Ref https://wordpress.org/plugins/display-a-meta-field-as-block/
     * (WP won't have a problem if the block isn't present)
     * Where metadata is stored as references/codes return the associated text string
     * Where metadata is already in text form leave alone.
     * @usedby filter 'meta_field_block_get_block_content'
     */
    public static function modify_meta_data($content, $attributes)
    {
        if ($content != '') {
            switch ($attributes['fieldName']) {
                case 'eventDate':
                    //TBD use similar code to event_date_and_time MAYBE by adding format functions
                    $content = date(get_option('date_format'), strtotime($content));
                    break;
                case 'eventVenue_ID':
                case 'eventGroup_ID':
                case 'eventOrganiser_ID':
                    $content = get_the_title($content);
                    break;
                case 'eventBookingRequired':
                    $content = ($content == 0) ? 'No' : 'Yes';
            }
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
        $all_posts = get_posts(array(
            'post_type' => 'u3a_event',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
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

    public static function is_importing()
    {
        return get_transient("u3a_events_importing");
    }

    public static function generate_ics_calendar($post_id, $post, $update)
    {
        // save is called with a null post
        if ($post == null) {
            return;
        }
        // Do not do this during event import
        if (self::is_importing()) {
            return;
        }
        // Only set for post_type = u3a_event
        if (self::$post_type !== $post->post_type) {
            return;
        }

        $filename = wp_get_upload_dir()['basedir'] . "/event_calendars/";
        if (!is_dir($filename)) {
            mkdir($filename, 0755, true);
        }

        $filename .= "u3a_event_calendar.ics";
        $file = fopen($filename, "w+");
        if (!$file) {
            return;
        }

        // Generate the ICS file
        $data = self::build_ics();

        fputs($file, $data);

        fclose($file);
    }

    private static function build_ics()
    {

        $data = '';
        $query_args = [
            'post_type' => U3A_EVENT_CPT,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_key' => 'eventDate',
            'orderby' => 'meta_value',
            'order'    => 'ASC',
        ];

        // This will only get future events - get them all - calendar is
        // configurable.
        // $now = date("Y-m-d");
        // $date_query = ['key' => 'eventDate', 'value' => $now, 'compare' => '>='];
        // $query_args['meta_query'] = [$date_query];

        $posts = get_posts($query_args);

        $valid_posts = array();

        // skip those events associated with non-published groups.
        foreach ($posts as $event) {
            if ($event->eventGroup_ID != '') {
                $groupstatus = get_post_status($event->eventGroup_ID);
                if ($groupstatus != 'publish') {
                    continue;
                }
            }
            $valid_posts[] = $event;
        }

        $data .= self::build_calendar_header();
        $data .= self::add_ics_entries($valid_posts);
        $data .= self::build_calendar_footer();
        return $data;
    }

    private static function build_calendar_footer()
    {
        return "END:VCALENDAR" . self::$CAL_EOL;
    }

    private static function build_calendar_header()
    {
        $data = "BEGIN:VCALENDAR" . self::$CAL_EOL;
        $data .= "VERSION:2.0" . self::$CAL_EOL;
        $data .=  "CALSCALE:GREGORIAN" . self::$CAL_EOL;
        // Use site name as PRODID
        $u3aname = strtoupper(get_bloginfo('name'));
        $data .= "PRODID:" . $u3aname . self::$CAL_EOL;
        $data .= "METHOD:PUBLISH" . self::$CAL_EOL;
        $data .= "BEGIN:VTIMEZONE" . self::$CAL_EOL;
        $data .= "TZID:Europe/London" . self::$CAL_EOL;
        $data .= "X-LIC-LOCATION:Europe/London" . self::$CAL_EOL;
        $data .= "BEGIN:DAYLIGHT" . self::$CAL_EOL;
        $data .= "TZOFFSETFROM:+0000" . self::$CAL_EOL;
        $data .= "TZOFFSETTO:+0100" . self::$CAL_EOL;
        $data .= "TZNAME:BST" . self::$CAL_EOL;
        $data .= "DTSTART:19700329T010000" . self::$CAL_EOL;
        $data .= "END:DAYLIGHT" . self::$CAL_EOL;
        $data .= "BEGIN:STANDARD" . self::$CAL_EOL;
        $data .= "TZOFFSETFROM:+0100" . self::$CAL_EOL;
        $data .= "TZOFFSETTO:+0000" . self::$CAL_EOL;
        $data .= "TZNAME:GMT" . self::$CAL_EOL;
        $data .= "DTSTART:19701025T020000" . self::$CAL_EOL;
        $data .= "END:STANDARD" . self::$CAL_EOL;
        $data .= "END:VTIMEZONE" . self::$CAL_EOL;
        return $data;
    }

    private static function remove_wp_tags($text)
    {
        $split = preg_split("/<!--.*?-->/", $text);
        for ($i = 0; $i < count($split); $i++) {
            if ($split[$i] == "") {
                $split[$i] = " ";
            }
        }
        $text = implode($split);
        return $text;
    }

    private static function adjust_formatting($text)
    {
        $text = preg_replace("/[\n\r]/", "", $text);
        $text = balanceTags($text);
        if ($text != "") {
            $text = self::remove_wp_tags($text);
            $text = wp_kses($text, self::$allowed_tags);
            $text = trim($text);
        }
        return $text;
    }

    private static function strip_formatting($text)
    {
        $text = preg_replace("/[\n\r]/", "", $text);
        $text = balanceTags($text);
        if ($text != "") {
            $text = self::remove_wp_tags($text);
            $text = wp_kses($text, array());
            $text = trim($text);
        }
        return $text;
    }

    private static function add_ics_entries($posts)
    {
        $createtime = date('Ymd') . 'T' . date('His');
        $data = '';
        add_filter('excerpt_length', function ($length) {
            return 30;
        });

        foreach ($posts as $post) {
            $metadata = get_post_meta($post->ID, '', false);

            $data .= "BEGIN:VEVENT" . self::$CAL_EOL;
            $data .= "SUMMARY:";
            $title = $post->post_title;

            if (isset($metadata['eventGroup_ID'])) {
                $group = get_the_title($metadata['eventGroup_ID'][0]);
                if ($group != "") {
                    $data .= "($group) ";
                }
            }
            $data .= $title . self::$CAL_EOL;

            $data .= 'UID:' . $post->ID . self::$CAL_EOL;
            $data .= 'SEQUENCE:0' . self::$CAL_EOL;
            $data .= 'STATUS:CONFIRMED' . self::$CAL_EOL;
            $data .= 'TRANSP:TRANSPARENT' . self::$CAL_EOL;
            $epochendtime = 0;
            $epochtime = 0;

            $hastime = false;
            $hasendtime = false;
            if (isset($metadata['eventDate'])) {
                $date = $metadata['eventDate'][0];
                if (isset($metadata['eventTime'])) {
                    $time = $metadata['eventTime'][0];
                    $epochtime = strtotime($date . ' ' . $time);
                    $hastime = true;
                } else {
                    $epochtime = strtotime($date);
                }
                if (isset($metadata['eventEndTime'])) {
                    $endtime = $metadata['eventEndTime'][0];
                    $epochendtime = strtotime($date . ' ' . $endtime);
                    $hasendtime = true;
                } else {
                    $epochendtime = $epochtime;
                }
            }
            if ($hastime) {
                $formatted_date = date("Ymd\THis", $epochtime);
                $data .= "DTSTART:" . $formatted_date . self::$CAL_EOL;
            } else {
                $formatted_date = date("Ymd", $epochtime);
                $data .= "DTSTART;VALUE=DATE:" . $formatted_date . self::$CAL_EOL;
            }

            if (isset($metadata['eventDays'])) {
                $days = $metadata['eventDays'][0];
                if ($days >= 1) {
                    $epochendtime += $days * 86400;
                }
            }
            if ($epochendtime > $epochtime) {
                if ($hasendtime) {
                    $formatted_date = date("Ymd\THis", $epochendtime);
                    $data .= "DTEND:" . $formatted_date . self::$CAL_EOL;
                } else {
                    $formatted_date = date("Ymd", $epochendtime);
                    $data .= "DTEND;VALUE=DATE:" . $formatted_date . self::$CAL_EOL;
                }
            }
            $data .= "DTSTAMP:" . $createtime . self::$CAL_EOL;

            $permalink = get_the_permalink($post->ID);
            $extract_html = self::adjust_formatting(htmlspecialchars_decode(get_the_excerpt($post->ID)));
            $extract_text = self::strip_formatting(htmlspecialchars_decode(get_the_excerpt($post->ID)));
            $data .= "X-ALT-DESC:" . $extract_html .
                " <a href=\"" . $permalink . "\">" . $title . "</a>" . self::$CAL_EOL;
            $data .= "DESCRIPTION:" . $extract_text .
                " " . $title . self::$CAL_EOL;

            // add the categories
            $terms = get_the_terms($post->ID, U3A_EVENT_TAXONOMY); // an array of terms or null
            if ((false !== $terms) && !is_wp_error($terms)) {
                $data .= "CATEGORIES:";
                $first = true;
                foreach ($terms as $term) {
                    if (!$first) {
                        $data .= ",";
                    }
                    $first = false;
                    $data .= $term->name;
                }
                $data .= self::$CAL_EOL;
            }
            // need location...
            if (isset($metadata['eventVenue_ID'])) {
                $venue = new U3aVenue($metadata['eventVenue_ID'][0]);
                $venue_name = (string)($venue->venue_name_with_link());
                if (!empty($venue_name)) {
                    $data .= "LOCATION:" . $venue_name . self::$CAL_EOL;
                }
            }
            $data .= "END:VEVENT" . self::$CAL_EOL;
        }
        $data = self::limit_data($data);
        return $data;
    }

    public static function limit_data($data)
    {
        $newdata = '';
        $lines = explode(self::$CAL_EOL, $data);
        foreach ($lines as $line) {
            if ($line != "") {
                if (strlen($line) < 60) {
                    $newdata .= $line . self::$CAL_EOL;
                } else {
                    $parts = str_split($line, 60);
                    $first = true;
                    foreach ($parts as $part) {
                        if (!$first) {
                            $newdata .= " ";
                        }
                        $newdata .= $part . self::$CAL_EOL;
                        $first = false;
                    }
                }
            }
        }
        return $newdata;
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
     *    event_cat = which event category to display (default all) - older
     *                version of event_cats allowing only a single value. Present for
     *                supporting lists created with older versions of siteworks.
     *    event_cats = which event categories - now an array of values.
     *    groups = corresponds to 'show group events' and is 'useglobal' or 'y' or 'n'
     *             '' is equivalent to 'useglobal' and is
     *             kept for compatibility with blocks created in versions 1.2.2 or below
     *    limitnum (int) = limits how many events to be displayed
     *    limitdays (int) = limits how many day in the future or past to show events
     *    layout = 'list' or 'grid' or 'line' at present. Other layouts may be added
     *    bgcolor = colour of background in layout grid
     *
     */
    public static function display_eventlist($atts, $content = '')
    {
        global $post;
        // valid display_args names and default values
        $display_args = [
            'showtitle' => 'y',
            'when' => 'future',
            'order' => '',
            'event_cat' => '',
            'event_cats' => [],
            'groups' => 'useglobal',
            'limitdays' => 0,
            'limitnum' => 0,
            'layout' => 'list',
            'crop' => 'y',
            'bgcolor' => '',
        ];
        // set from page query or from call attributes, page query parameters take priority
        foreach ($display_args as $name => $default) {
            // phpcs:disable WordPress.Security.NonceVerification.Recommended
            if (isset($_GET[$name])) {
                if (is_array($_GET[$name])) {
                    if (is_array($display_args[$name])) {
                        foreach ($_GET[$name] as $entry) {
                            $display_args[$name][] = sanitize_text_field($entry);
                        }
                    } else {
                        $display_args[$name] = sanitize_text_field($_GET[$name][0]);
                    }
                } else {
                    $display_args[$name] = strtolower(sanitize_text_field($_GET[$name]));
                }
                // phpcs:enable WordPress.Security.NonceVerification.Recommended
            } elseif (isset($atts[$name])) {
                if (is_array($atts[$name])) {
                    if (is_array($display_args[$name])) {
                        foreach ($atts[$name] as $entry) {
                            $display_args[$name][] = strtolower($entry);
                        }
                    } else {
                        $display_args[$name] = strtolower($atts[$name][0]);
                    }
                } else {
                    $display_args[$name] = strtolower($atts[$name]);
                }
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

        /* Lists created in prior releases were allowed only a single category, or 'all
         * categories' - this was stored in event_cat. Now we may have multiple categories,
         * so this is stored in new parameter event_cats (array). If an older list is displayed
         * event_cats will be populated from event_cat. If the list has been edited in the gutenberg
         *editor, then event_cats will be populated already, and event_cat will  be empty.
         */

        $single_cat = $display_args['event_cat'];
        $cats = $display_args['event_cats'];
        if ($single_cat != "") {
            if ($cats == []) {
                $cats = [$single_cat];
            }
        }

        $groups = $display_args['groups'];


        if (
            'useglobal' != $groups && 'y' != $groups
            &&  'n' != $groups && '' != $groups
        ) {
            $error .= 'bad parameter: groups=' . esc_html($groups) . '<br>';
            $groups = 'useglobal';
        }
        if ('' == $groups || 'useglobal' == $groups) { // set order depending on option setting
            $exclude_groups = get_option('events_nogroups', '1') == 1 ? true : false;
        } elseif ('n' == $groups) {
            // the setting was 'n' - so exclude group events
            $exclude_groups = true;
        } else {
            // the setting was 'y' - so include group events
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

        $layout = $display_args['layout'];
        if (!in_array($layout, ['list', 'grid', 'line'])) {
            $error .= 'bad parameter: layout=' . esc_html($layout) . '<br>';
            $layout = 'list'; //default
        }
        $crop = $display_args['crop'];
        if (!in_array($crop, ['y', 'n'])) {
            $error .= 'bad parameter: crop=' . esc_html($crop) . '<br>';
            $crop = 'y'; //default
        }
        $bgcolor = $display_args['bgcolor'];

        $showtitle = ($display_args['showtitle'] == "y") ? true : false;

        // end of validation checks

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
                    'relation' => 'AND',
                    ['key' => 'eventDate', 'value' => $now, 'compare' => '<'],
                    ['key' => 'eventEndDate', 'value' => $limit_date, 'compare' => '>=']
                ];
            } else {
                $limit_date = date("Y-m-d", time() + 86400 * $limitdays);
                $date_query = [
                    'relation' => 'AND',
                    ['key' => 'eventEndDate', 'value' => $now, 'compare' => '>='],
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
        if (!empty($cats)) {
            $skip = false;
            foreach ($cats as $item) {
                if ($item == 'all') {
                    $skip = true;
                }
            }
            if (!$skip) {
                $query_args['tax_query'] = [[
                    'taxonomy' => U3A_EVENT_TAXONOMY,
                    'field'    => 'slug',
                    'terms' => $cats, // is now an array
                ]];
            }
        }
        $posts = get_posts($query_args);
        // create an event object for each post
        $events = [];
        foreach ($posts as $post) {
            $events[] = new self($post->ID);
        }

        $valid_events = [];
        if (! $on_group_page) {
            // we are not on the group page, hide events associated with a non-published group
            foreach ($events as $event) {
                if ($event->group_ID != '') {
                    $groupstatus = get_post_status($event->group_ID);
                    if ($groupstatus != 'publish') {
                        continue;
                    }
                }
                $valid_events[] = $event;
            }
        } else {
            $valid_events = $events;
        }

        // now sort the events of each day by start/end times
        $sorted_events = self::sort_on_times($valid_events);

        $display_args = [
            'showtitle' => $showtitle,
            'layout' => $layout,
            'crop' => $crop,
            'bgcolor' => $bgcolor,
            // no need to show the event's group if we are on the group page!
            'show_group_info' => !($on_group_page),
        ];
        
        $html = self::display_selected_events($sorted_events, $when, $display_args);
        return $html;
    }

    /**
     * Sorting function to be used by usort in the sort_on_times function.
     * Only valid for events for the same date.
     * 
     * @param $a first event containing times for start/end, format hh:mm or empty string
     * @param $b second event containing times for start/end.
     *
     * @return int -1 = a lessthan b, 0 = equal, 1 = a greaterthan b
     */
    private static function timecompare($a, $b)
    {
        if ($a->starttime < $b->starttime) {
            return -1;
        }
        if ($a->starttime > $b->starttime) {
            return 1;
        }
        if ($a->endtime < $b->endtime) {
            return -1;
        }
        if ($a->endtime > $b->endtime) {
            return 1;
        }
        return 0;
    }

    /**
     * Sort the items within a day in time order.
     *
     * This sorts first by start time in ascending order, then within the same
     * start time sorts by end time.
     * A missing end time is considered to be an early end time.
     *
     * @param array $events
     *  The list of events to sort. These will already be in ascending or
     * descending date order, but not fully sorted by time within the days.
     *
     * @return array the sorted events.
     */
    private static function sort_on_times($events)
    {
        // split into arrays by date.
        $events_on_day = [];  // an array of events keyed on date
        foreach ($events as $event) {
            $events_on_day[$event->date][] = $event;
        }
        // sort each array
        foreach (array_keys($events_on_day) as $date) {
            usort($events_on_day[$date], 'U3aEvent::timecompare');
        }
        // reassemble
        $sortedevents = [];
        foreach ($events_on_day as $date => $events) {
            foreach ($events as $event) {
                $sortedevents[] = $event;
            }
        }
        return $sortedevents;
    }
    /* Return the HTML code for selected events.
     *
     * @param array $events   the selected event objects
     * @param str $when       'past' / 'future'
     * @param array $display_args how and what fields to display ...
     * NOTE: This function MUST ONLY be called from display_eventlist(),
     * which ensures that all arguments are validly set.
     *
     * @return HTML <div> with a <h3> and a div and sub-divs for each event </div>
     *              or empty string ''
     */
    public static function display_selected_events($events, $when, $display_args)
    {
        if (!$events) {
            return '';
        }

        $when_text = ('past' == $when) ? 'Previous' : 'Forthcoming';
        $blockattrs = wp_kses_data(get_block_wrapper_attributes(['class' => 'u3aeventlist']));
        $html = "<div $blockattrs >\n";
        if ($display_args['showtitle']) {
            $html .= "<h3>$when_text events</h3>\n";
        }

        foreach ($events as $event) {
            switch ($display_args['layout']) {
                case 'line':
                    $html .= $event->display_line_item($display_args);
                    break;
                case 'grid':
                    $html .= $event->display_grid_item($display_args);
                    break;
                case 'list':
                default:
                    $html .= $event->display_list_item($display_args);
                    break;
            }
        } // end foreach
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

    /*
     * Returns the HTML for this object's custom data.
     *
     * @return string The HTML.
     */
    public function display($atts, $content)
    {
        $blockattrs = wp_kses_data(get_block_wrapper_attributes());
        $html = "<div $blockattrs >\n";
        $html .= "<table class=\"u3a_event_table\">\n";
        // event categories
        $event_categories = $this->get_event_categories();
        if ('' != $event_categories) {
            $html .= "<tr><td>Event type:</td> <td>$event_categories</td></tr>";
        }
        // date, time, duration
        $date_time = $this->event_date_and_time();
        $date = $date_time['date'];
        $time = $date_time['time'];
        $endtime = $date_time['endtime'];
        $enddate = $date_time['enddate'];
        if ('' != $endtime) {
            $endtime = '- ' . $endtime;
        }
        if (!empty($date)) {
            $html .= "<tr><td>Date: </td> <td>$date</td></tr>";
        }
        if (!empty($time)) {
            $html .= "<tr><td>Time: </td> <td>$time $endtime</td></tr>";
        }
        $days_duration = $this->days_duration;
        if (!empty($days_duration) && $days_duration > 1) {
            $html .= "<tr><td>Duration: </td> <td>$days_duration days</td></tr>";
            if (!empty($enddate)) {
                $html .= "<tr><td>Until: </td> <td>$enddate</td></tr>";
            }
        }

        // Group
        $group_text = U3aCommon::title_and_link($this->group_ID);
        if (!empty($group_text)) {
            $html .= "<tr><td>Group: </td> <td>$group_text</td></tr>";
        }

        // Venue
        $the_venue = new U3aVenue($this->venue_ID);
        $venue_name_with_link = $the_venue->venue_name_with_link();
        if (!empty($venue_name_with_link)) {
            $html .= "<tr><td>Venue: </td> <td>$venue_name_with_link</td></tr>";
        }

        // Organiser
        $contact = new U3aContact($this->organiser_ID);
        $contact_info = $contact->contact_text();
        if ($contact_info) {
            $html .= "<tr><td>Organiser: </td> <td>$contact_info</td></tr>";
        }

        //Cost
        $cost = $this->cost;
        if (!empty($cost)) {
            $html .= "<tr><td>Cost: </td> <td>$cost</td></tr>";
        }

        //Booking Required
        if ($this->booking_required) {
            $html .= "<tr><td>Booking:</td> <td>Note that booking is required.</td></tr>";
        }

        $html .= "</table>\n";
        $html .= "</div>\n";
        return $html;
    }


    /*
     * Returns HTML for this event in a format without images.
     *
     * @return string The HTML.
     */
    private function display_list_item($display_args)
    {
        $clickable_title = U3aCommon::title_and_link($this->ID);

        $date_time = $this->event_date_and_time();
        $date = $date_time['date'];
        $time = $date_time['time'];
        $endtime = $date_time['endtime'];
        $enddate = $date_time['enddate'];

        $time_line = $this->get_event_time_line($time, $endtime);
        $end_date_line = $this->get_event_end_date_line($enddate);

        $event_categories = $this->get_event_categories();

        $group_line = '';
        if ($display_args['show_group_info']) { 
            $group_line = $this->get_event_group_line();
        }

        $extract = $this->get_event_extract('margin-bottom:8px;margin-top:8px;');
        $venue_line = U3aEvent::get_event_venue_line();
        $cost_line = $this->get_event_cost_line();
        $booking_required_line = $this->get_event_booking_required_line();

        $html = <<< END
            <div class="u3aeventlist-item">
                <div class="u3aevent-list-left">
                    <div><strong>$date</strong></div>
                    $time_line
                    $end_date_line
                    <div>$event_categories</div>
                    $group_line
                </div>
                <div class="u3aevent-list-right">
                    <div class="u3aeventtitle">$clickable_title</div>
                    $extract
                    $venue_line
                    $cost_line
                    $booking_required_line
                </div>
            </div>
            END;
        return $html;
    }

    /*
     * Returns HTML for this event in a format with the event featured image.
     *
     * @return string The HTML.
     */
    private function display_grid_item($display_args)
    {
        $clickable_title = U3aCommon::title_and_link($this->ID);

        $image_HTML = $this->get_event_image($display_args);
        $bgcolor = $display_args['bgcolor'];
        $style_bgcolor = ('' != $bgcolor) ? "style=\"background-color:$bgcolor\" " : "";
        $date_time = $this->event_date_and_time();
        $date = $date_time['date'];
        $time = $date_time['time'];
        $endtime = $date_time['endtime'];
        $enddate = $date_time['enddate'];

        $time_line = $this->get_event_time_line($time, $endtime);
        $end_date_line = $this->get_event_end_date_line($enddate);

        $extract = $this->get_event_extract('margin-bottom:8px;margin-top:8px;');
        $venue_line = U3aEvent::get_event_venue_line();
        $cost_line = $this->get_event_cost_line();
        $booking_required_line = $this->get_event_booking_required_line();

        $html = <<< END
            <div class="u3aeventlist-item" $style_bgcolor>
                <div class="u3aevent-grid-left">
                    <div>$image_HTML</div>
                </div>
                <div class="u3aevent-grid-right">
                    <div class="u3aeventtitle">$clickable_title</div>
                    <div><strong>$date</strong></div>
                    $time_line
                    $end_date_line
                    $extract
                    $venue_line
                    $cost_line
                    $booking_required_line
                </div>
            </div>
        END;
        return $html;
    }

    /*
     * Returns HTML for this event in a single line format.
     *
     * @return string The HTML.
     */
    private function display_line_item($display_args)
    {
        $clickable_title = U3aCommon::title_and_link($this->ID);
        $date_time = $this->event_date_and_time();
        $date = $date_time['date'];
        $time = $date_time['time'];
        $html = <<< END
            <div class="u3aeventlist-item">
                <div class="u3aevent-line-left">
                    $date
                </div>
                <div class="u3aevent-line-middle">
                    $time
                </div>
                <div class="u3aevent-line-right">
                    <div class="u3aeventtitle">$clickable_title</div>
                </div>
            </div>
        END;
        return $html;
    }

    /*
     * Returns HTML for this event's image which is clickable to give event's page.
     *
     * @return string The HTML.
     */
    private function get_event_image($display_args)
    {
        $featured_image = get_the_post_thumbnail_url($this->ID, 'medium');
        if (!$featured_image) {
            $image_HTML = <<<END
                <div class = "no-figure">
                </div>
            END;
            return $image_HTML;
        }
        $permalink = get_the_permalink($this->ID);
        $caption = get_the_post_thumbnail_caption($this->ID);
        $fit = ("y" == $display_args['crop']) ? "u3a-crop" : "u3a-scale-down";
        //width of image to match containing div and margin.
        $image_HTML = <<<END
            <figure>
                <a href="$permalink">
                    <img class="u3a-eventlist-featured-image $fit" src="$featured_image" />
                </a>
                <figcaption>$caption</figcaption>
            </figure>
        END;
        return $image_HTML;
    }

    // Argument is a formatted date
    private function get_event_end_date_line($enddate)
    {
        // 1 day events shouldn't display end date!
        // previously a minor bug, as relied on users not entering duration = 1
        return ($this->days_duration > 1) ? "<div> to $enddate </div>" : '';
    }

    private function get_event_booking_required_line()
    {
        // Display only if a booking is required
        return ($this->booking_required) ? "<div><strong>Booking Required</strong></div>" : '';
    }

    private function get_event_cost_line()
    {
        $cost = $this->cost;
        $cost_line = ($cost) ? "<div> <b>Cost:</b> $cost </div>" : '';
        return $cost_line;
    }

    private function get_event_venue_line()
    {
        $venue_text  = U3aCommon::title_and_link($this->venue_ID);
        $venue_line = ($venue_text) ? "<div> <b>Venue:</b> $venue_text </div>" : '';
        return $venue_line;
    }

    private function get_event_group_line()
    {
        $group_text = U3aCommon::title_and_link($this->group_ID);
        $group_line = ($group_text) ? "<div>$group_text</div>" : '';
        return $group_line;
    }

    // Arguments are formatted times.
    // note: This could be a static function if written differently.
    private function get_event_time_line($starttime, $endtime)
    {
        if ('' == $this->starttime) {
            return '';
        }
        $time_line = $starttime;
        if ('' != $endtime) {
            $time_line .= ' - ' . $endtime;
        }
        $time_line = '<div>' . $time_line . '</div>';
        return $time_line;
    }

    private function get_event_extract($style)
    {
        add_filter('excerpt_length', function ($length) {
            return 30;
        });
        $extract = htmlspecialchars_decode(get_the_excerpt($this->ID));
        if (empty($extract)) {
            return '';
        }
        return "<div style='$style'>$extract</div>";
    }

    /**
     * Returns a comma separated list of this event's categories.
     */
    private function get_event_categories()
    {
        // get an array of term objects or null.
        $terms = get_the_terms($this->ID, U3A_EVENT_TAXONOMY);
        if ((false == $terms) || is_wp_error($terms)) {
            return '';
        }
        // make array of term names using array_map and an "arrow function"
        $term_names = array_map(fn($term) => $term->name, $terms);
        $event_categories = join(', ', $term_names);
        return $event_categories;
    }

    /**
     * Formats the event date and time.
     *
     * @return array [formatted date, formatted time, formatted endtime, formatted enddate]
     */
    public function event_date_and_time()
    {
        $date = $this->date;
        if (empty($date)) {
            return ['', '', '',''];  // Should never occur as eventDate is required
        }
        $starttime = $this->starttime;
        $endtime = $this->endtime;
        $enddate = $this->enddate;

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
        $formatted_date = date($dateformat, strtotime($date));
        $formatted_starttime = ($starttime) ? date($timeformat, strtotime($starttime)) : '';
        $formatted_endtime = ($endtime) ? date($timeformat, strtotime($endtime)) : '';
        $formatted_enddate = ($enddate) ? date($dateformat, strtotime($enddate)) : '';

        return [
            'date' => $formatted_date,
            'time' => $formatted_starttime,
            'endtime' => $formatted_endtime,
            'enddate' => $formatted_enddate,
        ];
    }

    /**
     * Repeat Event
     * Add row action to the posts table for authorised users
     */

    public static function repeat_event_row_action($actions, $post)
    {
        if ($post->post_type == U3A_EVENT_CPT) {
            // Check we are authorised.  
            // Add action for author only if they are the author of this event
            if ((get_current_user_id() == $post->post_author) || current_user_can('edit_others_pages')) {
                $id = $post->ID;
                $url = wp_nonce_url(admin_url("admin.php?page=repeat-event&post=$id"), 'repeatEvent');
                $actions['repeat'] = "<a href=\"$url\">Repeat&nbsp;Event</a>";
            }
        }
        return $actions;
    }

    /**
     * Repeat Event
     * Add the admin page to the u3a Events menu but not shown as it makes no sense
     * to go direct to the page without selecting an event to repeat
     */

    public static function repeat_event_admin_page()
    {
        add_submenu_page(
            'edit.php?post_type=' . U3A_EVENT_CPT,
            'Repeat Event',
            '', // empty string to avoid appearing in menu
            'publish_posts',
            'repeat-event',
            array(self::class, 'render_repeat_event_admin_page')
        );
    }

    /**
     * Repeat Event
     * Display the admin page
     */

    public static function render_repeat_event_admin_page()
    {
        // Nonce check
        if (! isset($_REQUEST['_wpnonce']) || ! wp_verify_nonce($_REQUEST['_wpnonce'], 'repeatEvent')) {
            wp_die('Invalid access');
            exit;
        }

        // Event ID of field to repeat is passed in query string as post
        if (array_key_exists('post', $_GET)) {
            $eventID = filter_var($_GET['post'], FILTER_VALIDATE_INT);
        } else {
            wp_die('Invalid access');
            exit;
        }
        // Check we are either an admin/editor or an author that owns the event
        if (! (current_user_can('edit_others_posts') || get_current_user_id() == get_post_field('post_author', $eventID))) {
            wp_die('Unauthorised');
            exit;
        }
        // Check the event exists!
        // phpcs:disable WordPress.Security.EscapeOutput -- all variables trusted

        if (!get_post_status($eventID)) {
            $url = admin_url('edit.php?post_type=' . U3A_EVENT_CPT);
            echo <<< END
            <h2>Invalid event ID</h2>
            <p>Event with id $eventID does not exist.</p>
            <a href="$url" class="button-primary">Continue</a>
            END;
            return;
        }

        // Data needed to display the admin page
        $posttitle = get_the_title($eventID);
        $posteventdate = get_post_meta($eventID, 'eventDate', true);
        $group_ID= get_post_meta($eventID, 'eventGroup_ID', true);
        // set frequency based on frequncy of associated group, if that exists.
        $frequency = ($group_ID) ? get_post_meta($group_ID, 'frequency', true) : '';
        $postgroup = U3aCommon::title_and_link($group_ID);
        $group_header = ($postgroup) ? "<h3>A group event for $postgroup</h3>" : '';
        $datestring = gmdate(get_option('date_format'), strtotime($posteventdate));
        //pre-set selected frequency if known.
        $none_option = '';
        $weekly_option = '';
        $fortnightly_option = '';
        $monthly_option = '';
        $twice_monthly_option = '';
        switch ($frequency) {
          case 'Weekly':
            $weekly_option = 'selected';
            break;
          case 'Fortnightly':
            $fortnightly_option = 'selected';
            break;
          case 'Monthly':
            $monthly_option = 'selected';
            break;
          case 'Twice-monthly':
            $twice_monthly_option = 'selected';
            break;
          default:
            $none_option = 'selected';
        }

        $nonce_code =  wp_nonce_field('u3a_settings', 'u3a_nonce', true, false);
        $u3aMQDetect = "<input type=\"hidden\" name=\"u3aMQDetect\" value=\"test'\">\n";
        echo <<< END
        <div class="wrap u3a_repeat_event">
        <h1 class="wp-heading-inline">Repeat Event: $posttitle ($datestring)</h1>
        $group_header
        <hr class="wp-header-end">
        <form method="post" action="admin-post.php">
        $nonce_code
        $u3aMQDetect 
        <input type="hidden" name="action" value="u3a_repeat_event">
        <input type="hidden" id="posteventdate" value="$posteventdate">
        <input type="hidden" id="posttitle" value="$posttitle">
        <input type="hidden" name="eventID" id="eventID" value="$eventID">

        <div id="setup-form">
        <div>
            <p>You can define a regular series of events based on the above event as a template.</p>
            <p>The above event will either be the first event in the series (the default), or a template only and the first event in the series is on a different date.</p>
        </div>
        <div>
            <input type="radio" id="use_event" name="start-choice" value="use_event" checked>
            <label for="use_event">Use above event as first of series</label>
        </div>
        <div>
            <input type="radio" id="use_date" name="start-choice" value="use_date" >
            <label for="use_date">Start series on a different date</label>
        </div>
        <div class="boxinput" id="startDateInput" style="display:none">
            <label for="firsteventdate">Enter series start date</label>
            <div>
            <input type="date" name="firstEventDate" id="firstEventDate">
            </div>
        </div> <!-- end of startDateInput -->
        <div class="boxinput">
            <label for="repeatFrequency">Repeat Frequency</label>
            <div>
            <select name="repeatFrequency" id="repeatFrequency">
                <option value="none" $none_option>Select a frequency</option>
                <option value="weekly" $weekly_option>Weekly</option>
                <option value="fortnightly" $fortnightly_option>Fortnightly</option>
                <option value="monthly" $monthly_option>Monthly</option>
                <option value="twice-monthly" $twice_monthly_option>Twice-monthly</option>
            </select>
            <p id="datePattern">&nbsp;</p>
            </div>
        </div>
        
            <p>You must limit the number of events in the series by specifying at least one of the following:</p>
        
        <div class="boxinput">
            <label for="numEvents">Number of events in series<br>(max 13)</label>
            <div>
            <input id="numEvents" name="numEvents" type="number" min="1" max="13" >
            <p id="numEventsComment">This includes the existing event.</p>
            </div>
        </div>
        <div class="boxinput">
            <label for="eventCutoffDate">Cut-off date</label>
            <div>
            <input type="date" name="eventCutoffDate" id="eventCutoffDate">
            <p>If set, events will not be created beyond this date.</p>
            </div>
        </div>
        <input type="button" name="continueButton" id="continueButton" class="button button-primary" value="Continue">

        </div> <!-- end setup-form -->

        <div id="repeatEntriesSection">
        <h3>Events to be created</h3>
        <p>You may amend the event titles before creating the events, or remove events on dates which are not required.</p>
        <table>
        <tbody id="repeatEntries">
        <tr><th>Date</th><th>Event title</th><th></th></tr>
        </tbody>
        </table>

        <h3>Copying data</h3>
        <p>Data about the event will be copied from the template event.<br>However, if the desriptive content is specific to the original event, you can choose not to copy this to new events.</p>
        <div>
            <input type="radio" id="copyContent" name="copyContent" value="1" checked>
            <label for="copyContent">Copy descriptive content from original event</label>
        </div>
        <div>
            <input type="radio" id="notcopyContent" name="copyContent" value="0">
            <label for="notcopyContent">New events have no descriptive content</label>
        </div>

        <h3>Visibility</h3>
        <div>
            <input type="radio" id="publishEvents" name="publishEvents" value="1" checked>
            <label for="publishEvents">Publish now</label>
        </div>
        <div>
            <input type="radio" id="draftEvents" name="publishEvents" value="0">
            <label for="draftEvents">Save as draft</label>
        </div>

        <input type="submit" name="submit" id="submit" class="button button-primary" value="Create Events">
        <input type="button" id="repeatEventReset" class="button button-secondary" value="Reset">

        </div> <!-- end repeatEntriesSection  -->
        </form>
        </div> <!-- u3a_repeat_event -->
        END;
        // phpcs:enable WordPress.Security.EscapeOutput

    }

    /**
     * Repeat Event
     * Handle the form submission for repeat events
     * 
     * Event ID to duplicate is eventID
     * Dates in array newdates[]
     * Titles in array newtitles[]
     * Duplicate post content if copycontent is set
     */

    public static function repeat_event_save()
    {
        // Check we are authorised
        if (! current_user_can('publish_posts')) {
            wp_die('Unauthorised');
            exit;
        }
        // Check nonce
        if (check_admin_referer('u3a_settings', 'u3a_nonce') == false) {
            wp_die('Invalid form submission');
            exit;
        }
        $return_page = admin_url('edit.php?post_type=' . U3A_EVENT_CPT);

        // Check we have required $_POST arguments with some newdates
        if (!array_key_exists('eventID', $_POST) ||
            !array_key_exists('newdates', $_POST) ||
            !array_key_exists('newtitles', $_POST) ||
            !array_key_exists('copyContent', $_POST) ||
            !array_key_exists('publishEvents', $_POST)
           ) {
            wp_safe_redirect($return_page);
            exit;
        }
        $eventID = filter_var($_POST['eventID'], FILTER_VALIDATE_INT);
        if (!get_post_status($eventID)) {
            // Silently ignore if post not found
            wp_safe_redirect($return_page);
            exit;
        }
        $newdates = $_POST['newdates'];
        $newtitles = $_POST['newtitles'];

        // check for WP magic quotes
        $u3aMQDetect = $_POST['u3aMQDetect'];
        $needStripSlashes = (strlen($u3aMQDetect) > 5) ? true : false; // backslash added to apostrophe in test string?

        // Retrieve post content, metadata and terms for the post being repeated
        $post = get_post($eventID);
        $meta = get_post_meta($eventID);
        $terms = wp_get_object_terms($eventID, U3A_EVENT_TAXONOMY, array('fields' => 'ids'));

        $c = count($newdates);
        for ($i = 0; $i < $c; $i++) {
            // Assemble new post and meta data
            $meta_data = array('eventDate' => $newdates[$i]); //'eventEndDate' computed automatically on save
            foreach (['eventTime', 'eventEndTime','eventDays', 'eventGroup_ID', 'eventVenue_ID', 'eventOrganiser_ID', 'eventCost', 'eventBookingRequired'] as $key) {
                if (array_key_exists($key, $meta)) {
                    $meta_data[$key] = $meta[$key][0];
                }
            }
            $title = $needStripSlashes ? stripslashes($newtitles[$i]) : $newtitles[$i];
            $title = sanitize_text_field($title);
 
            // Get the event default content from a function in class U3aEvent, ...
            //   using a dummy object
            $dummy_post = (object)['post_type' => U3A_EVENT_CPT];
            $event_default_content = U3aEvent::add_default_content('', $dummy_post);

            $args = array(
                'post_author' => $post->post_author,
                'post_content' => ($_POST['copyContent']) ? $post->post_content : $event_default_content ,
                'post_title' => $title,
                'post_excerpt' => $post->post_excerpt,
                'post_status' => ($_POST['publishEvents']) ? 'publish' : 'draft',
                'post_type' => U3A_EVENT_CPT,
                'post_password' => $post->post_password,
                'meta_input' => $meta_data
            );
            $newID = wp_insert_post($args);
            // Add categories
            wp_set_object_terms($newID, $terms, U3A_EVENT_TAXONOMY);
        }
        // New events added  :-)
        wp_safe_redirect($return_page);
        exit;
    }

    /**
     * Repeat Event
     * Load scripts and CSS for the admin page
     */

    public static function repeat_event_scripts($hook)
    {
        if ($hook == 'u3a_event_page_repeat-event') {
            wp_enqueue_style(
                'repeateventstyle',
                plugins_url('css/u3a-repeatevent.css', self::$plugin_file),
                array(),
                U3A_SITEWORKS_CORE_VERSION,
                false
            );
            wp_enqueue_script(
                'repeateventscript',
                plugins_url('js/u3a-repeatevent.js', self::$plugin_file),
                array(),
                U3A_SITEWORKS_CORE_VERSION,
                true
            );
        }
    }

    /**
     * Add a Repeat Event action to the Admin Toolbar when displaying a single event
     * if current user has rights to do so
     */

    public static function repeat_event_admin_toolbar($wp_admin_bar)
    {
        $postID = get_the_ID();
        if (
            !is_admin() &&
            (get_post_type($postID) == U3A_EVENT_CPT)  &&
            ((current_user_can('edit_others_posts') || get_current_user_id() == get_post_field('post_author', $postID)))
        ) {
            $url = wp_nonce_url(admin_url("admin.php?page=repeat-event&post=$postID"), 'repeatEvent');
            $wp_admin_bar->add_menu(array(
                'id'    => 'repeat_event',
                'title' => 'Repeat Event',
                'href'  => $url
            ));
        }
    }
}
