<?php
/**
 * Plugin Name: LC After Dark Screensaver
 * Description: A manual + automatic After Dark-style screensaver for WordPress admins.
 * Version: 2.3
 * Author: LiveCanvas
 * Author URI: https://www.livecanvas.com
 */

if (!defined('ABSPATH')) exit;

const LC_SCREENSAVERS = [
    'flying-toasters', 'fish', 'globe', 'hard-rain', 'bouncing-ball',
    'warp', 'messages', 'messages2', 'fade-out', 'logo', 'rainstorm', 'spotlight'
];

// ==========================
// ADMIN MENU + PAGE
// ==========================
add_action('admin_menu', function () {
    add_menu_page(
        'After Dark Screensaver',
        'After Dark',
        'manage_options',
        'lc-screensaver',
        'lc_screensaver_admin_page',
        'dashicons-desktop',
        81
    );
});

function lc_screensaver_admin_page() {
    $current = get_option('lc_screensaver_default', 'flying-toasters');
    $current_url = add_query_arg('trigger_after_dark', $current, home_url('/'));

    echo '<div class="wrap" style="max-width:900px;font-size:16px;margin-top:30px">';
    echo '<h1 style="display:flex;align-items:center;gap:10px;">💾 After Dark Screensaver</h1>';

    echo '<p><strong>Relive the 90s screen magic.</strong> This plugin is a tribute to the legendary <em>After Dark</em> screensavers — the original animated time-wasters that turned idle monitors into interactive art.</p>';

    echo '<hr style="margin:30px 0;">';

    echo '<h2>⚙️ Settings</h2>';
    echo '<form method="post">';
    wp_nonce_field('lc_screensaver_settings');

    echo '<p><strong>Select your favorite screensaver:</strong></p>';
    echo '<div style="margin:20px 0;padding:15px;background:#f9f9f9;border:1px solid #ddd;border-radius:6px">';
    foreach (LC_SCREENSAVERS as $type) {
        echo '<label style="display:block;margin-bottom:8px">';
        echo '<input type="radio" name="screensaver" value="' . esc_attr($type) . '" ' . checked($current, $type, false) . '> ';
        echo ucwords(str_replace('-', ' ', $type));
        echo '</label>';
    }
    echo '</div>';

    echo '<p>';
    echo '<input type="submit" class="button button-primary" value="Save Settings"> ';
    echo '<a href="' . esc_url($current_url) . '" class="button button-secondary" target="_blank">🎬 Preview Screensaver</a>';
    echo '</p>';
    echo '</form>';

    echo '<hr style="margin:30px 0;">';

    echo '<h2>⚡ Zero Performance Impact</h2>';
    echo '<p>This plugin is fully dormant until manually triggered or idle timeout is reached. It injects nothing on page load and runs only for logged-in admins. Lightweight by design.</p>';

    echo '<h2>🎨 Powered by <code>after-dark-css</code></h2>';
    echo '<p>The visuals are provided by the amazing <a href="https://github.com/bryanbraun/after-dark-css" target="_blank"><strong>after-dark-css</strong></a> project by Bryan Braun — a pure HTML+CSS re-creation of classic screensavers like <em>Flying Toasters</em>, <em>Fish</em>, <em>Hard Rain</em> and more.</p>';

    echo '<h2>🙌 Why We Made It</h2>';
    echo '<p>This plugin is a side project built for fun by the team at <a href="https://livecanvas.com" target="_blank"><strong>LiveCanvas</strong></a>. It’s open source, nostalgia-fueled, and meant to bring a smile to your WP admin sessions.</p>';

    echo '</div>';
}

add_action('admin_init', function () {
    if (isset($_POST['screensaver']) && current_user_can('manage_options') && check_admin_referer('lc_screensaver_settings')) {
        $type = sanitize_text_field($_POST['screensaver']);
        if (in_array($type, LC_SCREENSAVERS)) {
            update_option('lc_screensaver_default', $type);
        }
    }
});

// ==========================
// ADMIN BAR
// ==========================
add_action('admin_bar_menu', function ($bar) {
    if (!current_user_can('manage_options')) return;
    $default = get_option('lc_screensaver_default', 'flying-toasters');

    $bar->add_node([
        'id'    => 'lc-screensaver',
        'title' => '💾 Screen Saver',
        'href'  => add_query_arg('trigger_after_dark', $default, home_url('/')),
    ]);

    foreach (LC_SCREENSAVERS as $type) {
        $bar->add_node([
            'id'     => 'lc-screensaver-' . $type,
            'parent' => 'lc-screensaver',
            'title'  => ucwords(str_replace('-', ' ', $type)),
            'href'   => add_query_arg('trigger_after_dark', $type, home_url('/')),
        ]);
    }
}, 1000);

// ==========================
// JS INJECTION
// ==========================
function lc_after_dark_inject_script() {
    if (!current_user_can('manage_options')) return;

    $requested = sanitize_text_field($_GET['trigger_after_dark'] ?? '');
    $default = get_option('lc_screensaver_default', 'flying-toasters');
    $type = in_array($requested, LC_SCREENSAVERS) ? $requested : $default;

    $url_base = plugin_dir_url(__FILE__) . 'after-dark-css-gh-pages/all/';
    $iframe_url = esc_url($url_base . $type . '.html');

    $idle_minutes = intval(get_option('lc_idle_minutes', 0));
    $idle_delay = ($idle_minutes > 0) ? $idle_minutes * 60 * 1000 : 999999999;
    ?>
    <script id="lc-screen-saver">
        (() => {
            const iframeURL = <?php echo json_encode($iframe_url); ?>;
            const idleDelay = <?php echo $idle_delay; ?>;
            let iframe = null;
            let timeoutId;
            let isManualTrigger = new URLSearchParams(location.search).has('trigger_after_dark');
            let hasShownOnce = false;

            const showScreensaver = () => {
                if (iframe || hasShownOnce) return;
                hasShownOnce = true;

                iframe = document.createElement('iframe');
                iframe.src = iframeURL;
                iframe.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;z-index:9999999;border:none;background:#000;';
                document.body.appendChild(iframe);

                const remove = () => {
                    if (iframe) iframe.remove();
                    iframe = null;
                    hasShownOnce = false;
                    if (!isManualTrigger) resetTimer();
                };

                iframe.onload = () => {
                    try {
                        const win = iframe.contentWindow;
                        win.addEventListener('click', remove);
                        win.addEventListener('keydown', remove);
                    } catch {
                        document.addEventListener('click', remove, { once: true });
                    }
                };
            };

            const resetTimer = () => {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(showScreensaver, idleDelay);
            };

            ['mousemove', 'keydown', 'click'].forEach(e =>
                document.addEventListener(e, () => {
                    if (!iframe) resetTimer();
                })
            );

            if (isManualTrigger) {
                showScreensaver();
            } else {
                resetTimer();
            }
        })();
    </script>
    <?php
}
add_action('wp_footer', 'lc_after_dark_inject_script');
add_action('admin_footer', 'lc_after_dark_inject_script');
