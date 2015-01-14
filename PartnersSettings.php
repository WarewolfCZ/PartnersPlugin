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
 * Manage settings for plugin
 *
 * @author WarewolfCZ
 */
class PartnersSettings {

    //TODO: add localization
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'page_init'));
    }

    public function partners_scripts() {
        wp_enqueue_media();
        wp_enqueue_script('custom-header');
    }

    /**
     * Add options page
     */
    public function add_plugin_page() {
        $page_hook_suffix = add_posts_page
                ('Partners', 'Manage Partners', 'manage_options', 'partners-manage', array($this, 'partners_options'));

        add_action('admin_print_scripts-' . $page_hook_suffix, array($this, 'partners_scripts'));
    }

    /**
     * Options page callback
     */
    public function partners_options() {

        // Set class property
        $this->options = get_option('partners_option');
        ?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <?php
            // Add to the top of our data-update-link page
            if (isset($_REQUEST['file']) && !isset($_REQUEST['settings-updated'])) {
                check_admin_referer("partners_option");

                // Process and save the image id
                $imageId = absint($_REQUEST['file']);
                if (!is_array($this->options['images']) || !in_array($imageId, $this->options['images'])) {
                    if ($this->options['images'] == NULL) {
                        $this->options['images'] = array($imageId);
                    } else
                        array_push($this->options['images'], $imageId);
                }
                if (update_option('partners_option', $this->options)) {
                    echo '<div id="message" class="updated fade"><p><strong>' . __('Image added', 'partners') . '.</strong></p></div>';
                }
            } else if (isset($_REQUEST['delete_id']) && !isset($_REQUEST['settings-updated'])) {
                check_admin_referer("partners_option");

                $imageId = absint($_REQUEST['delete_id']);


                $key = array_search($imageId, $this->options['images']);
                if ($key !== false) {
                    unset($this->options['images'][$key]);
                }
                if (update_option('partners_option', $this->options)) {
                    echo '<div id="message" class="updated fade"><p><strong>' . __('Image deleted', 'partners') . '.</strong></p></div>';
                }
            } else if (isset($_REQUEST['settings-updated'])) {
                echo '<div id="message" class="updated fade"><p><strong>' . __('Settings updated', 'partners') . '</strong></p></div>';
            }
            ?>
            <form method="post" action="options.php">
                <?php
                // This prints out all hidden setting fields
                settings_fields('partners_option_group');
                do_settings_sections('partners-manage');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init() {
        register_setting(
                'partners_option_group', // Option group
                'partners_option', // Option name
                array($this, 'sanitize') // Sanitize
        );

        add_settings_section(
                'partners_setting_section', // ID
                'Partners management page', // Title
                array($this, 'print_section_info'), // Callback
                'partners-manage' // Page
        );

        add_settings_field(
                'images', // ID
                'Partners', // Title 
                array($this, 'images_callback'), // Callback
                'partners-manage', // Page
                'partners_setting_section' // Section           
        );

        add_settings_field(
                'priority', // ID
                'Link priority', // Title 
                array($this, 'priority_callback'), // Callback
                'partners-manage', // Page
                'partners_setting_section' // Section           
        );
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize($input) {
        $new_input = array();
        if (isset($input['images'])) {
            array_walk($input['images'], array($this, 'sanitize_int'));
            $new_input['images'] = $input['images'];
        }

        if (isset($input['priority'])) {
            $new_input['priority'] = absint($input['priority']);
        }

        return $new_input;
    }

    public function sanitize_int(&$item1, $key) {
        $item1 = absint($item1);
    }

    /**
     * Print the Section text
     */
    public function print_section_info() {

        $modal_update_href = esc_url(add_query_arg(array(
            'page' => 'partners-manage',
            '_wpnonce' => wp_create_nonce('partners_option'),
                        ), admin_url('edit.php')));
        ?>
        <p>
            <a id="choose-from-library-link" href="#"
               data-update-link="<?php echo esc_attr($modal_update_href); ?>"
               data-choose="<?php esc_attr_e('Choose an image', 'partners'); ?>"
               data-update="<?php esc_attr_e('Add image', 'partners'); ?>"><?php _e('Add new image', 'partners'); ?>
            </a>
        </p>
        <?php
    }

    public function images_callback() {
        if ($this->options['images']) {
            echo "<ul>";

            foreach ($this->options['images'] as $key => $value) {
                echo "<li>" . wp_get_attachment_image($value, 'medium');
                echo '<input type="hidden" name="partners_option[images][]" value="' . $value . '" />';
                $modal_update_href = esc_url(add_query_arg(array(
                    'page' => 'partners-manage',
                    'delete_id' => $value,
                    '_wpnonce' => wp_create_nonce('partners_option'),
                                ), admin_url('edit.php')));
                echo '<div>' . __('Title', 'partners') . ': ' . get_the_title($value) . '</div>';
                echo '<a href="' . $modal_update_href . '">' . __('Delete', 'partners') . '</a>';
                echo "</li>";
            }
            echo "</ul>";
        }
    }

    public function priority_callback() {
        echo '<label for="partners_priority">' . __('Priority (default value: 10)', 'partners') . '</label> ';
        echo '<input type="text" id="partners_priority" name="partners_option[priority]" value="' . absint($this->options['priority']) . '" />';
    }

}
