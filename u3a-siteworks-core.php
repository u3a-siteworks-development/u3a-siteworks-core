<?php
/*
Plugin Name: u3a SiteWorks Core
Description: Provides facility to manage content for u3a groups, events, notices and related contacts and venues.
Version: 1.1.5
Author: u3a SiteWorks team
Author URI: https://siteworks.u3a.org.uk/
Plugin URI: https://siteworks.u3a.org.uk/
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Requires Plugins: meta-box
*/

if (!defined('ABSPATH')) exit;

define('U3A_SITEWORKS_CORE_VERSION', '1.1.5'); // Set to current plugin version number

// Check for metabox plugin present and activated
if ((require_once "inc/check-metabox.php") == false) return;

// Use the plugin update service on SiteWorks update server

require 'inc/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$u3acptUpdateChecker = PucFactory::buildUpdateChecker(
    'https://siteworks.u3a.org.uk/wp-update-server/?action=get_metadata&slug=u3a-siteworks-core', //Metadata URL
    __FILE__, //Full path to the main plugin file or functions.php.
    'u3a-siteworks-core'
);

// Required files
require_once 'inc/definitions.php';  //  Defines constants used in all classes
require_once 'inc/traits.php';       //  Defines functions that can be shared by all classes
require_once 'inc/update-siteworks-data.php';
require_once "classes/class-u3a-notice.php";

// Update data to conform to current data structure
// Done as init action rather than immediately
global $siteworks_storage_version;
$siteworks_storage_version = 4;
add_action('init', 'u3a_core_check_storage_updates');


// Initialise all classes

require_once "classes/class-u3a-group.php";
U3aGroup::initialise(__FILE__);
require_once "classes/class-u3a-event.php";
U3aEvent::initialise(__FILE__);
require_once "classes/class-u3a-venue.php";
U3aVenue::initialise(__FILE__);
require_once "classes/class-u3a-contact.php";
U3aContact::initialise(__FILE__);
require_once "classes/class-u3a-admin.php";
U3aAdmin::initialise(__FILE__);
require_once "classes/class-u3a-common.php";
U3aCommon::initialise(__FILE__);
//require_once "classes/class-u3a-notice.php";
U3aNotice::initialise(__FILE__);

// Expose some metadata in REST API
require_once "inc/restapi.php";
