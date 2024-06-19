<?php

class U3aVenue
{
    use ModifyQuickEdit;
    use ChangePrompt;
    use ManageCrossRefs;

    /**
     * The post_type for this class
     *
     * @var string 
     */
    public static $post_type = U3A_VENUE_CPT;

    /**
     * The short name for this class
     *
     * @var string 
     */
    public static $post_type_name = 'venue';

    /**
     * The term used for the title of these custom posts
     *
     * @var string 
     */
    public static $term_for_title = "venue name";

    /**
     * The meta keys that contain xrefs to this type of post
     *
     * @var string of keys within single quotes 
     */
    public static $xref_meta_key_list = "'venue_ID', 'eventVenue_ID'";

    // $plugin_file is the value of __FILE__ from the main plugin file
    private static $plugin_file;

    /* Limits on the max size of data input */
    const MAX_DISTRICT = 255;
    const MAX_ADDRESS_LINE = 255;
    const MAX_TOWN = 255; // its 58 really - but might add extra details.
    const MAX_POSTCODE = 60;
    const MAX_ACCESSIBILITY = 1024;
    const MAX_URL = 2000; // for all browsers
    const MAX_PHONE = 60;

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

    /**
     * Set up the actions and filters used by this class.
     *
     * @param $plugin_file the value of __FILE__ from the main plugin file 
     */
    public static function initialise($plugin_file)
    {
        self::$plugin_file = $plugin_file;

        // Register Venue CPT
        add_action('init', array(self::class, 'register_venues'));

        // Routine to run on plugin activation
        register_activation_hook($plugin_file, array(self::class, 'on_activation'));

        // Register the blocks
        add_action('init', array(self::class, 'register_blocks'));

        // Add action to restrict database field lengths
        add_action('save_post_u3a_venue', [self::class, 'validate_venue_fields'], 30, 2);

        // Add default content to new posts of this type
        add_filter('default_content', array(self::class, 'add_default_content'), 10, 2);

        // Change prompt shown for post title
        add_filter('enter_title_here', array(self::class, 'change_prompt'));

        // Set up the custom fields in a metabox (using free plugin from on metabox.io)
        add_filter( 'rwmb_meta_boxes', [self::class, 'add_metabox'] , 10, 1 );

        // Customise the Quick Edit panel
        add_action('admin_head-edit.php', array(self::class, 'modify_quick_edit'));
 
        // Prevent trashing when there there xrefs to this post in other posts.
        add_action('wp_trash_post', array(self::class, 'restrict_post_deletion'));
        
        //Add display of all xrefs to this post in other posts.
        add_filter('the_content', array(self::class, 'display_xrefs'), 20, 1);
   }

    // validate the lengths of fields on save
    public static function validate_venue_fields($post_id, $post)
    {
        $value = get_post_meta($post_id, 'district', true);
        if (strlen($value) > self::MAX_DISTRICT) {
            update_post_meta($post_id, 'district', '');
        }
        $value = get_post_meta($post_id, 'address1', true);
        if (strlen($value) > self::MAX_ADDRESS_LINE) {
            update_post_meta($post_id, 'address1', '');
        }
        $value = get_post_meta($post_id, 'address2', true);
        if (strlen($value) > self::MAX_ADDRESS_LINE) {
            update_post_meta($post_id, 'address2', '');
        }
        $value = get_post_meta($post_id, 'town', true);
        if (strlen($value) > self::MAX_TOWN) {
            update_post_meta($post_id, 'town', '');
        }
        $value = get_post_meta($post_id, 'postcode', true);
        if (strlen($value) > self::MAX_POSTCODE) {
            update_post_meta($post_id, 'postcode', '');
        }
        $value = get_post_meta($post_id, 'access', true);
        if (strlen($value) > self::MAX_ACCESSIBILITY) {
            update_post_meta($post_id, 'access', '');
        }
        $value = get_post_meta($post_id, 'url', true);
        if (strlen($value) > self::MAX_URL) {
            update_post_meta($post_id, 'url', '');
        }
        $value = get_post_meta($post_id, 'phone', true);
        if (strlen($value) > self::MAX_PHONE) {
            update_post_meta($post_id, 'phone', '');
        }
    }

    /**
     * Registers the custom post type for this class.
     */
    public static function register_venues()
    {
        $args = array(
            'public' => true,
            'show_in_rest' => true,
            'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt'),
            'rewrite' => array('slug' => sanitize_title(U3A_VENUE_CPT . 's')),
            'has_archive' => false,
            'menu_icon' => U3A_VENUE_ICON,
            'labels' => array(
                'name' => 'u3a Venues',
                'singular_name' => 'Venue',
                'add_new_item' => 'Add Venue',
                'add_new' => 'Add New Venue',
                'edit_item' => 'Edit Venue',
                'all_items' => 'All Venues',
                'view_item' => 'View Venue',
                'update_item' => 'Update Venue',
                'search_items' => 'Search Venues'
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
        register_post_type(U3A_VENUE_CPT,$args);
    }

    /**
     * Do tasks that should only be done on activation.
     *
     * Register post type and flush rewrite rules.
     */
    public static function on_activation()
    {
        self::register_venues();
        delete_option('rewrite_rules');
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
        if ($post->post_type == U3A_VENUE_CPT) {
            $content = '<!-- wp:u3a/venuedata /--><!-- wp:paragraph --><p></p><!-- /wp:paragraph -->';
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
     */
    public static function add_metabox( $metaboxes )
    {
        $metabox = [
            'title'    => 'Venue Information',
            'id'       => U3A_VENUE_CPT,
            'post_types' => [U3A_VENUE_CPT],
            'context'  => 'normal',
            'autosave' => true,
        ];
        $metabox['fields'] = self::field_descriptions();
        // add metabox to all input rwmb metaboxes
        $metaboxes[] = $metabox;
        return $metaboxes;
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
        if (get_option('field_v_district', '1') == '1') {
            $fields[] =
                [
                'type'    => 'text',
                'name'    => 'District',
                'id'      => 'district',
                'desc'    => '',
                'maxlength' => self::MAX_DISTRICT,
                ];
        }
        $fields[] =
            [
            'type'    => 'heading',
            'name'    => 'Address',
            ];
        $fields[] =
            [
            'type'    => 'text',
            'name'    => 'Address Line 1',
            'id'      => 'address1',
            'desc'    => '',
            'maxlength' => self::MAX_ADDRESS_LINE,
            ];
        $fields[] =
            [
            'type'    => 'text',
            'name'    => 'Address Line 2',
            'id'      => 'address2',
            'desc'    => '',
            'maxlength' => self::MAX_ADDRESS_LINE,
            ];
        $fields[] =
            [
            'type'    => 'text',
            'name'    => 'Town',
            'id'      => 'town',
            'desc'    => '',
            'maxlength' => self::MAX_TOWN,
            ];
        $fields[] =
            [
            'type'    => 'text',
            'name'    => 'Postcode',
            'id'      => 'postcode',
            'size'    => '30px',
            'desc'    => '',
            'maxlength' => self::MAX_POSTCODE,
            ];
        $fields[] =
            [
            'type'    => 'heading',
            'name'    => 'Other Information',
            ];
        $fields[] =
            [
            'type'    => 'text',
            'name'    => 'Accessibility',
            'id'      => 'access',
            'desc'    => 'Enter any accessibility limitations',
            'maxlength' => self::MAX_ACCESSIBILITY,
            ];
        $fields[] =
            [
            'type'    => 'text',
            'name'    => 'Phone number',
            'id'      => 'phone',
            'desc'    => '',
            'maxlength' => self::MAX_PHONE,
            ];
        $fields[] =
            [
            'type'    => 'url',
            'name'    => 'Venue\'s website URL',
            'id'      => 'url',
            'desc' => 'The URL should start with https://, or http:// for an unsecured website link.',
            'maxlength' => self::MAX_URL,
            ];
        return $fields;
    }

    /**
     * Registers the blocks u3a/venuedata and u3a/venuelist, and their render callbacks.
     *
     */
    public static function register_blocks()
    {
        wp_register_script(
            'u3avenueblocks',
            plugins_url('js/u3a-venue-blocks.js',
            self::$plugin_file), array('wp-blocks', 'wp-element'),
            U3A_SITEWORKS_CORE_VERSION,
            false,
        );
        wp_enqueue_script('u3avenueblocks');

        register_block_type('u3a/venuelist', array(
            'editor_script' => 'u3avenueblocks',
            'render_callback' => array(self::class, 'venue_list_cb')
        ));
        register_block_type('u3a/venuedata', array(
            'editor_script' => 'u3avenueblocks',
            'render_callback' => array(self::class, 'display_cb')
        ));
    }

    /**
     * This is the callback function for block u3a/grouplist.
     * Not yet implemented TBD
     *
     */
    public static function venue_list_cb()
    {
        return "<h2>Venue list here</h2><p>To be implemented</p>";
    }
    /**
     * Calls the display function for an object of this class
     * This code is common to all our custom post types, so don't edit it!
     */
    public static function display_cb($atts, $content='')
    {
        global $post;
        if ( U3A_VENUE_CPT != $post->post_type ) { // oops shouldn't be here
            return 'Error: only for use with items of type ' . U3A_VENUE_CPT;
        }
        $my_object = new self($post->ID); // an object of this class
        return $my_object->display($atts, $content);
    }

    /**
     * Returns the HTML for this object's custom data.
     *
     * @return string The HTML.
     */
    public function display($atts, $content)
    {
        $html = "<table class=\"u3a_venue_table\">\n";

        // District
        // Check Settings
        $field_v_district = get_option('field_v_district', '1');
        if ($field_v_district == 1) {
            $district = get_post_meta($this->ID, 'district', true);
            if (!empty($district)) {
                $html .= "<tr><td>District:</td><td>$district</td></tr>\n";
            }
        }

        // Address
        $ad1 = get_post_meta($this->ID, 'address1', true);
        $ad2 = get_post_meta($this->ID, 'address2', true);
        $town = get_post_meta($this->ID, 'town', true);
        $postcode = get_post_meta($this->ID, 'postcode', true);
        $address = '';
        if (!empty($ad1)) $address .= $ad1;
        if (!empty($ad2)) $address .= "<br>$ad2";
        if (!empty($town)) $address .= "<br>$town";
        if (!empty($postcode)) $address .= "<br>$postcode";
        if (!empty($address)) {
            $html .= "<tr><td>Address:</td><td>$address</td></tr>\n";
        }

        // Phone
        $phone = get_post_meta($this->ID, 'phone', true);
        if (!empty($phone)) {
            $html .= "<tr><td>Phone:</td><td>$phone</td></tr>\n";
        }

        // Website
        $website = get_post_meta($this->ID, 'url', true);
        if (!empty($website)) {
            $html .= "<tr><td>Website:</td><td><a target=\"_blank\" href=\"$website\">$website</a></div>\n";
        }
        // Access
        $access = get_post_meta($this->ID, 'access', true);
        if (!empty($access)) {
            $html .= "<tr><td>Accessibility:</td><td>$access</td></tr>\n";
        }

        $html .= "</table>";

        return $html;
    }

    /**
     * Gets the title and permalink of for this venue.
     *
     * @return HTML as <a> link
     */
    public function venue_name_with_link()
    {
        if ( $this->exists ) {
            $venue_name = get_post($this->ID)->post_title;
            $permalink = get_permalink($this->ID);
            return "<a href='$permalink'>$venue_name</a>";
        } else {
            return '';
        }
    }
}
