<?php

/**
 * Make some custom post data available via the REST API
 * Initially just for group status and category
 */

add_action('rest_api_init', 'u3a_setup_rest_meta_data');

/**
 * Make some group metadata readable via REST API
 * To make other data available add further register_rest_field calls
 *
 * @return void
 */
function u3a_setup_rest_meta_data()
{
    register_rest_field(
        array(U3A_GROUP_CPT),
        'groupstatus',
        array(
            'get_callback'    => 'rest_get_u3a_groupstatus',
            'schema'          => null,
        )
    );
    register_rest_field(
        array(U3A_GROUP_CPT),
        'groupcategory',
        array(
            'get_callback'    => 'rest_get_u3a_groupcategory',
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
function rest_get_u3a_groupstatus($object)
{
    $post_id = $object['id'];
    $status = get_post_meta($post_id, 'status_NUM', true);
    // error_log("Post ID $post_id " . $object['title']['rendered'] . " Status $status");
    return (int) $status;
}

/**
 * Make u3a group category available as 'groupcategory' (text)
 *
 * @param WP $object
 * @return (string) group category text (first category only if more than one)
 */
function rest_get_u3a_groupcategory($object)
{
    $post_id = $object['id'];
    $cats = get_the_terms($post_id, U3A_GROUP_TAXONOMY);
    $cat = !empty($cats) ? $cats[0]->name : '';
    // error_log("Post ID $post_id " . $object['title']['rendered'] . " Category $cat");
    return (string) $cat;
}
