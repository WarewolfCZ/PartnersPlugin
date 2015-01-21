<?php

/*
  Plugin Name: Partners
  Plugin URI: https://github.com/WarewolfCZ/PartnersPlugin/
  Description: Partners plugin adds option to select one of the predefined images with link and add it to the post
  Version: 1.0.2
  Author: WarewolfCZ
  Author URI: http://www.warewolf.cz
  License: GPLv2
  License URI: http://www.gnu.org/licenses/gpl-2.0.html
  Text Domain: partners
 */

/*
  Copyright (C) 2015 WarewolfCZ

  This program is free software; you can redistribute it and/or
  modify it under the terms of the GNU General Public License
  as published by the Free Software Foundation; either version 2
  of the License, or (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */
defined('ABSPATH') or die("No script kiddies please!");


require_once 'PartnersSettings.php';
require_once 'EditPost.php';

function addMetabox() {
    $edit = new EditPost();
}

function appendPartner($content) {
    global $post;
    $partner_selected = get_post_meta($post->ID, '_partners_meta_selected', true);
    if ($partner_selected > 0) {
        $default_attr = array(
            'alt' => trim(strip_tags(get_post_meta($partner_selected, '_wp_attachment_image_alt', true))),
        );
        $partner_link = get_post_meta($post->ID, '_partners_meta_link', true);
        $content = $content . '<a href="' . $partner_link . '">' .
                wp_get_attachment_image($partner_selected, 'full', false, $default_attr) .
                '</a>';
    }
    return $content;
}

function partners_load_textdomain() {
    load_plugin_textdomain( 'partners', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/');
}

add_action( 'plugins_loaded', 'partners_load_textdomain' );
register_activation_hook(   __FILE__, array( 'PartnersSettings', 'on_activation' ) );

if (is_admin()) {
    $my_settings_page = new PartnersSettings();
    add_action('load-post.php', 'addMetabox');
    add_action('load-post-new.php', 'addMetabox');
} else {
    $options = get_option('partners_option');
    add_filter('the_content', 'appendPartner', abs($options['priority']));
}
