<?php
/**
 * Plugin Name: Regole del negozio La Quiete (didattico)
 * Description: Regole di spedizione del caso La Quiete nelle Cime e blocco delle email. Negozio finto, solo per i corsi.
 *
 * Regole (da bibbia/06-catalogo.md):
 *  - Italia: 9,90 €, gratis sopra 89 €. I prodotti della classe "Refrigerato" aggiungono 6,00 € anche con spedizione gratis.
 *  - Estero (Austria, Germania): solo vino e dolci non refrigerati. Formaggi, yogurt e refrigerati solo in Italia.
 *  - Classe "Solo ritiro" (ricotta): si ritira in cantina, nessuna spedizione.
 * L'importo base e la soglia sono qui in alto: per cambiarli basta modificare le due righe.
 */
if (!defined('ABSPATH')) { exit; }

const QUIETE_BASE_ITALIA = 9.90;
const QUIETE_SOGLIA_GRATIS = 89.0;

// Nessuna email esce davvero da questo negozio.
add_filter('pre_wp_mail', '__return_true');

add_filter('woocommerce_package_rates', function ($rates, $package) {
    $classi = array();
    $cat_blocca_estero = false;
    foreach ($package['contents'] as $voce) {
        $p = $voce['data'];
        $classi[] = $p->get_shipping_class();
        $id = $p->get_parent_id() ? $p->get_parent_id() : $p->get_id();
        $slugs = wp_get_post_terms($id, 'product_cat', array('fields' => 'slugs'));
        if (!is_wp_error($slugs) && array_intersect($slugs, array('formaggi', 'yogurt'))) {
            $cat_blocca_estero = true;
        }
    }
    $paese = isset($package['destination']['country']) ? $package['destination']['country'] : '';
    $estero = ($paese !== '' && $paese !== 'IT');

    // Ricotta: solo ritiro in cantina.
    if (in_array('solo-ritiro', $classi, true)) {
        return array_filter($rates, function ($r) { return $r->get_method_id() === 'local_pickup'; });
    }
    // Estero: niente formaggi, yogurt, refrigerati, "solo Italia".
    if ($estero && ($cat_blocca_estero || array_intersect($classi, array('refrigerato', 'solo-italia')))) {
        return array();
    }
    // Italia: spedizione gratis sopra soglia (il supplemento refrigerato resta).
    if (!$estero && WC()->cart) {
        $totale = WC()->cart->get_subtotal() + WC()->cart->get_subtotal_tax();
        if ($totale >= QUIETE_SOGLIA_GRATIS) {
            foreach ($rates as $rate) {
                if ($rate->get_method_id() === 'flat_rate') {
                    $nuovo = max(0, (float) $rate->get_cost() - QUIETE_BASE_ITALIA);
                    $rate->set_cost($nuovo);
                    $rate->set_label($nuovo > 0
                        ? 'Spedizione gratuita (solo supplemento refrigerato)'
                        : 'Spedizione gratuita');
                }
            }
        }
    }
    return $rates;
}, 20, 2);

add_filter('woocommerce_no_shipping_available_html', function ($html) {
    return '<p>Per questo indirizzo non c\'è una spedizione disponibile. Formaggi, yogurt e prodotti refrigerati si spediscono solo in Italia; la ricotta si ritira in cantina.</p>';
});
