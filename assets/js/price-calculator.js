/**
 * WooCommerce Custom Price Calculator - JavaScript
 * Gère le calcul dynamique du prix en fonction des mesures saisies
 *
 * @package WC_Custom_Price_Calculator_Noveo
 */

(function($) {
    'use strict';

    /**
     * Objet principal du calculateur
     */
    var WCCPCCalculator = {

        /**
         * Initialisation
         */
        init: function() {
            this.cacheElements();
            this.bindEvents();
            this.handleVariations();
        },

        /**
         * Cache les éléments DOM pour de meilleures performances
         */
        cacheElements: function() {
            this.$fields = $('.wccpc-calculator-field');
            this.$fieldA = $('#wccpc-field-a');
            this.$fieldB = $('#wccpc-field-b');
            this.$fieldC = $('#wccpc-field-c');
            this.$fieldD = $('#wccpc-field-d');
            this.$fieldE = $('#wccpc-field-e');
            this.$fieldLength = $('#wccpc-field-length');
            this.$resultsContainer = $('.wccpc-results-container');
            this.$resultDeveloped = $('#wccpc-result-developed');
            this.$resultSurface = $('#wccpc-result-surface');
            this.$resultPrice = $('#wccpc-result-price');
            this.$resultInfo = $('#wccpc-result-info');
            this.$hiddenSurface = $('#wccpc-calculated-surface');
            this.$hiddenPrice = $('#wccpc-calculated-price');
            this.$hiddenDeveloped = $('#wccpc-calculated-developed');
            this.$basePrice = $('#wccpc-base-price');
            this.$isBackorder = $('#wccpc-is-backorder');
            this.$productType = $('#wccpc-product-type');
            this.$addToCartButton = $('button[type="submit"].single_add_to_cart_button');
        },

        /**
         * Attache les événements
         */
        bindEvents: function() {
            var self = this;

            // Événement sur les champs de saisie
            this.$fields.on('input change', function() {
                self.calculatePrice();
            });

            // Désactiver le bouton d'ajout au panier tant que le calcul n'est pas fait
            this.$addToCartButton.on('click', function(e) {
                if (!self.validateFields()) {
                    e.preventDefault();
                    alert(wccpcData.messages.fillAllFields);
                    return false;
                }
            });

            // Calculer au chargement si des valeurs sont présentes
            $(document).ready(function() {
                self.calculatePrice();
            });
        },

        /**
         * Gère les produits variables
         */
        handleVariations: function() {
            var self = this;

            // Pour les produits variables
            if (this.$productType.val() === 'variable') {
                // Écouter les changements de variation
                $('form.variations_form').on('found_variation', function(event, variation) {
                    // Mettre à jour le prix de base
                    if (variation.wccpc_base_price) {
                        self.$basePrice.val(variation.wccpc_base_price);
                    }

                    // Mettre à jour le statut backorder
                    if (typeof variation.wccpc_is_backorder !== 'undefined') {
                        self.$isBackorder.val(variation.wccpc_is_backorder ? '1' : '0');
                    }

                    // Recalculer le prix
                    self.calculatePrice();
                });

                // Réinitialiser quand la variation est supprimée
                $('form.variations_form').on('reset_data', function() {
                    self.$resultsContainer.hide();
                    self.$hiddenSurface.val('');
                    self.$hiddenPrice.val('');
                    self.$hiddenDeveloped.val('');
                });
            }
        },

        /**
         * Valide que tous les champs sont remplis
         */
        validateFields: function() {
            var allFilled = true;

            this.$fields.each(function() {
                var value = parseFloat($(this).val());
                if (isNaN(value) || value <= 0) {
                    allFilled = false;
                    return false; // break
                }
            });

            return allFilled;
        },

        /**
         * Récupère les valeurs des champs
         */
        getFieldValues: function() {
            return {
                a: parseFloat(this.$fieldA.val()) || 0,
                b: parseFloat(this.$fieldB.val()) || 0,
                c: parseFloat(this.$fieldC.val()) || 0,
                d: parseFloat(this.$fieldD.val()) || 0,
                e: parseFloat(this.$fieldE.val()) || 0,
                length: parseFloat(this.$fieldLength.val()) || 0
            };
        },

        /**
         * Calcule le prix en fonction des mesures
         */
        calculatePrice: function() {
            // Vérifier que tous les champs sont remplis
            if (!this.validateFields()) {
                this.$resultsContainer.hide();
                this.$hiddenSurface.val('');
                this.$hiddenPrice.val('');
                this.$hiddenDeveloped.val('');
                return;
            }

            // Récupérer les valeurs
            var values = this.getFieldValues();

            // Calcul du développé (somme de A, B, C, D, E en mm)
            var developed = values.a + values.b + values.c + values.d + values.e;

            // Calcul de la surface en m² : (Développé / 1000) × (Longueur / 1000)
            var surface = (developed / 1000) * (values.length / 1000);

            // Récupérer le prix au m²
            var pricePerSqm = parseFloat(this.$basePrice.val()) || 0;

            // Vérifier si le produit est en backorder
            var isBackorder = this.$isBackorder.val() === '1';

            // Calculer le prix final
            var finalPrice = surface * pricePerSqm;
            var minSurfaceApplied = false;
            var infoMessage = '';

            // Cas spécial backorder : surface minimale de 4.5 m²
            if (isBackorder && surface < wccpcData.minSurface) {
                finalPrice = (pricePerSqm * wccpcData.minSurface) + wccpcData.backorderSupplement;
                minSurfaceApplied = true;
                infoMessage = wccpcData.messages.minSurfaceApplied;
            }

            // Mettre à jour l'affichage
            this.updateDisplay(developed, surface, finalPrice, infoMessage);

            // Stocker les valeurs dans les champs cachés
            this.$hiddenDeveloped.val(developed);
            this.$hiddenSurface.val(surface.toFixed(4)); // Plus de précision pour les calculs
            this.$hiddenPrice.val(finalPrice.toFixed(2));
        },

        /**
         * Met à jour l'affichage des résultats
         */
        updateDisplay: function(developed, surface, price, infoMessage) {
            // Formater le développé
            this.$resultDeveloped.text(this.formatNumber(developed, 0) + ' mm');

            // Formater la surface
            this.$resultSurface.text(this.formatNumber(surface, 2) + ' m²');

            // Formater le prix
            var formattedPrice = this.formatPrice(price);
            this.$resultPrice.html(formattedPrice);

            // Afficher le message d'information si présent
            if (infoMessage) {
                this.$resultInfo.html('<i class="wccpc-info-icon">ℹ️</i> ' + infoMessage).show();
            } else {
                this.$resultInfo.hide();
            }

            // Afficher le conteneur de résultats
            this.$resultsContainer.slideDown(300);
        },

        /**
         * Formate un nombre avec les séparateurs appropriés
         */
        formatNumber: function(number, decimals) {
            decimals = decimals || 0;

            var n = parseFloat(number).toFixed(decimals);
            var parts = n.split('.');
            var integerPart = parts[0];
            var decimalPart = parts.length > 1 ? parts[1] : '';

            // Ajouter les séparateurs de milliers
            integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, wccpcData.thousandSeparator);

            // Construire le nombre formaté
            if (decimals > 0 && decimalPart) {
                return integerPart + wccpcData.decimalSeparator + decimalPart;
            }

            return integerPart;
        },

        /**
         * Formate un prix avec le symbole de la devise
         */
        formatPrice: function(price) {
            var formattedNumber = this.formatNumber(price, wccpcData.decimals);
            return formattedNumber + ' ' + wccpcData.currency;
        }
    };

    /**
     * Initialisation au chargement du DOM
     */
    $(document).ready(function() {
        // Vérifier que nous sommes sur une page produit avec le calculateur
        if ($('.wccpc-calculator-wrapper').length > 0) {
            WCCPCCalculator.init();
        }
    });

})(jQuery);
