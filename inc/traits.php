<?php

trait GenerateSelect
{
    /**
     * Helper function to generate <select> HTML code
     *
     * @param string $name Name attribute of select
     * @param array $contents Associative array in form select_value => select text
     * @param string $selected_value If empty display will default to "Please select ..."
     * @return string
     */
    public static function generate_select($name, $contents, $selected_value)
    {
        $html = "<select name=$name >";
        $html .= "<option value=''>Please select ...</option>";

        foreach ($contents as $value => $text) {
            $sel = ($value == $selected_value) ? 'selected' : '';
            $html .= "<option value=\"$value\" $sel>$text</option>";
        }
        $html .= '</select>';
        return $html;
    }
}

trait ModifyQuickEdit
{
    /**
     * Removes the Date, Password and Category input areas from the Quick Edit panel for the named $post_type
     * NB Function uses the heading text in spans to identify panels to remove.  If text changes this function will need corresponding changes.
     *
     * Uses the static property $post_type of the class in which it is used.
     * @ref https://wordpress.stackexchange.com/questions/59871/remove-specific-items-from-quick-edit-menu-of-a-custom-post-type
     *
     * @usedby action 'admin_head-edit.php'
     * @return void
     */
    public static function modify_quick_edit()
    {
        global $current_screen;
        if ('edit-' . self::$post_type != $current_screen->id) {
            return;
        }
        ?>
<script type="text/javascript">
    jQuery(document).ready(function($) {

        $('span:contains("Password")').each(function(i) {
            $(this).parent().parent().remove();
        });
        $('span:contains("Date")').each(function(i) {
            $(this).parent().remove();
        });
        $('span:contains("Category")').each(function(i) {
            $(this).parent().parent().remove();
        });
        $('span.title:contains("Group")').each(function(i) {
            $(this).parent().parent().remove();
        });
        $('.inline-edit-date').each(function(i) {
            $(this).remove();
        });
    });
</script>
        <?php
    }
}

trait ChangePrompt
{
    /**
     * Alter the "Add title" text when adding a custom post in the editor.
     *
     * @uses str self::$term_for_title
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
     * @return $content
     * @usedby filter 'the_content'
     */
    public static function display_xrefs($content)
    {
        global $post;
        if (self::$post_type == $post->post_type) {
            $result = self::find_xrefs($post->ID);
            $name = self::$post_type_name;
            if (!empty($result)) {
                if (!empty($result['groups'])) {
                    $content .= "<p>This $name is referenced in the following groups:<br>" . implode("<br>", $result['groups']) . "</p>";
                }
                if (!empty($result['events'])) {
                    $content .= "<p>This $name is referenced in the following events:<br>" . implode("<br>", $result['events']) . "</p>";
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
     * @return wp_die message if trashing not permitted
     * @usedby action 'wp_trash_post'
     */
    public static function restrict_post_deletion($post_id)
    {
        if (self::$post_type == get_post_type($post_id)) {
            $result = self::find_xrefs($post_id);
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
     * Gets all xrefs to this post in other posts.
     *
     * @param int   $post_id id of this u3a_contact post
     * @uses string self::$xref_meta_key_list Keys that contain xrefs to this type of post
     *
     * @return array  list of titles of groups/events that ref this contact,
     *                 or empty array if no xrefs.
     * @usedby action 'wp_trash_post'
     */
    public static function find_xrefs($post_id)
    {
        $meta_key_list = self::$xref_meta_key_list;
        global $wpdb;
        // This query finds custom groups or events with a contact postmeta key whose value matches
        // the contact post_id.
        // Much simpler than using WP_Query to do this!
        $query = "SELECT post_title, post_name, post_type FROM $wpdb->posts AS p JOIN $wpdb->postmeta AS m ON p.ID = m.post_ID";
        $query .= " WHERE p.post_status = 'publish'";
        $query .= " AND p.post_type IN ('u3a_group', 'u3a_event')";
        $query .= " AND m.meta_key IN ($meta_key_list)";
        $query .= " AND m.meta_value = %d ";
        // $xrefs = $wpdb->get_col($wpdb->prepare($query, $post_id));
        $groups = array();
        $events = array();
        $xrefs["groups"] = $groups;
        $xrefs["events"] = $events;
        //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $meta_key_list is a constant string
        $results = $wpdb->get_results($wpdb->prepare($query, $post_id));
        if ($results) {
            foreach ($results as $result) {
                if ($result->post_type == 'u3a_group') {
                    $xrefs["groups"][] = '<a href="' . get_site_url() . '/' . $result->post_type . 's/' . $result->post_name . '">' . $result->post_title . '</a>';
                } else {
                    $xrefs["events"][] = '<a href="' . get_site_url() . '/' . $result->post_type . 's/' . $result->post_name . '">' . $result->post_title . '</a>';
                }
            }
        }
        return $xrefs;
    }
}
