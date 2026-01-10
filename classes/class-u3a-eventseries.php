<?php

/**
 * This class manages these metadata fields:
 * eventStartDate   YYYY-MM-DD
 * eventFrequency   weekly, fortnightly, monthly or twice-monthty
 * eventNumber      maximum number of events in the series
 * eventCutoffDate  YYYY-MM-DD
 * other event data as for u3a_events
 * also eventsCreated set to 1 once the events in the series have been created.
 */

define( 'U3A_EVENTSERIES_ICON' , 'dashicons-schedule');

//NOTE: Need to ensure this CPT is listed in U3aCommon::load_ensure_title_script

class U3aEventSeries
{
    use ChangePrompt;

    /**
     * The post_type for this class
     *
     * @var string 
     */
    public static $post_type = U3A_EVENTSERIES_CPT;

    /**
     * The term used for the title of these custom posts
     *
     * @var string 
     */
    public static $term_for_title = "name for eventseries";

    // $plugin_file is the value of __FILE__ from the main plugin file
    private static $plugin_file;

    public static $frequency_list = ['weekly' => 'Weekly', 'fortnightly' => 'Fortnightly', 'monthly' => 'Monthly', 'twice-monthly' => 'Twice monthly'];

    /**
     * The ID of this post
     *
     * @var string
     */
    public $ID;

    /*
     * Construct a new object for a u3a_eventseries post.
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

        // Register Eventseries CPT
        add_action('init', array(self::class, 'register_eventseries'));

        // Routine to run on plugin activation
        register_activation_hook($plugin_file, array(self::class, 'on_activation'));

        // Change prompt shown for post title
        add_filter('enter_title_here', array(self::class, 'change_prompt'));

        // Set up the custom fields in a metabox (using free plugin from on metabox.io)
        add_filter('rwmb_meta_boxes', [self::class, 'add_metabox'], 10, 1);

        // Load admin javascript for event-series
        add_action('admin_enqueue_scripts', array(self::class, 'load_editor_script'), 10, 1);

        // Add action to restrict database field lengths
        add_action('save_post_u3a_eventseries', ['U3aEvent', 'validate_event_fields'], 30, 2);
 
        // Add action to create events in the series
        add_action('save_post_u3a_eventseries', [self::class, 'create_series_of_events'], 40, 2);
        //add_action('the_content', [self::class, 'create_series_of_events'], 20, 1); DEBUG
    
        // Add default content to new posts of this type
        add_filter('default_content', array(self::class, 'add_default_content'), 10, 2);
        // Register the shortcode
        add_shortcode('u3a_eventseries_list', array(self::class,'u3a_eventseries_list_shortcode'));
    }

    /**
     * Registers the custom post type and taxonomy for this class.
     */
    public static function register_eventseries()
    {
        $args = array(
            'public' => true,
            'show_in_rest' => true,
            'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'color'),
            'rewrite' => array('slug' => sanitize_title(U3A_EVENTSERIES_CPT)),
            'has_archive' => false,
            'menu_icon' => U3A_EVENTSERIES_ICON,
            'labels' => array(
                'name' => 'u3a Event Series',
                'singular_name' => 'Event Series',
                'add_new_item' => 'Add Event Series',
                'add_new' => 'Add New Event Series',
                'edit_item' => 'Edit Event Series',
                'all_items' => 'All Event Series',
                'view_item' => 'View Event Series',
                'update_item' => 'Update Event Series',
                'search_items' => 'Search Event Series'
            )
        );

        register_post_type(U3A_EVENTSERIES_CPT, $args);
    }

    /**
     * Do tasks that should only be done on activation
     *
     * Register post type and flush rewrite rules.
     */
    public static function on_activation()
    {
        self::register_eventseries();
        delete_option('rewrite_rules');
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
            'title'    => 'Event Series Information',
            'id'       => U3A_EVENTSERIES_CPT,
            'post_types' => [U3A_EVENTSERIES_CPT],
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
            'type'    => 'date',
            'name'    => 'First event date',
            'id'      => 'eventStartDate',
            'desc' => 'The date of the first event in the series',
            'required' => true,
            // TODO: Maybe no pattern needed as the picker restricts the value range?
            'pattern' => '[1-2][0-9][0-9][0-9]-[0-1][0-9]-[0-3][0-9]', // catches most bad input!
        ];
        $fields[] = [
            'type'    => 'select',
            'name'    => 'Frequency',
            'id'      => 'eventFrequency',
            'required' => true,
            'options' => self::$frequency_list,
            'std'     => 'weekly', // default value
            ];
        $fields[] = [
            'type'    => 'heading',
            'name'    => 'Make sure that the starting dates and frequency meet your needs.',
            'id'      => 'datePattern',
            'desc'    => '<strong>If you choose "Monthly" each event will be on the same day and week of each month. "Twice monthly" events will be in either the 1st and 3rd week or 2nd and 4th week of the month<br>If you choose "Monthly" or "Twice monthly", your start date must not be in the fifth week of a month (29th/30th/31st).</strong>',
            ];
        $fields[] = [
            'type'    => 'number',
            'name'    => 'Number of events to create (max 13)',
            'id'      => 'eventNumber',
            'min'     => 1,
            'max'     => 13,
            'desc' => 'Optional. Enter either this or cut-off date, or both',
        ];
        $fields[] = [
            'type'    => 'date',
            'name'    => 'Series cut-off date',
            'id'      => 'eventCutoffDate',
            'desc' => 'The series will go not beyond this date',
            // TODO: Maybe no pattern needed as the picker restricts the value range?
            'pattern' => '[1-2][0-9][0-9][0-9]-[0-1][0-9]-[0-3][0-9]', // catches most bad input!
        ];
        $fields[] = [
            'type'       => 'taxonomy',
            'name'       => 'Event category',
            'id'         => 'category',
            'taxonomy'   => U3A_EVENT_TAXONOMY,
            'field_type' => 'select_advanced',
            'required' => true,
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

    public static function load_editor_script($hook)
    {
        global $post;
        if ($hook == 'post-new.php' || $hook == 'post.php') {
            if (U3A_EVENTSERIES_CPT == $post->post_type) {
                wp_enqueue_script(
                    'event-series', 
                    plugins_url('js/u3a-event-series.js', self::$plugin_file), 
                    array('jquery', 'wp-data', 'wp-editor', 'wp-edit-post'),
                    U3A_SITEWORKS_CORE_VERSION,
                    false,
                );
            }
        }
    }


    /**
    * Create series from start date.
    *
    * options =   weekly
    *             fortnightly
    *             monthly on same day of the same wek of the month
    *             twice monthly (Either in the 1st and 3rd weeks, or in 3rd and 4th weeks) 
    * with
    * number of events = m
    * but not beyond  = edate
     */
    public static function create_series_of_events($post_id, $post)
    {
        $max_events = 13;
        if ($post->post_type != U3A_EVENTSERIES_CPT) {
            return $content;
        }

        if ($post->post_status != 'publish') { // apparently this is called when making a draft post
            return;
        }
        if (get_post_meta($post_id, 'eventsCreated', true)) { 
            // events in the series have already been created so do nothing.
            return;
        }
        $date = get_post_meta($post_id, 'eventStartDate', true);
        if (empty($date)) { // shouldn't happen: required on input.
            return;
        }
        $date = DateTime::createFromFormat('Y-m-d',$date);

        $frequency = get_post_meta($post_id, 'eventFrequency', true);
        if (empty($frequency)) { // shouldn't happen: required on input.
            return;
        }
        $num_events = get_post_meta($post_id, 'eventNumber', true);
        $num_events = (int)((!empty($num_events)) ? $num_events : $max_events);

        $cutoff_date = get_post_meta($post_id, 'eventCutoffDate', true);
        if (empty($cutoff_date)) {
            $cutoff_date = '2099-12-31'; // far in the future
        }
        $cutoff_date = DateTime::createFromFormat('Y-m-d',$cutoff_date);

        $dayOfWeek = $date->format('w');  // 0 = Sunday
        $day = $date->format('d');
        // $weekOfMonth 1 to 5 according to which week of the month $date is in
        $weekOfMonth = (int)(($date->format('d') + 6) / 7);
        $days_list = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $weeks_list = ['0th', '1st', '2nd', '3rd', '4th', '5th'];
        $two_weeks_list = ['0th', '1st and 3rd', '2nd and 4th', '1st and 3rd', '2nd and 4th', '5th'];

        // so $date is "the $weeks_list[$weekOfMonth] $days_list[$dayOfWeek] of the month"
        //echo  $post_id . ' The ' . $weeks_list[$weekOfMonth] . ' ' . $days_list[$dayOfWeek] . ' of the month', '<br>' ; //debug

        // Set variables common to all events in the series
        $event_meta = [];
        $keys = ['eventTime','eventEndTime','eventGroup_ID','eventVenue_ID',
                 'eventOrganiser_ID','eventCost','eventBookingRequired',
                ];
        foreach ($keys as $key) {
            $temp = get_post_meta($post_id, $key, true);
            if (!empty($temp)) {
                $event_meta[$key] = $temp;
            };
        }
        // Also set 'series' meta key
        $event_meta['series'] = $post_id;
        
        $terms = get_the_terms($post, U3A_EVENT_TAXONOMY);
        if (empty($terms)) {
            // event_category is required input, so shouldn't happen, return silently
            return;
        }
        // Get the default content from a function in class U3aEvent using a dummy object
        // $default_content = '<!-- wp:u3a/eventdata /--><!-- wp:paragraph --><p></p><!-- /wp:paragraph -->  ';
        $dummy_post = (object)['post_type' => U3A_EVENT_CPT];
        $default_content = U3aEvent::add_default_content('', $dummy_post);
        $event_category_slug = $terms[0]->slug;
        $event_insert_args = [
            'post_title'   => '', // set later
            'post_type'    => U3A_EVENT_CPT,
            'post_content' => $default_content, 
            'post_status'  => 'publish',
            'meta_input'   => $event_meta, // eventDate will be set later.
            'tax_input'    => [U3A_EVENT_TAXONOMY => $event_category_slug],
        ];
        // $eventseries_content = '<ul>';
        // now create each event
        for ($i = 1; $i <= $num_events; $i++) {
            if ($i > 1) { // need to increment date
                $check_week = false;
                if ('weekly' == $frequency) { 
                    $modify = '+7 days';
                } elseif ('fortnightly' == $frequency) {
                    $modify = '+14 days';
                } elseif ('monthly' == $frequency) {
                    $modify = '+28 days';
                    // but some times need an extra week.
                    $check_week = true;
                    $required_weekOfMonth = (int)(($date->format('d') + 6) / 7);
                } elseif ('twice-monthly' == $frequency) {
                    $modify = '+14 days';
                    if ($date->format('d') > 14 ) {
                        // after 14th of month may need an extra week
                        $check_week = true;
                        $old_weekOfMonth = (int)(($date->format('d') + 6) / 7);
                         // so, since we are in 3rd/4th week..
                        $required_weekOfMonth = $old_weekOfMonth - 2;
                    }
                } else {
                    // shouldn't happen, return silently
                    return;
                }
                $date->modify($modify); // increment by normal amount
                if ($check_week) {
                    $new_weekOfMonth = (int)(($date->format('d') + 6) / 7);
                    if ($new_weekOfMonth != $required_weekOfMonth) {
                        // add another week to get to correct week.
                        $date->modify('+7 days');
                    }
                }
            }
            if ($date > $cutoff_date) {
                break;
            }
            // now create new event for $date
            // finally set the eventDate and create the event
            $date_string = $date->format('Y-m-d');
            $event_title = $post->post_title;
            $event_insert_args['meta_input']['eventDate'] = $date_string;
            $event_insert_args['post_title'] = $event_title;
            $event_id = wp_insert_post($event_insert_args);
            
            $edit_link = admin_url("post.php?post=" . $event_id . "&action=edit");
            $edit_hint = "Edit this event";
            $edit_HTML = "<a href= '$edit_link'><span style='background-color:yellow;' class='dashicons dashicons-edit' title='$edit_hint'></span></a>";
            
            //$eventseries_content .= "    <li> $event_title $edit_HTML</li>";

        }

        // mark this eventseries to prevent repeated creation of events.
        update_post_meta($post_id, 'eventsCreated', 1);
        // put list of events into eventseries content.
        //$eventseries_content .= '</ul>';
        $shortcode = "<!-- wp:shortcode -->[u3a_eventseries_list]<!-- /wp:shortcode -->";
        $new_content = self::add_default_content('', $post) . $shortcode;
        wp_update_post(['ID' => $post_id,
                        'post_content' => $new_content,
                        ]);
        return;
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
        if ($post->post_type == U3A_EVENTSERIES_CPT) {
            $content = <<< END
            <!-- wp:paragraph -->
            <p>When you publish this, a series of events will be created.<br>
            Each event will have a title consisting of the series name.<br>
            <b>You can only edit this event series as follows</b>:You can change the title of any specific event and delete specific events. You can also go to edit any event in order to make other changes to the event including adding other information. This will open in a separate tab so you can go back to the event series page in order to add details to other events in the series.</p>
            <!-- /wp:paragraph -->
            END;
            return $content;
        }
        return $content;
    }

    /**
     * This shortcode can only be used on an event_series page 
     *
     */
    public static function u3a_eventseries_list_shortcode($args)
    {
        global $post;
        if (U3A_EVENTSERIES_CPT != $post->post_type) {
            return "This shortcode is not valid here.";
        }
        $html = '';
        $slug = $post->post_name;

        // Is this a vaild response from the form?
        if (isset($_POST['action']) && 'changeEvents' == $_POST['action']){
            $html .= '<p> <b>Ho ho ho, actions not coded yet!</b></p>';
            $html .= '<p>' .json_encode($_POST) . '</p>';
            if (isset($_POST['u3a_eventSeries_nonce'])
          && wp_verify_nonce($_POST['u3a_eventSeries_nonce'], 'eventSeries'. $slug)){
              $html .= '<p> nonce verified</p>';
            }
        $html .= '<p> <b>End of change events report!</b></p>';
        }

        // Now generate form containing the list of events in the series

        $slug = $post->post_name;
        $series_ID = $post->ID;
        $nonce_code =  wp_nonce_field('eventSeries' . $slug, 'u3a_eventSeries_nonce', true, false);
        //$submit_button = get_submit_button('Save Settings');
        $query_args = [
            'post_type' => U3A_EVENT_CPT,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_key' => 'eventDate',
            'orderby' => 'meta_value',
            'order'    => 'ASC',
        ];
        // just the events in this series
        $query_args['meta_query'] = [
            ['key' => 'series','value' => $series_ID,]
        ];
        $html .= <<< END
        <form id="eventSeries" method="POST">
            <input type="hidden" name="action" value="changeEvents">
            $nonce_code
            <table class="u3a_event_table">
            
        END;
        //
        $posts = get_posts($query_args);
        
        foreach ($posts as $event) {
            $event_ID = $event->ID;
            $event_obj = new U3aEvent($event_ID);  // look forward to events being a child class of WP_Post!!
            list($date, $time, $endtime) = $event_obj->event_date_and_time();
            $remove = "remove" . $event_ID;
            $title = $event->post_title;
            $newtitle = "newtitle" . $event_ID;
            $edit_link = admin_url("post.php?post=" . $event_ID . "&action=edit");
            $edit_event_html = "<a href= '$edit_link'>Edit event<span style='background-color:yellow;' class='dashicons dashicons-edit'></span></a>";
            $html .= <<< END
                  <tr>
                    <td>$date<br/>
                        <label for="$remove">Remove? </label>
                        <input type="checkbox" id="$remove" name="$remove" value="1">
                    </td>
                    <td>$title<br/>
                        <input type="text" name="$newtitle" placeholder="Enter revised title, if reqd.">
                    </td>
                    <td>$edit_event_html
                    <!-- NOT a big button!<a class="wp-element-button" href="...">Edit event</a>  -->
                    </td>
                  </tr>
                  
            END;
        }
        $html .= <<< END
              <tr>
                <td></td>
                <td>
                    <button class="wp-element-button" id="submit" name="submit" type="submit">Submit changes</button>
                </td>
                <td></td>
              </tr>
            </table>
        </form>
        END;
        // not needed?? wp_reset_postdata();
        return $html;
    }
}
