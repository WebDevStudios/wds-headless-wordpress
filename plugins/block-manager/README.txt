=== Gutenberg Block Manager ===
Contributors: dcooney, connekthq
Tags: gutenberg, blocks, disable blocks, gutenberg blocks, manage blocks, block administration
Requires at least: 5.0
Tested up to: 5.6
License: GPLv2 or later
Stable tag: trunk
Homepage: https://connekthq.com/
Version: 1.0.1

Gutenberg Block Manager by [Connekt](https://connekthq.com) will allow you to manage the activation status of Gutenberg blocks and remove unwanted blocks from the WordPress post editor.


== Description ==

The Gutenberg Block Manager is an intuitive tool for WordPress site admins to *globally* manage the enabled/disabled state of each block. Disabled blocks will be removed from the block inserter on post edit screens.


### Features
* **Globally enable/disable blocks** - Unlike the block manager in the Gutenberg editor, this tool globally enables/disables blocks for all users.
* **Block Search and Filter** - Quickly locate blocks using the block search tool.


== Installation ==

How to install Block Manager.

= Using The WordPress Dashboard =

1. Navigate to the 'Add New' in the plugins dashboard
2. Search for 'Block Manager'
3. Click 'Install Now'
4. Activate the plugin on the Plugin dashboard

= Uploading in WordPress Dashboard =

1. Navigate to the 'Add New' in the plugins dashboard
2. Navigate to the 'Upload' area
3. Select `block-manager.zip` from your computer
4. Click 'Install Now'
5. Activate the plugin in the Plugin dashboard

= Using FTP =

1. Download `block-manager.zip`
2. Extract the `block-manager` directory to your computer
3. Upload the `block-manager` directory to the `/wp-content/plugins/` directory
4. Activate the plugin in the Plugin dashboard


Then navigate to `wp-admin -> Settings -> Block Manager` to use the plugin.

== Screenshots ==

1. Block Manager WordPress Admin
1. Disable all (or some) of the useless Embed blocks :)
3. Disable entire block categories with a single click.

== Changelog ==

= 1.0.1 - January 2, 2021 =

* NEW - Added support for Embed blocks (Twitter, FB, Spotify etc). These blocks were changed in WP 5.6 and the handler had to be updated to manage the active/inactive states.
* FIX - Fixed REST API warning for missing `permissions_callback`.

= 1.0 - January 6, 2020 =

* Initial release
