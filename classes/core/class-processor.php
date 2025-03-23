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

        foreach (array_chunk($url_mappings, 10) as $batch) {
            foreach ($batch as $mapping) {
                if (!isset($mapping['old_url']) || !isset($mapping['new_url'])) {
                    $this->logger->log_error("Invalid URL mapping", $mapping);
                    continue;
                }

                $old_url = untrailingslashit(trim($mapping['old_url']));
                $new_url = untrailingslashit(trim($mapping['new_url']));

                # DEBUG: Check if old_url exists
                $match = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_content LIKE %s",
                        '%' . $wpdb->esc_like($old_url) . '%'
                    )
                );

                if (!$match) {
                    $this->logger->log_error("Not found. Skipping.", $old_url);
                    continue;
                } else {
                    $this->logger->write_log("✅ Found {$match} match(es) for '{$old_url}'");
                }

                # Attempt the replacement
                $updated_rows = $wpdb->query(
                    $wpdb->prepare(
                        "UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, %s, %s) WHERE post_content LIKE %s",
                        $old_url,
                        $new_url,
                        '%' . $wpdb->esc_like($old_url) . '%'
                    )
                );

                if ($updated_rows !== false && $updated_rows > 0) {
                    $success_count += $updated_rows;
                    $this->logger->log_success($old_url, $new_url, $updated_rows);
                } else {
                    $this->logger->log_error("Failed to update", $old_url);
                }
            }

            sleep(1); # Optional for large batches
        }

        $this->logger->write_log("✅ Processed {$success_count} URLs.");

        # Remove the JSON file after processing
        $this->remove_json_file();

        # Return the success and total counts
        return array(
            'success_count' => $success_count,
            'total_count' => $total_count,
        );
    }

    private function remove_json_file()
    {
        if (file_exists($this->json_file)) {
            unlink($this->json_file);
        }
    }
}
