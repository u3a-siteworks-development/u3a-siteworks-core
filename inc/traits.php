<?php
trait AddMetabox
{
    /**
     * Filter that adds a metabox for a post_type.
     *
     * @param array $metaboxes List of existing metaboxes.
     * @uses str self::$post_type of the class in which it is used
     * @uses str self::$metabox_title of the class in which it is used
     * @uses  static::field_descriptions() gets the rwmb info for the fields in the metabox.
     *
     * @return array $metaboxes With the added metabox
     */
    public static function add_metabox($metaboxes)
    {
        $metabox = [
            'title'    => self::$metabox_title,
            'id'       => self::$post_type,
            'post_types' => [self::$post_type],
            'context'  => 'normal',
            'autosave' => true,
        ];
        $metabox['fields'] = self::field_descriptions();
        // add metabox to all input rwmb metaboxes
        $metaboxes[] = $metabox;
        return $metaboxes;
    }
}

trait ChangePrompt
{
    /**
     * Alter the "Add title" text when adding a custom post in the editor.
     *
     * @uses str self::$term_for_title of the class in which it is used
     * @usedby filter 'enter_title_here'
     */
    public static function change_prompt($title)
    {
        $screen = get_current_screen();
        if (self::$post_type == $screen->post_type) {
            $title = 'Enter ' . self::$term_for_title;
        }
        return $title;
    }
}
trait ManageCrossRefs
{
    /**
     * Adds display of all xrefs to this post in other posts.
     *
     * @param $content to be filtered
     *
     * @uses  self::$post_type, self::$post_type_name of the class in which it is used.
     * @return $content
     * @usedby filter 'the_content'
     */
    public static function display_xrefs($content)
    {
        global $post;
        if (self::$post_type == $post->post_type) {
            $result = self::find_xrefs($post->ID, true);
            $name = self::$post_type_name;
            if (!empty($result)) {
                if (!empty($result['groups'])) {
                    $content .= "<p> This $name is referenced in the following groups:<br> " . implode("<br>", $result['groups']) . " </p>";
                }
                if (!empty($result['events'])) {
                    $content .= "<p> This $name is referenced in the following events:<br> " . implode("<br>", $result['events']) . " </p>";
                }
            }
        }
        return $content;
    }

    /**
     * Prevents trashing when there there xrefs to this post in other posts.
     *
     * @param $post_id candidate for trashing
     *
     * @uses  self::$post_type, self::$post_type_name of the class in which it is used.
     * @return wp_die message if trashing not permitted
     * @usedby action 'wp_trash_post'
     */
    public static function restrict_post_deletion($post_id)
    {
        if (self::$post_type == get_post_type($post_id)) {
            $result = self::find_xrefs($post_id, false);
            if (empty($result)) {
                return;
            }
            if (empty($result["groups"]) && empty($result["events"])) {
                return;
            }
            $post_title = get_the_title($post_id);
            $name = self::$post_type_name;
            $message = "The $name '$post_title' cannot be binned, as it is referenced by these groups or events:";
            if (wp_is_json_request()) {
                if (!empty($result["groups"])) {
                    $message .= "'" . implode("','", $result['groups']) . "'";
                    if (!empty($result["events"])) {
                        $message .= ",";
                    }
                }
                if (!empty($result["events"])) {
                    $message .=  "'" . implode("','", $result['events']) . "'";
                }
            } else {
                if (!empty($result["groups"])) {
                    $message .= "<br>'" . implode("'<br>'", $result['groups']) . "'";
                }
                if (!empty($result["events"])) {
                    $message .= "<br>'" . implode("'<br>'", $result['events']) . "'";
                }
                $message .= "<br><br><a href=" . get_bloginfo('url') .
                "/wp-admin/edit.php?post_type=" . self::$post_type . ">Return to previous page</a>";
            }
            //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- only title and slug of various posts.
            wp_die($message);
        }
    }

    /**
     * Gets all xrefs to this post in other posts of type u3a_group or u3a_event.
     * Only relevent to a post in CPTs u3a_contact and u3a_venue)
     * If the user is not an administrator then past events will not be returned.
     *
     * @param int   $post_id id of this post 
     * @param bool   $date_filter whether to only return events yet to happen.
     * @uses string self::$xref_meta_key_list Keys that contain xrefs to this type of post
     *
     * @return array  list of titles with permalinks of groups/events that ref this contact,
     *                 or empty array if no xrefs.
     * @usedby functions display_xrefs() and restrict_post_deletion()
     */
    public static function find_xrefs($post_id, $date_filter)
    {
        $meta_key_list = self::$xref_meta_key_list;
        global $wpdb;
         // This query finds custom groups or events with a contact postmeta key whose value matches
        // the contact post_id.
        // Much simpler than using WP_Query to do this!
        $query = "SELECT ID, post_title, post_name, post_type FROM $wpdb->posts AS p JOIN $wpdb->postmeta AS m ON p.ID = m.post_ID";
        $query .= " WHERE p.post_status = 'publish'";
        $query .= " AND p.post_type IN ('u3a_group', 'u3a_event')";
        $query .= " AND m.meta_key IN ($meta_key_list)";
        $query .= " AND m.meta_value = %d ";
        // $xrefs = $wpdb->get_col($wpdb->prepare($query, $post_id));
        $groups = array();
        $events = array();
        $xrefs["groups"] = $groups;
        $xrefs["events"] = $events;
        $eventdates = array();
        $current_date = strtotime("00:00:00");
        $user_is_admin = in_array('administrator', wp_get_current_user()->roles);

        //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $meta_key_list is a constant string
        $results = $wpdb->get_results($wpdb->prepare($query, $post_id));
        if ($results) {
            foreach ($results as $result) {
                $permalink = get_permalink($result->ID);
                $linkHTML = "<a href=\"$permalink\">\"$result->post_title\" </a>";
                if ($result->post_type == 'u3a_group') {
                    $xrefs["groups"][] = $linkHTML;
                } else {
                    // get the date of the event
                    $eventdate = get_post_meta($result->ID, 'eventDate', true);
                    if (strlen($eventdate) > 0) {
                        // should always be gt 0, but being cautious.
                        $eventdate = strtotime($eventdate);
                        $formatted_date = date(get_option('date_format'), $eventdate);
                        // If the user is not an administrator then past events will not be returned.
                        if (!$date_filter || $user_is_admin || $eventdate >= $current_date){
                            $event = new stdClass();
                            $event->date = $eventdate;
                            $event->link = "$linkHTML  on $formatted_date ";
                            $eventdates[] = $event;
                        }
                    } else {
                        $event = new stdClass();
                        $event->date = $current_date;
                        $event->link = $linkHTML;
                        $eventdates[] = $event;
                    }
                }
            }
            // sort by event->date
            usort($eventdates, function($a, $b) {
                return ($a->date > $b->date);
            });
            foreach ($eventdates as $eventdate) {
                $xrefs["events"][] = $eventdate->link;
            }
        }
        return $xrefs;
    }
}
