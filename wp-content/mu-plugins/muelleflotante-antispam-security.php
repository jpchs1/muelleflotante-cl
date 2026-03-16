<?php
/**
 * Plugin Name: Muelle Flotante - Anti-Spam & Security Maximum
 * Description: Proteccion maxima contra spam, virus, bots y ataques para muelleflotante.cl.
 *              Incluye: honeypot CF7, rate limiting, bloqueo de contenido sospechoso,
 *              sanitizacion de adjuntos, deshabilitacion de XML-RPC/pingbacks/trackbacks,
 *              proteccion de headers de email, y mas.
 * Version: 1.0
 * Author: Muelle Flotante
 */

if (!defined('ABSPATH')) exit;

// =============================================
// 1. DISABLE XML-RPC COMPLETELY
// =============================================
add_filter('xmlrpc_enabled', '__return_false');

add_filter('xmlrpc_methods', function ($methods) {
    return array();
});

add_filter('wp_headers', function ($headers) {
    unset($headers['X-Pingback']);
    return $headers;
});

remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'wp_generator');

// =============================================
// 2. DISABLE PINGBACKS & TRACKBACKS
// =============================================
add_filter('pings_open', '__return_false', 20, 2);
add_filter('pre_option_default_pingback_flag', '__return_zero');
add_filter('pre_option_default_ping_status', function () {
    return 'closed';
});

add_action('pre_ping', function (&$links) {
    $home = get_option('home');
    foreach ($links as $l => $link) {
        if (0 === strpos($link, $home)) {
            unset($links[$l]);
        }
    }
});

// =============================================
// 3. CF7 HONEYPOT ANTI-SPAM
// =============================================
add_filter('wpcf7_form_elements', function ($content) {
    $honeypot = '<div style="position:absolute;left:-9999px;top:-9999px;height:0;width:0;overflow:hidden;" aria-hidden="true">';
    $honeypot .= '<label for="muelleflotante_website_url">Website</label>';
    $honeypot .= '<input type="text" name="muelleflotante_website_url" id="muelleflotante_website_url" value="" tabindex="-1" autocomplete="off" />';
    $honeypot .= '<input type="text" name="muelleflotante_phone_confirm" id="muelleflotante_phone_confirm" value="" tabindex="-1" autocomplete="off" />';
    $honeypot .= '</div>';
    return $content . $honeypot;
});

add_filter('wpcf7_spam', function ($spam, $submission) {
    if ($spam) return $spam;

    if (!empty($_POST['muelleflotante_website_url']) || !empty($_POST['muelleflotante_phone_confirm'])) {
        $submission->add_spam_log(array(
            'agent'  => 'muelleflotante_honeypot',
            'reason' => 'Honeypot field was filled - bot detected.',
        ));
        return true;
    }

    return $spam;
}, 5, 2);

// =============================================
// 4. CF7 RATE LIMITING (anti-flood)
// =============================================
add_filter('wpcf7_spam', function ($spam, $submission) {
    if ($spam) return $spam;

    $ip = muelleflotante_get_client_ip();
    $transient_key = 'muelleflotante_cf7_rate_' . md5($ip);
    $submissions = get_transient($transient_key);

    if ($submissions === false) {
        $submissions = 0;
    }

    if ($submissions >= 3) {
        $submission->add_spam_log(array(
            'agent'  => 'muelleflotante_rate_limit',
            'reason' => sprintf('Rate limit exceeded: %d submissions from IP %s in 10 minutes.', $submissions, $ip),
        ));
        return true;
    }

    set_transient($transient_key, $submissions + 1, 10 * MINUTE_IN_SECONDS);
    return $spam;
}, 6, 2);

// =============================================
// 5. CF7 SUSPICIOUS CONTENT BLOCKING
// =============================================
add_filter('wpcf7_spam', function ($spam, $submission) {
    if ($spam) return $spam;

    $posted_data = $submission->get_posted_data();
    $all_content = implode(' ', array_map(function ($v) {
        return is_array($v) ? implode(' ', $v) : $v;
    }, $posted_data));

    if (preg_match('/(\r|\n|%0a|%0d|bcc:|cc:|to:|content-type:|mime-version:|subject:)/i', $all_content)) {
        $submission->add_spam_log(array(
            'agent'  => 'muelleflotante_header_injection',
            'reason' => 'Email header injection attempt detected.',
        ));
        return true;
    }

    $url_count = preg_match_all('/https?:\/\//i', $all_content);
    if ($url_count > 3) {
        $submission->add_spam_log(array(
            'agent'  => 'muelleflotante_url_spam',
            'reason' => sprintf('Too many URLs in submission: %d found.', $url_count),
        ));
        return true;
    }

    $spam_keywords = array(
        'viagra', 'cialis', 'casino', 'poker', 'slot machine',
        'cryptocurrency investment', 'bitcoin trading', 'forex trading',
        'make money fast', 'earn money online', 'work from home opportunity',
        'nigerian prince', 'lottery winner', 'you have been selected',
        'click here now', 'act now', 'limited time offer',
        'free iphone', 'free macbook', 'congratulations you won',
        'weight loss pill', 'diet pill', 'enlargement',
        'russian bride', 'dating site', 'adult content',
        'SEO service', 'link building service', 'web traffic',
        'buy followers', 'buy likes', 'social media marketing',
        'hacking service', 'ddos service', 'malware',
        'ransomware', 'phishing', 'trojan',
    );

    $content_lower = mb_strtolower($all_content, 'UTF-8');
    foreach ($spam_keywords as $keyword) {
        if (strpos($content_lower, strtolower($keyword)) !== false) {
            $submission->add_spam_log(array(
                'agent'  => 'muelleflotante_keyword_spam',
                'reason' => sprintf('Spam keyword detected: "%s".', $keyword),
            ));
            return true;
        }
    }

    $latin_chars = preg_match_all('/[a-zA-Z\x{00C0}-\x{024F}]/u', $all_content);
    $cyrillic_chars = preg_match_all('/[\x{0400}-\x{04FF}]/u', $all_content);
    if ($cyrillic_chars > 0 && $cyrillic_chars > $latin_chars) {
        $submission->add_spam_log(array(
            'agent'  => 'muelleflotante_language_spam',
            'reason' => 'Submission primarily in Cyrillic script - likely spam.',
        ));
        return true;
    }

    return $spam;
}, 7, 2);

// =============================================
// 6. CF7 TIME-BASED SPAM DETECTION
// =============================================
add_filter('wpcf7_form_elements', function ($content) {
    $timestamp = '<input type="hidden" name="muelleflotante_form_time" value="' . esc_attr(time()) . '" />';
    return $content . $timestamp;
});

add_filter('wpcf7_spam', function ($spam, $submission) {
    if ($spam) return $spam;

    if (isset($_POST['muelleflotante_form_time'])) {
        $form_time = intval($_POST['muelleflotante_form_time']);
        $elapsed = time() - $form_time;

        if ($elapsed < 3 && $elapsed >= 0) {
            $submission->add_spam_log(array(
                'agent'  => 'muelleflotante_time_check',
                'reason' => sprintf('Form submitted too quickly: %d seconds (minimum 3).', $elapsed),
            ));
            return true;
        }
    }

    return $spam;
}, 4, 2);

// =============================================
// 7. SANITIZE EMAIL ATTACHMENTS
// =============================================
add_filter('wpcf7_file_validation', function ($result, $tag) {
    $file = isset($_FILES[$tag->name]) ? $_FILES[$tag->name] : null;
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        return $result;
    }

    $filename = strtolower($file['name']);
    $dangerous_extensions = array(
        '.exe', '.bat', '.cmd', '.com', '.cpl', '.dll', '.inf',
        '.js', '.jse', '.lnk', '.msc', '.msi', '.msp', '.mst',
        '.pif', '.ps1', '.ps2', '.reg', '.rgs', '.scr', '.sct',
        '.shb', '.shs', '.vb', '.vbe', '.vbs', '.wsc', '.wsf',
        '.wsh', '.ws', '.hta', '.jar', '.php', '.php3', '.php4',
        '.php5', '.phtml', '.py', '.rb', '.pl', '.cgi', '.asp',
        '.aspx', '.sh', '.bash', '.svg', '.swf',
    );

    foreach ($dangerous_extensions as $ext) {
        if (substr($filename, -strlen($ext)) === $ext) {
            $result->invalidate($tag, sprintf(
                'El tipo de archivo "%s" no esta permitido por razones de seguridad.',
                $ext
            ));
            break;
        }
    }

    if (preg_match('/\.[a-z0-9]+\.(exe|bat|cmd|com|scr|vbs|js|php|phtml|sh)$/i', $filename)) {
        $result->invalidate($tag, 'Nombre de archivo sospechoso detectado. Por favor, renombre el archivo.');
    }

    return $result;
}, 10, 2);

// =============================================
// 8. BLOCK SPAM COMMENTS
// =============================================
add_filter('comments_open', '__return_false', 20, 2);
add_filter('pings_open', '__return_false', 20, 2);

add_filter('comments_array', function ($comments) {
    return array();
}, 10, 2);

add_action('admin_bar_menu', function ($wp_admin_bar) {
    $wp_admin_bar->remove_node('comments');
}, 999);

// =============================================
// 9. PREVENT USER ENUMERATION VIA REST API
// =============================================
add_filter('rest_endpoints', function ($endpoints) {
    if (isset($endpoints['/wp/v2/users'])) {
        unset($endpoints['/wp/v2/users']);
    }
    if (isset($endpoints['/wp/v2/users/(?P<id>[\d]+)'])) {
        unset($endpoints['/wp/v2/users/(?P<id>[\d]+)']);
    }
    return $endpoints;
});

// =============================================
// 10. ADDITIONAL wp_mail SECURITY
// =============================================
add_filter('wp_mail', function ($args) {
    $args['subject'] = str_replace(array("\r", "\n", "%0a", "%0d"), '', $args['subject']);

    if (is_string($args['to'])) {
        $args['to'] = str_replace(array("\r", "\n", "%0a", "%0d"), '', $args['to']);
    }

    if (!empty($args['headers'])) {
        if (is_string($args['headers'])) {
            $args['headers'] = explode("\n", $args['headers']);
        }
        $clean_headers = array();
        foreach ($args['headers'] as $header) {
            $header = trim($header);
            if (empty($header)) continue;
            if (preg_match('/^(bcc|cc)\s*:/i', $header)) {
                if (strpos($header, '@muelleflotante.cl') !== false) {
                    $clean_headers[] = $header;
                }
            } else {
                $clean_headers[] = $header;
            }
        }
        $args['headers'] = $clean_headers;
    }

    return $args;
}, 1);

// =============================================
// 11. LOGIN SECURITY
// =============================================
add_filter('authenticate', function ($user, $username, $password) {
    if (empty($username) || empty($password)) {
        return $user;
    }

    $ip = muelleflotante_get_client_ip();
    $transient_key = 'muelleflotante_login_attempts_' . md5($ip);
    $attempts = get_transient($transient_key);

    if ($attempts === false) {
        $attempts = 0;
    }

    if ($attempts >= 5) {
        return new WP_Error(
            'muelleflotante_too_many_attempts',
            '<strong>Seguridad Muelle Flotante:</strong> Demasiados intentos fallidos. Por favor espere 30 minutos antes de intentar nuevamente.'
        );
    }

    return $user;
}, 30, 3);

add_action('wp_login_failed', function ($username) {
    $ip = muelleflotante_get_client_ip();
    $transient_key = 'muelleflotante_login_attempts_' . md5($ip);
    $attempts = get_transient($transient_key);

    if ($attempts === false) {
        $attempts = 0;
    }

    set_transient($transient_key, $attempts + 1, 30 * MINUTE_IN_SECONDS);
});

add_action('wp_login', function ($user_login, $user) {
    $ip = muelleflotante_get_client_ip();
    $transient_key = 'muelleflotante_login_attempts_' . md5($ip);
    delete_transient($transient_key);
}, 10, 2);

// =============================================
// 12. RESTRICT REST API FOR NON-AUTHENTICATED USERS
// =============================================
add_filter('rest_authentication_errors', function ($result) {
    if (true === $result || is_wp_error($result)) {
        return $result;
    }

    if (is_user_logged_in()) {
        return $result;
    }

    $allowed_routes = array(
        '/wp/v2/pages',
        '/wp/v2/posts',
        '/wp/v2/media',
        '/wp/v2/categories',
        '/wp/v2/tags',
        '/contact-form-7/',
        '/oembed/',
        '/elementor/',
        '/wc/',
        '/aioseo/',
        '/litespeed/',
    );

    $current_route = isset($GLOBALS['wp']->query_vars['rest_route'])
        ? $GLOBALS['wp']->query_vars['rest_route']
        : '';

    foreach ($allowed_routes as $route) {
        if (strpos($current_route, $route) !== false) {
            return $result;
        }
    }

    return new WP_Error(
        'rest_not_authorized',
        'REST API access restricted.',
        array('status' => 401)
    );
});

// =============================================
// 13. REMOVE UNNECESSARY WORDPRESS HEAD INFO
// =============================================
remove_action('wp_head', 'wp_generator');
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'wp_shortlink_wp_head');
remove_action('wp_head', 'rest_output_link_wp_head');
remove_action('wp_head', 'wp_oembed_add_discovery_links');
remove_action('wp_head', 'wp_resource_hints', 2);
remove_action('wp_head', 'feed_links', 2);
remove_action('wp_head', 'feed_links_extra', 3);

// =============================================
// HELPER FUNCTIONS
// =============================================
function muelleflotante_get_client_ip() {
    $ip_keys = array(
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_REAL_IP',
        'REMOTE_ADDR',
    );

    foreach ($ip_keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = $_SERVER[$key];
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }

    return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
}

// =============================================
// 14. LOG SPAM ATTEMPTS (for monitoring)
// =============================================
add_filter('wpcf7_spam', function ($spam) {
    if ($spam) {
        $ip = muelleflotante_get_client_ip();
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown';
        error_log(sprintf(
            '[Muelle Flotante AntiSpam] SPAM BLOCKED | IP: %s | UA: %s | Time: %s',
            $ip,
            substr($ua, 0, 100),
            current_time('mysql')
        ));
    }
    return $spam;
}, 99);
