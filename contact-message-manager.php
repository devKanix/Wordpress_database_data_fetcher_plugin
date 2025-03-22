<?php
/*
Plugin Name: Contact Message Manager
Description: Manage contact messages directly from the dashboard.
Version: 1.1
Author: Kanishk Chaudhary
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Enqueue scripts and styles
add_action('admin_enqueue_scripts', 'contact_message_manager_scripts');
function contact_message_manager_scripts()
{
    wp_enqueue_script('jquery');
    wp_enqueue_script('contact-manager-ajax', plugin_dir_url(__FILE__) . 'contact-manager.js', ['jquery'], null, true);
    wp_localize_script('contact-manager-ajax', 'ajax_object', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('update_message_nonce'),
    ]);
}

// Register the admin menu
add_action('admin_menu', 'contact_message_manager_menu');
function contact_message_manager_menu()
{
    add_menu_page(
        'Contact Messages',
        'Contact Messages',
        'manage_options',
        'contact-message-manager',
        'display_contact_messages',
        'dashicons-email',
        20
    );
}

// Handle Delete Operation
function handle_delete_message()
{
    global $wpdb;
    if (isset($_GET['delete'])) {
        $delete_id = intval($_GET['delete']);
        $table_name = $wpdb->prefix . 'contact_messages';

        $deleted = $wpdb->delete($table_name, ['id' => $delete_id]);

        if ($deleted) {
            echo '<div id="delete-success" style="position:fixed; top:30%; left:40%; width:300px; background:#fff; color: white; padding:20px; border:2px solid #333; z-index:1000;">
                <h3>Deleted Successfully</h3>
                <button type="button" id="close-success" class="button">Close</button>
            </div>
            <script>
                document.getElementById("close-success").addEventListener("click", function() {
                    location.replace("admin.php?page=contact-message-manager");
                });
            </script>';
        } else {
            echo '<div id="delete-failed" style="position:fixed; top:30%; left:40%; width:300px; background:#fff; color: white; padding:20px; border:2px solid #333; z-index:1000;">
                <h3>Deletion Failed</h3>
                <button type="button" id="close-failed" class="button">Close</button>
            </div>
            <script>
                document.getElementById("close-failed").addEventListener("click", function() {
                    location.replace("admin.php?page=contact-message-manager");
                });
            </script>';
        }
        
    }
}

// Handle Update Operation using AJAX
// Handle AJAX Update Message
// Handle Update Operation using AJAX
add_action('wp_ajax_update_message', 'update_message');
add_action('wp_ajax_nopriv_update_message', 'update_message');

function update_message()
{
    global $wpdb;

    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'update_message_nonce')) {
        wp_send_json_error(['message' => 'Invalid security token']);
    }

    // Get the POST data
    $id = intval($_POST['id']);
    $email = sanitize_email($_POST['email']);
    $message = sanitize_textarea_field($_POST['message']);
    $updated_at = current_time('mysql');


    if (empty($email) && empty($message)) {
        wp_send_json_error(['message' => 'Both email and message are missing.']);
    } else if (empty($message)) {
        wp_send_json_error(['message' => 'Message is missing.']);
    } else if (empty($email)) {
        wp_send_json_error(['message' => 'Email is missing.']);
    }


    $table_name = $wpdb->prefix . 'contact_messages';

    // Update the message in the database
    $updated = $wpdb->update($table_name, [
        'email' => $email,
        'message' => $message,
        'updated_at' => $updated_at,
    ], ['id' => $id]);

    if ($updated !== false) {
        wp_send_json_success(['message' => 'Message updated successfully.']);
    } else {
        wp_send_json_error(['message' => 'Failed to update message.']);
    }

    wp_die(); // Important to end AJAX requests in WordPress
}



// Export Contact Messages to CSV
add_action('admin_post_export_contact_messages', 'export_contact_messages');
function export_contact_messages()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'contact_messages';
    $messages = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC");

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="contact_messages.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Email', 'Message', 'Submitted At', 'Updated At']);

    foreach ($messages as $message) {
        fputcsv($output, [$message->id, $message->email, $message->message, $message->submitted_at, $message->updated_at]);
    }

    fclose($output);
    exit;
}

// Display Contact Messages
function display_contact_messages()
{
    global $wpdb;
    handle_delete_message();

    $table_name = $wpdb->prefix . 'contact_messages';
    $results = $wpdb->get_results("SELECT * FROM $table_name");

    echo '<div class="wrap"><h2>Contact Messages</h2>';
    echo '<a href="' . admin_url('admin-post.php?action=export_contact_messages') . '" class="button button-primary" style="margin: 20px 0;">Export to CSV</a>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>ID</th><th>Email</th><th>Message</th><th>Submitted At</th><th>Updated At</th><th>Actions</th></tr></thead>';
    echo '<tbody>';
    foreach ($results as $row) {
        echo '<tr>';
        echo '<td>' . esc_html($row->id) . '</td>';
        echo '<td>' . esc_html($row->email) . '</td>';
        echo '<td>' . esc_html($row->message) . '</td>';
        echo '<td>' . esc_html($row->submitted_at) . '</td>';
        echo '<td>' . esc_html($row->updated_at) . '</td>';
        echo '<td>';
        echo '<a href="#" class="button edit-message" data-id="' . esc_attr($row->id) . '" data-email="' . esc_attr($row->email) . '" data-message="' . esc_attr($row->message) . '">Edit</a> ';
        echo '<form method="GET" action="" style="display:inline;" class="delete-form">
        <input type="hidden" name="page" value="contact-message-manager">
        <input type="hidden" name="delete" value="' . esc_attr($row->id) . '">
        <button type="button" class="button delete-btn" style="color: red;">Delete</button>
    </form>';
        echo '</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}

// AJAX Script for Inline Editing
add_action('admin_footer', 'edit_message_modal');
function edit_message_modal()
{
    ?>
    <div id="edit-modal"
        style="display:none; position:fixed; top:30%; left:40%; width:300px; background:#fff; padding:20px; border:2px solid #333; z-index:1000;">
        <h3>Edit Message</h3>
        <form id="update-message-form">
            <input type="hidden" name="id">
            <label>Email:</label>
            <input type="email" name="email" style="width:100%; margin:5px 0;">
            <label>Message:</label>
            <textarea name="message" style="width:100%; margin:5px 0;"></textarea>
            <div id="form-status" style="margin-top: 8px; margin: 5px; color: #f44336;"></div>
            <button type="submit" class="button button-primary">Update</button>
            <button type="button" id="close-modal" class="button">Cancel</button>
        </form>
    </div>
    <div id="successful"
        style="display:none; position:fixed; top:30%; left:40%; width:300px; background:#fff; color: white; padding:20px; border:2px solid #333; z-index:1000;">
        <h3>Updated Successfully</h3>
        <button type="button" id="close-success" class="button">Close</button>
    </div>
    <div id="delete-modal" style="display:none; position:fixed; top:30%; left:40%; width:300px; background:#fff; padding:20px; border:2px solid #333; z-index:1000;">
    <h3>Confirm Deletion</h3>
    <p>Are you sure you want to delete this message?</p>
    <button id="confirm-delete" class="button button-primary">Yes, Delete</button>
    <button id="cancel-delete" class="button">Cancel</button>
</div>

    <?php

}
?>