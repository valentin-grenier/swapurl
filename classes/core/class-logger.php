<?php

/**
 * Class SWAPURL_Logger
 * 
 * This class handles logging for the SwapURL plugin.
 * It stores logs of successful URL replacements and errors,
 * and provides a method to retrieve logs for display in the admin panel.
 */
class SWAPURL_Logger
{
    private $log_file;

    public function __construct()
    {
        $this->log_file = SWAPURL_PLUGIN_DIR . 'logs/swapurl.log';

        # Ensure logs directory exists
        if (!file_exists(SWAPURL_PLUGIN_DIR . 'logs')) {
            mkdir(SWAPURL_PLUGIN_DIR . 'logs', 0755, true);
        }
    }

    # Log a successful URL replacement
    public function log_success($old_url, $new_url, $updated_rows)
    {
        $message = sprintf("[✅] Replaced '%s' with '%s' in %d posts.", $old_url, $new_url, $updated_rows);
        $this->write_log($message);
    }

    # Log an error
    public function log_error($error_message, $data = null)
    {
        $message = "[⚠️] " . $error_message;
        if ($data) {
            $message .= " - " . json_encode($data);
        }
        $this->write_log($message);
    }

    # Write log message to log file
    public function write_log($message)
    {
        $timestamp = date("Y-m-d H:i:s");
        file_put_contents($this->log_file, "[$timestamp] " . $message . PHP_EOL, FILE_APPEND);
    }

    # Get logs from log file
    public function get_logs()
    {
        if (!file_exists($this->log_file)) {
            return [];
        }
        return file($this->log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }

    # Clear log file
    public function clear_logs()
    {
        if (file_exists($this->log_file)) {
            file_put_contents($this->log_file, '');
        }
    }
}
