<?php
/**
 * Classe pour gérer le panier WooCommerce
 *
 * @package WC_Custom_Price_Calculator_Noveo
 */

// Si ce fichier est appelé directement, on arrête l'exécution
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Classe WCCPC_Cart_Handler
 * Gère l'ajout des données personnalisées au panier et le recalcul des prix
 */
class WCCPC_Cart_Handler {

    /**
     * Constructeur de la classe
     */
    public function __construct() {
        // Ajoute les données personnalisées au panier
        add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_cart_item_data' ), 10, 3 );

        // Modifie les données de l'article dans le panier
        add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'get_cart_item_from_session' ), 10, 2 );

        // Recalcule le prix de l'article dans le panier
        add_action( 'woocommerce_before_calculate_totals', array( $this, 'before_calculate_totals' ), 10, 1 );

        // Affiche les données personnalisées dans le panier
        add_filter( 'woocommerce_get_item_data', array( $this, 'display_cart_item_data' ), 10, 2 );

        // Support pour les variations de produit
        add_filter( 'woocommerce_available_variation', array( $this, 'update_variation_data' ), 10, 3 );
    }

    /**
     * Ajoute les données de calcul personnalisées lors de l'ajout au panier
     *
     * @param array $cart_item_data Données de l'article
     * @param int $product_id ID du produit
     * @param int $variation_id ID de la variation (si applicable)
     * @return array
     */
    public function add_cart_item_data( $cart_item_data, $product_id, $variation_id ) {
        // Vérifier le nonce
        if ( ! isset( $_POST['wccpc_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wccpc_nonce'] ) ), 'wccpc_add_to_cart' ) ) {
            return $cart_item_data;
        }

        // Récupérer et sanitiser les valeurs des champs
        $field_a = isset( $_POST['wccpc_field_a'] ) ? floatval( $_POST['wccpc_field_a'] ) : 0;
        $field_b = isset( $_POST['wccpc_field_b'] ) ? floatval( $_POST['wccpc_field_b'] ) : 0;
        $field_c = isset( $_POST['wccpc_field_c'] ) ? floatval( $_POST['wccpc_field_c'] ) : 0;
        $field_d = isset( $_POST['wccpc_field_d'] ) ? floatval( $_POST['wccpc_field_d'] ) : 0;
        $field_e = isset( $_POST['wccpc_field_e'] ) ? floatval( $_POST['wccpc_field_e'] ) : 0;
        $field_length = isset( $_POST['wccpc_field_length'] ) ? floatval( $_POST['wccpc_field_length'] ) : 0;

        // Calculer le développé (somme de A, B, C, D, E)
        $developed = $field_a + $field_b + $field_c + $field_d + $field_e;

        // Calculer la surface en m² : (Développé / 1000) × (Longueur / 1000)
        $surface = ( $developed / 1000 ) * ( $field_length / 1000 );

        // Récupérer le produit pour obtenir le prix au m²
        $product = wc_get_product( $variation_id ? $variation_id : $product_id );
        $price_per_sqm = floatval( $product->get_price() );

        // Vérifier si le produit est en backorder
        $backorders = $product->get_backorders();
        $is_backorder = ( $backorders === 'yes' || $backorders === 'notify' );

        // Calculer le prix final
        $final_price = $surface * $price_per_sqm;
        $min_surface_applied = false;

        // Cas spécial backorder : surface minimale de 4.5 m²
        if ( $is_backorder && $surface < 4.5 ) {
            $final_price = ( $price_per_sqm * 4.5 ) + 60;
            $min_surface_applied = true;
        }

        // Stocker toutes les données dans le panier
        $cart_item_data['wccpc_data'] = array(
            'field_a' => $field_a,
            'field_b' => $field_b,
            'field_c' => $field_c,
            'field_d' => $field_d,
            'field_e' => $field_e,
            'field_length' => $field_length,
            'developed' => $developed,
            'surface' => $surface,
            'price_per_sqm' => $price_per_sqm,
            'final_price' => $final_price,
            'is_backorder' => $is_backorder,
            'min_surface_applied' => $min_surface_applied,
        );

        // Ajouter un identifiant unique pour permettre plusieurs articles avec des mesures différentes
        $cart_item_data['wccpc_unique_key'] = md5( wp_json_encode( $cart_item_data['wccpc_data'] ) . time() );

        return $cart_item_data;
    }

    /**
     * Récupère les données de la session pour l'article du panier
     *
     * @param array $cart_item Données de l'article
     * @param array $values Valeurs stockées
     * @return array
     */
    public function get_cart_item_from_session( $cart_item, $values ) {
        if ( isset( $values['wccpc_data'] ) ) {
            $cart_item['wccpc_data'] = $values['wccpc_data'];
        }

        if ( isset( $values['wccpc_unique_key'] ) ) {
            $cart_item['wccpc_unique_key'] = $values['wccpc_unique_key'];
        }

        return $cart_item;
    }

    /**
     * Recalcule le prix de l'article dans le panier
     *
     * @param WC_Cart $cart Objet panier WooCommerce
     */
    public function before_calculate_totals( $cart ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            return;
        }

        // Parcourir tous les articles du panier
        foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
            // Si l'article contient nos données personnalisées
            if ( isset( $cart_item['wccpc_data'] ) ) {
                $custom_data = $cart_item['wccpc_data'];

                // Récupérer le prix final calculé
                $final_price = $custom_data['final_price'];

                // Définir le nouveau prix
                $cart_item['data']->set_price( $final_price );
            }
        }
    }

    /**
     * Affiche les données personnalisées dans le panier
     *
     * @param array $item_data Données de l'article
     * @param array $cart_item Article du panier
     * @return array
     */
    public function display_cart_item_data( $item_data, $cart_item ) {
        // Si l'article contient nos données personnalisées
        if ( isset( $cart_item['wccpc_data'] ) ) {
            $custom_data = $cart_item['wccpc_data'];

            // Ajouter les mesures
            $item_data[] = array(
                'key'     => __( 'Mesures (mm)', 'wc-custom-price-calculator' ),
                'value'   => sprintf(
                    'A: %s, B: %s, C: %s, D: %s, E: %s',
                    number_format( $custom_data['field_a'], 0, ',', ' ' ),
                    number_format( $custom_data['field_b'], 0, ',', ' ' ),
                    number_format( $custom_data['field_c'], 0, ',', ' ' ),
                    number_format( $custom_data['field_d'], 0, ',', ' ' ),
                    number_format( $custom_data['field_e'], 0, ',', ' ' )
                ),
                'display' => '',
            );

            // Ajouter la longueur
            $item_data[] = array(
                'key'     => __( 'Longueur (mm)', 'wc-custom-price-calculator' ),
                'value'   => number_format( $custom_data['field_length'], 0, ',', ' ' ),
                'display' => '',
            );

            // Ajouter le développé
            $item_data[] = array(
                'key'     => __( 'Développé (mm)', 'wc-custom-price-calculator' ),
                'value'   => number_format( $custom_data['developed'], 0, ',', ' ' ),
                'display' => '',
            );

            // Ajouter la surface
            $item_data[] = array(
                'key'     => __( 'Surface', 'wc-custom-price-calculator' ),
                'value'   => number_format( $custom_data['surface'], 2, ',', ' ' ) . ' m²',
                'display' => '',
            );

            // Ajouter le prix au m²
            $item_data[] = array(
                'key'     => __( 'Prix au m²', 'wc-custom-price-calculator' ),
                'value'   => wc_price( $custom_data['price_per_sqm'] ),
                'display' => '',
            );

            // Si surface minimale appliquée
            if ( $custom_data['min_surface_applied'] ) {
                $item_data[] = array(
                    'key'     => __( 'Info', 'wc-custom-price-calculator' ),
                    'value'   => __( 'Surface minimale de 4.5 m² appliquée (supplément de 60€)', 'wc-custom-price-calculator' ),
                    'display' => '',
                );
            }
        }

        return $item_data;
    }

    /**
     * Met à jour les données de variation pour le JavaScript
     *
     * @param array $variation_data Données de variation
     * @param WC_Product $product Produit parent
     * @param WC_Product_Variation $variation Variation
     * @return array
     */
    public function update_variation_data( $variation_data, $product, $variation ) {
        // Ajouter les informations de backorder pour la variation
        $backorders = $variation->get_backorders();
        $is_backorder = ( $backorders === 'yes' || $backorders === 'notify' );

        $variation_data['wccpc_is_backorder'] = $is_backorder;
        $variation_data['wccpc_base_price'] = $variation->get_price();

        return $variation_data;
    }
}
