<?php
class U3aContact
{
    use ChangePrompt;
    use AddMetabox;
    use ManageCrossRefs;

    /**
     * The post_type for this class
     *
     * @var string 
     */
    public static $post_type = U3A_CONTACT_CPT;

    /**
     * The short name for this class
     *
     * @var string 
     */
    public static $post_type_name = 'contact';

    /**
     * The term used for the title of these custom posts
     *
     * @var string 
     */
    public static $term_for_title = "contact's display name";

    /**
     * The metabox title of these custom posts
     *
     * @var string 
     */
    public static $metabox_title = "Contact Information";

    /**
     * The meta keys that contain xrefs to this type of post
     *
     * @var string of keys within single quotes  
     */
    public static $xref_meta_key_list = "'coordinator_ID', 'coordinator2_ID', 'deputy_ID', 'tutor_ID', 'eventOrganiser_ID'";

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
        // Register Contact CPT
        add_action('init', array(self::class, 'register_contacts'));

        // Routine to run on plugin activation
        register_activation_hook($plugin_file, array(self::class, 'on_activation'));

        // Change prompt shown for post title
        add_filter('enter_title_here', array(self::class, 'change_prompt'));

        // Set up the custom fields in a metabox (using free plugin from on metabox.io)
        add_filter( 'rwmb_meta_boxes', [self::class, 'add_metabox'] , 10, 1 );

        // Alter the columns that are displayed in the Posts list admin page
        add_filter('manage_' . U3A_CONTACT_CPT . '_posts_columns', array(self::class, 'change_columns'));
        add_action('manage_' . U3A_CONTACT_CPT . '_posts_custom_column', array(self::class, 'show_column_data'), 10, 2);

        // Prevent trashing when there there xrefs to this post in other posts.
        add_action('wp_trash_post', array(self::class, 'restrict_post_deletion'));
        
        //Add display of all xrefs to this post in other posts.
        add_filter('the_content', array(self::class, 'display_xrefs'), 20, 1);
    }

    /**
     * Registers the custom post type for this class.
     */
    public static function register_contacts()
    {
        $args = array(
            'public' => true,
            'show_in_rest' => true,
            'supports' => array('title', 'editor', 'author', 'thumbnail'),
            'rewrite' => array('slug' => sanitize_title(U3A_CONTACT_CPT . 's')),
            'has_archive' => false,
            'menu_icon' => U3A_CONTACT_ICON,
            'labels' => array(
                'name' => 'u3a Contacts',
                'singular_name' => 'Contact',
                'add_new_item' => 'Add Contact',
                'add_new' => 'Add New Contact',
                'edit_item' => 'Edit Contact',
                'all_items' => 'All Contacts',
                'view_item' => 'View Contact',
                'update_item' => 'Update Contact',
                'search_items' => 'Search Contacts'
            )
        );

        // 'Authors' can not create new contacts nor see Contacts in menu
        if (!(current_user_can('edit_others_pages'))) {
            $args += array(
                'capabilities' => array(
                    'create_posts' => 'do_not_allow'
                ),
                'map_meta_cap' => true,
                'show_ui' => false,
                'show_in_menu' => false
            );
        }
        register_post_type(U3A_CONTACT_CPT, $args);
    }

    /**
     * Do tasks that should only be done on activation.
     *
     * Register post type and flush rewrite rules.
     */
    public static function on_activation()
    {
        self::register_contacts();
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
        $fields[] = [
            'type'    => 'custom_html',
            'std'    => 'A contact may be generic with a display name, e.g. like "French Leader"<br>' . 
                            'Each item of information is optional and should be omitted if the contact does not want to be contacted by that method.',
            ];
        $fields[] = [
            'type'    => 'text',
            'name'    => 'u3a Membership Number',
            'id'      => 'memberid',
            'size'    => '30px',
            ];
        $fields[] = [
            'type'    => 'text',
            'name'    => 'Given Name',
            'id'      => 'givenname',
            'size'    => '100px',
            ];
        $fields[] = [
            'type'    => 'text',
            'name'    => 'Family Name',
            'id'      => 'familyname',
            'size'    => '100px',
            ];
        $fields[] = [
            'type'    => 'text',
            'name'    => 'Phone number',
            'id'      => 'phone',
            'size'    => '50px',
            ];
        $fields[] = [
            'type'    => 'text',
            'name'    => 'Alternate phone number',
            'id'      => 'phone2',
            'size'    => '50px',
            ];
        $fields[] = [
            'type'    => 'email',
            'name'    => 'Email address',
            'id'      => 'email',
            'size'    => '100px',
];
        return $fields;
    }

    /**
     * Alter the columns that are displayed in the contacts posts list admin page.
     * @param array $columns
     * @return modified columns
     * @usedby filter 'manage_' . U3A_CONTACT_CPT . '_posts_columns'
     */
    public static function change_columns($columns)
    {
        unset($columns['date']);

        $columns['title'] = 'Contact Name'; // just changing the displayed name of this column.
        $columns['email'] = 'Email';
        return $columns;
    }

    /**
     * Alter what is shown fo one row in the columns that are displayed in the contacts posts list admin page.
     * @param str $column
     * @param int $post_id  the id of the post for the row 
     * @usedby action 'manage_' . U3A_CONTACT_CPT . '_posts_custom_column'
     */
    public static function show_column_data($column, $post_id)
    {
        switch ($column) {
            case 'email':
                $email = sanitize_email(get_post_meta($post_id, 'email', true));
                if (!empty($email)) {
                    print esc_html($email);
                } else {
                    print 'not set';
                }
        }
    }

    /**
     * Create a contact-form record and return a link to contact form if hide emails requested.
     *  Otherwise create a mailto link for the email.
     *  
     * So name is a bit of a misnomer, since depends on option setting.
     * 
     * @param string $address email address
     * @param string $to name of recipient
     * @return string HTML 
     */
    public static function cloak_email($address, $to)
    {
        if (empty($address)) return '';
        if ('Yes' == get_option('u3a_hide_email', 'Yes')) {
            // check if u3a-siteworks-contact-form is active
            if (!shortcode_exists('u3a_contact')) { 
                return "<strong>error: </strong>u3a contact form not available";
            }
            return do_shortcode('[u3a_contact name="' .  $to . '" email="' . $address . '"]');
        } else {
            return "<a title='Opens your email app' href='mailto:$address'>$to</a> $address";   
        }
    }

    // Below here are object methods.

    /**
     * Produce text info for this contact.
     *
     * If user can edit posts then adds a link to edit this contact's details.
     *
     * @return HTML
     */
    public function contact_text()
    {
        if (false == $this->exists) {
            return '';
        }
        $contact_name = esc_html(get_the_title($this->ID));
        $phone = esc_html(get_post_meta($this->ID, 'phone', true));
        $phone2 = esc_html(get_post_meta($this->ID, 'phone2', true));
        $phonetext = empty($phone2) ? "$phone" : "$phone or $phone2";
        $phonetext = empty($phonetext) ? '' : "<strong>Tel:</strong> $phonetext" ;
        $email = self::cloak_email(get_post_meta($this->ID, 'email', true), $contact_name);
        // display email link if it exists, else display name
        $contact = ($email) ? $email : $contact_name;
        // add link to edit this contact, but only if ...
        // user is logged-in with editing rights and ...
        // the u3a has not switched off the top toolbar for such users 
        // (which will be done by the u3a-configuration plugin when 'u3a_enable_toolbar' == 9)
        $enableToolbar = get_option('u3a_enable_toolbar', 1);
        $disabled_top_toolbar = (!current_user_can('manage_options') && !is_admin() && ($enableToolbar == 9));
        $edit_HTML = '';
        if (current_user_can('edit_others_posts') and !$disabled_top_toolbar) {
            $edit_link = admin_url("post.php?post=" . $this->ID . "&action=edit");
            $edit_hint = "Edit this contact details";
            $edit_HTML = "<a href= '$edit_link'><span style='flex; background-color:yellow;' class='dashicons dashicons-edit' title='$edit_hint'></span></a>";
        };

        // The HTML uses a flex container around spans to prevent overflow with long name + email+ phone
        $html = <<<END
        <div style="display:flex; flex-wrap:wrap;">
        <span style="flex; padding-right: 4px;">$contact</span>
        <span style="flex;  padding-right: 4px;">$phonetext</span>
        $edit_HTML
        </div>
        END;
        return $html;
    }
}
