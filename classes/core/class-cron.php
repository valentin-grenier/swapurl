<?php

/**
 * Class SWAPURL_Cron
 * 
 * Handles the scheduling and execution of the URL replacement process via WP Cron.
 */
class SWAPURL_Cron
{
    private $logger;
    private $lock_file;

    public function __construct()
    {
        $this->logger = new SWAPURL_Logger();
        $this->lock_file = SWAPURL_JSON_DIR . '/swapurl.lock';

        add_action('swapurl_process_cron', array($this, 'process_cron_job'));
    }


    # Schedule the cron job to run 5 seconds after file upload
    public function schedule_cron()
    {
        if (!wp_next_scheduled('swapurl_process_cron')) {
            wp_schedule_single_event(time() + 5, 'swapurl_process_cron');
            $this->logger->write_log("Cron job scheduled successfully.");
        }
    }

    # Process the cron job for URL replacements
    public function process_cron_job()
    {
        # Prevent multiple jobs from running simultaneously
        if (file_exists($this->lock_file)) {
            $this->logger->log_error("Cron job skipped: Another process is already running.");
            return;
        }

        # Create a lock file to prevent concurrent executions
        $this->create_lock_file();

        $json_files = glob(SWAPURL_JSON_DIR . '/*.json');

        if (empty($json_files)) {
            $this->remove_lock_file(); # Remove the lock file if no JSON files are found
            $this->logger->log_error("No JSON files found for processing.");
            return;
        }

        # Process each JSON file
        $processor = new SWAPURL_Processor($json_files[0]);
        $processor->process_replacements();

        # Remove the lock file after processing
        $this->remove_lock_file();
        $this->logger->write_log("Cron job completed successfully.");
    }

    # Create lock file to prevent concurrent executions
    public function create_lock_file()
    {
        file_put_contents($this->lock_file, time());
    }

    # Remove lock file after Processing
    public function remove_lock_file()
    {
        unlink($this->lock_file);
    }
}
