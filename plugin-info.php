<?php
add_action('admin_menu', function () {
    add_menu_page(
        'After Dark Screensaver',
        'After Dark',
        'manage_options',
        'after-dark-info',
        'lc_after_dark_info_page',
        'dashicons-desktop',
        81
    );
});


function lc_after_dark_info_page() {
    // Save setting if form was submitted
    if (isset($_POST['lc_idle_minutes'])) {
        check_admin_referer('lc_after_dark_settings');
        $idle_minutes = max(0, intval($_POST['lc_idle_minutes']));
        update_option('lc_idle_minutes', $idle_minutes);
        echo '<div class="updated notice is-dismissible"><p>Settings saved.</p></div>';
    }

    // Read current setting value
    $current_idle_minutes = intval(get_option('lc_idle_minutes', 0));

    // Screensaver demo types
    $types = ['flying-toasters', 'fish', 'globe', 'hard-rain', 'bouncing-ball', 'warp', 'messages', 'messages2', 'fade-out', 'logo', 'rainstorm', 'spotlight'];

    ?>
    <div class="wrap" style="max-width: 900px; font-size: 16px;">
        <h1 style="display: flex; align-items: center; gap: 10px;">
            💾 After Dark Screensaver
        </h1>

        <p>
            <strong>Relive the 90s screen magic.</strong> This plugin is a tribute to the legendary <em>After Dark</em> screensavers — the original animated time-wasters
            that turned idle monitors into interactive art.
        </p>

        <hr style="margin: 30px 0;" />

        <!-- Settings section -->
        <h2>⚙️ Settings</h2>
        <form method="post">
            <?php wp_nonce_field('lc_after_dark_settings'); ?>
            <label for="lc_idle_minutes"><strong>Number of idle minutes before triggering screen saver (0 = never)</strong></label><br>
            <input type="number" id="lc_idle_minutes" name="lc_idle_minutes" value="<?php echo esc_attr($current_idle_minutes); ?>" min="0" style="width: 80px; margin-top: 5px;">
            <p class="description">Default: 0 (never trigger automatically).</p>
            <p><button type="submit" class="button button-primary">Save Settings</button></p>
        </form>

        <hr style="margin: 30px 0;" />

        <!-- Screensaver demo links -->
        <h2>🎬 Screensaver Demos</h2>
        <p>Click to launch the screensaver:</p>
        <ul style="columns: 2; -webkit-columns: 2; -moz-columns: 2; line-height: 1.7;">
            <?php foreach ($types as $type): ?>
                <li>
                    <a href="<?php echo esc_url(add_query_arg('trigger_after_dark', $type, home_url('/'))); ?>" target="_blank">
                        <?php echo esc_html(ucwords(str_replace('-', ' ', $type))); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <hr style="margin: 40px 0;" />

        <!-- Keep original info content -->
        <h2>⚡ Zero Performance Impact</h2>
        <p>
            This plugin is fully dormant until manually triggered or idle timeout is reached. It injects nothing on page load and
            runs only for logged-in admins. Lightweight by design.
        </p>

        <h2>🎨 Powered by <code>after-dark-css</code></h2>
        <p>
            The visuals are provided by the amazing
            <a href="https://github.com/bryanbraun/after-dark-css" target="_blank"><strong>after-dark-css</strong></a> project by Brian Brown —
            a pure HTML+CSS re-creation of classic screensavers like <em>Flying Toasters</em>, <em>Fish</em>, <em>Hard Rain</em> and more.
        </p>

        <h2>🙌 Why We Made It</h2>
        <p>
            This plugin is a side project built for fun by the team at  <strong>  <a href="https://livecanvas.com" target="_blank"  >LiveCanvas</a> </strong>.
                 It’s open source, nostalgia-fueled,
            and meant to bring a smile to your WP admin sessions.
        </p>
    </div>
    <?php
}

