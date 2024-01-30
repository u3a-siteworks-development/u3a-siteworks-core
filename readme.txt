=== u3a-custom-post-types ===
Requires at least: 5.9
Tested up to: 6.4
Stable tag: 5.9
Requires PHP: 7.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Provides facility to manage content for u3a groups, events  and related contacts and venues.

== Description ==

This plugin is part of the SiteWorks project.  It provides facilities to manage u3a groups and events and 
to store related contact and venue information.

For more information please refer to the [SiteWorks website](https://siteworks.u3a.org.uk/)

== Frequently Asked Questions ==

Please refer to the documentation on the [SiteWorks website](https://siteworks.u3a.org.uk/u3a-siteworks-training/)

== Changelog ==
= 1.0.6 =
* Feature 1004: Add support for Meta Field Block to display all group and event metadata
* Feature 491: Make group category available via REST API (Jan 2024)
= 1.0.5 =
* Bug 1000: On Production website, display of Groups is unpredictable (Jan 2024)
= 1.0.4 = 
* Feature 491: Make group status available via REST API to support development of an Oversights facility for SiteWorks sites (Jan 2024)
= 1.0.3 =
* Feature 1003: Change error message when custom post has no title (Jan 2024)
= 1.0.2 =
* Feature 952:  Display all Group Sort By buttons on a single line if possible (Dec 2023)
* Feature 1003: Omit prefix "When:" in group list display (Dec 2023)
* Feature 996: Add optional start and end times to groups (Dec 2023)
= 1.0.1 =
* Bug 983:  Remove '2nd' prefix when a second group leader is shown in 'u3a single group data' block (Nov 2023)
= 1.0.0 =
* First production code release
* Tested up to WordPress 6.4
= 0.7.98 =
* Release candidate 1
* Update plugin update checker library to v5p2
= 0.7.14 =
* Revisions arising from security review of code.
= 0.7.13 = 
* Bug 742 - The page displayed on failure to bin a post needs a way to return to the previous page.
= 0.7.12 =
* Bug 693 - The Event list will use the post EXCERPT if present rather than an extract from the text on the event page
* Bug 726 - Avoid including email addresses in the extract.
= 0.7.11 =
* Bug 723 - Permissions for custom post types changed so that an 'author' can create an Event, but must select a group and the
groups available will only be those for which they are the author.
* Bug 723 - Permissions for Contacts custom post type changed so that an 'author' can not view the list of Contacts to avoid
unnecessary exposure of email addresses.
= 0.7.10 =
* Bug 723 - Permissions for custom post types changed so that an 'author' can not create new groups, events, venues, contacts or notices
and can only edit those where an editor or administrator has made them the post author.
= 0.7.9 =
* Bug 611 - amend event listing to include events which started in the past but are still ongoing (duration in days set)
* Bug 407, etc - Layout changes to Groups list
* Events can now have include Cost and a checkbox to show if booking is required
* Bug 403, etc - Layout changes to Events list

= 0.7.8 =
* Bug 407 etc - amend group listing layout to include more data in summary, provide View by Venue option, minor changes to CSS style rules

= 0.7.7 =
* Bug 602 - groups with unspecified meeting day (day_NUM not defined) now included under the 'Unspecified' heading
 when listing groups ordered by meeting day

= 0.7.5 =
* Bug 575 - alter CSS to improve group list column display on iPad
* Bug 569 - Add event time and date display options to u3a Settings (Events tab)

= 0.7.4 =
* Venue website URL: corrected field type to ensure validation and included additional text to clarify what is required.

= 0.7.3 =
* Use multiple columns for group lists.  Will fit as many 350px columns as possible.

= 0.7.2 =
* Ensure Notices post type permalinks work

= 0.7.1 =
* Add initial support for the Notice post type and u3a/noticelist block

= 0.6.7 =
* Moved structured data to top of default content for groups, events and venues

= 0.6.6 =
* BUG 493 - Activating core plugin fails when WordPress table prefix is not wp_

= 0.6.5 =
* BUG 467 - A user with the 'Author' role can now only create a group event for groups where they are the group author.

= 0.6.4 =
* Changed name to u3a-siteworks-core
* Modified how a group's "when" info is displayed

= 0.6.3 =
* Alter the columns in the Contacts list admin page to include email addresses.
* Update style of buttons in group_list_filtered to us theme's style.
* Minor layout fix for single event.

= 0.6.2 =
* Prevent trashing of contacts and venues when they have xrefs from groups/events
* Include Author metabox for all post types
* various layout and textual improvements

= 0.6.1 =
* Changed contact post type name to u3a_contact
* Changed event category name to u3a_event_category
* Implemented Siteworks_storage_version and set to 1.

= 0.5.1 =
* Add support for plugin updates via the SiteWorks WP Update Server

= 0.4 series =
* Intial development code

