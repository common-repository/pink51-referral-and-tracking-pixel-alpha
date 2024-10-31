<?php

    /**
     * Plugin Name: Pink51 Referral and Tracking Pixel
     * Plugin URI: http://www.pink51.com/developer
     * Description: Adds a Pink51 Merchant Tracking Pixel to a WooCommerce system, adds both the Referral and Conversion tracking.
     * Version: 0.1.0
     * Author: Sean Klarich
     * Author URI: http://www.bluefractal.la
     * License: GPLv2 or later
     * License URI: http://www.gnu.org/licenses/gpl-2.0.html
     *
     * Many thanks to Wolf & Bär (http://www.wolfundbaer.ch) whose "WooCommerce Google AdWords conversion tracking tag" plugin was the base that this plugin was built upon.
     */

    /*  Copyright YEAR  PLUGIN_AUTHOR_NAME  (email : PLUGIN AUTHOR EMAIL)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
    */

    class PinkCT
    {

        public function __construct()
        {

            // insert the tracking code into the footer of the WooCommerce page
            add_action('wp_footer', array($this, 'Pink51Tag'));

            // add the admin options page
            add_action('admin_menu', array($this, 'pink51_plugin_admin_add_page'));

            // install a settings page in the admin console
            add_action('admin_init', array($this, 'pink51_plugin_admin_init'));

            // add a settings link on the plugins page
            add_filter('plugin_action_links', array($this, 'pink51_settings_link'), 10, 2);

        }

        // adds a link on the plugins page for the pink51 settings
        function pink51_settings_link($links, $file)
        {
            if ($file == plugin_basename(__FILE__)) $links[] = '<a href="' . admin_url("options-general.php?page=do_pink51") . '">' . __('Settings') . '</a>';
            return $links;
        }

        // add the admin options page
        function pink51_plugin_admin_add_page()
        {
            add_options_page('Pink51 Plugin Page', 'Pink51 Plugin Menu', 'manage_options', 'do_pink51', array($this, 'pink51_plugin_options_page'));
        }

        // display the admin options page
        function pink51_plugin_options_page()
        {

            // Throw a warning if WooCommerce is disabled. CHECK DISABLED because it doesn't work properly when the multisite feature is turned on in WP

             if (! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
                    echo '<div><h1><font color="red"><b>It seems like WooCommerce is not installed. This error might be due to Multi-Site being enabled, if so please ignore!</b></font></h1></div>';
             }

            ?>

            <br>
            <div style="background: #eee; width: 772px">
                <div style="background: #ccc; padding: 10px; font-weight: bold">Configuration for the WooCommerce Pink51 conversion tracking tag</div>
                <form action="options.php" method="post">

                    <?php settings_fields('pink51_plugin_options'); ?>
                    <?php do_settings_sections('do_pink51'); ?>
                    <br>
                    <table class="form-table" style="margin: 10px">
                        <tr>
                            <th scope="row" style="white-space: nowrap">
                                <input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" class="button"/>
                            </th>

                        </tr>
                    </table>
                </form>

            </div>

        <?php


        }


        // add the admin settings and such

        function pink51_plugin_admin_init()
        {
            register_setting('pink51_plugin_options', 'pink51_plugin_options_1');
            add_settings_section('pink51_plugin_main', 'Pink51 Main Settings', array($this, 'pink51_plugin_section_text'), 'do_pink51');
            add_settings_field('pink51_plugin_text_string_1', 'Merchant ID', array($this, 'pink51_plugin_setting_string_1'), 'do_pink51', 'pink51_plugin_main');
        }

        function pink51_plugin_section_text()
        {
            echo '<p>Woocommerce Pink51 conversion tracking tag</p>';
        }

        function pink51_plugin_setting_string_1()
        {
            $options = get_option('pink51_plugin_options_1');
            echo "<input id='pink51_plugin_text_string_1' name='pink51_plugin_options_1[text_string]' size='40' type='text' value='{$options['text_string']}' />";
        }

        private function get_merchant_id()
        {

            $opt = get_option('pink51_plugin_options_1');
            $merchant_id = $opt['text_string'];
            return $merchant_id;
        }

        public function Pink51Tag()
        {

            global $woocommerce;
            $merchant_id = $this->get_merchant_id();

            if($merchant_id)
            {
                if (is_order_received_page()) {

                    /**
                     * Ugly work around to get most recent order ID. This must be replaced.
                     * A bit more information on that: Unfortunately there is a filter in WP (up to the current version 3.6) where WP messes up the Google AdWords tracking tag after injecting it into the thankyou page. There is no workaround other than not injecting it into the thankyou page and placing the tracking code somewhere else where the WP filter is not applied. This bug was reported years ago and is still an issue: http://core.trac.wordpress.org/ticket/3670
                     * Until the the bug is resolved or I find a workaround I can't place the tracking code into the thankyou page.
                     **/
                    global $wpdb;

                    $recent_order_id = $wpdb->get_var("
						SELECT MAX(id)
						FROM $wpdb->posts
						");


                    $order = new WC_order($recent_order_id);
                    $order_number = $order->get_order_number();
                    $order_total = $order->get_total();
                    $items = $order->get_items();
                    $totals = $order->get_order_item_totals();
                    $temp_sub = $totals['cart_subtotal']['value'];
                    preg_match_all('/\d+/', $temp_sub, $matches);
                    $sub_total = number_format($matches[0][1], 2, '.', '');


                    ?>




                    <script>
                        (function (w, d, s, k) {
                            var e, p, m = ["config", "track"], c = function () {
                                var i, c = this;
                                for (c._q = [], i = 0; m.length > i; i++)(function (i) {
                                    c[i] = function () {
                                        return c._q.push([i].concat(Array.prototype.slice.call(arguments, 0))), c
                                    }
                                })(m[i])
                            };
                            w._pink = k, w[k] = w[k] || new c, e = d.createElement(s), e.async = 1,
                                e.src = "//pink51.com/tracker.js", p = d.getElementsByTagName(s)[0], p.parentNode.insertBefore(e, p)
                        })(window, document, "script", "pink");
                        pink.config('merchant_id', <?php echo $merchant_id; ?>);
                        pink.track('purchase', {
                            txn_id: "<?php echo $order_number; ?>",
                            txn_subtotal: <?php echo $sub_total; ?>,
                            txn_total: <?php echo $order_total; ?>,
                            items: [
                                <?php foreach($items as $item): ?>
                                {
                                    id: <?php echo $item['product_id']; ?>,
                                    name: "<?php echo $item['name']; ?>",
                                    price: <?php echo $item['line_subtotal']; ?>,
                                    qty: <?php echo $item['qty']; ?>
                                },
                                <?php endforeach; ?>
                            ]
                        });
                    </script>



                <?php

                }
                ?>

                <script>
                    (function (w, d, s, k) {
                        var e, p, m = ["config", "track"], c = function () {
                            var i, c = this;
                            for (c._q = [], i = 0; m.length > i; i++)(function (i) {
                                c[i] = function () {
                                    return c._q.push([i].concat(Array.prototype.slice.call(arguments, 0))), c
                                }
                            })(m[i])
                        };
                        w._pink = k, w[k] = w[k] || new c, e = d.createElement(s), e.async = 1, e.src = "//pink51.com/tracker.js", p = d.getElementsByTagName(s)[0], p.parentNode.insertBefore(e, p)
                    })(window, document, "script", "pink");
                    pink.config('merchant_id', <?php echo $merchant_id; ?>);
                </script>
            <?php
            }
        }

    }

    $pink51 = new PinkCT();

?>