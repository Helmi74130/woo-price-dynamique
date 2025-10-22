<?php
/**
 * Classe pour gérer les commandes WooCommerce
 *
 * @package WC_Custom_Price_Calculator_Noveo
 */

// Si ce fichier est appelé directement, on arrête l'exécution
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Classe WCCPC_Checkout_Handler
 * Gère la sauvegarde des données personnalisées dans les commandes
 */
class WCCPC_Checkout_Handler {

    /**
     * Constructeur de la classe
     */
    public function __construct() {
        // Ajoute les données personnalisées aux éléments de commande
        add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'add_order_item_meta' ), 10, 4 );

        // Affiche les données personnalisées dans les commandes (admin)
        add_filter( 'woocommerce_order_item_display_meta_key', array( $this, 'display_meta_key' ), 10, 3 );

        // Affiche les données personnalisées dans les commandes (frontend)
        add_filter( 'woocommerce_order_item_display_meta_value', array( $this, 'display_meta_value' ), 10, 3 );
    }

    /**
     * Ajoute les métadonnées personnalisées à l'élément de commande
     *
     * @param WC_Order_Item_Product $item Élément de commande
     * @param string $cart_item_key Clé de l'article du panier
     * @param array $values Valeurs de l'article du panier
     * @param WC_Order $order Objet commande
     */
    public function add_order_item_meta( $item, $cart_item_key, $values, $order ) {
        // Si l'article contient nos données personnalisées
        if ( isset( $values['wccpc_data'] ) ) {
            $custom_data = $values['wccpc_data'];

            // Ajouter toutes les données comme métadonnées de commande
            $item->add_meta_data( '_wccpc_field_a', $custom_data['field_a'], true );
            $item->add_meta_data( '_wccpc_field_b', $custom_data['field_b'], true );
            $item->add_meta_data( '_wccpc_field_c', $custom_data['field_c'], true );
            $item->add_meta_data( '_wccpc_field_d', $custom_data['field_d'], true );
            $item->add_meta_data( '_wccpc_field_e', $custom_data['field_e'], true );
            $item->add_meta_data( '_wccpc_field_length', $custom_data['field_length'], true );
            $item->add_meta_data( '_wccpc_developed', $custom_data['developed'], true );
            $item->add_meta_data( '_wccpc_surface', $custom_data['surface'], true );
            $item->add_meta_data( '_wccpc_price_per_sqm', $custom_data['price_per_sqm'], true );
            $item->add_meta_data( '_wccpc_final_price', $custom_data['final_price'], true );
            $item->add_meta_data( '_wccpc_is_backorder', $custom_data['is_backorder'], true );
            $item->add_meta_data( '_wccpc_min_surface_applied', $custom_data['min_surface_applied'], true );

            // Ajouter des métadonnées visibles pour l'affichage
            $item->add_meta_data(
                __( 'Mesures (mm)', 'wc-custom-price-calculator' ),
                sprintf(
                    'A: %s, B: %s, C: %s, D: %s, E: %s',
                    number_format( $custom_data['field_a'], 0, ',', ' ' ),
                    number_format( $custom_data['field_b'], 0, ',', ' ' ),
                    number_format( $custom_data['field_c'], 0, ',', ' ' ),
                    number_format( $custom_data['field_d'], 0, ',', ' ' ),
                    number_format( $custom_data['field_e'], 0, ',', ' ' )
                ),
                true
            );

            $item->add_meta_data(
                __( 'Longueur (mm)', 'wc-custom-price-calculator' ),
                number_format( $custom_data['field_length'], 0, ',', ' ' ),
                true
            );

            $item->add_meta_data(
                __( 'Développé (mm)', 'wc-custom-price-calculator' ),
                number_format( $custom_data['developed'], 0, ',', ' ' ),
                true
            );

            $item->add_meta_data(
                __( 'Surface calculée', 'wc-custom-price-calculator' ),
                number_format( $custom_data['surface'], 2, ',', ' ' ) . ' m²',
                true
            );

            $item->add_meta_data(
                __( 'Prix au m²', 'wc-custom-price-calculator' ),
                wc_price( $custom_data['price_per_sqm'] ),
                true
            );

            // Si surface minimale appliquée
            if ( $custom_data['min_surface_applied'] ) {
                $item->add_meta_data(
                    __( 'Information', 'wc-custom-price-calculator' ),
                    __( 'Surface minimale de 4.5 m² appliquée (supplément de 60€)', 'wc-custom-price-calculator' ),
                    true
                );
            }
        }
    }

    /**
     * Personnalise l'affichage des clés de métadonnées
     *
     * @param string $display_key Clé affichée
     * @param object $meta Métadonnée
     * @param WC_Order_Item $item Élément de commande
     * @return string
     */
    public function display_meta_key( $display_key, $meta, $item ) {
        // Les métadonnées internes (préfixées par _) ne sont pas affichées par défaut
        // On laisse WooCommerce gérer cela naturellement
        return $display_key;
    }

    /**
     * Personnalise l'affichage des valeurs de métadonnées
     *
     * @param string $display_value Valeur affichée
     * @param object $meta Métadonnée
     * @param WC_Order_Item $item Élément de commande
     * @return string
     */
    public function display_meta_value( $display_value, $meta, $item ) {
        // On retourne la valeur telle quelle
        // Les formatages ont déjà été appliqués lors de l'ajout
        return $display_value;
    }
}
