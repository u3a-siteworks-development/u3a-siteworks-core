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
    if (3 < $latest_version) {
        // we only handle versions up to 3 at present!!
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
  