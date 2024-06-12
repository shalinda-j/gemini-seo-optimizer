<?php
/*
Plugin Name: Gemini SEO Optimizer
Description: A plugin to automatically optimize content using the Gemini API.
Version: 2.0
Author: Your Name
*/

// Ensure no direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('GEMINI_SEO_OPTIMIZER_VERSION', '2.0');

// Add settings menu
function gemini_seo_optimizer_menu() {
    add_options_page(
        'Gemini SEO Optimizer Settings',
        'Gemini SEO Optimizer',
        'manage_options',
        'gemini-seo-optimizer',
        'gemini_seo_optimizer_settings_page'
    );
}
add_action('admin_menu', 'gemini_seo_optimizer_menu');

// Settings page content
function gemini_seo_optimizer_settings_page() {
    ?>
    <div class="wrap">
        <h1>Gemini SEO Optimizer Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('gemini_seo_optimizer_settings');
            do_settings_sections('gemini-seo-optimizer');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register settings
function gemini_seo_optimizer_register_settings() {
    register_setting('gemini_seo_optimizer_settings', 'gemini_api_key');

    add_settings_section(
        'gemini_seo_optimizer_main_section',
        'Main Settings',
        null,
        'gemini-seo-optimizer'
    );

    add_settings_field(
        'gemini_api_key',
        'Gemini API Key',
        'gemini_seo_optimizer_api_key_field',
        'gemini-seo-optimizer',
        'gemini_seo_optimizer_main_section'
    );
}
add_action('admin_init', 'gemini_seo_optimizer_register_settings');

function gemini_seo_optimizer_api_key_field() {
    $apiKey = get_option('gemini_api_key');
    echo '<input type="text" name="gemini_api_key" value="' . esc_attr($apiKey) . '" class="regular-text">';
}

// Gemini Client Class
class GeminiClient {
    private $apiKey;

    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }

    public function generateOptimizedContent($content) {
        // Mockup API call - replace with actual API call
        // Here you should implement the real HTTP request to the Gemini API
        if (empty($this->apiKey)) {
            throw new Exception("API Key is missing.");
        }

        // Simulate API response
        $response = new stdClass();
        $response->success = true; // Simulate a successful response
        $response->optimizedContent = strtoupper($content); // Example transformation

        if ($response->success) {
            return $response->optimizedContent;
        } else {
            throw new Exception("API request failed.");
        }
    }
}

// Function to optimize content
function optimizeContentWithGemini($content) {
    $startCode = "<!--start-->";
    $endCode = "<!--end-->";

    $pattern = "/".preg_quote($startCode, '/')."(.*?)".preg_quote($endCode, '/')."/s";
    preg_match($pattern, $content, $matches);

    if (isset($matches[1])) {
        $middleChord = $matches[1];

        try {
            $apiKey = get_option('gemini_api_key');
            $geminiClient = new GeminiClient($apiKey);
            $optimizedContent = $geminiClient->generateOptimizedContent($middleChord);

            $content = preg_replace($pattern, $startCode . $optimizedContent . $endCode, $content);

            // Log optimization
            error_log('Content optimized successfully for post ID: ' . get_the_ID());
        } catch (Exception $e) {
            error_log('Gemini SEO Optimizer error: ' . $e->getMessage());
        }
    }

    return $content;
}

// Hook into WordPress' content filter for display
add_filter('the_content', 'optimizeContentWithGemini');

// Optimize content when a post is saved
function optimizeContentOnSave($post_id) {
    if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
        return;
    }

    $post = get_post($post_id);
    $content = $post->post_content;

    $optimizedContent = optimizeContentWithGemini($content);

    if ($content !== $optimizedContent) {
        remove_action('save_post', 'optimizeContentOnSave');
        wp_update_post([
            'ID' => $post_id,
            'post_content' => $optimizedContent
        ]);
        add_action('save_post', 'optimizeContentOnSave');
    }
}
add_action('save_post', 'optimizeContentOnSave');
