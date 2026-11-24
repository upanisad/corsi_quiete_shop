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
update_option('blogdescription', 'Azienda agricola · Agriturismo, dal 1978');
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

/* ---------- 4b. Logo ---------- */
$logo = $dir . 'img/logo-quiete.png';
if (file_exists($logo)) {
    $tmp = wp_tempnam('logo-quiete.png');
    copy($logo, $tmp);
    $lid = media_handle_sideload(array('name' => 'logo-quiete.png', 'tmp_name' => $tmp), 0, 'Logo La Quiete nelle Cime');
    if (!is_wp_error($lid)) {
        update_post_meta($lid, '_wp_attachment_image_alt', 'La Quiete nelle Cime, azienda agricola e agriturismo dal 1978');
        set_theme_mod('custom_logo', $lid);
        update_option('site_logo', $lid);
    }
}

/* ---------- 5. Prodotti ---------- */
$mesi_it = array(1 => 'gennaio', 2 => 'febbraio', 3 => 'marzo', 4 => 'aprile', 5 => 'maggio', 6 => 'giugno',
    7 => 'luglio', 8 => 'agosto', 9 => 'settembre', 10 => 'ottobre', 11 => 'novembre', 12 => 'dicembre');
$prod = array(); // sku => WC_Product
$nomi = array();
foreach ($dati['prodotti'] as $x) { $nomi[$x['sku']] = $x['nome']; }
update_option('woocommerce_weight_unit', 'kg');
update_option('woocommerce_dimension_unit', 'cm');
$pos = 0;
foreach ($dati['prodotti'] as $d) {
    $pos++;
    $esistente = wc_get_product_id_by_sku($d['sku']);
    $p = $esistente ? wc_get_product($esistente) : new WC_Product_Simple();
    $p->set_name($d['nome']);
    $p->set_sku($d['sku']);
    $p->set_status('publish');
    $p->set_regular_price((string) $d['prezzo']);
    $p->set_tax_status('taxable');
    $p->set_tax_class($d['classe_iva']);
    $p->set_catalog_visibility('visible');
    $p->set_menu_order($pos);
    $p->set_category_ids(array($cat_id[$d['categoria']]));
    $p->set_shipping_class_id($d['classe_spedizione'] ? $sp_id[$d['classe_spedizione']] : 0);
    $p->set_short_description($d['breve']);
    $extra = '<p><strong>Formato:</strong> ' . esc_html($d['formato']) . '.</p>';
    if ($d['mesi_stagione']) {
        $extra .= '<p><strong>Disponibile:</strong> ' . esc_html(implode(', ', array_map(function ($m) use ($mesi_it) { return $mesi_it[$m]; }, $d['mesi_stagione']))) . '.</p>';
    }
    $sez = '<p>' . esc_html($d['lunga']) . '</p>' . $extra;
    if ($d['componenti']) {
        $sez .= '<h3>Contenuto</h3><ul>';
        foreach ($d['componenti'] as $cc) { $sez .= '<li>' . esc_html($nomi[$cc]) . '</li>'; }
        $sez .= '</ul>';
    }
    if (!empty($d['abbinamenti'])) { $sez .= '<h3>Abbinamenti</h3><p>' . esc_html($d['abbinamenti']) . '</p>'; }
    if (!empty($d['ingredienti']) || !empty($d['allergeni_testo'])) {
        $sez .= '<h3>Ingredienti e allergeni</h3>';
        if (!empty($d['ingredienti'])) {
            $ing = preg_replace('/\b([A-ZÀ-Ý]{3,})\b/u', '<strong>$1</strong>', esc_html($d['ingredienti']));
            $sez .= '<p><strong>Ingredienti:</strong> ' . $ing . '.</p>';
        }
        if (!empty($d['allergeni_testo'])) { $sez .= '<p><strong>Allergeni:</strong> ' . esc_html($d['allergeni_testo']) . '.</p>'; }
        if (!empty($d['tracce_testo'])) { $sez .= '<p>Può contenere tracce di: ' . esc_html($d['tracce_testo']) . '.</p>'; }
    }
    if (!empty($d['nutrizione'])) {
        $sez .= '<h3>' . esc_html($d['nutrizione']['titolo']) . '</h3><table><tbody>';
        foreach ($d['nutrizione']['righe'] as $r) {
            $v = ($variante === 'sporco' && $d['nutrizione']['mancante']) ? 'Da verificare' : $r[1];
            $sez .= '<tr><th>' . esc_html($r[0]) . '</th><td>' . esc_html($v) . '</td></tr>';
        }
        $sez .= '</tbody></table>';
    }
    if (!empty($d['conservazione'])) { $sez .= '<h3>Conservazione</h3><p>' . esc_html($d['conservazione']) . '</p>'; }
    $p->set_description($sez);
    $atts = array(); $ia = 0;
    foreach ($d['attributi'] as $a) {
        $val = ($variante === 'sporco' && $a['mancante']) ? 'Da verificare' : $a['valore'];
        $at = new WC_Product_Attribute();
        $at->set_id(0); $at->set_name($a['nome']); $at->set_options(array($val));
        $at->set_position($ia++); $at->set_visible(true); $at->set_variation(false);
        $atts[] = $at;
    }
    $p->set_attributes($atts);
    if (!empty($d['peso_kg'])) { $p->set_weight((string) $d['peso_kg']); }
    if (!empty($d['dim_cm'])) { $p->set_length((string) $d['dim_cm'][2]); $p->set_width((string) $d['dim_cm'][0]); $p->set_height((string) $d['dim_cm'][1]); }
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
    $prod['C02']->set_description(str_replace(array($c[0], esc_html($c[0])), array($c[1], esc_html($c[1])), $prod['C02']->get_description()));
    $prod['C02']->save();
    $att = array();                                                                        // 7 V03 Extra Brut con 14 g/l di zuccheri (valore da Extra Dry)
    foreach ($prod['V03']->get_attributes() as $a) {                                       // cloni: se si modificano gli stessi oggetti WooCommerce non vede il cambio e non salva
        $b = clone $a;
        if ($b->get_name() === 'Zuccheri residui') { $b->set_options(array('14 g/l')); }
        $att[] = $b;
    }
    $prod['V03']->set_attributes($att); $prod['V03']->save();
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
    wp_update_post(array('ID' => $ord->get_id(), 'post_title' => 'Ordine – ' . $o['data']));
    $n_ord++;
}

/* ---------- 8a. Foto del sito (luoghi e persone, generate con AI) ---------- */
/* dati in quiete-dati/immagini-sito.json, file in quiete-dati/img-sito/. Nello sporco: le foto in img-sito/originali/ al posto di quelle pronte (errore 8: L02 non compressa; errore 11: ritratto di Nicola col logo di un marchio reale) e niente testo alternativo (errore 9) */
$foto = array();
$fs_json = $dir . 'immagini-sito.json';
$fs = file_exists($fs_json) ? json_decode(file_get_contents($fs_json), true) : array('immagini' => array());
foreach ($fs['immagini'] as $cod => $x) {
    $file = $dir . 'img-sito/' . $cod . '.jpg';
    if ($variante === 'sporco' && file_exists($dir . 'img-sito/originali/' . $cod . '.jpg')) { $file = $dir . 'img-sito/originali/' . $cod . '.jpg'; }
    if (!file_exists($file)) { continue; }
    $tmp = wp_tempnam($cod . '.jpg');
    copy($file, $tmp);
    $nome_file = ($variante === 'sporco') ? 'IMG_' . (4100 + count($foto)) . '.jpg' : sanitize_title($x['titolo']) . '.jpg';
    $aid = media_handle_sideload(array('name' => $nome_file, 'tmp_name' => $tmp), 0, $x['titolo']);
    if (is_wp_error($aid)) { continue; }
    $alt = ($variante === 'sporco') ? '' : $x['alt'];
    if ($alt !== '') { update_post_meta($aid, '_wp_attachment_image_alt', $alt); }
    $cap = ($variante === 'sporco' && isset($x['didascalia_sporco'])) ? $x['didascalia_sporco'] : $x['didascalia'];
    $foto[$cod] = array('id' => $aid, 'alt' => $alt, 'cap' => $cap, 'full' => wp_get_attachment_url($aid), 'large' => wp_get_attachment_image_url($aid, 'large'));
}
/* blocco immagine; $didascalia=false per non mostrarla */
$img = function ($cod, $didascalia = true, $link = '') use ($foto) {
    if (!isset($foto[$cod])) { return ''; }
    $f = $foto[$cod];
    $tag = '<img src="' . esc_url($f['large']) . '" alt="' . esc_attr($f['alt']) . '" class="wp-image-' . $f['id'] . '"/>';
    if ($link) { $tag = '<a href="' . esc_url($link) . '">' . $tag . '</a>'; }
    $cap = $didascalia ? '<figcaption class="wp-element-caption">' . esc_html($f['cap']) . '</figcaption>' : '';
    $attr = array('id' => $f['id'], 'sizeSlug' => 'large', 'linkDestination' => $link ? 'custom' : 'none');
    return '<!-- wp:image ' . wp_json_encode($attr) . ' --><figure class="wp-block-image size-large">' . $tag . $cap . '</figure><!-- /wp:image -->';
};
$foto_cat = array('Vini' => 'L04', 'Formaggi' => 'L11', 'Yogurt' => 'L07', 'Dolci' => 'L14', 'Confezioni regalo' => 'L10');
foreach ($foto_cat as $nome_cat => $cod) {
    if (isset($foto[$cod], $cat_id[$nome_cat])) { update_term_meta($cat_id[$nome_cat], 'thumbnail_id', $foto[$cod]['id']); }
}
/* due o tre immagini affiancate */
$affianca = function ($blocchi) {
    $c = '<!-- wp:columns --><div class="wp-block-columns">';
    foreach ($blocchi as $b) { $c .= '<!-- wp:column --><div class="wp-block-column">' . $b . '</div><!-- /wp:column -->'; }
    return $c . '</div><!-- /wp:columns -->';
};
/* griglia del team, 3 per riga, con nome e ruolo */
$persone = '';
$righe = array_chunk(array('bruno', 'dario', 'elena', 'graziella', 'ivano', 'marta', 'nicola', 'sandro', 'toni'), 3);
foreach ($righe as $riga) {
    $col = array();
    foreach ($riga as $n) {
        $k = 'R-' . $n;
        if (!isset($foto[$k], $fs['immagini'][$k])) { continue; }
        $col[] = $img($k, false) . '<!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center"><strong>' . esc_html($fs['immagini'][$k]['nome']) . '</strong><br>' . esc_html($fs['immagini'][$k]['ruolo']) . '</p><!-- /wp:paragraph -->';
    }
    if ($col) { $persone .= $affianca($col); }
}
$par = function ($t) { return '<!-- wp:paragraph --><p>' . $t . '</p><!-- /wp:paragraph -->'; };
$tit = function ($t) { return '<!-- wp:heading --><h2 class="wp-block-heading">' . $t . '</h2><!-- /wp:heading -->'; };
$avviso = $par('<strong>Attenzione:</strong> questo è un negozio di prova creato per i corsi di StyleSoft dML. L\'azienda, i prodotti, i prezzi e i clienti sono inventati, e le foto sono generate con AI.');

/* ---------- 8. Pagine ---------- */
$pagine = array(
    'Chi siamo' => $img('IN01')
        . $par('Siamo una famiglia di viticoltori e casari di Borgo Cime, sulle colline del Valdobbiadene. Tutto quello che vendiamo lo facciamo noi: il vino prima di tutto, poi formaggi, yogurt e dolci.')
        . $tit('Dal 1978')
        . $par('Nel 1978 Aldo e Rina Bressanel smettono di portare l\'uva alla cantina sociale e imbottigliano il primo vino con la loro etichetta. Dal 1996 l\'azienda è guidata dal figlio Bruno. Nel 2008 apre l\'agriturismo, nel 2012 il caseificio, nel 2015 il laboratorio dei dolci. Oggi in azienda lavorano anche Elena, che guida l\'agriturismo, e Nicola, che nel 2023 ha aperto questo negozio.')
        . $img('IN02')
        . $tit('Il vigneto')
        . $par('Quattordici ettari, nove dei quali a Glera per il Valdobbiadene DOCG. I filari stanno su pendenze così forti che quasi tutto si lavora a mano, vendemmia compresa. Ne escono circa 110.000 bottiglie l\'anno.')
        . $img('L04')
        . $tit('La cantina')
        . $par('La cantina ha due anime. Nella sala vasche, tutta in acciaio, nasce il Prosecco: le bollicine si fanno in autoclave, grandi serbatoi chiusi che tengono la pressione. Nella bottaia, sotto la volta in mattoni, riposano il passito e i vini fermi. Le degustazioni si fanno su prenotazione, nella sala accanto alla bottaia.')
        . $affianca(array($img('L08'), $img('L10')))
        . $tit('La stalla, il caseificio, i dolci')
        . $par('Dodici vacche danno il latte per i nostri formaggi e yogurt, che Marta lavora ogni mattina a pochi metri dalla stalla. Accanto c\'è il laboratorio dei dolci, nato per le colazioni dell\'agriturismo.')
        . $affianca(array($img('L07'), $img('L11'), $img('L14')))
        . $tit('L\'agriturismo')
        . $par('Otto camere e una sala da 45 coperti, aperti da metà marzo all\'Epifania. Tutto nella pagina <a href="' . esc_url(home_url('/agriturismo/')) . '">Agriturismo</a>.')
        . $tit('Le persone')
        . $persone
        . $avviso,
    'Agriturismo' => $img('L06')
        . $par('L\'agriturismo apre nel 2008: prima la sala, poi le camere. Oggi le camere sono otto, con 18 posti letto, e la sala ha 45 coperti. Siamo aperti da metà marzo all\'Epifania; si cena il venerdì, il sabato e la domenica.')
        . $tit('La sala e la cucina')
        . $par('In sala c\'è Elena; in cucina Luisa, con le verdure dell\'orto, i nostri formaggi e, naturalmente, il nostro vino.')
        . $affianca(array($img('L18'), $img('L20')))
        . $tit('Le camere')
        . $par('Camere semplici, con la finestra sulle vigne. La colazione la prepara il nostro laboratorio dei dolci.')
        . $img('L19')
        . $tit('Prenotare')
        . $par('Per una camera, una cena o una degustazione scriveteci a info@quietenellecime.example o chiamate lo 0499 55 01 001.')
        . $avviso,
    'Spedizioni e resi' => $par('Italia: 9,90 €, gratis sopra 89 €. I prodotti refrigerati hanno un supplemento di 6,00 € e partono solo il lunedì e il martedì.')
        . $par('Austria e Germania: solo vino e dolci non refrigerati. Formaggi e yogurt si spediscono solo in Italia. La ricotta fresca si ritira in cantina.')
        . $img('L03'),
    'Contatti' => $par('Email: info@quietenellecime.example<br>Telefono: 0499 55 01 001')
        . $tit('Come arrivare')
        . $par('Borgo Cime è in cima alla collina, 6 km sopra Pieve di Sotto. L\'ultimo chilometro è stretto e in salita.')
        . $img('L03', false)
        . $par('Sito dimostrativo: nessuna richiesta riceverà risposta.'),
    'Privacy' => '<p>Testo segnaposto. Negozio di prova: non raccogliamo dati di persone reali.</p>',
);
foreach ($pagine as $titolo => $testo) {
    if (!get_posts(array('post_type' => 'page', 'title' => $titolo, 'post_status' => 'any', 'numberposts' => 1))) {
        wp_insert_post(array('post_type' => 'page', 'post_title' => $titolo, 'post_status' => 'publish', 'post_content' => $testo));
    }
}
/* pulizia residui WordPress e pagine WooCommerce in italiano */
foreach (array('sample-page', 'hello-world') as $slug) {
    foreach (get_posts(array('name' => $slug, 'post_type' => array('page', 'post'), 'post_status' => 'any', 'numberposts' => 1)) as $x) { wp_delete_post($x->ID, true); }
}
foreach (array('woocommerce_cart_page_id' => 'Carrello', 'woocommerce_checkout_page_id' => 'Cassa', 'woocommerce_myaccount_page_id' => 'Il mio account') as $opt => $tit) {
    $pid = (int) get_option($opt);
    if ($pid) { wp_update_post(array('ID' => $pid, 'post_title' => $tit)); }
}
$shop_id = wc_get_page_id('shop');
if ($shop_id) { wp_update_post(array('ID' => $shop_id, 'post_title' => 'Negozio')); }
update_option('sidebars_widgets', array('wp_inactive_widgets' => array(), 'sidebar-1' => array(), 'array_version' => 3));
/* ---------- 9. Home, footer e aspetto (tema a blocchi Drinkify Lite) ---------- */
$home_categorie = '';
$tess = array();
foreach ($foto_cat as $nome_cat => $cod) {
    if (!isset($foto[$cod], $cat_id[$nome_cat])) { continue; }
    $link = get_term_link($cat_id[$nome_cat], 'product_cat');
    if (is_wp_error($link)) { continue; }
    $tess[] = $img($cod, false, $link) . '<!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center"><a href="' . esc_url($link) . '"><strong>' . esc_html($nome_cat) . '</strong></a></p><!-- /wp:paragraph -->';
}
if ($tess) {
    $home_categorie = '<!-- wp:heading {"textAlign":"center"} --><h2 class="wp-block-heading has-text-align-center">Le categorie</h2><!-- /wp:heading -->'
        . $affianca($tess)
        . '<!-- wp:spacer {"height":"40px"} --><div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->';
}
$cop = isset($foto['L02']) ? $foto['L02'] : null;
$cop_attr = $cop ? '"url":"' . esc_url($cop['full']) . '","id":' . $cop['id'] . ',"alt":"' . esc_attr($cop['alt']) . '","dimRatio":50,' : '';
$cop_img = $cop ? '<img class="wp-block-cover__image-background wp-image-' . $cop['id'] . '" alt="' . esc_attr($cop['alt']) . '" src="' . esc_url($cop['full']) . '" data-object-fit="cover"/>' : '';
$cop_dim = $cop ? 'has-background-dim-50 has-background-dim' : 'has-background-dim-100 has-background-dim';
$home_blocks = '<!-- wp:cover {' . $cop_attr . '"customOverlayColor":"#4f1420","minHeight":' . ($cop ? 480 : 380) . ',"isDark":true,"align":"full"} -->'
 . '<div class="wp-block-cover alignfull is-dark" style="min-height:' . ($cop ? 480 : 380) . 'px"><span aria-hidden="true" class="wp-block-cover__background ' . $cop_dim . '" style="background-color:#4f1420"></span>' . $cop_img . '<div class="wp-block-cover__inner-container">'
 . '<!-- wp:heading {"textAlign":"center","level":1,"textColor":"white"} --><h1 class="wp-block-heading has-text-align-center has-white-color has-text-color">La Quiete nelle Cime</h1><!-- /wp:heading -->'
 . '<!-- wp:paragraph {"align":"center","textColor":"white","fontSize":"large"} --><p class="has-text-align-center has-white-color has-text-color has-large-font-size">Vino, formaggi, yogurt e dolci fatti da noi, sui colli del Prosecco Superiore. Azienda agricola e agriturismo dal 1978.</p><!-- /wp:paragraph -->'
 . '<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="' . esc_url(get_permalink(wc_get_page_id('shop'))) . '">Vai al negozio</a></div><!-- /wp:button --></div><!-- /wp:buttons -->'
 . '</div></div><!-- /wp:cover -->'
 . '<!-- wp:spacer {"height":"40px"} --><div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->'
 . $home_categorie
 . (isset($foto['L04'], $foto['IN02'], $foto['IN01'])
    ? '<!-- wp:heading {"textAlign":"center"} --><h2 class="wp-block-heading has-text-align-center">Chi siamo</h2><!-- /wp:heading -->'
      . '<!-- wp:columns --><div class="wp-block-columns">'
      . '<!-- wp:column --><div class="wp-block-column">' . $img('L04', false, home_url('/chi-siamo/')) . '<!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center"><strong>Le vigne</strong><br>14 ettari ripidi, lavorati a mano.</p><!-- /wp:paragraph --></div><!-- /wp:column -->'
      . '<!-- wp:column --><div class="wp-block-column">' . $img('IN02', false, home_url('/chi-siamo/')) . '<!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center"><strong>La famiglia</strong><br>Tre generazioni, dal 1978.</p><!-- /wp:paragraph --></div><!-- /wp:column -->'
      . '<!-- wp:column --><div class="wp-block-column">' . $img('IN01', false, home_url('/chi-siamo/')) . '<!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center"><strong>La squadra</strong><br>Vigna, cantina, caseificio e agriturismo.</p><!-- /wp:paragraph --></div><!-- /wp:column -->'
      . '</div><!-- /wp:columns -->'
      . '<!-- wp:spacer {"height":"40px"} --><div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->'
    : '')
 . '<!-- wp:heading {"textAlign":"center"} --><h2 class="wp-block-heading has-text-align-center">I nostri prodotti</h2><!-- /wp:heading -->'
 . '<!-- wp:shortcode -->[products limit="12" columns="3" orderby="menu_order" order="ASC"]<!-- /wp:shortcode -->';
$home_id = wp_insert_post(array('post_type' => 'page', 'post_title' => 'Home', 'post_name' => 'home', 'post_status' => 'publish', 'post_content' => $home_blocks));

/* inserisce un template/parte di template e gli assegna esplicitamente il tema (tax_input non vale senza utente loggato) */
function quiete_tpl($tipo, $slug, $titolo, $contenuto, $tema, $area = '') {
    $id = wp_insert_post(array('post_type' => $tipo, 'post_name' => $slug, 'post_title' => $titolo, 'post_status' => 'publish', 'post_content' => $contenuto));
    if ($id && !is_wp_error($id)) {
        wp_set_object_terms($id, $tema, 'wp_theme');
        if ($area) { wp_set_object_terms($id, $area, 'wp_template_part_area'); }
    }
    return $id;
}

/* parti del template: sostituisco il footer d'esempio del tema (indirizzo e telefono inventati altrove) */
$tema = get_stylesheet();
$footer_blocks = '<!-- wp:group {"style":{"spacing":{"padding":{"top":"40px","bottom":"40px"}}},"backgroundColor":"contrast","textColor":"base","layout":{"type":"constrained"}} -->'
 . '<div class="wp-block-group has-base-color has-contrast-background-color has-text-color has-background" style="padding-top:40px;padding-bottom:40px">'
 . '<!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center"><strong>La Quiete nelle Cime</strong> · Azienda agricola · Agriturismo, dal 1978<br>Borgo Cime 1, Pieve di Sotto (TV) · info@quietenellecime.example · 0499 55 01 001</p><!-- /wp:paragraph -->'
 . '<!-- wp:page-list {"isNavigationChild":false} /-->'
 . '<!-- wp:paragraph {"align":"center","fontSize":"small"} --><p class="has-text-align-center has-small-font-size">Negozio di prova per i corsi StyleSoft dML: azienda, prodotti, prezzi e clienti sono inventati. Le immagini sono generate con AI.</p><!-- /wp:paragraph -->'
 . '</div><!-- /wp:group -->';
$footers = get_block_templates(array('area' => 'footer'), 'wp_template_part');
foreach ($footers as $f) {
    $id = quiete_tpl('wp_template_part', $f->slug, 'Footer', $footer_blocks, $tema, 'footer');
}
/* il banner "Sweet Elegant Fine Winery" sta dentro la parte "Header With Banner": la sostituisco con un'intestazione mia (logo, titolo, menu, account, carrello) */
$hdr = 'header';
$u = function ($path) { return esc_url(home_url($path)); };
$voci = array('Home' => '/', 'Negozio' => '/shop/', 'Chi siamo' => '/chi-siamo/', 'Agriturismo' => '/agriturismo/', 'Contatti' => '/contatti/', 'Spedizioni e resi' => '/spedizioni-e-resi/');
$nav = '';
foreach ($voci as $et => $pa) {
    $nav .= '<!-- wp:navigation-link {"label":"' . $et . '","type":"custom","url":"' . $u($pa) . '","kind":"custom","isTopLevelLink":true} /-->';
}
$header_blocks = '<!-- wp:group {"style":{"spacing":{"padding":{"top":"14px","bottom":"14px","left":"24px","right":"24px"}},"color":{"background":"#ffffff"}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"center"}} -->'
 . '<div class="wp-block-group has-background" style="background-color:#ffffff;padding:14px 24px">'
 . '<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} --><div class="wp-block-group">'
 . '<!-- wp:site-logo {"width":150} /-->'
 . '<!-- wp:group {"layout":{"type":"constrained"}} --><div class="wp-block-group">'
 . '<!-- wp:site-title {"level":0,"style":{"color":{"text":"#4f1420"},"typography":{"fontSize":"26px"}}} /-->'
 . '<!-- wp:site-tagline {"style":{"color":{"text":"#7a6658"},"typography":{"fontSize":"14px"}}} /-->'
 . '</div><!-- /wp:group --></div><!-- /wp:group -->'
 . '<!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap","verticalAlignment":"center"}} --><div class="wp-block-group">'
 . '<!-- wp:navigation {"overlayMenu":"mobile","layout":{"type":"flex","setCascadingProperties":true,"justifyContent":"right"}} -->' . $nav . '<!-- /wp:navigation -->'
 . '<!-- wp:woocommerce/customer-account {"displayStyle":"icon_only","iconClass":"wc-block-customer-account__account-icon"} /-->'
 . '<!-- wp:woocommerce/mini-cart /-->'
 . '</div><!-- /wp:group --></div><!-- /wp:group -->';
foreach (get_block_templates(array('area' => 'header'), 'wp_template_part') as $h) {
    if (preg_match('/banner|hero|^header$|primary/i', $h->slug)) {
        quiete_tpl('wp_template_part', $h->slug, 'Header', $header_blocks, $tema, 'header');
    }
}
$ftr = $footers ? $footers[0]->slug : 'footer';
$tpl = '<!-- wp:template-part {"slug":"' . $hdr . '","tagName":"header"} /--><!-- wp:group {"tagName":"main","layout":{"type":"default"}} --><main class="wp-block-group"><!-- wp:post-content {"layout":{"type":"default"}} /--></main><!-- /wp:group --><!-- wp:template-part {"slug":"' . $ftr . '","tagName":"footer"} /-->';
foreach (array('front-page', 'home') as $slug) {
    quiete_tpl('wp_template', $slug, 'Home', $tpl, $tema);
}

/* pagine del negozio: sostituisco i "pattern" inglesi del tema (banner, sidebar con filtri rotti, FAQ, pagamenti) con blocchi miei in italiano */
$banda = function ($blocco) {
    return '<!-- wp:group {"style":{"color":{"background":"#4f1420"},"spacing":{"padding":{"top":"36px","bottom":"36px"}}},"layout":{"type":"constrained"}} -->'
        . '<div class="wp-block-group has-background" style="background-color:#4f1420;padding-top:36px;padding-bottom:36px">' . $blocco . '</div><!-- /wp:group -->';
};
$titolo_bianco = '"textAlign":"center","level":1,"style":{"color":{"text":"#ffffff"}}';
$b_query = $banda('<!-- wp:query-title {"type":"archive","showPrefix":false,' . $titolo_bianco . '} /-->');
$b_post  = $banda('<!-- wp:post-title {' . $titolo_bianco . '} /-->');
$spedizioni = '<!-- wp:group {"style":{"spacing":{"padding":{"top":"16px","bottom":"16px"}}},"layout":{"type":"constrained"}} --><div class="wp-block-group" style="padding-top:16px;padding-bottom:16px">'
    . '<!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Spedizioni e resi</h3><!-- /wp:heading -->'
    . '<!-- wp:paragraph --><p>Spedizione gratuita da 89 €. I prodotti freschi hanno regole particolari: tutti i dettagli sono nella pagina <a href="' . esc_url(home_url('/spedizioni-e-resi/')) . '">Spedizioni e resi</a>.</p><!-- /wp:paragraph -->'
    . '</div><!-- /wp:group -->';
$sost_pattern = array(
    'drinkify-lite/shop-header' => $b_query,
    'drinkify-lite/post-header-with-background' => $b_post,
    'drinkify-lite/archive-header-with-background' => $b_query,
    'drinkify-lite/blog-header-with-background' => $b_query,
    'drinkify-lite/secure-transactions' => '',
    'drinkify-lite/shopping-features' => '',
    'drinkify-lite/product-faq' => $spedizioni,
);
$sost_testi = array(
    '>Description<' => '>Descrizione<',
    '>Additional Information<' => '>Informazioni aggiuntive<',
    '>Reviews<' => '>Recensioni<',
    '>Related Products<' => '>Prodotti correlati<',
    '"prefix":"Category: "' => '"prefix":"Categoria: "',
    '<p>No products were found matching your selection.</p>' => '<p>Nessun prodotto corrisponde alla ricerca.</p>',
    '<strong>No results found</strong>' => '<strong>Nessun risultato</strong>',
);
foreach (get_block_templates(array(), 'wp_template') as $t) {
    $c = $t->content;
    foreach ($sost_pattern as $slug_p => $rep) { $c = str_replace('<!-- wp:pattern {"slug":"' . $slug_p . '"} /-->', $rep, $c); }
    $c = preg_replace('/<!-- wp:post-terms \{"term":"product_(tag|brand)"[^}]*\} \/-->/', '', $c);
    $c = str_replace(array_keys($sost_testi), array_values($sost_testi), $c);
    if ($c !== $t->content) { quiete_tpl('wp_template', $t->slug, $t->title ? $t->title : $t->slug, $c, $tema); }
}
$sidebar = '<!-- wp:group {"style":{"spacing":{"padding":{"top":"8px"}}},"layout":{"type":"constrained"}} --><div class="wp-block-group" style="padding-top:8px">'
    . '<!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Categorie</h3><!-- /wp:heading -->'
    . '<!-- wp:woocommerce/product-categories {"hasCount":true} /--></div><!-- /wp:group -->';
foreach (array('product-sidebar', 'sidebar') as $sp) { quiete_tpl('wp_template_part', $sp, 'Sidebar', $sidebar, $tema, 'uncategorized'); }

wp_update_custom_css_post(
    ".wp-block-site-logo img{width:170px!important;height:auto!important}\n"
  . ".woocommerce-store-notice,.wc-block-components-notice-banner.is-info{background:#4f1420!important;color:#fff!important}\n"
  . ".woocommerce-store-notice a{color:#f1dc8a!important}\n"
  . "h1,h2,h3,h4,.wp-block-post-title,.wp-block-query-title,.wp-block-button__link{text-transform:none!important}",
    array('stylesheet' => $tema)
);
update_option('show_on_front', 'page');
update_option('page_on_front', $home_id);

flush_rewrite_rules();
wc_delete_product_transients();
update_option('quiete_build_esito', array('variante' => $variante, 'prodotti' => count($prod), 'clienti' => count($utenti), 'ordini' => $n_ord));
