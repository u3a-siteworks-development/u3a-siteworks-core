<?php
/**
 * Updates the stored data to the latest version.
 * @note $latest_version = $siteworks_storage_version
 *     This is the version to which the data will be updated.
 * @usedby 'init'
 * @return boolean true on success, not used since action ignores return value.
 */
function u3a_core_check_storage_updates()
{
    global $siteworks_storage_version;
    $latest_version = $siteworks_storage_version;
    $stored_version = get_option('SiteWorks_storage_version', 0);
    if (0 == $stored_version &&  0 < $latest_version) {
        $status = u3a_core_update_storage_0_to_1();
        if ('ok' == $status) {
            update_option('SiteWorks_storage_version', 1);
            $stored_version = 1;
        } else {
            u3a_core_updates_failure($status);
            return false;
        }
    }
    if (1 == $stored_version &&  1 < $latest_version) {
        $status = u3a_core_update_storage_1_to_2();
        if ('ok' == $status) {
            update_option('SiteWorks_storage_version', 2);
            $stored_version = 2;
        } else {
            u3a_core_updates_failure($status);
            return false;
        }
    }
    if (2 == $stored_version &&  2 < $latest_version) {
        $status = u3a_core_update_storage_2_to_3();
        if ('ok' == $status) {
            update_option('SiteWorks_storage_version', 3);
            $stored_version = 3;
        } else {
            u3a_core_updates_failure($status);
            return false;
        }
    }
    if (3 == $stored_version &&  3 < $latest_version) {
        $status = u3a_core_update_storage_3_to_4();
        if ('ok' == $status) {
            update_option('SiteWorks_storage_version', 4);
            $stored_version = 4;
        } else {
            u3a_core_updates_failure($status);
            return false;
        }
    }
    if (4 < $latest_version) {
        // we only handle versions up to 4 at present!!
        u3a_core_updates_failure('No update available for storage version ' . $latest_version);
        return false;
    }
    return true;
}

/**
 * Updates the stored data from version 0 to version 1.
 * @return str 'ok' or failure reason
 */
function u3a_core_update_storage_0_to_1()
{
    global $wpdb;
    // arguments to $wpdb->update are ($table, $data, $where)
    // contact CPT name changed
    $updated = $wpdb->update(
        $wpdb->posts,
        ['post_type' => 'u3a_contact'],
        ['post_type' => 'u3a_person'],
    );
    if (false === $updated) {
        return 'Failed to update contact CPT name.';
    }
     // event taxonomy name changed 
    $updated = $wpdb->update(
        $wpdb->term_taxonomy,
        ['taxonomy' => 'u3a_event_category'],
        ['taxonomy' => 'event_category'],
    );
    if (false === $updated) {
        return 'Failed to update event taxonomy name.';
    }
    delete_option('rewrite_rules'); // as we've changed the permalink structure for u3a_contacts
    return 'ok';
}
/**
 * Updates the stored data from version 1 to version 2.
 * @return str 'ok' or failure reason
 */
function u3a_core_update_storage_1_to_2()
{
    // Get correct permalinks for Notices
    U3aNotice::on_activation();
    return 'ok';
}
/**
 * Updates the stored data from version 2 to version 3.
 * @return str 'ok' - no failure case is passed back.
 */
function u3a_core_update_storage_2_to_3()
{
    U3aEvent::update_allEventsEndDate();
    return 'ok';
}
/**
 * Updates the stored data from version 3 to version 4.
 * Changes the attributes of u3a eventlist and grouplist in post_content
 * fom cat to event_cat and group_cat and fro status to group_status.
 * @return str 'ok' - no failure case is passed back.
 */
function u3a_core_update_storage_3_to_4()
{
    global $wpdb;

    // get the content of all posts regardless of post_type and post_status
    $results = $wpdb->get_results("SELECT ID, post_content, post_title FROM $wpdb->posts ");

    $num_changed_rows = 0;
    foreach($results as $row) {
        $id = $row->ID;
        $content0 = $row->post_content;
        $title = $row->post_title;
        $changes = 0;

        // find all occurences of attribute "cat": within a u3a/eventlist block
        // and replace with "event_cat":
        $pattern = '#(<!-- wp:u3a/eventlist.*?)("cat":)(.*?/-->)#';
        $replacement = '$1"event_cat":$3';
        $content1 = preg_replace($pattern, $replacement, $content0, -1, $count);
        if ($count === false) { 
            return "post id: $id - Eventlist - failed to change \"cat\".";
        }
        $changes += $count;

        // find all occurences of attribute "cat": within a u3a/grouplist block
        // and replace with "group_cat":
        $pattern = '#(<!-- wp:u3a/grouplist.*?)("cat":)(.*?/-->)#';
        $replacement = '$1"group_cat":$3';
        $content2 = preg_replace($pattern, $replacement, $content1, -1, $count);
        if ($count === false) { 
            return "post id: $id - Grouplist - failed to change \"cat\".";
        }
        $changes += 10*$count;

        // find all occurences of attribute "status": within a u3a/grouplist block
        // and replace with "group_status":
        $pattern = '#(<!-- wp:u3a/grouplist.*?)("status":)(.*?/-->)#';
        $replacement = '$1"group_status":$3';
        $content3 = preg_replace($pattern, $replacement, $content2, -1, $count);
        if ($count === false) { 
            return "post id: $id - Grouplist - failed to change \"status\".";
        }
        $changes += 100*$count;

        // now similar changes for shortcodes
        
        // find all occurences of attribute cat within a u3aeventlist shortcode
        // and replace with event_cat
        // need to escape single quote in $pattern
        $pattern = '#(\[u3aeventlist.*?\h+?)(cat)(\h*?=\h*?["\'].*?])#';
        $replacement = '$1event_cat$3';
        $content4 = preg_replace($pattern, $replacement, $content3, -1, $count);
        if ($count === false) { 
            return "post id: $id - Eventlist shortcode - failed to change \"cat\".";
        }
        $changes += $count;

        // find all occurences of attribute cat within a u3agrouplist shortcode
        // and replace with group_cat
        // need to escape single quote in $pattern
        $pattern = '#(\[u3agrouplist.*?\h+?)(cat)(\h*?=\h*?["\'].*?])#';
        $replacement = '$1group_cat$3';
        $content5 = preg_replace($pattern, $replacement, $content4, -1, $count);
        if ($count === false) { 
            return "post id: $id - Grouplist shortcode - failed to change \"cat\".";
        }
        $changes += 10*$count;

        // find all occurences of attribute status within a u3agrouplist shortcode
        // and replace with group_status
        // need to escape single quote in $pattern
        $pattern = '#(\[u3agrouplist.*?\h+?)(status)(\h*?=\h*?["\'].*?])#';
        $replacement = '$1group_status$3';
        $content6 = preg_replace($pattern, $replacement, $content5, -1, $count);
        if ($count === false) { 
            return "post id: $id - Grouplist shortcode - failed to change \"status\".";
        }
        $changes += 100*$count;
        
        if ($changes > 0) {
            $num_changed_rows += 1;
            $status = $wpdb->update($wpdb->posts,
                      array('post_content' => $content6),
                      array('ID' => $id),
                     );
            if (false === $status) {
                return "post id: $id - failed to update post_content";
            }

        }
    }
    return 'ok';
}

/**
 * Displays an admin notice about an error.
 * @param str $reason
 */
function u3a_core_updates_failure($reason)
{
    global $u3a_core_updates_failure_reason;
    $u3a_core_updates_failure_reason = $reason;
    add_action( 'admin_notices', function () {
        global $u3a_core_updates_failure_reason;
        print '<div class="notice notice-error"><p><strong>' . esc_HTML($u3a_core_updates_failure_reason) . '<br> Seek expert help.</strong></p></div>';
    });
}
  