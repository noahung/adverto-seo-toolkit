<?php
/*
Plugin Name: Bulk Alt Text Editor
Plugin URI: http://advertomedia.co.uk
Description: A plugin to bulk edit alt texts of images in the media library.
Author: Noah
Version: 1.0
Author URI: http://advertomedia.co.uk
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Enqueue admin scripts and styles
function bate_enqueue_admin_scripts($hook) {
    if ($hook != 'toplevel_page_bulk-alt-text-editor') {
        return;
    }
    wp_enqueue_style('bate-admin-css', plugin_dir_url(__FILE__) . 'bate-admin.css');
    wp_enqueue_script('jquery');
    wp_enqueue_script('bate-admin-js', plugin_dir_url(__FILE__) . 'bate-admin.js', array('jquery'), null, true);
    wp_localize_script('bate-admin-js', 'bateAjax', array('ajaxurl' => admin_url('admin-ajax.php')));
}
add_action('admin_enqueue_scripts', 'bate_enqueue_admin_scripts');

// Add menu item for the plugin
function bate_add_admin_menu() {
    add_menu_page(
        'Bulk Alt Text Editor',   // Page title
        'Bulk Alt Text Editor',   // Menu title
        'manage_options',         // Capability
        'bulk-alt-text-editor',   // Menu slug
        'bate_admin_page',        // Callback function
        'dashicons-edit',         // Icon URL
        6                         // Position
    );
}
add_action('admin_menu', 'bate_add_admin_menu');

// Display the admin page
function bate_admin_page() {
    ?>
    <div class="wrap">
        <h1>Bulk Alt Text Editor</h1>
        <button id="bate-load-images" class="button button-primary" data-page="1">Load Images</button>
        <form id="bate-form" method="post">
            <table id="bate-table" class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th class="column-image">Image</th>
                        <th class="column-alt-text">Alt Text</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
            <button type="submit" class="button button-primary">Save Changes</button>
        </form>
        <div id="bate-pagination" style="margin-top: 20px;"></div>
    </div>
    <?php
}

// Handle AJAX request to load images
function bate_load_images() {
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $images_per_page = 50;
    $offset = ($page - 1) * $images_per_page;

    $query_args = array(
        'post_type'      => 'attachment',
        'post_mime_type' => 'image',
        'post_status'    => 'inherit',
        'posts_per_page' => $images_per_page,
        'offset'         => $offset,
    );

    $query = new WP_Query($query_args);

    $images = array();
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $images[] = array(
                'id'    => get_the_ID(),
                'src'   => wp_get_attachment_url(get_the_ID()),
                'alt'   => get_post_meta(get_the_ID(), '_wp_attachment_image_alt', true),
            );
        }
    }
    wp_reset_postdata();

    $total_images = $query->found_posts;
    $total_pages = ceil($total_images / $images_per_page);

    wp_send_json_success(array('images' => $images, 'total_pages' => $total_pages));
}
add_action('wp_ajax_bate_load_images', 'bate_load_images');

// Handle form submission to save alt texts
function bate_save_alt_texts() {
    if (!isset($_POST['images']) || !is_array($_POST['images'])) {
        wp_send_json_error('Invalid data.');
    }

    foreach ($_POST['images'] as $image) {
        $id = intval($image['id']);
        $alt = sanitize_text_field($image['alt']);
        update_post_meta($id, '_wp_attachment_image_alt', $alt);
    }

    wp_send_json_success('Alt texts updated.');
}
add_action('wp_ajax_bate_save_alt_texts', 'bate_save_alt_texts');
?>
