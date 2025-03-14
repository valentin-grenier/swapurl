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

    <p>Upload your JSON file below:</p>

    <form action="<?php echo admin_url('admin-post.php'); ?>" method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('swapurl_upload_action', 'swapurl_nonce'); ?>
        <input type="hidden" name="action" value="swapurl_upload">
        <input type="file" name="swapurl_json_file">
        <input type="submit" value="Upload JSON File" class="button-primary">
    </form>
</div>