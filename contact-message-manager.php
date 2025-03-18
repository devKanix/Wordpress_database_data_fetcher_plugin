<?php
/*
Plugin Name: Contact Message Manager
Description: Manage contact messages directly from the dashboard.
Version: 1.0
Author: Kanishk Chaudhary
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Register the admin menu
add_action('admin_menu', 'contact_message_manager_menu');

function contact_message_manager_menu() {
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
function handle_delete_message() {
    global $wpdb;
    if (isset($_GET['delete'])) {
        $delete_id = intval($_GET['delete']);
        $table_name = $wpdb->prefix . 'contact_messages';

        $deleted = $wpdb->delete($table_name, ['id' => $delete_id]);

        if ($deleted) {
            echo '<script>
                alert("Message deleted successfully!");
                location.replace("admin.php?page=contact-message-manager");
            </script>';
        } else {
            echo '<script>alert("Failed to delete message. Please try again.");</script>';
        }
    }
}

// Handle Update Operation
function handle_update_message() {
    global $wpdb;

    if (isset($_GET['edit'])) {
        $edit_id = intval($_GET['edit']);
        $table_name = $wpdb->prefix . 'contact_messages';
        $edit_message = $wpdb->get_row("SELECT * FROM $table_name WHERE id = $edit_id");

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_message'])) {
            $email = sanitize_email($_POST['email']);
            $message = sanitize_textarea_field($_POST['message']);
            $submitted_at = sanitize_text_field($_POST['submitted_at']);

            $updated = $wpdb->update($table_name, [
                'email' => $email,
                'message' => $message,
                'submitted_at' => $submitted_at,
            ], ['id' => $edit_id]);

            if ($updated !== false) {
                echo '<script>
                    alert("Message updated successfully!");
                    location.replace("admin.php?page=contact-message-manager");
                </script>';
            } else {
                echo '<script>alert("Failed to update message. Please try again.");</script>';
            }
        }

        // Display the Edit Form
        ?>
        <div class="wrap">
            <h2>Edit Contact Message</h2>
            <form method="post">
                <label>Email:</label>
                <input type="email" name="email" value="<?php echo esc_html($edit_message->email); ?>" required />
                <br><br>
                <label>Message:</label>
                <textarea name="message" required><?php echo esc_html($edit_message->message); ?></textarea>
                <br><br>
                <label>Submitted At:</label>
                <input type="text" name="submitted_at" value="<?php echo esc_html($edit_message->submitted_at); ?>" required />
                <br><br>
                <input type="submit" name="update_message" value="Update Message" />
                <a href="?page=contact-message-manager">Cancel</a>
            </form>
        </div>
        <?php
        exit; // Exit after displaying the edit form
    }
}

// Add Export Button only on the Contact Messages page
function add_export_button() {
    $screen = get_current_screen();
    if ($screen->id === 'toplevel_page_contact-message-manager') {
        echo '<a href="' . admin_url('admin-post.php?action=export_contact_messages') . '" class="button button-primary" style="margin: 20px 0;">Export to CSV</a>';
    }
}
add_action('admin_notices', 'add_export_button');

// Export Contact Messages to CSV
function export_contact_messages() {
    if (isset($_GET['action']) && $_GET['action'] === 'export_contact_messages') {
        global $wpdb;

        $table_name = $wpdb->prefix . 'contact_messages';
        $messages = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC");

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="contact_messages.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, array('ID', 'Email', 'Message', 'Submitted At'));

        foreach ($messages as $message) {
            fputcsv($output, array($message->id, $message->email, $message->message, $message->submitted_at));
        }

        fclose($output);
        exit;
    }
}
add_action('admin_post_export_contact_messages', 'export_contact_messages');


// Display Contact Messages
function display_contact_messages() {
    global $wpdb;
    handle_delete_message();  // Call delete message handler
    handle_update_message();  // Call update message handler

    $table_name = $wpdb->prefix . 'contact_messages';
    $results = $wpdb->get_results("SELECT * FROM $table_name");

    echo '<div class="wrap"><h2>Contact Messages</h2>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>ID</th><th>Email</th><th>Message</th><th>Submitted At</th><th>Actions</th></tr></thead>';
    echo '<tbody>';
    foreach ($results as $row) {
        echo '<tr>';
        echo '<td>' . esc_html($row->id) . '</td>';
        echo '<td>' . esc_html($row->email) . '</td>';
        echo '<td>' . esc_html($row->message) . '</td>';
        echo '<td>' . esc_html($row->submitted_at) . '</td>';
        echo '<td>';
        echo '<a href="?page=contact-message-manager&edit=' . esc_attr($row->id) . '" class="button">Edit</a> ';
        echo '<a href="?page=contact-message-manager&delete=' . esc_attr($row->id) . '" 
    class="button" style="color: red;" 
    onclick="return confirm(\'Are you sure you want to delete this message?\');">Delete</a>';
        echo '</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}
?>
