<?php

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

/**
 * Adding metabox to post editing page
 *
 * @author WarewolfCZ
 */
class EditPost {

    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    public function __construct() {
        add_action('add_meta_boxes_post', array($this, 'add_partner_selection'));
        add_action('save_post', array($this, 'partners_save_meta_box_data'));
    }

    public function add_partner_selection() {
        // Set class property
        $this->options = get_option('partners_option');
        add_meta_box('partners_metabox_id', __('Select partner', 'partners'), array($this, 'print_partner_selection'), 'post', 'normal');
    }

    public function print_partner_selection($post) {
        // Add an nonce field so we can check for it later.
        wp_nonce_field('partners_meta_box', 'partners_meta_box_nonce');

        /*
         * Use get_post_meta() to retrieve an existing value
         * from the database and use the value for the form.
         */
        $partner_selected = get_post_meta($post->ID, '_partners_meta_selected', true);
        $partner_link = get_post_meta($post->ID, '_partners_meta_link', true);

        echo '<label for="partner_link">' . __('Fill in the URL', 'partners') . '</label> ';
        echo '<input type="text" id="partner_link" name="partner_link" value="' . esc_attr($partner_link) . '" size="95" />';
        echo '<label for="partner_selected"><div>' . __('Select partner image', 'partners') . '</div></label>';
        echo '<div>';
        echo '<select id="partner_selected" name="partner_selected">';
        echo '<option value="" ' . (!$partner_selected ? 'selected="selected"' : '') . '>' .
                __('-None-', 'partners') . '</option>';
        foreach ($this->options['images'] as $key => $value) {
            echo '<option value="' . esc_attr($value) . '" ' 
                    . ($partner_selected == $value ? 'selected="selected"' : '') . '>' 
                    . get_the_title($value) . '</option>';
        }
        echo "</select>";
    }

    /**
     * When the post is saved, saves our custom data.
     *
     * @param int $post_id The ID of the post being saved.
     */
    function partners_save_meta_box_data($post_id) {

        /*
         * We need to verify this came from our screen and with proper authorization,
         * because the save_post action can be triggered at other times.
         */

        // Check if our nonce is set.
        if (!isset($_POST['partners_meta_box_nonce'])) {
            return;
        }

        // Verify that the nonce is valid.
        if (!wp_verify_nonce($_POST['partners_meta_box_nonce'], 'partners_meta_box')) {
            return;
        }

        // If this is an autosave, our form has not been submitted, so we don't want to do anything.
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check the user's permissions.
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        /* OK, it's safe for us to save the data now. */

        // Make sure that it is set.
        if (!isset($_POST['partner_selected']) || !isset($_POST['partner_link'])) {
            return;
        }

        // Sanitize user input.
        $selected = absint($_POST['partner_selected']);
        $link = esc_url($_POST['partner_link'], array('http', 'https'));

        // Update the meta field in the database.
        update_post_meta($post_id, '_partners_meta_selected', $selected);
        update_post_meta($post_id, '_partners_meta_link', $link);
    }

}
