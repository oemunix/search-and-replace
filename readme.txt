=== Search and Replace ===
Contributors: Bueltge, inpsyde
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=RHWH8VG798CSC
Tags: database, mysql, search, replace, admin, security
Requires at least: 3.0
Tested up to: 4.1.0
Stable tag: trunk
License: GPLv2+

The Search and Replace plugin is no longer under maintenance.

== Description ==

**The Search and Replace plugin is no longer under maintenance.**

The plugin have a long history. But the end of life is be reached.
It is not helpful to maintain the plugin, a refactoring, rewrite is necassary. I find no helping hands in the community and it give newer plugins.

Some recommendations:

 * [Search-Replace-DB](https://github.com/interconnectit/Search-Replace-DB)
 * [Better Search Replace](https://wordpress.org/plugins/better-search-replace/)


== Changelog ==
= End of Support 07/2015 =
* No longer development, maintenance
* Changes on the database structure of WP core is not easy to maintenance with this plugin
* A lot of field in inside the database are in serilized data fields, not handle vie sql default
* rewrite id much effort and other plugins are done, helpful

= v2.7.1 (2015-05-28) =
* Fix for changes on database collate since WordPress version 4.2
* Fix to reduce backslashes in search and replace string

= v2.7.0 (2014-09-14) =
* Exclude serialized data from replace function (maybe we reduce the support)
* Add hint, if is serialized data on the result table
* Fix to see also the result case sensitive

= v2.6.6 (09/05/2014) =
* *Thanks to [Ron Guerin](http://wordpress.org/support/profile/rong) for help to maintain the plugin*
* Fix to use $wpdb object for all database access
* Fix inability to search and replace quoted strings
* Output changes to clarify when searching vs. searching and replacing
* Some changes to English strings and string identifiers

= v2.6.5 =
* Fix for change User-ID, add table `comments`

= v2.6.4 =
* Fix capability check, if the constant `DISALLOW_FILE_EDIT` ist defined

= v2.6.3 (10/10/2011) =
* filter for return values, html-filter
* add belarussian language
* add romanian language files

= v2.6.2 (09/11/2011) =
* change right object for use the plugin also on WP smaller 3.0, include 2.9
* add function search and replace in all tables of the database - special care!

= v2.6.1 (01/25/2011) =
* Feature: Add Signups-Table for WP MU
* Maintenance: check for tables, PHP Warning fix

= v2.6.0 (01/03/2011) =
* Feature: add an new search for find strings (maybe a new way for search strings)
* Maintenance: small changes on source

= v2.5.1 (07/07/2010) =
* small changes for use in WP 3.0
