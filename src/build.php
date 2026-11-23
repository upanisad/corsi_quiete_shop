<?php
/**
 * Costruisce il negozio La Quiete dentro WordPress Playground.
 * Lanciato dal Blueprint di costruzione (build-pulito.json / build-sporco.json).
 * Prima del require, il Blueprint definisce QUIETE_VARIANTE = 'pulito' | 'sporco'.
 * Dati letti da wp-content/quiete-dati/ (prodotti.json, ordini.json) e immagini da quiete-dati/img/<SKU>.jpg.
 */
require_once '/wordpress/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$variante = defined('QUIETE_VARIANTE') ? QUIETE_VARIANTE : 'pulito';
$dir = WP_CONTENT_DIR . '/quiete-dati/';
$dati = json_decode(file_get_contents($dir . 'prodotti.json'), true);
$ordini = json_decode(file_get_contents($dir . 'ordini.json'), true);
$mese_negozio = (int) substr($dati['data_negozio'], 5, 2);

add_filter('pre_wp_mail', '__return_true');

if (!get_option('woocommerce_db_version')) { WC_Install::install(); }
if (!wc_get_page_id('shop') || wc_get_page_id('shop') < 1) { WC_Install::create_pages(); }

/* ---------- 1. Impostazioni ---------- */
update_option('blogname', 'La Quiete nelle Cime');
update_option('blogdescription', 'Negozio di prova per i corsi StyleSoft dML: azienda, prodotti e clienti sono inventati');
update_option('woocommerce_default_country', 'IT:TV');
update_option('woocommerce_store_address', 'Borgo Cime 1');
update_option('woocommerce_store_city', 'Pieve di Sotto');
update_option('woocommerce_currency', 'EUR');
update_option('woocommerce_currency_pos', 'right_space');
update_option('woocommerce_price_thousand_sep', '.');
update_option('woocommerce_price_decimal_sep', ',');
update_option('woocommerce_calc_taxes', 'yes');
update_option('woocommerce_prices_include_tax', 'yes');
update_option('woocommerce_tax_display_shop', 'incl');
update_option('woocommerce_tax_display_cart', 'incl');
update_option('woocommerce_shipping_tax_class', 'zero-rate');
update_option('woocommerce_allowed_countries', 'specific');
update_option('woocommerce_specific_allowed_countries', array('IT', 'AT', 'DE'));
update_option('woocommerce_demo_store', 'yes');
update_option('woocommerce_demo_store_notice', 'Negozio di prova per i corsi StyleSoft dML: non è un sito vero, nessun ordine verrà spedito.');
update_option('woocommerce_coming_soon', 'no');
update_option('woocommerce_store_pages_only', 'no');
update_option('woocommerce_allow_tracking', 'no');
update_option('woocommerce_show_marketplace_suggestions', 'no');
update_option('woocommerce_onboarding_profile', array('skipped' => true));
update_option('woocommerce_task_list_hidden', 'yes');
update_option('woocommerce_task_list_welcome_modal_dismissed', 'yes');
delete_transient('_wc_activation_redirect');
update_option('woocommerce_bacs_settings', array('enabled' => 'yes', 'title' => 'Bonifico bancario (finto)', 'description' => 'Pagamento finto: nessun conto reale.'));
update_option('woocommerce_cod_settings', array('enabled' => 'yes', 'title' => 'Pagamento alla consegna (finto)'));

/* ---------- 2. IVA: 22% standard, 10% e 4% come classi ---------- */
foreach (array(array('IVA 10%', 'iva-10', '10.0000'), array('IVA 4%', 'iva-4', '4.0000')) as $c) {
    if (!in_array($c[1], WC_Tax::get_tax_class_slugs(), true)) { WC_Tax::create_tax_class($c[0], $c[1]); }
}
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->prefix}woocommerce_tax_rates");
$classi_iva = array('' => array('IVA 22%', '22.0000'), 'iva-10' => array('IVA 10%', '10.0000'), 'iva-4' => array('IVA 4%', '4.0000'));
foreach ($classi_iva as $slug => $v) {
    WC_Tax::_insert_tax_rate(array('tax_rate_country' => 'IT', 'tax_rate_state' => '', 'tax_rate' => $v[1],
        'tax_rate_name' => $v[0], 'tax_rate_priority' => 1, 'tax_rate_compound' => 0,
        'tax_rate_shipping' => 0, 'tax_rate_order' => 1, 'tax_rate_class' => $slug));
}

/* ---------- 3. Categorie e classi di spedizione ---------- */
$cat_id = array();
foreach (array('Vini', 'Formaggi', 'Yogurt', 'Dolci', 'Confezioni regalo') as $nome) {
    $t = term_exists($nome, 'product_cat');
    if (!$t) { $t = wp_insert_term($nome, 'product_cat'); }
    $cat_id[$nome] = is_array($t) ? (int) $t['term_id'] : (int) $t;
}
$sp_id = array();
foreach (array('refrigerato' => 'Refrigerato', 'solo-ritiro' => 'Solo ritiro', 'solo-italia' => 'Solo Italia') as $slug => $nome) {
    $t = term_exists($slug, 'product_shipping_class');
    if (!$t) { $t = wp_insert_term($nome, 'product_shipping_class', array('slug' => $slug)); }
    $sp_id[$slug] = is_array($t) ? (int) $t['term_id'] : (int) $t;
}

/* ---------- 4. Zone di spedizione ---------- */
foreach (WC_Shipping_Zones::get_zones() as $z) { WC_Shipping_Zones::delete_zone($z['id']); }
$zi = new WC_Shipping_Zone();
$zi->set_zone_name('Italia'); $zi->set_zone_order(1); $zi->add_location('IT', 'country'); $zi->save();
$fid = $zi->add_shipping_method('flat_rate');
update_option("woocommerce_flat_rate_{$fid}_settings", array(
    'title' => 'Spedizione con corriere', 'tax_status' => 'none', 'cost' => '9.90', 'type' => 'class',
    'class_cost_' . $sp_id['refrigerato'] => '6.00', 'no_class_cost' => ''));
$pid = $zi->add_shipping_method('local_pickup');
update_option("woocommerce_local_pickup_{$pid}_settings", array('title' => 'Ritiro in cantina', 'tax_status' => 'none', 'cost' => '0'));
$ze = new WC_Shipping_Zone();
$ze->set_zone_name('Austria e Germania'); $ze->set_zone_order(2);
$ze->add_location('AT', 'country'); $ze->add_location('DE', 'country'); $ze->save();
$fe = $ze->add_shipping_method('flat_rate');
update_option("woocommerce_flat_rate_{$fe}_settings", array('title' => 'Spedizione estero', 'tax_status' => 'none', 'cost' => '14.90', 'type' => 'class'));

/* ---------- 5. Prodotti ---------- */
$mesi_it = array(1 => 'gennaio', 2 => 'febbraio', 3 => 'marzo', 4 => 'aprile', 5 => 'maggio', 6 => 'giugno',
    7 => 'luglio', 8 => 'agosto', 9 => 'settembre', 10 => 'ottobre', 11 => 'novembre', 12 => 'dicembre');
$prod = array(); // sku => WC_Product
foreach ($dati['prodotti'] as $d) {
    $esistente = wc_get_product_id_by_sku($d['sku']);
    $p = $esistente ? wc_get_product($esistente) : new WC_Product_Simple();
    $p->set_name($d['nome']);
    $p->set_sku($d['sku']);
    $p->set_status('publish');
    $p->set_regular_price((string) $d['prezzo']);
    $p->set_tax_status('taxable');
    $p->set_tax_class($d['classe_iva']);
    $p->set_catalog_visibility('visible');
    $p->set_category_ids(array($cat_id[$d['categoria']]));
    $p->set_shipping_class_id($d['classe_spedizione'] ? $sp_id[$d['classe_spedizione']] : 0);
    $p->set_short_description($d['breve']);
    $extra = '<p><strong>Formato:</strong> ' . esc_html($d['formato']) . '.</p>';
    if ($d['mesi_stagione']) {
        $extra .= '<p><strong>Disponibile:</strong> ' . esc_html(implode(', ', array_map(function ($m) use ($mesi_it) { return $mesi_it[$m]; }, $d['mesi_stagione']))) . '.</p>';
    }
    $p->set_description('<p>' . esc_html($d['lunga']) . '</p>' . $extra);
    $fuori = $d['mesi_stagione'] && !in_array($mese_negozio, $d['mesi_stagione'], true);
    $p->set_manage_stock(false);
    $p->set_stock_status($fuori ? 'outofstock' : 'instock');
    $img = $dir . 'img/' . $d['sku'] . '.jpg';
    if (file_exists($img) && !$p->get_image_id()) {
        $tmp = wp_tempnam($d['sku'] . '.jpg');
        copy($img, $tmp);
        $aid = media_handle_sideload(array('name' => $d['sku'] . '.jpg', 'tmp_name' => $tmp), 0, $d['nome']);
        if (!is_wp_error($aid)) {
            $p->set_image_id($aid);
            update_post_meta($aid, '_wp_attachment_image_alt', $d['nome']);
        }
    }
    $p->save();
    $prod[$d['sku']] = $p;
}

/* ---------- 6. Errori piantati (solo variante sporca) ---------- */
if ($variante === 'sporco') {
    $prod['V01']->set_regular_price('125.00'); $prod['V01']->save();                       // 1 prezzo x10
    $prod['F03']->set_tax_class(''); $prod['F03']->save();                                 // 2 IVA 22% sul formaggio
    $prod['F06']->set_shipping_class_id(0); $prod['F06']->save();                          // 3 ricotta spedibile
    $prod['D05']->set_stock_status('instock'); $prod['D05']->save();                       // 4 focaccia di Pasqua in vendita a novembre
    $prod['V09']->set_category_ids(array($cat_id['Formaggi'])); $prod['V09']->save();      // 5 passito tra i formaggi
    $c = $dati['c02_sporca'];                                                              // 6 descrizione C02 con il vino sbagliato
    $prod['C02']->set_description(str_replace($c[0], $c[1], $prod['C02']->get_description()));
    $prod['C02']->set_short_description(str_replace('Un vino', 'Un vino (Brut "Cime")', $prod['C02']->get_short_description()));
    $prod['C02']->save();
}

/* ---------- 7. Clienti e ordini (dal dataset del caso) ---------- */
$utenti = array();
foreach ($ordini as $o) {
    $c = $o['cliente'];
    if (isset($utenti[$c['id']])) { continue; }
    $uid = email_exists($c['email']);
    if (!$uid) {
        $uid = wc_create_new_customer($c['email'], '', wp_generate_password(20), array('first_name' => $c['nome'], 'last_name' => $c['cognome']));
    }
    if (is_wp_error($uid)) { continue; }
    $wc = new WC_Customer($uid);
    foreach (array('billing', 'shipping') as $t) {
        $wc->{"set_{$t}_first_name"}($c['nome']); $wc->{"set_{$t}_last_name"}($c['cognome']);
        $wc->{"set_{$t}_address_1"}($c['indirizzo']); $wc->{"set_{$t}_city"}($c['citta']);
        $wc->{"set_{$t}_postcode"}($c['cap']); $wc->{"set_{$t}_state"}($c['provincia']); $wc->{"set_{$t}_country"}('IT');
    }
    $wc->set_billing_email($c['email']); $wc->set_billing_phone($c['telefono']);
    $wc->save();
    $utenti[$c['id']] = $uid;
}
$n_ord = 0;
foreach ($ordini as $o) {
    if (!isset($utenti[$o['cliente']['id']])) { continue; }
    $c = $o['cliente'];
    $ord = wc_create_order(array('customer_id' => $utenti[$c['id']]));
    foreach (array('billing', 'shipping') as $t) {
        $ord->{"set_{$t}_first_name"}($c['nome']); $ord->{"set_{$t}_last_name"}($c['cognome']);
        $ord->{"set_{$t}_address_1"}($c['indirizzo']); $ord->{"set_{$t}_city"}($c['citta']);
        $ord->{"set_{$t}_postcode"}($c['cap']); $ord->{"set_{$t}_state"}($c['provincia']); $ord->{"set_{$t}_country"}('IT');
    }
    $ord->set_billing_email($c['email']); $ord->set_billing_phone($c['telefono']);
    foreach ($o['righe'] as $r) {
        $sku = $r['codice'];
        $q = (int) $r['q'];
        foreach ($dati['prodotti'] as $d) {
            if ($d['sku'] === $sku && $d['confezione_n']) { $q = max(1, (int) ceil($q / $d['confezione_n'])); }
        }
        if (isset($prod[$sku])) { $ord->add_product($prod[$sku], $q); }
    }
    if ($o['spedizione'] > 0) {
        $s = new WC_Order_Item_Shipping();
        $s->set_method_title('Spedizione con corriere'); $s->set_method_id('flat_rate'); $s->set_total($o['spedizione']);
        $ord->add_item($s);
    }
    $ord->set_payment_method('bacs'); $ord->set_payment_method_title('Bonifico bancario (finto)');
    $ord->set_date_created($o['data'] . ' 10:00:00');
    $ord->update_meta_data('id_dataset', $o['id']);
    $ord->calculate_totals(true);
    $ord->set_status($o['stato']);
    if (in_array($o['stato'], array('completed', 'processing', 'refunded'), true)) { $ord->set_date_paid($o['data'] . ' 10:05:00'); }
    $ord->add_order_note('Ordine del dataset del caso: ' . $o['id'] . ($o['note'] ? ' (' . $o['note'] . ')' : ''));
    $ord->save();
    $n_ord++;
}

/* ---------- 8. Pagine ---------- */
$pagine = array(
    'Chi siamo' => '<p>Siamo una famiglia di viticoltori e casari di Borgo Cime, sulle colline del Valdobbiadene: vino, formaggi, yogurt e dolci.</p><p><strong>Attenzione:</strong> questo è un negozio di prova creato per i corsi di StyleSoft dML. L\'azienda, i prodotti, i prezzi e i clienti sono inventati.</p>',
    'Spedizioni e resi' => '<p>Italia: 9,90 €, gratis sopra 89 €. I prodotti refrigerati hanno un supplemento di 6,00 € e partono solo il lunedì e il martedì.</p><p>Austria e Germania: solo vino e dolci non refrigerati. Formaggi e yogurt si spediscono solo in Italia. La ricotta fresca si ritira in cantina.</p>',
    'Contatti' => '<p>Email: info@quietenellecime.example<br>Telefono: 0499 55 01 001</p><p>Sito dimostrativo: nessuna richiesta riceverà risposta.</p>',
    'Privacy' => '<p>Testo segnaposto. Negozio di prova: non raccogliamo dati di persone reali.</p>',
);
foreach ($pagine as $titolo => $testo) {
    if (!get_posts(array('post_type' => 'page', 'title' => $titolo, 'post_status' => 'any', 'numberposts' => 1))) {
        wp_insert_post(array('post_type' => 'page', 'post_title' => $titolo, 'post_status' => 'publish', 'post_content' => $testo));
    }
}
update_option('show_on_front', 'page');
update_option('page_on_front', wc_get_page_id('shop'));

flush_rewrite_rules();
wc_delete_product_transients();
update_option('quiete_build_esito', array('variante' => $variante, 'prodotti' => count($prod), 'clienti' => count($utenti), 'ordini' => $n_ord));
