<?php

/**
 * Make some custom post metadata available via the REST API
 * Initially just for group status
 */

add_action('rest_api_init', 'u3a_setup_rest_meta_data');

/**
 * Make some group metadata readable via REST API
 * To make other metadata fields available add further register_rest_field calls.
 *
 * @return void
 */
function u3a_setup_rest_meta_data()
{
    register_rest_field(
        array(U3A_GROUP_CPT),
        'groupstatus',
        array(
            'get_callback'    => 'get_post_meta_groupstatus',
            'schema'          => null,
        )
    );
}

/**
 * Make u3a group metadata field status_NUM available as 'groupstatus'
 *
 * @param WP $object
 * @return (int) group status code
 */
function get_post_meta_groupstatus($object)
{
    $post_id = $object['id'];
    $status = get_post_meta($post_id, 'status_NUM', true);
    // error_log("Post ID $post_id " . $object['title']['rendered'] . " Status $status");
    return (int) $status;
}
