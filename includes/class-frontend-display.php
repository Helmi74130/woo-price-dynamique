<?php
/**
 * Classe pour l'affichage frontend des champs de calcul
 *
 * @package WC_Custom_Price_Calculator_Noveo
 */

// Si ce fichier est appelé directement, on arrête l'exécution
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Classe WCCPC_Frontend_Display
 * Gère l'affichage des champs de calcul sur la page produit
 */
class WCCPC_Frontend_Display {

    /**
     * Constructeur de la classe
     */
    public function __construct() {
        // Affiche les champs avant le bouton "Ajouter au panier"
        add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'display_calculator_fields' ) );

        // Valide les champs avant l'ajout au panier
        add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_calculator_fields' ), 10, 3 );
    }

    /**
     * Affiche les champs de calcul sur la page produit
     */
    public function display_calculator_fields() {
        global $product;

        // Vérifier que nous avons bien un produit
        if ( ! $product ) {
            return;
        }

        // Récupérer le produit actuel
        $product_id = $product->get_id();
        $product_type = $product->get_type();

        // Déterminer si le produit est en backorder
        $is_backorder = false;
        if ( $product_type === 'simple' ) {
            $backorders = $product->get_backorders();
            $is_backorder = ( $backorders === 'yes' || $backorders === 'notify' );
        }

        // Récupérer le prix du produit (prix au m²)
        $price_per_sqm = $product->get_price();

        ?>
        <div class="wccpc-calculator-wrapper">
            <input type="hidden" id="wccpc-product-id" value="<?php echo esc_attr( $product_id ); ?>" />
            <input type="hidden" id="wccpc-product-type" value="<?php echo esc_attr( $product_type ); ?>" />
            <input type="hidden" id="wccpc-is-backorder" value="<?php echo esc_attr( $is_backorder ? '1' : '0' ); ?>" />
            <input type="hidden" id="wccpc-base-price" value="<?php echo esc_attr( $price_per_sqm ); ?>" />

            <h3 class="wccpc-calculator-title">
                <?php esc_html_e( 'Calcul personnalisé', 'wc-custom-price-calculator' ); ?>
            </h3>

            <p class="wccpc-calculator-description">
                <?php esc_html_e( 'Veuillez entrer vos mesures en millimètres (mm) :', 'wc-custom-price-calculator' ); ?>
            </p>

            <div class="wccpc-fields-container">
                <div class="wccpc-field-row">
                    <div class="wccpc-field-group">
                        <label for="wccpc-field-a">
                            <?php esc_html_e( 'A (mm)', 'wc-custom-price-calculator' ); ?>
                        </label>
                        <input
                            type="number"
                            id="wccpc-field-a"
                            name="wccpc_field_a"
                            class="wccpc-calculator-field"
                            min="0"
                            step="1"
                            placeholder="0"
                            required
                        />
                    </div>

                    <div class="wccpc-field-group">
                        <label for="wccpc-field-b">
                            <?php esc_html_e( 'B (mm)', 'wc-custom-price-calculator' ); ?>
                        </label>
                        <input
                            type="number"
                            id="wccpc-field-b"
                            name="wccpc_field_b"
                            class="wccpc-calculator-field"
                            min="0"
                            step="1"
                            placeholder="0"
                            required
                        />
                    </div>

                    <div class="wccpc-field-group">
                        <label for="wccpc-field-c">
                            <?php esc_html_e( 'C (mm)', 'wc-custom-price-calculator' ); ?>
                        </label>
                        <input
                            type="number"
                            id="wccpc-field-c"
                            name="wccpc_field_c"
                            class="wccpc-calculator-field"
                            min="0"
                            step="1"
                            placeholder="0"
                            required
                        />
                    </div>
                </div>

                <div class="wccpc-field-row">
                    <div class="wccpc-field-group">
                        <label for="wccpc-field-d">
                            <?php esc_html_e( 'D (mm)', 'wc-custom-price-calculator' ); ?>
                        </label>
                        <input
                            type="number"
                            id="wccpc-field-d"
                            name="wccpc_field_d"
                            class="wccpc-calculator-field"
                            min="0"
                            step="1"
                            placeholder="0"
                            required
                        />
                    </div>

                    <div class="wccpc-field-group">
                        <label for="wccpc-field-e">
                            <?php esc_html_e( 'E (mm)', 'wc-custom-price-calculator' ); ?>
                        </label>
                        <input
                            type="number"
                            id="wccpc-field-e"
                            name="wccpc_field_e"
                            class="wccpc-calculator-field"
                            min="0"
                            step="1"
                            placeholder="0"
                            required
                        />
                    </div>

                    <div class="wccpc-field-group">
                        <label for="wccpc-field-length">
                            <?php esc_html_e( 'Longueur (mm)', 'wc-custom-price-calculator' ); ?>
                        </label>
                        <input
                            type="number"
                            id="wccpc-field-length"
                            name="wccpc_field_length"
                            class="wccpc-calculator-field"
                            min="0"
                            step="1"
                            placeholder="0"
                            required
                        />
                    </div>
                </div>
            </div>

            <div class="wccpc-results-container" style="display: none;">
                <div class="wccpc-result-item">
                    <span class="wccpc-result-label">
                        <?php esc_html_e( 'Développé :', 'wc-custom-price-calculator' ); ?>
                    </span>
                    <span class="wccpc-result-value" id="wccpc-result-developed">0 mm</span>
                </div>

                <div class="wccpc-result-item">
                    <span class="wccpc-result-label">
                        <?php esc_html_e( 'Surface :', 'wc-custom-price-calculator' ); ?>
                    </span>
                    <span class="wccpc-result-value" id="wccpc-result-surface">0 m²</span>
                </div>

                <div class="wccpc-result-item wccpc-result-price">
                    <span class="wccpc-result-label">
                        <?php esc_html_e( 'Prix estimé :', 'wc-custom-price-calculator' ); ?>
                    </span>
                    <span class="wccpc-result-value" id="wccpc-result-price">0 <?php echo esc_html( get_woocommerce_currency_symbol() ); ?></span>
                </div>

                <div class="wccpc-result-info" id="wccpc-result-info" style="display: none;"></div>
            </div>

            <!-- Champs cachés pour stocker les valeurs calculées -->
            <input type="hidden" id="wccpc-calculated-surface" name="wccpc_calculated_surface" value="" />
            <input type="hidden" id="wccpc-calculated-price" name="wccpc_calculated_price" value="" />
            <input type="hidden" id="wccpc-calculated-developed" name="wccpc_calculated_developed" value="" />

            <?php wp_nonce_field( 'wccpc_add_to_cart', 'wccpc_nonce' ); ?>
        </div>
        <?php
    }

    /**
     * Valide les champs du calculateur avant l'ajout au panier
     *
     * @param bool $passed Validation par défaut
     * @param int $product_id ID du produit
     * @param int $quantity Quantité
     * @return bool
     */
    public function validate_calculator_fields( $passed, $product_id, $quantity ) {
        // Vérifier le nonce
        if ( ! isset( $_POST['wccpc_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wccpc_nonce'] ) ), 'wccpc_add_to_cart' ) ) {
            // Si le nonce n'est pas présent, on ne valide pas (pas notre formulaire)
            return $passed;
        }

        // Vérifier que tous les champs sont remplis
        $required_fields = array( 'wccpc_field_a', 'wccpc_field_b', 'wccpc_field_c', 'wccpc_field_d', 'wccpc_field_e', 'wccpc_field_length' );

        foreach ( $required_fields as $field ) {
            if ( ! isset( $_POST[ $field ] ) || empty( $_POST[ $field ] ) || $_POST[ $field ] <= 0 ) {
                wc_add_notice( __( 'Veuillez remplir tous les champs de mesure avec des valeurs positives.', 'wc-custom-price-calculator' ), 'error' );
                return false;
            }
        }

        // Vérifier les valeurs calculées
        if ( ! isset( $_POST['wccpc_calculated_surface'] ) || empty( $_POST['wccpc_calculated_surface'] ) ) {
            wc_add_notice( __( 'Erreur de calcul. Veuillez réessayer.', 'wc-custom-price-calculator' ), 'error' );
            return false;
        }

        if ( ! isset( $_POST['wccpc_calculated_price'] ) || empty( $_POST['wccpc_calculated_price'] ) ) {
            wc_add_notice( __( 'Erreur de calcul du prix. Veuillez réessayer.', 'wc-custom-price-calculator' ), 'error' );
            return false;
        }

        return $passed;
    }
}
