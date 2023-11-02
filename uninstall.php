<?php

// exit if uninstall constant is not defined
if (!defined('WP_UNINSTALL_PLUGIN')) exit;
require 'inc/definitions.php';

// Delete u3a CPT posts and metadata
u3a_delete_CPT_posts( U3A_GROUP_CPT );
u3a_delete_CPT_posts( U3A_EVENT_CPT );
u3a_delete_CPT_posts( U3A_VENUE_CPT );
u3a_delete_CPT_posts( U3A_CONTACT_CPT );

// Delete u3a taxonomy terms
u3a_delete_taxonomy_and_its_terms(U3A_GROUP_TAXONOMY);
u3a_delete_taxonomy_and_its_terms(U3A_EVENT_TAXONOMY);

// Remove the u3a settings fields
$u3a_settings = array(
    'u3a_enable_toolbar',
    'u3a_coord_term', 'u3a_catsingular_term', 'u3a_catplural_term', 'u3a_hide_email',
    'field_coord2', 'field_deputy', 'field_tutor', 'field_groupemail', 'field_groupemail2', 'field_cost',
    'grouplist_threshold', 'u3a_grouplist_type',
    'field_v_district', 'events_nogroups',
);
foreach ($u3a_settings as $setting) {
    delete_option( $setting );
}

// function definitions

/**
 * Deletes CPT posts.
 * Also removes post metadata associated with CPT)
 * @param $CPT_post_type  the post type whose post will be deleted
 */
function u3a_delete_CPT_posts($CPT_post_type)
{
    $all_posts = array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash');
    $my_posts = get_posts(array('post_type' => $CPT_post_type, 'posts_per_page' => -1, 'post_status' => $all_posts));
    foreach ($my_posts as $post) {
        wp_delete_post($post->ID, true);
    }
}

/*
 *  ** METHOD 1 ** This does not work!
 *  Note:  As plugin is no longer active, it's not possible to use WP get_terms() to get terms to delete as the taxonomy is not registered
 *  // Ref https://wordpress.stackexchange.com/questions/119229/how-to-delete-custom-taxonomy-terms-in-plugins-uninstall-php
 *
 *  ** Method 2  **  This does not work either!
 *  Ref: https://wordpress.org/support/topic/delete-terms-from-database/
 *
 *  ** Method 3 ** This appears to work
 *  Ref https://wordpress.org/support/topic/how-to-delete-plugin-data-completely/
 *
 */
function u3a_delete_taxonomy_and_its_terms( $taxonomy )
{
    global $wpdb;
    // Remove term meta
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM $wpdb->termmeta WHERE term_id IN ( SELECT term_id FROM $wpdb->term_taxonomy WHERE taxonomy = %s)",
            $taxonomy
        )
    );
    // Remove term relations
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM $wpdb->term_relationships WHERE term_taxonomy_id IN ( SELECT term_taxonomy_id FROM $wpdb->term_taxonomy WHERE taxonomy = %s)",
            $taxonomy
        )
    );
    // Remove terms
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM $wpdb->terms WHERE term_id IN (SELECT term_id FROM $wpdb->term_taxonomy WHERE taxonomy = %s)",
            $taxonomy
        )
    );
    // Remove taxonomy
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM $wpdb->term_taxonomy WHERE taxonomy = %s",
            $taxonomy
        )
    );
}
