<?php
/**
 * Plugin Name: Woo Checkout Modal Addons
 * Plugin URI: hhttps://nextdart.com/
 * Description: Este plugin lanza un modal en el checkout de WooCommerce mostrando productos relacionados y permitiendo añadirlos rápidamente a la compra actual.
 * Version: 1.0.0
 * Author: Daniel Paz
 * Author URI: https://nextdart.com/
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: woo-checkout-modal-addons
 * Domain Path: /languages
 */

// Evitar el acceso directo al archivo
if (!defined('ABSPATH')) {
    exit;
}

// Verificar si WooCommerce está activo
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

    function wcma_enqueue_scripts()
    {
        wp_enqueue_style('wcma-styles', plugin_dir_url(__FILE__) . 'css/wcma-styles.css');
        wp_enqueue_script('wcma-scripts', plugin_dir_url(__FILE__) . 'js/wcma-scripts.js', array('jquery'), '1.0.0', true);
        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css');
        wp_localize_script(
            'wcma-scripts',
            'wcma_params',
            array(
                'ajax_url' => admin_url('admin-ajax.php')
            )
        );

    }
    add_action('wp_enqueue_scripts', 'wcma_enqueue_scripts');


    // Crear el modal en el checkout
    function wcma_checkout_modal()
    {
        $cart_items = WC()->cart->get_cart();
        if (empty($cart_items)) {
            return; // No hay productos en el carrito, no mostrar el modal
        }

        // Obtener los productos relacionados de todos los productos en el carrito
        $related_products = [];
        foreach ($cart_items as $cart_item) {
            $product = wc_get_product($cart_item['product_id']);
            if ($product) {
                $related_products = array_merge($related_products, wc_get_related_products($product->get_id(), 4));
            }
        }

        // Eliminar duplicados y verificar si son comprables
        $related_products = array_unique($related_products);
        $purchasable_related_products = array_filter($related_products, 'is_product_purchasable');

        // Limitar el número máximo de productos en el modal
        $max_related_products = 3;
        $limited_related_products = array_slice($purchasable_related_products, 0, $max_related_products);

        echo '<div id="wcma-modal" title="Productos relacionados" style="display:none;">';

        if (!empty($limited_related_products)) {
            echo '<ul class="wcma-related-products">';
            foreach ($limited_related_products as $related_product_id) {
                $related_product = wc_get_product($related_product_id);
                echo '<li>';
                echo '<a href="' . esc_url(get_permalink($related_product_id)) . '">' . $related_product->get_image() . '</a>';
                echo '<h3><a href="' . esc_url(get_permalink($related_product_id)) . '">' . $related_product->get_name() . '</a></h3>';
                echo '<p>' . $related_product->get_price_html() . '</p>';
                echo '<button class="wcma-add-to-cart" data-product_id="' . $related_product_id . '">Añadir al carrito</button>';
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>No se encontraron productos relacionados.</p>';
        }

        echo '</div>';
    }

    add_action('woocommerce_after_checkout_form', 'wcma_checkout_modal');

    // Función para verificar si un producto está disponible para la compra
    function is_product_purchasable($product_id)
    {
        $product = wc_get_product($product_id);
        return $product && $product->is_purchasable() && $product->is_in_stock();
    }

    function wcma_add_products_to_cart()
    {
        if (isset($_POST['product_id'])) {
            $product_id = intval($_POST['product_id']);
            $added = WC()->cart->add_to_cart($product_id);

            if ($added) {
                wp_send_json_success();
            } else {
                wp_send_json_error(array('message' => 'No se pudo añadir el producto al carrito.'));
            }
        } else {
            wp_send_json_error(array('message' => 'ID de producto no válido.'));
        }
    }

    add_action('wp_ajax_wcma_add_products_to_cart', 'wcma_add_products_to_cart');
    add_action('wp_ajax_nopriv_wcma_add_products_to_cart', 'wcma_add_products_to_cart');

}