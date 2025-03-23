<?php
$logger = new SWAPURL_Logger();
?>

<div class="wrap">
    <?php if (isset($_GET['upload_success']) && $_GET['upload_success']) {
        echo '<div class="notice notice-success is-dismissible"><p>File uploaded successfully.</p></div>';
    } ?>

    <h1>SwapURL</h1>
    <p>Upload a JSON file to replace URLs in post content.</p>

    <p>JSON file must be formatted as follows:</p>
    <pre>
        {
            "old_url": "https://example.com",
            "new_url": "https://new-example.com"
        }
    </pre>

    <!-- <h2>Test</h2>
    <form action="<?php echo admin_url('admin-post.php'); ?>" method="post">
        <?php wp_nonce_field('swapurl_test_action', 'swapurl_nonce'); ?>
        <input type="hidden" name="action" value="swapurl_test">
        <input type="submit" value="Run Tests" class="button-primary">
    </form> -->

    <p>Upload your JSON file below:</p>

    <form action="<?php echo admin_url('admin-post.php'); ?>" method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('swapurl_upload_action', 'swapurl_nonce'); ?>
        <input type="hidden" name="action" value="swapurl_upload">
        <input type="file" name="swapurl_json_file" accept="application/json">
        <input type="submit" value="Upload JSON File" class="button-primary">
    </form>

    <h2>Process Logs</h2>
    <form action="<?php echo admin_url('admin-post.php'); ?>" method="post">
        <?php wp_nonce_field('swapurl_clear_logs_action', 'swapurl_nonce'); ?>
        <input type="hidden" name="action" value="swapurl_clear_logs">
        <input type="submit" value="Clear Logs" class="button-secondary">
    </form>

    <pre style="background:#f5f5f5; padding:10px; border:1px solid #ddd; max-height:300px; overflow:auto; white-space: pre-line;">
        <?php
        $logs = $this->logger->get_logs();
        if (!empty($logs)) {
            echo implode("\n", $logs);
        } else {
            echo "No logs available.";
        }
        ?>
    </pre>
</div>