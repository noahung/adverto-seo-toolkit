<?php
/*
Plugin Name: Adverto SEO Tool Kit
Description: A suite of SEO tools including Canonical URL Tool and Duplicate SEO Wizard.
Author: Noah
Version: 1.0
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
                ?>
                <label class="checkbox-container"><?php echo $page->post_title; ?>
                    <input type="checkbox" name="selected_pages[]" value="<?php echo $page->ID; ?>">
                    <span class="checkmark"></span>
                </label>
                <?php
            }
            ?>
            <h2>Canonical URL</h2>
            <input type="text" name="canonical_url" value="">
            <br><br>
            <input type="submit" name="submit" value="Paste Canonical URLs" class="button button-primary">
        </form>
    </div>
    <style>
    /* Custom checkbox styles */
    .checkbox-container {
        display: block;
        position: relative;
        padding-left: 35px;
        margin-bottom: 12px;
        cursor: pointer;
        font-size: 16px;
        user-select: none;
    }

    .checkbox-container input {
        position: absolute;
        opacity: 0;
        cursor: pointer;
        height: 0;
        width: 0;
    }

    .checkmark {
        position: absolute;
        top: 0;
        left: 0;
        height: 25px;
        width: 25px;
        background-color: #eee;
    }

    .checkbox-container:hover input ~ .checkmark {
        background-color: #ccc;
    }

    .checkbox-container input:checked ~ .checkmark {
        background-color: #2196F3;
    }

    .checkmark:after {
        content: "";
        position: absolute;
        display: none;
    }

    .checkbox-container input:checked ~ .checkmark:after {
        display: block;
    }

    .checkbox-container .checkmark:after {
        left: 9px;
        top: 5px;
        width: 5px;
        height: 10px;
        border: solid white;
        border-width: 0 3px 3px 0;
        transform: rotate(45deg);
    }
    </style>
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
        } else {
            echo '<div class="error"><p>Failed to duplicate page with replacement: ' . esc_html($replace) . '</p></div>';
        }

        // Update Yoast SEO fields in duplicated page
        $new_focus_keyphrase = str_replace($find, $replace, $focus_keyphrase);
        $new_seo_title = str_replace($find, $replace, $seo_title);
        $new_meta_description = str_replace($find, $replace, $meta_description);

        update_post_meta($new_page_id, '_yoast_wpseo_focuskw', $new_focus_keyphrase);
        update_post_meta($new_page_id, '_yoast_wpseo_title', $new_seo_title);
        update_post_meta($new_page_id, '_yoast_wpseo_metadesc', $new_meta_description);

        // Update Pixfort options in duplicated page
        update_post_meta($new_page_id, 'pix-hide-top-padding', $pix_hide_top_padding);
        update_post_meta($new_page_id, 'pix-hide-top-area', $pix_hide_top_area);
    }
}
?>
