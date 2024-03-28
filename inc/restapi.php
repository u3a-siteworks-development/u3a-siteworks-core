<?php

/**
 * Make some custom post data for Groups and Events available via the REST API
 * Specifically for use with Oversights but may have other applications
 * Provides fields:
 *  - group status as 'groupstatus' (int)
 *  - group category as 'groupcategory' (string array)
 *  - event category as 'eventcategory' (string)
 *  - event date as 'eventdate' (string YYYY-MM-DD)
 *  - event duration as 'eventduration' (int default 1)
 *  - event groupname as 'eventgroup' (string default null for not a group event)
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
    register_rest_field(
        array(U3A_EVENT_CPT),
        'eventcategory',
        array(
            'get_callback'    => 'rest_get_u3a_eventcategory',
            'schema'          => null,
        )
    );
    register_rest_field(
        array(U3A_EVENT_CPT),
        'eventdate',
        array(
            'get_callback'    => 'rest_get_u3a_eventdate',
            'schema'          => null,
        )
    );
    register_rest_field(
        array(U3A_EVENT_CPT),
        'eventduration',
        array(
            'get_callback'    => 'rest_get_u3a_eventduration',
            'schema'          => null,
        )
    );
    register_rest_field(
        array(U3A_EVENT_CPT),
        'eventgroup',
        array(
            'get_callback'    => 'rest_get_u3a_eventgroup',
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
    return (int) $status;
}

/**
 * Make u3a group category available as 'groupcategory' (string array)
 *
 * @param WP $object
 * 
 * @return (string array) group category text 
 */
function rest_get_u3a_groupcategory($object)
{
    $post_id = $object['id'];
    $cats = get_the_terms($post_id, U3A_GROUP_TAXONOMY);
    if ($cats === false || is_wp_error($cats)) {
        return array();
    }
    $cat_names = array();
    foreach ($cats as $cat) {
        $cat_names[] = $cat->name;
    }
    return $cat_names;
}

/**
 * Make u3a event category available as 'eventcategory' (string)
 *
 * @param WP $object
 * @return (string) event category text (first category only if more than one)
 */
function rest_get_u3a_eventcategory($object)
{
    $post_id = $object['id'];
    $cats = get_the_terms($post_id, U3A_EVENT_TAXONOMY);
    $cat = !empty($cats) ? $cats[0]->name : '';
    return (string) $cat;
}

/**
 * Make u3a event date available as 'eventdate' (string YYYY-MM-DD)
 *
 * @param WP $object
 * @return (string) event date
 */
function rest_get_u3a_eventdate($object)
{
    $post_id = $object['id'];
    $date = get_post_meta($post_id, 'eventDate', true);
    return (string) $date;
}

/**
 * Make u3a event duration in days available as 'eventduration' (int, default 1)
 *
 * @param WP $object
 * @return (int) event duration in days
 */
function rest_get_u3a_eventduration($object)
{
    $post_id = $object['id'];
    $days = get_post_meta($post_id, 'eventDays', true);
    if (empty($days)) {
        $days = 1;
    }
    return (int) $days;
}

/**
 * Make group associated with u3a event available as 'eventgroup' (string, default null)
 *
 * @param WP $object
 * @return (string) name of group associated with event or null string
 */
function rest_get_u3a_eventgroup($object)
{
    $post_id = $object['id'];
    $groupID = get_post_meta($post_id, 'eventGroup_ID', true);
    $groupName = empty($groupID) ? '' : get_the_title($groupID);
    return (string) $groupName;
}