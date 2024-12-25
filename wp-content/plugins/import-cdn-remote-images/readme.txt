=== Import CDN-Remote Images ===
Contributors: atakanau
Author link: https://atakanau.blogspot.com
Tags: remote image, remote media, cdn image, external media, cloudinary
Requires at least: 4.7.4
Tested up to: 6.7
Stable tag: 2.1.2
Requires PHP: 5.6
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0-standalone.html

Add external images to the media library without importing, i.e. uploading them to your WordPress site.

== Description ==

By default, adding an image to the WordPress media library requires you to import or upload the image to the WordPress site, which means there must be a copy of the image file stored in the site. This plugin enables you to add an image stored in an external site to the media library by just reading list of remote images using CDN service's (Cloudinary) API. In this way you can host the images in a dedicated server other than the WordPress site, and still be able to show them by various gallery plugins which only take images from the media library.

The plugin provides a dedicated 'Media' -> 'Import images' submenu page.

Supported CDN services:
* Cloudinary
(others coming soon)

[Blog and feedback](https://atakanau.blogspot.com/2020/10/import-cdn-remote-images-wp-plugin.html?utm_content=textlink&utm_medium=link&utm_source=wporg&utm_campaign=import-cdn-remote-images-desc)

Need a custom work?
If you need template customization, optimization or custom software development service, please [contact me](https://bit.ly/aaucontact1).

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/import-cdn-remote-images` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.

= Usage =

After installation you can use the plugin to add external media without import:

1. Go to setting page of plugin and save required CDN service parameters such as Cloud name, API key, API secret.
2. Click the 'Media' -> 'Import images' submenu in the side bar.
3. Click 'Update' button and automatically fill in the URLs of the images you want to add.
4. Click the 'Add' button, the remote images will be added.

== Screenshots ==
1. Settings Page - Cloudinary
2. Import Page - Bulk URL
3. Import Page - Cloudinary
4. Sample imported image 1
5. Sample imported image 2

== Changelog ==

= Version 2.1.2 =
* Minor code changes
* Tested up to:
  * `6.7`

For the changelog of earlier versions, please refer to [Import CDN-Remote Images changelog](https://atakanau.blogspot.com/2020/10/import-cdn-remote-images-wp-plugin.html?utm_content=textlink&utm_medium=link&utm_source=wporg&utm_campaign=import-cdn-remote-images-changelog) section on blog.
