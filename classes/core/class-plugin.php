<?php

/**
 * Set up core plugin functionality
 * Ensure required directories exist
 * Register activation and deactivation hooks
 */

class SWAPURL_Plugin
{
    public function __construct()
    {
        $this->init();
    }

    private function init()
    {
        # Ensure the json-files directory exists
        if (!file_exists(SWAPURL_JSON_DIR)) {
            mkdir(SWAPURL_JSON_DIR, 0755, true);
        }

        # Register activation and deactivation hooks
        register_activation_hook(SWAPURL_PLUGIN_DIR . 'swapurl.php', array($this, 'activation_hook'));

        # Autoload dependencies if they exist
        if (file_exists(SWAPURL_PLUGIN_DIR . 'vendor/autoload.php')) {
            require_once SWAPURL_PLUGIN_DIR . 'vendor/autoload.php';
        }
    }

    public function activation_hook()
    {
        # Ensure the json-files directory exists
        if (!file_exists(SWAPURL_JSON_DIR)) {
            mkdir(SWAPURL_JSON_DIR, 0755, true);
        }
    }
}
