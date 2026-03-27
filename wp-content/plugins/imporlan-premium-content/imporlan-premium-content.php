<?php
/**
 * Plugin Name: Imporlan Premium Content & Blog Carousel
 * Plugin URI: https://muelleflotante.cl
 * Description: Crea entrada premium SEO para Imporlan (lanchas usadas Chile) y carrusel de entradas del blog.
 * Version: 1.0.0
 * Author: Muelle Flotante
 * Text Domain: imporlan-premium
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

define('IPC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('IPC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('IPC_VERSION', '1.0.0');

class Imporlan_Premium_Content {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Activation hook
        register_activation_hook(__FILE__, [$this, 'activate']);

        // Frontend assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

        // Shortcodes
        add_shortcode('blog_carousel', [$this, 'render_blog_carousel']);
        add_shortcode('imporlan_cta', [$this, 'render_cta_button']);

        // SEO Schema markup
        add_action('wp_head', [$this, 'inject_schema_markup']);

        // Custom SEO meta for the premium post
        add_action('wp_head', [$this, 'inject_seo_meta']);

        // Add custom body class
        add_filter('body_class', [$this, 'add_body_class']);
    }

    /**
     * Plugin activation: create premium post
     */
    public function activate() {
        $existing = get_page_by_path('lanchas-usadas-chile-imporlan', OBJECT, 'post');
        if ($existing) return;

        $content = $this->get_premium_post_content();

        $post_id = wp_insert_post([
            'post_title'   => 'Lanchas Usadas en Chile: Imporlan, Tu Referente N°1 en Importaci&oacute;n y Venta',
            'post_name'    => 'lanchas-usadas-chile-imporlan',
            'post_content' => $content,
            'post_status'  => 'publish',
            'post_type'    => 'post',
            'post_author'  => 1,
            'post_excerpt' => 'Descubre por qu&eacute; Imporlan es el referente #1 en importaci&oacute;n y comercializaci&oacute;n de lanchas usadas en Chile. Modelos certificados, financiamiento flexible y entrega en todo el pa&iacute;s. Cotiza ahora.',
            'meta_input'   => [
                '_yoast_wpseo_title'            => 'Lanchas Usadas en Chile | Imporlan - Importador N°1 %%sep%% %%sitename%%',
                '_yoast_wpseo_metadesc'         => 'Imporlan: el referente #1 en importación y venta de lanchas usadas en Chile. Modelos certificados desde EE.UU., Japón y Europa. Financiamiento flexible. ¡Cotiza gratis!',
                '_yoast_wpseo_focuskw'          => 'lanchas usadas Chile',
                '_yoast_wpseo_canonical'        => home_url('/lanchas-usadas-chile-imporlan/'),
                '_yoast_wpseo_opengraph-title'  => 'Lanchas Usadas en Chile | Imporlan - El Importador Líder',
                '_yoast_wpseo_opengraph-description' => 'Importamos las mejores lanchas usadas del mundo para Chile. Certificación, garantía y financiamiento. Conoce nuestro catálogo.',
                '_yoast_wpseo_twitter-title'    => 'Lanchas Usadas en Chile | Imporlan N°1',
                '_yoast_wpseo_twitter-description' => 'El referente en importación de lanchas usadas en Chile. Modelos certificados y financiamiento flexible.',
                '_ipc_premium_post'             => '1',
            ],
        ]);

        if ($post_id && !is_wp_error($post_id)) {
            // Set categories
            $cat_id = wp_create_category('Lanchas Usadas');
            wp_set_post_categories($post_id, [$cat_id]);

            // Set tags
            wp_set_post_tags($post_id, [
                'lanchas usadas',
                'lanchas usadas Chile',
                'importar lanchas',
                'lanchas importadas',
                'comprar lancha usada',
                'venta lanchas Chile',
                'Imporlan',
                'lanchas americanas',
                'lanchas japonesas',
                'botes usados Chile',
                'embarcaciones usadas',
                'muelle flotante',
            ]);

            update_option('ipc_premium_post_id', $post_id);
        }
    }

    /**
     * Get premium post HTML content
     */
    private function get_premium_post_content() {
        ob_start();
        include IPC_PLUGIN_DIR . 'templates/premium-post-content.php';
        return ob_get_clean();
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_assets() {
        wp_enqueue_style(
            'ipc-premium-styles',
            IPC_PLUGIN_URL . 'assets/css/premium-landing.css',
            [],
            IPC_VERSION
        );

        wp_enqueue_style(
            'ipc-carousel-styles',
            IPC_PLUGIN_URL . 'assets/css/blog-carousel.css',
            [],
            IPC_VERSION
        );

        wp_enqueue_script(
            'ipc-carousel-js',
            IPC_PLUGIN_URL . 'assets/js/blog-carousel.js',
            [],
            IPC_VERSION,
            true
        );

        // Auto-carousel: convierte la sección de Elementor "Últimas entradas del blog" en carrusel
        wp_enqueue_style(
            'ipc-auto-carousel-styles',
            IPC_PLUGIN_URL . 'assets/css/auto-carousel.css',
            [],
            IPC_VERSION
        );

        wp_enqueue_script(
            'ipc-auto-carousel-js',
            IPC_PLUGIN_URL . 'assets/js/auto-carousel.js',
            [],
            IPC_VERSION,
            true
        );
    }

    /**
     * Blog Carousel Shortcode
     */
    public function render_blog_carousel($atts) {
        $atts = shortcode_atts([
            'posts'    => 6,
            'category' => '',
            'orderby'  => 'date',
            'order'    => 'DESC',
            'columns'  => 3,
            'autoplay' => 'true',
            'speed'    => 4000,
        ], $atts, 'blog_carousel');

        $args = [
            'post_type'      => 'post',
            'posts_per_page' => intval($atts['posts']),
            'orderby'        => sanitize_text_field($atts['orderby']),
            'order'          => sanitize_text_field($atts['order']),
            'post_status'    => 'publish',
        ];

        if (!empty($atts['category'])) {
            $args['category_name'] = sanitize_text_field($atts['category']);
        }

        $query = new WP_Query($args);

        if (!$query->have_posts()) {
            return '<p class="ipc-no-posts">No hay entradas disponibles.</p>';
        }

        $autoplay = $atts['autoplay'] === 'true' ? 'true' : 'false';
        $speed    = intval($atts['speed']);
        $columns  = intval($atts['columns']);

        ob_start();
        ?>
        <div class="ipc-carousel-wrapper" data-autoplay="<?php echo esc_attr($autoplay); ?>" data-speed="<?php echo esc_attr($speed); ?>" data-columns="<?php echo esc_attr($columns); ?>">
            <div class="ipc-carousel-header">
                <h2 class="ipc-carousel-title">Nuestro Blog</h2>
                <p class="ipc-carousel-subtitle">Art&iacute;culos, gu&iacute;as y novedades sobre lanchas, muelles flotantes y vida n&aacute;utica en Chile</p>
                <div class="ipc-carousel-nav">
                    <button class="ipc-carousel-btn ipc-prev" aria-label="Anterior">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
                    </button>
                    <button class="ipc-carousel-btn ipc-next" aria-label="Siguiente">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </button>
                </div>
            </div>

            <div class="ipc-carousel-track">
                <?php while ($query->have_posts()) : $query->the_post(); ?>
                <article class="ipc-carousel-card">
                    <a href="<?php the_permalink(); ?>" class="ipc-card-link" aria-label="<?php echo esc_attr(get_the_title()); ?>">
                        <div class="ipc-card-image">
                            <?php if (has_post_thumbnail()) : ?>
                                <?php the_post_thumbnail('medium_large', ['loading' => 'lazy', 'alt' => esc_attr(get_the_title())]); ?>
                            <?php else : ?>
                                <div class="ipc-card-placeholder">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#1a3a5c" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                                </div>
                            <?php endif; ?>
                            <?php
                            $categories = get_the_category();
                            if (!empty($categories)) : ?>
                                <span class="ipc-card-category"><?php echo esc_html($categories[0]->name); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="ipc-card-content">
                            <time class="ipc-card-date" datetime="<?php echo get_the_date('c'); ?>">
                                <?php echo get_the_date('d M, Y'); ?>
                            </time>
                            <h3 class="ipc-card-title"><?php the_title(); ?></h3>
                            <p class="ipc-card-excerpt"><?php echo wp_trim_words(get_the_excerpt(), 18, '...'); ?></p>
                            <span class="ipc-card-readmore">Leer m&aacute;s &rarr;</span>
                        </div>
                    </a>
                </article>
                <?php endwhile; ?>
            </div>

            <div class="ipc-carousel-dots"></div>
        </div>
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }

    /**
     * CTA Button Shortcode
     */
    public function render_cta_button($atts) {
        $atts = shortcode_atts([
            'text' => 'Cotiza Tu Lancha Ahora',
            'url'  => home_url('/cotizaciononline/'),
            'style' => 'primary',
        ], $atts, 'imporlan_cta');

        $class = $atts['style'] === 'secondary' ? 'ipc-cta-btn ipc-cta-secondary' : 'ipc-cta-btn ipc-cta-primary';

        return sprintf(
            '<div class="ipc-cta-wrapper"><a href="%s" class="%s" rel="nofollow">%s</a></div>',
            esc_url($atts['url']),
            esc_attr($class),
            esc_html($atts['text'])
        );
    }

    /**
     * Inject Schema.org structured data
     */
    public function inject_schema_markup() {
        if (!is_singular('post')) return;

        $post_id = get_the_ID();
        if (get_post_meta($post_id, '_ipc_premium_post', true) !== '1') return;

        $schema = [
            '@context' => 'https://schema.org',
            '@graph'   => [
                [
                    '@type'       => 'Article',
                    '@id'         => get_permalink($post_id) . '#article',
                    'headline'    => get_the_title($post_id),
                    'description' => get_the_excerpt($post_id),
                    'datePublished'  => get_the_date('c', $post_id),
                    'dateModified'   => get_the_modified_date('c', $post_id),
                    'author'      => [
                        '@type' => 'Organization',
                        'name'  => 'Imporlan',
                    ],
                    'publisher'   => [
                        '@type' => 'Organization',
                        'name'  => 'Muelle Flotante',
                        'logo'  => [
                            '@type' => 'ImageObject',
                            'url'   => content_url('/uploads/2023/08/logo-muelle-flotante.png'),
                        ],
                    ],
                    'mainEntityOfPage' => [
                        '@type' => 'WebPage',
                        '@id'   => get_permalink($post_id),
                    ],
                    'image' => content_url('/uploads/2023/08/1-1.webp'),
                    'keywords' => 'lanchas usadas Chile, importar lanchas, comprar lancha usada, Imporlan, embarcaciones importadas, botes usados, lanchas americanas Chile',
                ],
                [
                    '@type'       => 'LocalBusiness',
                    '@id'         => home_url('/#business'),
                    'name'        => 'Imporlan - Muelle Flotante',
                    'description' => 'Importador y comercializador l&iacute;der de lanchas usadas en Chile. Embarcaciones certificadas desde EE.UU., Jap&oacute;n y Europa.',
                    'url'         => home_url('/'),
                    'email'       => 'info@muelleflotante.cl',
                    'address'     => [
                        '@type'          => 'PostalAddress',
                        'addressCountry' => 'CL',
                        'addressRegion'  => 'Regi&oacute;n Metropolitana',
                    ],
                    'areaServed'  => [
                        '@type' => 'Country',
                        'name'  => 'Chile',
                    ],
                    'priceRange'  => '$$$',
                    'image'       => content_url('/uploads/2023/08/logo-muelle-flotante.png'),
                    'sameAs'      => [],
                ],
                [
                    '@type'          => 'FAQPage',
                    'mainEntity'     => [
                        [
                            '@type'        => 'Question',
                            'name'         => '&iquest;Cu&aacute;nto cuesta importar una lancha usada a Chile?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text'  => 'El costo de importar una lancha usada a Chile var&iacute;a seg&uacute;n el tama&ntilde;o, modelo y pa&iacute;s de origen. Con Imporlan, los precios parten desde aproximadamente $5.000.000 CLP para modelos b&aacute;sicos, incluyendo transporte mar&iacute;timo, internaci&oacute;n aduanera y certificaci&oacute;n. Solicita una cotizaci&oacute;n personalizada sin compromiso.',
                            ],
                        ],
                        [
                            '@type'        => 'Question',
                            'name'         => '&iquest;Qu&eacute; marcas de lanchas importa Imporlan?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text'  => 'Imporlan trabaja con las principales marcas mundiales: Bayliner, Sea Ray, Boston Whaler, Yamaha, Mercury, Chaparral, Grady-White, entre otras. Importamos desde Estados Unidos, Jap&oacute;n, Europa y otros mercados internacionales.',
                            ],
                        ],
                        [
                            '@type'        => 'Question',
                            'name'         => '&iquest;Imporlan entrega en todo Chile?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text'  => 'S&iacute;. Imporlan realiza entregas a lo largo de todo Chile, desde Arica hasta Punta Arenas. Contamos con log&iacute;stica especializada para el transporte seguro de embarcaciones a cualquier regi&oacute;n del pa&iacute;s.',
                            ],
                        ],
                        [
                            '@type'        => 'Question',
                            'name'         => '&iquest;Las lanchas importadas vienen con garant&iacute;a?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text'  => 'Todas las lanchas comercializadas por Imporlan pasan por un riguroso proceso de inspecci&oacute;n t&eacute;cnica y certificaci&oacute;n. Ofrecemos garant&iacute;a en motor y estructura, adem&aacute;s de asesor&iacute;a post-venta permanente.',
                            ],
                        ],
                        [
                            '@type'        => 'Question',
                            'name'         => '&iquest;Se puede financiar la compra de una lancha usada?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text'  => 'S&iacute;. Imporlan ofrece opciones de financiamiento flexible para la compra de lanchas usadas, con planes adaptados a cada cliente. Consulta por nuestras alternativas de pago en cuotas y cr&eacute;ditos especiales.',
                            ],
                        ],
                    ],
                ],
                [
                    '@type'       => 'BreadcrumbList',
                    'itemListElement' => [
                        [
                            '@type'    => 'ListItem',
                            'position' => 1,
                            'name'     => 'Inicio',
                            'item'     => home_url('/'),
                        ],
                        [
                            '@type'    => 'ListItem',
                            'position' => 2,
                            'name'     => 'Blog',
                            'item'     => home_url('/blog/'),
                        ],
                        [
                            '@type'    => 'ListItem',
                            'position' => 3,
                            'name'     => 'Lanchas Usadas Chile - Imporlan',
                            'item'     => get_permalink($post_id),
                        ],
                    ],
                ],
            ],
        ];

        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . '</script>' . "\n";
    }

    /**
     * Inject additional SEO meta tags
     */
    public function inject_seo_meta() {
        if (!is_singular('post')) return;

        $post_id = get_the_ID();
        if (get_post_meta($post_id, '_ipc_premium_post', true) !== '1') return;

        $url = get_permalink($post_id);
        $img = content_url('/uploads/2023/08/1-1.webp');
        ?>
        <!-- Imporlan Premium SEO Meta -->
        <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
        <meta name="geo.region" content="CL">
        <meta name="geo.placename" content="Chile">
        <meta name="language" content="es">
        <link rel="canonical" href="<?php echo esc_url($url); ?>">
        <meta property="article:publisher" content="<?php echo esc_url(home_url('/')); ?>">
        <meta property="article:section" content="Lanchas Usadas">
        <meta property="article:tag" content="lanchas usadas Chile">
        <meta property="article:tag" content="importar lanchas">
        <meta property="article:tag" content="Imporlan">
        <meta property="article:tag" content="comprar lancha usada Chile">
        <meta property="article:tag" content="embarcaciones importadas">
        <?php
    }

    /**
     * Add body class for premium post
     */
    public function add_body_class($classes) {
        if (is_singular('post') && get_post_meta(get_the_ID(), '_ipc_premium_post', true) === '1') {
            $classes[] = 'ipc-premium-landing';
        }
        return $classes;
    }
}

// Initialize
Imporlan_Premium_Content::get_instance();
