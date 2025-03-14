# SwapURL Plugin (Under Development)

SwapURL is a WordPress plugin that allows administrators to replace specific URLs in post content based on a JSON file. This ensures that outdated links are updated efficiently across all posts.

## Features

-   [x] Admin interface for uploading JSON files containing old and new URLs.
-   [ ] Automated processing via WP Cron to replace URLs in post content.
-   [ ] Logging of successful replacements and errors.
-   [ ] Secure file handling with nonce verification.
-   [ ] Simple progress tracking.

## Installation

1. Upload the `swapurl` plugin to the `wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to **SwapURL** in the WordPress admin menu.
4. Upload a JSON file containing URL mappings.

## JSON File Format

The JSON file should be an array of objects, each containing:

```json
[
	{
		"old_url": "https://old-url.com",
		"new_url": "https://new-url.com"
	},
	{
		"old_url": "https://another-old-url.com",
		"new_url": "https://another-new-url.com"
	}
]
```

## How It Works

1. Upload a JSON file via the **SwapURL** admin page.
2. A cron job runs 15 seconds after the successful upload.
3. The plugin searches `post_content` in `wp_posts` and replaces old URLs with the new ones.
4. Logs are stored and displayed on the admin page.

## Security & Validation

-   Nonce verification prevents unauthorized uploads.
-   Uploaded JSON files are validated to ensure they contain only `to_replace` and `new_url` keys.

## Uninstallation

-   Deactivating the plugin will stop processing but retain logs and settings.
-   Manually delete uploaded JSON files from `wp-content/json-files/` if necessary.

## Author

Developed by [Valentin Grenier](valentingrenier.fr), freelance WordPress Developer from [Studio Val](studio-val.fr).
