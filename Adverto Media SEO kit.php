<?php
/*
Plugin Name: Adverto SEO Tool Kit
Description: A suite of SEO tools including Canonical URL Tool and Duplicate SEO Wizard.
Author: Noah
Version: 1.1
*/

add_action('admin_menu', 'adverto_seo_tool_kit_menu');

function adverto_seo_tool_kit_menu() {
    // Add main menu with custom icon
    $icon_url = plugin_dir_url(__FILE__) . 'adverto-logo.png';
    add_menu_page('Adverto SEO Tool Kit', 'Adverto SEO Tool Kit', 'manage_options', 'adverto-seo-tool-kit', 'adverto_seo_tool_kit_page', $icon_url, 20);
    
    // Add submenus
    add_submenu_page('adverto-seo-tool-kit', 'Canonical URL Tool', 'Canonical URL Tool', 'manage_options', 'canonical-url-tool', 'canonical_url_tool_page');
    add_submenu_page('adverto-seo-tool-kit', 'Duplicate SEO Wizard', 'Duplicate SEO Wizard', 'manage_options', 'duplicate-seo-wizard', 'duplicate_seo_wizard_page');
}

function adverto_seo_tool_kit_page() {
    echo '<h1>Adverto SEO Tool Kit</h1>';
    echo '<p>Welcome to the Adverto SEO Tool Kit. Please choose a tool from the menu.</p>';
}

// Canonical URL Tool
function canonical_url_tool_page() {
    ?>
    <div class="wrap">
        <h1>Canonical URL Tool for Yoast</h1>
        <form method="post" action="">
            <h2>Select Pages</h2>
            <?php
            $args = array(
                'post_type' => 'page',
                'posts_per_page' => -1,
                'orderby' => 'title',
                'order' => 'ASC', // Sort pages in ascending order by title
            );
            $pages = get_pages($args);
            foreach ($pages as $page) {
                echo '<input type="checkbox" name="selected_pages[]" value="' . $page->ID . '"> ' . $page->post_title . '<br>';
            }
            ?>
            <h2>Canonical URL</h2>
            <input type="text" name="canonical_url" value="">
            <br><br>
            <input type="submit" name="submit" value="Paste Canonical URLs" class="button button-primary">
        </form>
    </div>
    <?php
    if (isset($_POST['submit'])) {
        canonical_url_tool_update_urls();
    }
}

function canonical_url_tool_update_urls() {
    if (isset($_POST['selected_pages']) && isset($_POST['canonical_url'])) {
        $selected_pages = $_POST['selected_pages'];
        $canonical_url = sanitize_text_field($_POST['canonical_url']);
        foreach ($selected_pages as $page_id) {
            update_post_meta($page_id, '_yoast_wpseo_canonical', $canonical_url);
        }
        echo '<div class="updated"><p>Canonical URLs updated successfully!</p></div>';
    }
}

// Duplicate SEO Wizard
function duplicate_seo_wizard_page() {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['dpr_nonce_field']) && wp_verify_nonce($_POST['dpr_nonce_field'], 'dpr_nonce_action')) {
            $num_duplicates = intval($_POST['dpr_num_duplicates']);
            $replacements = [];
            for ($i = 1; $i <= $num_duplicates; $i++) {
                $replacements[] = sanitize_text_field($_POST["dpr_replace_$i"]);
            }
            dpr_duplicate_pages(intval($_POST['dpr_page_id']), sanitize_text_field($_POST['dpr_find']), $replacements);
        } else {
            wp_die('Security check failed');
        }
    }

    ?>
    <div class="wrap">
        <h1>Duplicate SEO Wizard</h1>
        <form method="post">
            <?php wp_nonce_field('dpr_nonce_action', 'dpr_nonce_field'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Page to Duplicate</th>
                    <td>
                        <select name="dpr_page_id">
                            <?php
                            $pages = get_pages();
                            foreach ($pages as $page) {
                                echo '<option value="' . esc_attr($page->ID) . '">' . esc_html($page->post_title) . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Find</th>
                    <td><input type="text" name="dpr_find" required /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Number of Duplicates</th>
                    <td><input type="number" name="dpr_num_duplicates" id="dpr_num_duplicates" value="1" min="1" required /></td>
                </tr>
                <tbody id="dpr_replacements">
                    <tr valign="top">
                        <th scope="row">Replace 1</th>
                        <td><input type="text" name="dpr_replace_1" required /></td>
                    </tr>
                </tbody>
            </table>
            <?php submit_button('Duplicate and Replace', 'primary', 'dpr_submit'); ?>
        </form>
    </div>
    <script>
    document.getElementById('dpr_num_duplicates').addEventListener('change', function () {
        var num = this.value;
        var replacementsDiv = document.getElementById('dpr_replacements');
        replacementsDiv.innerHTML = '';
        for (var i = 1; i <= num; i++) {
            var row = '<tr valign="top"><th scope="row">Replace ' + i + '</th><td><input type="text" name="dpr_replace_' + i + '" required /></td></tr>';
            replacementsDiv.innerHTML += row;
        }
    });
    </script>
    <?php
}

function dpr_duplicate_pages($page_id, $find, $replacements) {
    $page = get_post($page_id);

    if (!$page) {
        echo '<div class="error"><p>Page not found!</p></div>';
        return;
    }

    // Retrieve Yoast SEO fields
    $focus_keyphrase = get_post_meta($page_id, '_yoast_wpseo_focuskw', true);
    $seo_title = get_post_meta($page_id, '_yoast_wpseo_title', true);
    $meta_description = get_post_meta($page_id, '_yoast_wpseo_metadesc', true);

    // Retrieve Pixfort options
    $pix_hide_top_padding = get_post_meta($page_id, 'pix-hide-top-padding', true);
    $pix_hide_top_area = get_post_meta($page_id, 'pix-hide-top-area', true);

    // Retrieve page attributes
    $parent_id = wp_get_post_parent_id($page_id);

    // Duplicate images and update alt text
    $image_ids = array();
    $images = get_attached_media('image', $page_id);

    foreach ($replacements as $replace) {
        $new_page = array(
            'post_title' => str_replace($find, $replace, $page->post_title),
            'post_content' => str_replace($find, $replace, $page->post_content),
            'post_status' => 'draft',
            'post_type' => $page->post_type,
            'post_author' => $page->post_author,
            'post_parent' => $parent_id,
        );

        $new_page_id = wp_insert_post($new_page);

        if ($new_page_id) {
            echo '<div class="updated"><p>Page duplicated successfully with replacement: ' . esc_html($replace) . '</p></div>';

            // Update Yoast SEO fields in duplicated page
            $new_focus_keyphrase = str_replace($find, $replace, $focus_keyphrase);
            $new_seo_title = str_replace($find, $replace, $seo_title);
            $new_meta_description = str_replace($find, $replace, $meta_description);

            update_post_meta($new_page_id, '_yoast_wpseo_focuskw', $new_focus_keyphrase);
            update_post_meta($new_page_id, '_yoast_wpseo_title', $new_seo_title);
            update_post_meta($new_page_id, '_yoast_wpseo_metadesc', $new_meta_description);

            // Set Pixfort options to value 1
            update_post_meta($new_page_id, 'pix-hide-top-padding', '1');
            update_post_meta($new_page_id, 'pix-hide-top-area', '1');

            // Duplicate images
            foreach ($images as $image) {
                $image_id = $image->ID;
                $new_image_id = duplicate_image($image_id, $new_page_id, str_replace($find, $replace, $page->post_title));
                if ($new_image_id) {
                    $image_ids[] = $new_image_id;
                }
            }

            // Update post content with new image IDs
            $new_content = $new_page['post_content'];
            foreach ($images as $index => $image) {
                $new_content = str_replace('wp-image-' . $image->ID, 'wp-image-' . $image_ids[$index], $new_content);
            }

            wp_update_post(array(
                'ID' => $new_page_id,
                'post_content' => $new_content
            ));
        } else {
            echo '<div class="error"><p>Failed to duplicate page with replacement: ' . esc_html($replace) . '</p></div>';
        }
    }
}

function duplicate_image($image_id, $new_page_id, $new_page_title) {
    $image = get_post($image_id);
    $image_data = get_attached_file($image_id);
    $uploads = wp_upload_dir();
    $new_image_path = $uploads['path'] . '/' . wp_basename($image_data);
    if (!copy($image_data, $new_image_path)) {
        return false;
    }

    $new_image_url = $uploads['url'] . '/' . wp_basename($image_data);
    $new_image = array(
        'guid' => $new_image_url,
        'post_mime_type' => $image->post_mime_type,
        'post_title' => $image->post_title,
        'post_content' => $image->post_content,
        'post_status' => 'inherit',
        'post_parent' => $new_page_id,
    );

    $new_image_id = wp_insert_attachment($new_image, $new_image_path, $new_page_id);
    if (is_wp_error($new_image_id)) {
        return false;
    }

    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata($new_image_id, $new_image_path);
    wp_update_attachment_metadata($new_image_id, $attach_data);

    update_post_meta($new_image_id, '_wp_attachment_image_alt', $new_page_title);

    return $new_image_id;
}
?>
