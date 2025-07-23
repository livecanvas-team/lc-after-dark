<?php
/**
 * Plugin Name: LC After Dark Screensaver
 * Description: A manual + automatic After Dark-style screensaver for WordPress admins.
 * Version: 2.0
 * Author: LiveCanvas
 * Author URI: https://www.livecanvas.com
 */

// Require info page
require_once plugin_dir_path(__FILE__) . 'plugin-info.php';

// Admin Bar Menu
add_action('admin_bar_menu', function ($wp_admin_bar) {
    if (!current_user_can('manage_options')) return;

    $types = ['flying-toasters', 'fish', 'globe', 'hard-rain', 'bouncing-ball', 'warp', 'messages', 'messages2', 'fade-out', 'logo', 'rainstorm', 'spotlight'];
    $default_type = 'flying-toasters';

    $wp_admin_bar->add_node([
        'id'    => 'lc-screensaver',
        'title' => '💾 Screen Saver',
        'href'  => add_query_arg('trigger_after_dark', $default_type, home_url('/')),
    ]);

    foreach ($types as $type) {
        $wp_admin_bar->add_node([
            'id'     => 'lc-screensaver-' . $type,
            'parent' => 'lc-screensaver',
            'title'  => ucwords(str_replace('-', ' ', $type)),
            'href'   => add_query_arg('trigger_after_dark', $type, home_url('/')),
        ]);
    }
}, 1000);

// JS injection on frontend & backend
$inject_screensaver = function () {
    if (!current_user_can('manage_options')) return; // only for admins

    $triggered_type = sanitize_text_field($_GET['trigger_after_dark'] ?? '');
    $default_type = 'flying-toasters';
    $valid_types = ['flying-toasters', 'fish', 'globe', 'hard-rain', 'bouncing-ball', 'warp', 'messages', 'messages2', 'fade-out', 'logo', 'rainstorm', 'spotlight'];
    $type = in_array($triggered_type, $valid_types) ? $triggered_type : $default_type;

    $url_base = plugin_dir_url(__FILE__) . 'after-dark-css-gh-pages/all/';
    $screenSaverURL = esc_url($url_base . $type . '.html');

    // Read idle_minutes option
    $idle_minutes = intval(get_option('lc_idle_minutes', 0));
    $idle_delay_ms = ($idle_minutes > 0) ? $idle_minutes * 60 * 1000 : 999999999; // effectively disables if 0
    ?>
    <script id="lc-screen-saver">
        (() => {
            let iframe;
            const delay = <?php echo $idle_delay_ms; ?>;
            let timeoutId;

            function showScreensaver() {
                if (iframe) return;
                iframe = document.createElement("iframe");
                iframe.style.cssText = "position:fixed;top:0;left:0;width:100%;height:100%;z-index:9999999;border:none;background:#000;";
                iframe.src = "<?php echo esc_js($screenSaverURL); ?>";
                document.body.appendChild(iframe);

                iframe.onload = () => {
                    try {
                        iframe.contentWindow.focus();
                        const win = iframe.contentWindow;
                        const close = () => {
                            iframe.remove();
                            iframe = null;
                            resetTimer();
                        };
                        win.addEventListener("click", close);
                        win.addEventListener("keydown", close);
                    } catch (e) {
                        console.warn("Iframe event binding failed", e);
                        const fallback = () => {
                            iframe?.remove();
                            iframe = null;
                            resetTimer();
                        };
                        document.addEventListener("click", fallback, { once: true });
                    }
                };
            }

            function resetTimer() {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(showScreensaver, delay);
            }

            document.addEventListener("mousemove", resetTimer);
            document.addEventListener("keydown", resetTimer);
            document.addEventListener("click", resetTimer);

            resetTimer();
        })();
    </script>
    <?php
};

add_action('wp_footer', $inject_screensaver);
add_action('admin_footer', $inject_screensaver);
