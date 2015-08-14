=== My shared widgets ===
Contributors: bastho, n4thaniel, ecolosites
Donate link: 
Tags: widget, share, multisites
Requires at least: 3.7.0
Tested up to: 4.3
Stable tag: /trunk
License: GPLv2
Network : 1
Tags: widget, widgets, network, share, EELV

create and share your text widgets in a multisites plateform

== Description ==

create a post-type widgets on a multisite plateform.
Any "my widget" created appears as a widget in all sites and display the HTML source of the created widget.

== Installation ==

1. Upload `eelv_widgets` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress network admin

== Frequently asked questions ==

= Does the widgets have an expiration date ? =

Yes, It can be edited in the network admin. The default value is "no-expire"

== Screenshots ==

http://ecolosites.eelv.fr/files/2012/10/widgets.png
http://ecolosites.eelv.fr/files/2012/10/widgets2.png

== Changelog ==


= 1.7 =
release date: aug. 14 2015

* WP 4.3 compliant
* Use of native wp_mail function, more explicit notification
* Code cleanup

= 1.6.7 =
* Fix : More flexible jQuery call

= 1.6.6 =
* Fix : Limit the search of widgets on available blogs

= 1.6.5 =
* Fix : Refresh cache when just extending lifetime
* Fix : Make extend button usable many times

= 1.6.4 =
* Fix : Optimize alert mail headers
* Add : Widget content in the alert mail

= 1.6.3 =
* Fix : SQL optimization

= 1.6.2 =
* Add : "the_title" filter to widgets title
* Add : switch to blog to execute filters on the apporpriate blog
* Fix : All blogs were not parsed if there were deleted blogs

= 1.6.1 =
* Add : "the_content" filter to widgets content

= 1.6.0 =
* Add : Widgets option to show/hide title

= 1.5.1 =
* Fix : bug fix

= 1.5.0 =
* Change : "My shared widgets" more efficient than "My Widgets"
* Add : Column to display share status for each widget
* Add : Extend button
* Change : Loud changes in code structure
* Fix : some bugs

= 1.4.4 =
* Fix : change name to "My widgets"
* Fix : lighter SQL queries
* Licence : Change licence to GPLv2

= 1.4.3 =
* Fix : More than 100 sites plateform bug
* Fix : SQL optimization by spliting query

= 1.4.2 =
* Fix : Force refresh when saving a new widget

= 1.4.1 =
* Add : Author name in widgets list
* Fix : Multi-widgets bug

= 1.4.0 =
* Warning ! due to widget ID changing, widgets will be removed from sidebar and will need to be re-introduced
* Add: Use new wp_get_sites() function
* Fix: Large performances optimisaton
* Fix: PHP warning

= 1.3.4 =
* Fix: Remove PHP Warning for missing parameter

= 1.3.3 =
* Fix: Bug fix

= 1.3.2 =
* Fix: Bug fix

= 1.3.1 =
* Fix: Human date correctly displayed

= 1.3.0 =
* Add: Network admin option for hidding old widgets
* Add: Human date displayed for widget creation
* Fix: Now supports widgets with specials caracters in the title

= 1.2.2 =
* Fix: Incorrect SQL query in some cases

= 1.2.1 =
* Fix: replace bad caracters in sites name

= 1.2 =
* Fix: Incorrect table prefix used in some case fixed

= 1.1 =
* Add: Network admin email alert for each creation (optionnal)
* Add: Cache results for better performances

= 1.0 =
* Add: Improved performances

= 0.1 =
* plugin creation

== Upgrade notice ==

= 1.6.2 =
the_title & the_content filters are applied, so widget's content can be different