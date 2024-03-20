<?php

class U3aAdmin
{
    /**
     * Set up the actions and filters used by this class.
     *
     * @param $plugin_file the value of __FILE__ from the main plugin file 
     */
    public static function initialise($plugin_file)
    {
        // Add the Settings menu to the dashboard
        add_action('admin_menu', array(self::class, 'settings_menu'));

        // Hook: function to process the Settings General tab
        add_action('admin_post_u3a_general_settings', array(self::class, 'save_general_settings'));
        add_action('admin_post_u3a_group_settings', array(self::class, 'save_group_settings'));
        add_action('admin_post_u3a_venue_settings', array(self::class, 'save_venue_settings'));
        add_action('admin_post_u3a_event_settings', array(self::class, 'save_event_settings'));

    }

    /**
     * Add menu for plugin settings.
     *
     */
    public static function settings_menu()
    {
        add_menu_page(
            'u3a Settings',
            'u3a Settings',
            'manage_options',
            'u3a-settings',
            array(self::class, 'render_settings_menu'),
            'dashicons-admin-generic',
            30
        );
    }

    /**
     * Print menu page for customisable settings used in this plugin.
     *
     * @note $status and $tab are the page query parameters
     */
    public static function render_settings_menu()
    {
        global $u3aMQDetect;

        // Check if there is a status returned from a save on one of the tabs

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : "";
        $status_text = '';
        if ($status != "") {
            switch ($status) {
                case 0:
                    $status_text = 'Change failed';
                    break;
                case 1:
                    $status_text = 'Change successful';
                    break;
                default:
                    $status_text = esc_HTML($status);
                    // add other case values as required
            }
        }

        // content common to all tabs

        $nonce_code =  wp_nonce_field('u3a_settings', 'u3a_nonce', true, false);
        $submit_button = get_submit_button('Save Settings');

        // Content for General tab

        $u3aname = get_option('blogname');
        $enableToolbar = get_option('u3a_enable_toolbar', 1);
        $enableToolbar_chk = ($enableToolbar == '1') ? ' checked' : '';

        // content for Groups tab
        $coordinator_term = esc_HTML(get_option('u3a_coord_term', 'coordinator'));
        $category_singular_term = esc_HTML(get_option('u3a_catsingular_term', 'category'));
        $category_plural_term = esc_HTML(get_option('u3a_catplural_term', 'categories'));

        $u3a_hide_email = get_option('u3a_hide_email', 'Yes');
        $emailY = ($u3a_hide_email == 'Yes') ? ' checked' : '';
        $emailN = ($u3a_hide_email == 'No') ? ' checked' : '';

        $u3a_grouplist_type = get_option('u3a_grouplist_type', 'sorted');
        $list_sorted = ($u3a_grouplist_type == 'sorted') ? ' checked' : '';
        $list_filtered = ($u3a_grouplist_type == 'filtered') ? ' checked' : '';

        $field_coord2 = get_option('field_coord2', '1');
        $field_coord2_chk = ($field_coord2 == '1') ? ' checked' : '';
        $field_deputy = get_option('field_deputy', '1');
        $field_deputy_chk = ($field_deputy == '1') ? ' checked' : '';
        $field_tutor = get_option('field_tutor', '1');
        $field_tutor_chk = ($field_tutor == '1') ? ' checked' : '';
        $field_groupemail = get_option('field_groupemail', '1');
        $field_groupemail_chk = ($field_groupemail == '1') ? ' checked' : '';
        $field_groupemail2 = get_option('field_groupemail2', '1');
        $field_groupemail2_chk = ($field_groupemail2   == '1') ? ' checked' : '';
        $field_cost = get_option('field_cost', '1');
        $field_cost_chk = ($field_cost == '1') ? ' checked' : '';

        $grouplist_threshold = get_option('grouplist_threshold', 20);


        // Content for Venues tab

        $field_v_district = get_option('field_v_district', '1');
        $field_v_district_chk = ($field_v_district == '1') ? ' checked' : '';


        // Content for Events tab

        $events_nogroups = get_option('events_nogroups', '1');
        $events_nogroups_chk = ($events_nogroups == '1') ? ' checked' : '';

        $events_timeformat = get_option('events_timeformat', 'system');  // options are 'system', '12hr', '24hr'
        $events_timeformat_system = '';
        $events_timeformat_24hr = '';
        $events_timeformat_12hr = '';
        switch ($events_timeformat) {
            case 'system': $events_timeformat_system = 'checked'; break;
            case '12hr': $events_timeformat_12hr = 'checked'; break;
            default: $events_timeformat_24hr = 'checked';
        }
        $system_time_example = date(get_option('time_format'), strtotime("14:30"));

        $events_dateformat = get_option('events_dateformat', 'system');  // options are 'system', 'short', 'long'
        $events_dateformat_system = '';
        $events_dateformat_short = '';
        $events_dateformat_long = '';
        switch ($events_dateformat) {
            case 'system': $events_dateformat_system = 'checked'; break;
            case 'short': $events_dateformat_short = 'checked'; break;
            default: $events_dateformat_long = 'checked';
        }
        $system_date_example = date(get_option('date_format'));
        $short_date_example = date('D M jS');
        $long_date_example = date('l jS F Y');


        // Assemble the tab navigation menu

        $tabs = array('General' => '', 'Groups' => 'groups', 'Venues' => 'venues', 'Events' => 'events');
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : ''; // current tab

        $tab_navbar = '<nav class="nav-tab-wrapper">';
        foreach ($tabs as $tabText => $tabName) {
            $tab_navbar .= '<a href="?page=u3a-settings';
            $tab_navbar .= empty($tabName) ? '' : "&tab=$tabName";
            $tab_navbar .= '" class="nav-tab ';
            if ($tab == $tabName) {
                $tab_navbar .= 'nav-tab-active';
            }
            $tab_navbar .= '">';
            $tab_navbar .= $tabText;
            $tab_navbar .= '</a>';
        }
        $tab_navbar .= "</nav>\n";


        // Ouput common top-of-page content


        //phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.Security.EscapeOutput.HeredocOutputNotEscaped
        print <<<END
    <div class="wrap">
    <div class="notice notice-error is-dismissible inline"><p>$status_text</p></div>
    <h2>u3a Settings Menu</h2>
    $tab_navbar
    END;
        //phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.Security.EscapeOutput.HeredocOutputNotEscaped

        // Output current tab content

        switch ($tab) {
            case 'groups':

                // The Groups settings tab page

                //phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.Security.EscapeOutput.HeredocOutputNotEscaped
                print <<<END
        <form method="POST" action="admin-post.php">
        <input type="hidden" name="action" value="u3a_group_settings">
        $nonce_code
        $u3aMQDetect

        <h3>Term for Group Coordinator</h3>
        <p>Your u3a may wish to use a different term for the person who manages a group, such as "leader"<br>
        You can change the term used here.</p>
        <input type="text" class="regular-text" id="cterm" name="cterm" maxlength="30" value="$coordinator_term">


        <h3>Term for Group Categories</h3>
        <p>Your u3a may wish to use a different term for the group categories, such as "faculties"<br>
        You can change the terms used here.</p>
        Singular: <input type="text" id="catsingleterm" name="catsingleterm" maxlength="20" value="$category_singular_term">
        Plural: <input type="text" id="catpluralterm" name="catpluralterm" maxlength="20" value="$category_plural_term">

        <h3>Fields to show when adding a new group</h3>
        <p>Tick the fields you want to use when defining your groups</p>
        <input type="checkbox" id="coord2" name="coord2" value="1" $field_coord2_chk>
        <label for="coord2"> Second $coordinator_term</label><br>
        <input type="checkbox" id="deputy" name="deputy"  value="1" $field_deputy_chk>
        <label for="deputy"> Deputy</label><br>
        <input type="checkbox" id="tutor" name="tutor" value="1" $field_tutor_chk>
        <label for="tutor"> Tutor</label><br>
        <input type="checkbox" id="groupemail" name="groupemail"  value="1" $field_groupemail_chk>
        <label for="groupemail"> Group email</label><br>
        <input type="checkbox" id="groupemail2" name="groupemail2"  value="1" $field_groupemail2_chk>
        <label for="groupemail2"> Second group email</label><br>
        <input type="checkbox" id="cost" name="cost"  value="1" $field_cost_chk>
        <label for="cost"> Cost</label><br>

        <h3>Group list display</h3>
        <p> Group lists can be shown either with sorting or filtering options. Choose which will be used.</p>
        <label for="sorted"><input type="radio" id="sorted" name="u3a_grouplist_type" value="sorted" $list_sorted>sorted</label><br>
        <label for="filtered"><input type="radio" id="filtered" name="u3a_grouplist_type" value="filtered" $list_filtered>filtered</label>

        <p>For the filtered option, with a limited number of groups, the list will be shown in alphabetical order.  With more groups, the system will display options to filter the list before showing the selected groups.<br>
        <label for="grouplistthreshold">Maximum size of group list before automatically filtering:</label> <input type="number" class="small-text" id="grouplistthreshold" name="grouplistthreshold" value="$grouplist_threshold" min="0" max="999"></p>

        <h3>Hide email addresses</h3>
        <p>Do you want the system to hide email addresses of group {$coordinator_term}s<br>
        When you choose 'yes' the system will provide a link to a contact form for that person instead of a regular email link.</p>
        <label for="y"><input type="radio" id="y" name="hideemail" value="Yes" $emailY>Yes</label><br>
        <label for="n"><input type="radio" id="n" name="hideemail" value="No" $emailN>No</label>

        $submit_button
        </form>
    </div>
    END;
                //phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.Security.EscapeOutput.HeredocOutputNotEscaped
                break;

            case 'venues':

                // Venues tab page

                //phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.Security.EscapeOutput.HeredocOutputNotEscaped
                print <<<END
        <form method="POST" action="admin-post.php">
        <input type="hidden" name="action" value="u3a_venue_settings">
        $nonce_code
        $u3aMQDetect

        <h3>Fields to show when adding a new venue</h3>
        <p>Tick the fields you want to use when defining your venues</p>

        <p>
        <input type="checkbox" id="vdistrict" name="vdistrict"  value="1" $field_v_district_chk>
        <label for="vdistrict"> District</label>
        </p>

        $submit_button
        </form>
    </div>
END;
                //phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.Security.EscapeOutput.HeredocOutputNotEscaped
                break;

            case 'events':

                // Events tab page

                    //phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.Security.EscapeOutput.HeredocOutputNotEscaped
                    print <<<END
        <form method="POST" action="admin-post.php">
        <input type="hidden" name="action" value="u3a_event_settings">
        $nonce_code
        $u3aMQDetect

        <h3>Event list options</h3>
        <p>Do you want to exclude group events from the list on the Events page?</p>
        <p>
        <input type="checkbox" id="events_nogroups" name="events_nogroups"  value="1" $events_nogroups_chk>
        <label for="events_nogroups"> Exclude group events</label>
        </p>

        <h3>Time format</h3>
        <p>
        <input type="radio" id="timeformat-system" name="timeformat" value="system" $events_timeformat_system>
        <label for="timeformat-system">WordPress Time Format: $system_time_example</label><br>
        <input type="radio" id="timeformat-12hr" name="timeformat" value="12hr" $events_timeformat_12hr>
        <label for="timeformat-system">12 hour format: 2:30pm</label><br>
        <input type="radio" id="timeformat-24hr" name="timeformat" value="24hr" $events_timeformat_24hr>
        <label for="timeformat-system">24 hour format: 14:30</label>
        </p>

        <h3>Date format</h3>
        <p>
        <input type="radio" id="dateformat-system" name="dateformat" value="system" $events_dateformat_system>
        <label for="dateformat-system">WordPress Date Format: $system_date_example</label><br>
        <input type="radio" id="dateformat-short" name="dateformat" value="short" $events_dateformat_short>
        <label for="dateformat-short">Short date format: $short_date_example</label><br>
        <input type="radio" id="dateformat-long" name="dateformat" value="long" $events_dateformat_long>
        <label for="dateformat-long">Long date format: $long_date_example</label>
        </p>

        $submit_button
        </form>
    </div>
END;
                //phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.Security.EscapeOutput.HeredocOutputNotEscaped
                break;

            default:

                // General tab page

                //phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.Security.EscapeOutput.HeredocOutputNotEscaped
                print <<<END
        <form method="POST" action="admin-post.php">
        <input type="hidden" name="action" value="u3a_general_settings">
        $nonce_code
        $u3aMQDetect

        <h3>Your u3a name</h3>
        <p>This will be used in page headings and various other places.<br/>
        Do not include 'u3a' in the name.</p>
        <p>Note: This setting is the same as the Site Title on the WordPress Setting page.</p>

        <input type="text" class="regular-text" id="u3aname" name="u3aname" value="$u3aname">

        <h3>WordPress Admin Tool Bar</h3>
        <p>The admin tool bar is shown at the top of the page when viewing the public website pages.<br>
        It provides quick access to site editing features.<br>
        If this setting is not checked the tool bar will only be shown to administrators.</p>
        <p><input type="checkbox" id="enableToolbar" name="enableToolbar" value="1" $enableToolbar_chk>
        <label for="enableToolbar">Enable Tool Bar for all users.</label></p>

        $submit_button
        </form>
    </div>
END;
                //phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.Security.EscapeOutput.HeredocOutputNotEscaped
        }
    }

    /**
     * Save General settings as WordPress options.
     * Then redirect to u3a setting page.
     */
    public static function save_general_settings()
    {
        // check nonce
        if (check_admin_referer('u3a_settings', 'u3a_nonce') == false) wp_die('Invalid form submission');
        // check for WP magic quotes
        $u3aMQDetect = $_POST['u3aMQDetect'];
        $needStripSlashes = (strlen($u3aMQDetect) > 5) ? true : false; // backslash added to apostrophe in test string?

        $u3aname = $needStripSlashes ? stripslashes($_POST['u3aname']) : $_POST['u3aname'];
        $u3aname = sanitize_text_field($u3aname);
        if (!empty($u3aname)) {
            update_option('blogname', $u3aname);
        }

        $enableToolbar = isset($_POST['enableToolbar']) ? '1' : '9';
        update_option('u3a_enable_toolbar', $enableToolbar);

        // redirect back to u3a settings page (general tab) with status set to success (1)
        wp_safe_redirect(admin_url('admin.php?page=u3a-settings&status=1'));
        exit();
    }

    /**
     * Save group-related settings as WordPress options.
     * Then redirect to u3a setting page.
     */
    public static function save_group_settings()
    {
        // check nonce
        if (check_admin_referer('u3a_settings', 'u3a_nonce') == false) wp_die('Invalid form submission');
        // check for WP magic quotes
        $u3aMQDetect = $_POST['u3aMQDetect'];
        $needStripSlashes = (strlen($u3aMQDetect) > 5) ? true : false; // backslash added to apostrophe in test string?

        $cterm = $needStripSlashes ? stripslashes($_POST['cterm']) : $_POST['cterm'];
        $coordinator_term = trim(strtolower($cterm));
        $coordinator_term = ($coordinator_term == '') ? 'coordinator' : $coordinator_term;

        $catsingleterm = $needStripSlashes ? stripslashes($_POST['catsingleterm']) : $_POST['catsingleterm'];
        $category_singular_term = trim(strtolower($catsingleterm));
        $category_singular_term = ($category_singular_term == '') ? 'category' : $category_singular_term;
        $catpluralterm = $needStripSlashes ? stripslashes($_POST['catpluralterm']) : $_POST['catpluralterm'];
        $category_plural_term = trim(strtolower($catpluralterm));
        $category_plural_term = ($category_plural_term == '') ? 'categories' : $category_plural_term;

        $field_coord2 = isset($_POST['coord2']) ? '1' : '9';
        $field_deputy = isset($_POST['deputy']) ? '1' : '9';
        $field_tutor = isset($_POST['tutor']) ? '1' : '9';
        $field_groupemail = isset($_POST['groupemail']) ? '1' : '9';
        $field_groupemail2 = isset($_POST['groupemail2']) ? '1' : '9';
        $field_cost = isset($_POST['cost']) ? '1' : '9';

        if (get_option('field_coord2')) {
            update_option('field_coord2', $field_coord2);
        } else {
            add_option('field_coord2', $field_coord2);
        }
        if (get_option('field_deputy')) {
            update_option('field_deputy', $field_deputy);
        } else {
            add_option('field_deputy', $field_deputy);
        }
        if (get_option('field_tutor')) {
            update_option('field_tutor', $field_tutor);
        } else {
            add_option('field_tutor', $field_tutor);
        }
        if (get_option('field_groupemail')) {
            update_option('field_groupemail', $field_groupemail);
        } else {
            add_option('field_groupemail', $field_groupemail);
        }
        if (get_option('field_groupemail2')) {
            update_option('field_groupemail2', $field_groupemail2);
        } else {
            add_option('field_groupemail2', $field_groupemail2);
        }
        if (get_option('field_cost')) {
            update_option('field_cost', $field_cost);
        } else {
            add_option('field_cost', $field_cost);
        }

        $u3a_grouplist_type = $_POST['u3a_grouplist_type'];
        if (get_option('u3a_grouplist_type')) {
            update_option('u3a_grouplist_type', $u3a_grouplist_type);
        } else {
            add_option('u3a_grouplist_type', $u3a_grouplist_type);
        }

        $grouplist_threshold = $_POST['grouplistthreshold'];
        if (get_option('grouplist_threshold') !== false) {
            update_option('grouplist_threshold', $grouplist_threshold);
        } else {
            add_option('grouplist_threshold', $grouplist_threshold);
        }

        if (get_option('u3a_coord_term')) {
            update_option('u3a_coord_term', $coordinator_term);
        } else {
            add_option('u3a_coord_term', $coordinator_term);
        }

        if (get_option('u3a_catsingular_term')) {
            update_option('u3a_catsingular_term', $category_singular_term);
        } else {
            add_option('u3a_catsingular_term', $category_singular_term);
        }

        if (get_option('u3a_catplural_term')) {
            update_option('u3a_catplural_term', $category_plural_term);
        } else {
            add_option('u3a_catplural_term', $category_plural_term);
        }

        $hide_email = $_POST['hideemail'];
        if (get_option('u3a_hide_email')) {
            update_option('u3a_hide_email', $hide_email);
        } else {
            add_option('u3a_hide_email', $hide_email);
        }

        // redirect back to u3a settings page (groups tab) with status set to success (1)
        wp_safe_redirect(admin_url('admin.php?page=u3a-settings&tab=groups&status=1'));
        exit();
    }

    /**
     * Save venue-related settings as WordPress options.
     * Then redirect to u3a setting page.
     */

    public static function save_venue_settings()
    {
        // check nonce
        if (check_admin_referer('u3a_settings', 'u3a_nonce') == false) wp_die('Invalid form submission');
        // check for WP magic quotes
        $u3aMQDetect = $_POST['u3aMQDetect'];
        $needStripSlashes = (strlen($u3aMQDetect) > 5) ? true : false; // backslash added to apostrophe in test string?

        $field_v_district = isset($_POST['vdistrict']) ? '1' : '9';
        update_option('field_v_district', $field_v_district);

        // redirect back to u3a settings page (venues tab) with status set to success (1)
        wp_safe_redirect(admin_url('admin.php?page=u3a-settings&tab=venues&status=1'));
        exit();
    }

    /**
     * Save event-related settings as WordPress options.
     * Then redirect to u3a setting page.
     */
    public static function save_event_settings()
    {
        // check nonce
        if (check_admin_referer('u3a_settings', 'u3a_nonce') == false) wp_die('Invalid form submission');
        // check for WP magic quotes
        $u3aMQDetect = $_POST['u3aMQDetect'];
        $needStripSlashes = (strlen($u3aMQDetect) > 5) ? true : false; // backslash added to apostrophe in test string?

        $events_nogroups = isset($_POST['events_nogroups']) ? '1' : '9';
        update_option('events_nogroups', $events_nogroups);

        $events_timeformat = isset($_POST['timeformat']) ? $_POST['timeformat'] : 'system';
        update_option('events_timeformat', $events_timeformat);

        $events_dateformat = isset($_POST['dateformat']) ? $_POST['dateformat'] : 'system';
        update_option('events_dateformat', $events_dateformat);

        // redirect back to u3a settings page (events tab) with status set to success (1)
        wp_safe_redirect(admin_url('admin.php?page=u3a-settings&tab=events&status=1'));
        exit();
    }
}
