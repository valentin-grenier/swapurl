<?php

/**
 * 
 */

class SWAPURL_Admin
{
    private $logger;

    public function __construct()
    {
        $this->logger = new SWAPURL_Logger();
        add_action('admin_menu', array($this, 'add_admin_page'));
        add_action('admin_post_swapurl_upload', array($this, 'handle_file_upload'));
        add_action('admin_post_swapurl_clear_logs', array($this, 'clear_logs'));
        add_action('admin_post_swapurl_test', array($this, 'run_tests'));
    }

    public function add_admin_page()
    {
        add_menu_page(
            'SwapURL',
            'SwapURL',
            'manage_options',
            'swapurl',
            array($this, 'render_admin_page'),
            'dashicons-randomize',
        );
    }

    public function render_admin_page()
    {
        require_once SWAPURL_PLUGIN_DIR . 'views/admin-page.php';
    }

    public function handle_file_upload()
    {
        # Check if user has permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'swapurl'));
        }

        # Check if nonce is valid
        if (!isset($_POST['swapurl_nonce']) || !wp_verify_nonce($_POST['swapurl_nonce'], 'swapurl_upload_action')) {
            wp_die(__('Nonce verification failed.', 'swapurl'));
        }

        # Check if file was uploaded
        if (!isset($_FILES['swapurl_json_file']) || $_FILES['swapurl_json_file']['error'] != 0) {
            wp_die(__('Error uploading file. Please try again.', 'swapurl'));
        }

        # Check if file is a JSON file
        $json_file_path = $_FILES['swapurl_json_file']['tmp_name'];
        $json_validation_result = $this->validate_json_file($json_file_path);

        if (!$json_validation_result) {
            wp_die($json_validation_result);
        }

        # Get uploaded file
        $uploaded_file = $_FILES['swapurl_json_file'];
        $destination = SWAPURL_JSON_DIR . '/' . $uploaded_file['name'];

        # Move uploaded file to json-files directory and check if a file with the same name already exists
        if (file_exists($destination)) {
            wp_die(__('A file with the same name already exists. Please rename your file and try again.', 'swapurl'));
        }

        if (!move_uploaded_file($uploaded_file['tmp_name'], $destination)) {
            wp_die(__('Failed to save uploaded file.'));
        }

        # Schedule the cron job to run 5 seconds after file upload
        $cron = new SWAPURL_Cron();
        $cron->schedule_cron();

        # Redirect to the admin page with a success message
        wp_redirect(admin_url('admin.php?page=swapurl&upload_success=1'));
        exit;
    }

    private function validate_json_file($file_path)
    {
        $json_data = file_get_contents($file_path);
        $data = json_decode($json_data, true);

        # Check if file is a JSON file
        if ($data === null) {
            return "Invalid JSON format.";
        }

        # Check each entry in the JSON file for required keys
        foreach ($data as $index => $entry) {
            if (!is_array($entry) || !isset($entry['old_url']) || !isset($entry['new_url'])) {
                wp_die(__("Unexpected key name found in entry #$index. Each entry must contain \"old_url\" and \"new_url\" keys.", 'swapurl'));
            }

            # Ensure no extra keys exist
            if (count(array_keys($entry)) !== 2) {
                return "Unexpected keys found in entry #$index. Only 'old_url' and 'new_url' are allowed.";
            }
        }

        return true;
    }

    public function clear_logs()
    {
        # Check if user has permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'swapurl'));
        }

        # Check if nonce is valid
        if (!isset($_POST['swapurl_nonce']) || !wp_verify_nonce($_POST['swapurl_nonce'], 'swapurl_clear_logs_action')) {
            wp_die(__('Nonce verification failed.', 'swapurl'));
        }

        $this->logger->clear_logs();
        wp_redirect(admin_url('admin.php?page=swapurl&logs_cleared=1'));
        exit;
    }

    public function run_tests()
    {
        # Check if user has permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'swapurl'));
        }

        # Check if nonce is valid
        if (!isset($_POST['swapurl_nonce']) || !wp_verify_nonce($_POST['swapurl_nonce'], 'swapurl_test_action')) {
            wp_die(__('Nonce verification failed.', 'swapurl'));
        }

        $processor = new SWAPURL_Processor(SWAPURL_JSON_DIR . '/test.json');
        $result = $processor->process_replacements();

        if ($result['success_count'] === $result['total_count']) {
            wp_redirect(admin_url('admin.php?page=swapurl&tests_passed=1'));
        } else {
            wp_redirect(admin_url('admin.php?page=swapurl&tests_failed=1'));
        }

        exit;
    }
}
