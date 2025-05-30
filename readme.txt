=== u3a-siteworks-core ===
Requires at least: 5.9
Tested up to: 6.8
Stable tag: 5.9
Requires PHP: 7.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Provides facility to manage content for u3a groups, events  and related contacts and venues.

== Description ==

This plugin is part of the SiteWorks project.  It provides facilities to manage u3a groups and events and 
to store related contact and venue information.

For more information please refer to the [SiteWorks website](https://siteworks.u3a.org.uk/)
For guidance on the design of the code read file 'u3a Siteworks Core structure.odt'

== Frequently Asked Questions ==

Please refer to the documentation on the [SiteWorks website](https://siteworks.u3a.org.uk/u3a-siteworks-training/)

== Changelog ==
= 1.2.1 =
* Feature 1136: Allow authors to add non group events
* Bug 1142: Notice block Title disappears when there are no notices
* Bug 1137: Group category with & will not display in Filtered Group List
= 1.2.0 =
* Tested with WordPress 6.8
* Feature 1130: Add option to hide list of events & groups on venue pages
* Feature 1125: Events now sorted by time as well as date
* Bug 1121: Duplicate entries in the reference lists for venues/contacts
* Bug 1119: Provide validation of date format for u3a Notice start or end date
* Feature 1105: Add new sorting and selection facilities to the 'u3a notice list' block
* Bug 1103: Line breaks missing from search results
* Code refactored to access plugin update service via configuration plugin
= 1.1.6 =
* Added documentation u3a Siteworks Core structure.odt
* Refactored code for ease of future maintenance
* Feature 1082 Have consistent approach to admin page AllEvents/AllGroups etc
* Tested up to WP 6.7
* Add Requires Plugins: meta-box header
* Feature 1094: Change short text form of group status to "Waiting list"
= 1.1.5 =
* Bug 1081: Remove u3a venue list block as this will not be implemented
= 1.1.4 =
* Bug 1074 u3aeventlist and u3agrouplist filters. Now fixed the bug introduced in v1.1.3
= 1.1.3 =
* Bug 1043: Venue page shows associated events with no indication of date
* Bug 1045: u3a blocks do not implement support for additional CSS classes
* Bug 1053: Posts not displayed in dashboard when filtering by group and sorting by date
* Bug 1055: add support for Excerpts when defining Group and Venue custom post types
* Bug 1062: Incorrect result when using the u3a group list on the homepage with the Filtered display option and selecting a day
* Bug 1065: WordPress quick edit shows "published" posts as "scheduled"
* Feature 1054: Make featured image in event list grid layout a clickable link to the event
* Feature 1063: Add option to enable/disable auto-scrolling to group list sort buttons
= 1.1.2 =
* Bug 1048: venue not initialised in shortcode u3agrouplist
* Bug 1046: u3a group list properties panel does not initially show Sort Order option
* Bug 1042: group events should not appear in listings if group is not currently 'published'
= 1.1.1 =
* Bug 1034: Allow suppression of the default event list title
= 1.1.0 =
* Release 1098 feedback: Respect WordPress time format in group listings, minor changes to event list block properties
* Feature 1023: Option to display venue in group list.
* Feature 1032: Alter group status text "Suspended" to "Dormant"
* Feature 1015: Add optional event end time (metadata field eventEndTime)
* Feature 1024: Support multiple u3a_group categories in REST API endpoint
* Bug 1027: Label for "category" in u3a group list does not respect u3a settings
# Issues  #42 and #43 bug fixes
* Feature 1004: Add support for Meta Field Block to display all group and event metadata
* Feature 1002: Choice of alphabetic flow in group list
* Feature 491: Make some group and event metadata available via REST API (Feb 2024)
* Feature 991: Improve display of group contacts when 'hide email addresses' is off (Jan 2024)
* Feature 994: Add editable attributes to event list and group list blocks (Jan 2024)
= 1.0.5 =
* Bug 1000: On Production website, display of Groups is unpredictable (Jan 2024)
= 1.0.4 =
* Feature 1003: Change error message when custom post type has no title (Jan 2024)
= 1.0.3 =
* Feature 491: Make group status available via REST API to support development of an Oversights facility for SiteWorks sites (Jan 2024)
= 1.0.2 = 
* Feature 952:  Display all Group Sort By buttons on a single line if possible (Dec 2023)
* Feature 1003: Omit prefix "When:" in group list display (Dec 2023)
* Feature 996: Add optional start and end times to groups (Dec 2023)
= 1.0.1 =
* Bug 983: Remove '2nd' prefix when a second group leader is shown in 'u3a single group data' block (Nov 2023)
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

