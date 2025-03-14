<?php

/**
 * Class SWAPURL_Processor
 * 
 * This class handles the processing of URL replacements within post content.
 * It reads a JSON file containing old and new URLs, iterates through the posts,
 * and replaces occurrences of old URLs with the new ones.
 * 
 * The class also logs successes and errors for tracking purposes.
 */

class SWAPURL_Processor
{
    private $json_file;
    private $logger;

    public function __construct($json_file)
    {
        $this->json_file = $json_file;
        $this->logger = new SWAPURL_Logger();
    }

    public function process_replacements()
    {
        if (!file_exists($this->json_file)) {
            $this->logger->log_error("File not found", $this->json_file);
            return;
        }

        $json_data = file_get_contents($this->json_file);
        $url_mappings = json_decode($json_data, true);

        if (!is_array($url_mappings)) {
            $this->logger->log_error("Invalid JSON format", $this->json_file);
            return;
        }

        # Loop through the URL mappings
        global $wpdb;
        $success_count = 0;
        $total_count = count($url_mappings);

        foreach ($url_mappings as $mapping) {
            if (!isset($mapping['old_url']) || !isset($mapping['new_url'])) {
                $this->logger->log_error("Invalid URL mapping", $mapping);
                continue;
            }

            $old_url = esc_url_raw($mapping['old_url']);
            $new_url = esc_url_raw($mapping['new_url']);

            $updated_rows = $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, %s, %s) WHERE post_content LIKE %s",
                    $old_url,
                    $new_url,
                    '%' . $wpdb->esc_like($old_url) . '%'
                )
            );

            if ($updated_rows) {
                $success_count += $updated_rows;
                $this->logger->log_success($old_url, $new_url, $updated_rows);
            } else {
                $this->logger->log_error("Database update failed", $mapping);
            }

            return array(
                'success_count' => $success_count,
                'total_count' => $total_count,
            );
        }
    }
}
