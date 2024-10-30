=== Media from ZIP ===
Contributors: Katsushi Kawamori
Donate link: https://shop.riverforest-wp.info/donate/
Tags: archive, extract, media, zip
Requires at least: 4.7
Requires PHP: 8.0
Tested up to: 6.6
Stable tag: 1.20
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Extract from ZIP archive to Media Library.

== Description ==

= How to register =
* Register by upload.
* Register on server.

= Register to media library =
* Maintain folder structure.
* This create a thumbnail of the image file.
* This create a metadata(Images, Videos, Audios).
* Change the date/time.
* Perform background processing and notify by e-mail.
* Sibling plugin -> [Media from FTP](https://wordpress.org/plugins/media-from-ftp/).
* Sibling plugin -> [ZIP from Media](https://wordpress.org/plugins/zip-from-media/).

= Note =
* On servers where PHP max_execution_time is fixed to a short time (30 seconds), you may time out if you want to generate a large number of images.
* File names using multi-byte characters are not supported.

= How it works =
[youtube https://youtu.be/ujMloYKBRik]

== Installation ==

1. Upload `media-from-zip` directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

none

== Screenshots ==

1. Upload & Unzip to Media Library
2. Unzip to Media Library
3. Settings

== Changelog ==

= [1.20] 2024/03/05 =
* Fix - Changed file operations to WP_Filesystem.

= 1.19 =
Supported WordPress 6.4.
PHP 8.0 is now required.

= 1.18 =
Fixed problem with EXIF data acquisition.

= 1.17 =
Fixed translation.
Fixed problem with EXIF data acquisition.

= 1.16 =
Fixed uninstall.

= 1.15 =
Supported XAMPP.

= 1.14 =
Supported WordPress 5.6.

= 1.13 =
Fixed problem of metadta.

= 1.12 =
Fixed problem of metadta.

= 1.11 =
Added a link to [ZIP from Media](https://wordpress.org/plugins/zip-from-media/).

= 1.10 =
Fixed problem of loading class.

= 1.09 =
Fixed MIME type retrieval problem.

= 1.08 =
Fixed timeout check issue.

= 1.07 =
Fixed translation.

= 1.06 =
Fixed admin screen notice issue.

= 1.05 =
Fixed timeout issue with large amounts of data.

= 1.04 =
Fixed background processing.

= 1.03 =
Added management screen notification of completion of addition to Media Library.

= 1.02 =
Fixed an issue with file name sanitization.
Added the display of original files for large images.

= 1.01 =
Added a filter for cache files in ZIP files.

= 1.00 =
Initial release.

== Upgrade Notice ==

= 1.00 =
Initial release.
