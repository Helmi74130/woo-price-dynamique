# WooCommerce Custom Price Calculator Noveo

Un plugin WordPress/WooCommerce qui permet de calculer dynamiquement le prix d'un produit en fonction de mesures personnalisées saisies par le client.

## 📋 Description

Ce plugin ajoute des champs de saisie personnalisés sur la page produit WooCommerce, permettant aux clients de saisir des mesures en millimètres. Le prix du produit est ensuite calculé automatiquement en fonction de ces mesures.

### Fonctionnalités principales

- ✅ **6 champs de saisie personnalisés** : A, B, C, D, E, Longueur (en millimètres)
- ✅ **Calcul automatique en temps réel** : Le prix se met à jour instantanément sans recharger la page
- ✅ **Compatibilité produits simples et variables** : Fonctionne avec tous les types de produits WooCommerce
- ✅ **Gestion backorder** : Applique automatiquement une surface minimale et un supplément pour les produits en backorder
- ✅ **Sauvegarde des données** : Toutes les mesures et calculs sont sauvegardés dans le panier et la commande
- ✅ **Interface responsive** : S'adapte à tous les types d'écrans (desktop, tablette, mobile)
- ✅ **Compatible HPOS** : Support natif du système de stockage haute performance de WooCommerce

## 🔢 Formules de calcul

Le plugin utilise les formules suivantes :

1. **Développé (mm)** = A + B + C + D + E
2. **Longueur (mm)** = Valeur saisie
3. **Surface (m²)** = (Développé / 1000) × (Longueur / 1000)
4. **Prix de base** = Surface (m²) × Prix du m² (prix du produit WooCommerce)

### Cas spécial - Backorder

Si le produit est en backorder et que la surface calculée est inférieure à 4,5 m² :

**Prix final** = (Prix du m² × 4,5) + 60 €

Un message d'information s'affiche pour informer le client de l'application de cette règle.

## 📦 Installation

### Méthode 1 : Installation manuelle

1. Téléchargez le plugin
2. Uploadez le dossier `woocommerce-custom-size-calculator` dans `/wp-content/plugins/`
3. Activez le plugin depuis le menu "Extensions" de WordPress

### Méthode 2 : Via Git

```bash
cd wp-content/plugins/
git clone https://github.com/Helmi74130/woo-price-dynamique.git woocommerce-custom-size-calculator
```

Puis activez le plugin depuis l'administration WordPress.

## ⚙️ Configuration requise

- **WordPress** : 5.8 ou supérieur
- **WooCommerce** : 5.0 ou supérieur
- **PHP** : 7.4 ou supérieur

## 🚀 Utilisation

### Pour les administrateurs

1. Activez le plugin
2. Le plugin fonctionne automatiquement sur toutes les pages produits
3. Le prix du produit WooCommerce représente le **prix au m²**

### Pour les clients

1. Sur la page produit, remplissez les 6 champs de mesure en millimètres :
   - A, B, C, D, E
   - Longueur
2. Le prix se calcule automatiquement en temps réel
3. Les résultats affichés incluent :
   - Le développé (somme de A, B, C, D, E)
   - La surface calculée en m²
   - Le prix estimé
4. Ajoutez le produit au panier avec le prix calculé

### Exemple de calcul

**Mesures saisies :**
- A = 100 mm
- B = 200 mm
- C = 150 mm
- D = 100 mm
- E = 50 mm
- Longueur = 2000 mm

**Calculs :**
- Développé = 100 + 200 + 150 + 100 + 50 = 600 mm
- Surface = (600 / 1000) × (2000 / 1000) = 0,6 × 2 = 1,2 m²
- Prix du m² = 50 € (prix du produit WooCommerce)
- **Prix final = 1,2 × 50 = 60 €**

**Cas backorder (surface < 4,5 m²) :**
- Surface = 1,2 m² (< 4,5 m²)
- Prix du m² = 50 €
- **Prix final = (50 × 4,5) + 60 = 285 €**
- Message affiché : "Surface minimale de 4.5 m² appliquée (supplément de 60€)"

## 📂 Structure du plugin

```
woocommerce-custom-size-calculator/
├── woocommerce-custom-size-calculator.php  # Fichier principal
├── includes/                                # Classes PHP
│   ├── class-frontend-display.php          # Affichage frontend
│   ├── class-cart-handler.php              # Gestion du panier
│   └── class-checkout-handler.php          # Gestion des commandes
├── assets/                                  # Ressources
│   ├── js/
│   │   └── price-calculator.js             # JavaScript du calculateur
│   └── css/
│       └── style.css                       # Styles CSS
└── README.md                                # Documentation
```

## 🔧 Personnalisation

### Modifier la surface minimale

Par défaut, la surface minimale pour les produits en backorder est de 4,5 m². Pour la modifier, éditez le fichier principal :

```php
// Dans woocommerce-custom-size-calculator.php, ligne ~130
'minSurface' => 4.5, // Changez cette valeur
```

### Modifier le supplément backorder

Par défaut, le supplément est de 60 €. Pour le modifier :

```php
// Dans woocommerce-custom-size-calculator.php, ligne ~131
'backorderSupplement' => 60, // Changez cette valeur
```

### Personnaliser les styles

Tous les styles sont dans `assets/css/style.css`. Vous pouvez les modifier selon vos besoins ou ajouter vos propres classes CSS.

## 🛠️ Développement

### Structure du code

Le plugin suit les meilleures pratiques WordPress :

- **Architecture orientée objet** : Utilisation de classes PHP
- **Pattern Singleton** : Une seule instance du plugin
- **Hooks WordPress/WooCommerce** : Intégration native
- **Sécurité** : Nonces, sanitization, validation
- **Standards de code** : WordPress Coding Standards

### Hooks disponibles

Le plugin utilise les hooks WooCommerce suivants :

- `woocommerce_before_add_to_cart_button` : Affichage des champs
- `woocommerce_add_to_cart_validation` : Validation des données
- `woocommerce_add_cart_item_data` : Ajout des données au panier
- `woocommerce_get_cart_item_from_session` : Récupération depuis la session
- `woocommerce_before_calculate_totals` : Recalcul des prix
- `woocommerce_get_item_data` : Affichage dans le panier
- `woocommerce_checkout_create_order_line_item` : Sauvegarde dans la commande

## 🐛 Dépannage

### Le calculateur ne s'affiche pas

1. Vérifiez que WooCommerce est installé et activé
2. Vérifiez que vous êtes sur une page produit
3. Videz le cache de votre navigateur
4. Vérifiez la console JavaScript pour des erreurs

### Le prix ne se calcule pas

1. Vérifiez que tous les champs sont remplis avec des valeurs positives
2. Vérifiez que le produit a un prix défini
3. Ouvrez la console du navigateur pour identifier les erreurs JavaScript

### Les données ne sont pas sauvegardées

1. Vérifiez les permissions PHP
2. Vérifiez que les nonces sont valides
3. Vérifiez les logs d'erreur WordPress

## 📝 Licence

Ce plugin est sous licence GPL v2 ou supérieure.

## 👨‍💻 Auteur

**Noveo**
- Site web : [https://noveo.fr](https://noveo.fr)
- GitHub : [https://github.com/Helmi74130/woo-price-dynamique](https://github.com/Helmi74130/woo-price-dynamique)

## 🤝 Contribution

Les contributions sont les bienvenues ! N'hésitez pas à :

1. Fork le projet
2. Créer une branche pour votre fonctionnalité (`git checkout -b feature/AmazingFeature`)
3. Commit vos changements (`git commit -m 'Add some AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

## 📞 Support

Pour toute question ou problème :

1. Consultez la documentation ci-dessus
2. Ouvrez une issue sur GitHub
3. Contactez Noveo via le site web

## 🔄 Changelog

### Version 1.0.0 (2025-10-22)

- ✨ Version initiale
- ✅ Ajout des 6 champs de saisie personnalisés
- ✅ Calcul dynamique du prix en temps réel
- ✅ Support des produits simples et variables
- ✅ Gestion des produits en backorder
- ✅ Sauvegarde complète dans le panier et les commandes
- ✅ Interface responsive
- ✅ Compatibilité HPOS

## ⭐ Remerciements

Merci d'utiliser WooCommerce Custom Price Calculator Noveo !

Si ce plugin vous est utile, n'hésitez pas à laisser une étoile sur GitHub ⭐
