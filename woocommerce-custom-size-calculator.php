<?php
/**
 * Plugin Name: WooCommerce Custom Price Calculator Noveo
 * Plugin URI: https://github.com/Helmi74130/woo-price-dynamique
 * Description: Ajoute des champs de saisie personnalisés pour calculer dynamiquement le prix d'un produit selon les mesures saisies par le client.
 * Version: 1.0.0
 * Author: Noveo
 * Author URI: https://noveo.fr
 * Text Domain: wc-custom-price-calculator
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Si ce fichier est appelé directement, on arrête l'exécution
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Classe principale du plugin WooCommerce Custom Price Calculator Noveo
 */
class WC_Custom_Price_Calculator_Noveo {

    /**
     * Version du plugin
     */
    const VERSION = '1.0.0';

    /**
     * Instance unique de la classe (singleton)
     *
     * @var WC_Custom_Price_Calculator_Noveo
     */
    private static $instance = null;

    /**
     * Retourne l'instance unique de la classe
     *
     * @return WC_Custom_Price_Calculator_Noveo
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructeur privé pour empêcher l'instanciation directe
     */
    private function __construct() {
        $this->define_constants();
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Définit les constantes du plugin
     */
    private function define_constants() {
        define( 'WCCPC_VERSION', self::VERSION );
        define( 'WCCPC_PLUGIN_FILE', __FILE__ );
        define( 'WCCPC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
        define( 'WCCPC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
    }

    /**
     * Inclut les fichiers nécessaires
     */
    private function includes() {
        // Vérifier que WooCommerce est actif
        if ( ! class_exists( 'WooCommerce' ) ) {
            add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
            return;
        }
    }

    /**
     * Initialise les hooks WordPress
     */
    private function init_hooks() {
        // Hook d'activation du plugin
        register_activation_hook( __FILE__, array( $this, 'activate' ) );

        // Hook de désactivation du plugin
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

        // Charge les scripts et styles
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

        // Initialise les classes
        add_action( 'plugins_loaded', array( $this, 'init_classes' ) );

        // Déclare la compatibilité HPOS (High Performance Order Storage)
        add_action( 'before_woocommerce_init', array( $this, 'declare_hpos_compatibility' ) );
    }

    /**
     * Initialise les classes du plugin
     */
    public function init_classes() {
        if ( class_exists( 'WooCommerce' ) ) {
            // Inclure les classes du plugin
            require_once WCCPC_PLUGIN_DIR . 'includes/class-frontend-display.php';
            require_once WCCPC_PLUGIN_DIR . 'includes/class-cart-handler.php';
            require_once WCCPC_PLUGIN_DIR . 'includes/class-checkout-handler.php';

            // Instancier les classes
            new WCCPC_Frontend_Display();
            new WCCPC_Cart_Handler();
            new WCCPC_Checkout_Handler();
        }
    }

    /**
     * Charge les scripts et styles du plugin
     */
    public function enqueue_scripts() {
        // Charger uniquement sur les pages produits
        if ( is_product() ) {
            // Style CSS
            wp_enqueue_style(
                'wccpc-style',
                WCCPC_PLUGIN_URL . 'assets/css/style.css',
                array(),
                WCCPC_VERSION
            );

            // Script JavaScript
            wp_enqueue_script(
                'wccpc-calculator',
                WCCPC_PLUGIN_URL . 'assets/js/price-calculator.js',
                array( 'jquery' ),
                WCCPC_VERSION,
                true
            );

            // Passer des données PHP à JavaScript
            wp_localize_script(
                'wccpc-calculator',
                'wccpcData',
                array(
                    'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                    'nonce' => wp_create_nonce( 'wccpc_calculate_price' ),
                    'currency' => get_woocommerce_currency_symbol(),
                    'decimals' => wc_get_price_decimals(),
                    'decimalSeparator' => wc_get_price_decimal_separator(),
                    'thousandSeparator' => wc_get_price_thousand_separator(),
                    'minSurface' => 4.5,
                    'backorderSupplement' => 60,
                    'messages' => array(
                        'fillAllFields' => __( 'Veuillez remplir tous les champs.', 'wc-custom-price-calculator' ),
                        'surface' => __( 'Surface', 'wc-custom-price-calculator' ),
                        'estimatedPrice' => __( 'Prix estimé', 'wc-custom-price-calculator' ),
                        'minSurfaceApplied' => __( 'Surface minimale de 4.5 m² appliquée (supplément de 60€)', 'wc-custom-price-calculator' ),
                    )
                )
            );
        }
    }

    /**
     * Déclare la compatibilité avec HPOS (WooCommerce 7.1+)
     */
    public function declare_hpos_compatibility() {
        if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                'custom_order_tables',
                __FILE__,
                true
            );
        }
    }

    /**
     * Fonction appelée lors de l'activation du plugin
     */
    public function activate() {
        // Vérifier la version de PHP
        if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
            deactivate_plugins( plugin_basename( __FILE__ ) );
            wp_die( __( 'Ce plugin nécessite PHP 7.4 ou supérieur.', 'wc-custom-price-calculator' ) );
        }

        // Vérifier que WooCommerce est installé
        if ( ! class_exists( 'WooCommerce' ) ) {
            deactivate_plugins( plugin_basename( __FILE__ ) );
            wp_die( __( 'Ce plugin nécessite WooCommerce pour fonctionner.', 'wc-custom-price-calculator' ) );
        }

        // Créer les options du plugin
        add_option( 'wccpc_version', WCCPC_VERSION );

        // Flush les règles de réécriture
        flush_rewrite_rules();
    }

    /**
     * Fonction appelée lors de la désactivation du plugin
     */
    public function deactivate() {
        // Flush les règles de réécriture
        flush_rewrite_rules();
    }

    /**
     * Affiche une notice si WooCommerce n'est pas actif
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="error">
            <p>
                <?php
                echo wp_kses_post(
                    sprintf(
                        /* translators: %s: URL vers la page des plugins */
                        __( '<strong>WooCommerce Custom Price Calculator Noveo</strong> nécessite WooCommerce pour fonctionner. Veuillez <a href="%s">installer et activer WooCommerce</a>.', 'wc-custom-price-calculator' ),
                        admin_url( 'plugin-install.php?s=woocommerce&tab=search&type=term' )
                    )
                );
                ?>
            </p>
        </div>
        <?php
    }
}

/**
 * Retourne l'instance unique du plugin
 *
 * @return WC_Custom_Price_Calculator_Noveo
 */
function wccpc() {
    return WC_Custom_Price_Calculator_Noveo::get_instance();
}

// Initialise le plugin
wccpc();
