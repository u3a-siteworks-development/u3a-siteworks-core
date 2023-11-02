<?php
if (!defined('ABSPATH')) exit;

// Definitions, for renaming if required

define( 'U3A_GROUP_CPT', 'u3a_group');
define( 'U3A_GROUP_TAXONOMY', 'u3a_group_category');
define( 'U3A_GROUP_ICON' , 'dashicons-groups');

define( 'U3A_EVENT_CPT', 'u3a_event');
define( 'U3A_EVENT_ICON' , 'dashicons-schedule');
define( 'U3A_EVENT_TAXONOMY', 'u3a_event_category');


define( 'U3A_VENUE_CPT', 'u3a_venue');
define( 'U3A_VENUE_ICON' , 'dashicons-building');

define( 'U3A_CONTACT_CPT', 'u3a_contact');
define( 'U3A_CONTACT_ICON' , 'dashicons-businessperson');

define( 'U3A_NOTICE_CPT', 'u3a_notice');
define( 'U3A_NOTICE_ICON' , 'dashicons-testimonial');

// Define hidden field to detect WordPress magic_quotes processing in forms sent via admin-post.php
// Ref: https://core.trac.wordpress.org/ticket/18322

$u3aMQDetect = "<input type=\"hidden\" name=\"u3aMQDetect\" value=\"test'\">\n";
