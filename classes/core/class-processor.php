<?php

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

                # Analyze URL structure
                $structure = $this->analyze_url_structure($old_url);
                if ($structure['has_category']) {
                    # Update category
                    $old_slug = $structure['post_slug'];
                    $new_category_slug = $this->extract_category_slug($new_url);

                    if (!$old_slug || !$new_category_slug) {
                        $this->logger->log_error("Could not extract slugs", ['old' => $old_url, 'new' => $new_url]);
                        continue;
                    }

                    $updated_post_category = $this->update_post_category_by_slug($old_slug, $new_category_slug);
                    if (!$updated_post_category) {
                        $this->logger->log_error("Not found", $old_url);
                        continue;
                    }

                    # Replace post slug
                    $updated_post_slug = $this->replace_post_slug($old_url, $new_url);

                    if ($updated_post_category && $updated_post_slug) {
                        $success_count++;
                        $this->logger->write_log("✅ Successfully updated {$old_url} to {$new_url}");
                    } else {
                        $this->logger->log_error("Not found", $old_url);
                    }
                } else {
                    # Check if old URL is found in post content
                    $match = $wpdb->get_var(
                        $wpdb->prepare(
                            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_content LIKE %s",
                            '%' . $wpdb->esc_like($old_url) . '%'
                        )
                    );

                    if (!$match) {
                        $this->logger->log_error("Not found", $old_url);
                        continue;
                    }

                    # Replace URLs in post content
                    $updated_post_content = $this->replace_url_in_post($old_url, $new_url);

                    if ($updated_post_content) {
                        $success_count++;
                        $this->logger->write_log("✅ Successfully replaced {$old_url} with {$new_url}");
                    } else {
                        $this->logger->log_error("Failed to replace URL in post content", $old_url);
                    }
                }
            }

            sleep(1);
        }

        # Log completion
        $this->logger->write_log("Processed {$success_count} URLs.");

        # Remove JSON file if it's the test file
        if (basename($this->json_file) !== 'test.json') {
            $this->remove_json_file();
        }

        # Return success and total count
        return [
            'success_count' => $success_count,
            'total_count'   => $total_count,
        ];
    }

    private function replace_url_in_post($old_url, $new_url)
    {
        global $wpdb;

        return $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, %s, %s) WHERE post_content LIKE %s",
                $old_url,
                $new_url,
                '%' . $wpdb->esc_like($old_url) . '%'
            )
        );
    }

    private function replace_post_slug($old_url, $new_url)
    {
        global $wpdb;

        $old_slug = $this->extract_post_slug($old_url);
        $new_slug = $this->extract_post_slug($new_url);

        if (!$old_slug || !$new_slug) {
            $this->logger->log_error("Could not extract slugs", ['old' => $old_url, 'new' => $new_url]);
            return false;
        }

        return $wpdb->update(
            $wpdb->posts,
            ['post_name' => $new_slug],
            ['post_name' => $old_slug],
            ['%s'],
            ['%s']
        );
    }

    private function update_post_category_by_slug($post_slug, $new_category_slug)
    {
        $post = $this->get_post_by_slug($post_slug);
        if (!$post) {
            return false;
        }

        $category = get_category_by_slug($new_category_slug);
        if (!$category) {
            $this->logger->log_error("New category not found", $new_category_slug);
            return false;
        }

        wp_set_post_categories($post->ID, [$category->term_id]);

        return true;
    }

    private function get_post_by_slug($slug)
    {
        $query = new WP_Query([
            'name'           => $slug,
            'post_type'      => 'post',
            'post_status'    => 'any',
            'posts_per_page' => 1,
        ]);

        return $query->have_posts() ? $query->posts[0] : null;
    }

    private function extract_post_slug($url)
    {
        $path = trim(parse_url($url, PHP_URL_PATH), '/');
        $segments = explode('/', $path);
        return end($segments);
    }

    private function extract_category_slug($url)
    {
        $path = trim(parse_url($url, PHP_URL_PATH), '/');
        $segments = explode('/', $path);
        return $segments[count($segments) - 2] ?? null;
    }

    private function analyze_url_structure($url)
    {
        $url_path = trim(parse_url($url, PHP_URL_PATH), '/');
        $segments = explode('/', $url_path);
        $count = count($segments);

        return [
            'has_category'  => $count > 1,
            'category_slug' => $segments[$count - 2] ?? null,
            'post_slug'     => $segments[$count - 1] ?? null,
            'segment_count' => $count,
        ];
    }

    private function remove_json_file()
    {
        if (file_exists($this->json_file)) {
            unlink($this->json_file);
        }
    }
}
