<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Delivery_Date {
    protected static $_instance = null;

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        add_action( 'woocommerce_product_options_general_product_data', [ $this, 'add_delivery_date_field' ] );
        add_action( 'woocommerce_process_product_meta', [ $this, 'save_delivery_date_field' ] );

        add_action( 'woocommerce_before_add_to_cart_button', [ $this, 'display_delivery_dates' ] );
        add_filter( 'woocommerce_add_cart_item_data', [ $this, 'add_delivery_date_to_cart' ], 10, 2 );
        add_filter( 'woocommerce_get_item_data', [ $this, 'display_delivery_date_cart' ], 10, 2 );
        add_action( 'woocommerce_before_calculate_totals', [ $this, 'update_delivery_date_in_cart' ] );

        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

        add_action( 'wp_ajax_update_delivery_date', [ $this, 'update_delivery_date_callback' ] );
        add_action( 'wp_ajax_nopriv_update_delivery_date', [ $this, 'update_delivery_date_callback' ] );
    }

    public function add_delivery_date_field() {
        global $product_object;

        echo '<div class="options_group">';
        woocommerce_wp_text_input( [
            'id'          => '_delivery_date',
            'label'       => __( 'Delivery Date', 'woocommerce-delivery-date' ),
            'placeholder' => __( 'e.g., every 3rd day of every 2 weeks, or every X day of every Y week/month', 'woocommerce-delivery-date' ),
            'desc_tip'    => 'true',
            'description' => __( 'Enter the recurring delivery date.', 'woocommerce-delivery-date' ),
            'value'       => get_post_meta( $product_object->get_id(), '_delivery_date', true ),
        ] );
        echo '</div>';
    }

    public function save_delivery_date_field( $post_id ) {
        $delivery_date = isset( $_POST['_delivery_date'] ) ? sanitize_text_field( $_POST['_delivery_date'] ) : '';
        update_post_meta( $post_id, '_delivery_date', $delivery_date );
    }

    public function display_delivery_dates() {
        global $product;
        if ( ! $product || ! $product->is_type( 'subscription' ) ) {
            return;
        }

        $delivery_date = get_post_meta( $product->get_id(), '_delivery_date', true );
        if ( ! $delivery_date ) {
            return;
        }

        $dates = $this->calculate_next_delivery_dates( $delivery_date );
        if ( empty( $dates ) ) {
            return;
        }

        echo '<div class="delivery-date-dropdown">';
        echo '<label for="delivery_date">' . __( 'Your Next Three Recurring Dates:', 'woocommerce-delivery-date' ) . '</label>';
        echo '<select name="delivery_date" id="delivery_date">';
        foreach ( $dates as $date ) {
            echo '<option value="' . esc_attr( $date ) . '">' . esc_html( $date ) . '</option>';
        }
        echo '</select>';
        echo '</div>';
    }

    public function add_delivery_date_to_cart( $cart_item_data, $product_id ) {
        if ( isset( $_POST['delivery_date'] ) ) {
            $cart_item_data['delivery_date'] = sanitize_text_field( $_POST['delivery_date'] );
        }
        return $cart_item_data;
    }

    public function display_delivery_date_cart( $item_data, $cart_item ) {
        if ( isset( $cart_item['delivery_date'] ) ) {
            $item_data[] = [
                'key'   => __( 'Delivery Date', 'woocommerce-delivery-date' ),
                'value' => sanitize_text_field( $cart_item['delivery_date'] ),
            ];
        }
        return $item_data;
    }

    public function update_delivery_date_in_cart( $cart_object ) {
        if ( ! WC()->session->__isset( 'delivery_date' ) ) {
            return;
        }

        foreach ( $cart_object->get_cart() as $hash => $value ) {
            if ( isset( $value['delivery_date'] ) ) {
                $value['data']->set_price( $value['data']->get_price() );
            }
        }
    }

    private function calculate_next_delivery_dates( $delivery_date ) {
        $dates = [];
        $current_date = new DateTime();
        $interval = $this->parse_delivery_interval( $delivery_date );

        if ( ! $interval ) {
            return $dates;
        }

        for ( $i = 0; $i < 3; $i++ ) {
            $next_date = clone $current_date;
            $next_date->add( $interval );
            $dates[] = $next_date->format( 'Y-m-d' );
            $current_date = $next_date;
        }

        return $dates;
    }

    private function parse_delivery_interval( $delivery_date ) {
        // Match patterns like "every 3rd day of every 2 weeks", "every 1st day of every month", "1st day of every 6th month"
        if ( preg_match( '/^every (\d+)(st|nd|rd|th) day of every (\d+) (week|month)s?$/', $delivery_date, $matches ) ||
             preg_match( '/^(\d+)(st|nd|rd|th) day of every (\d+) (week|month)s?$/', $delivery_date, $matches ) ) {

            $amount_day = $matches[1];
            $amount_period = $matches[3];
            $unit = $matches[4];

            if ( $unit == 'week' ) {
                return new DateInterval( 'P' . ($amount_period * 7) . 'D' );
            } elseif ( $unit == 'month' ) {
                return new DateInterval( 'P' . $amount_period . 'M' );
            }
        } elseif ( preg_match( '/^(\d+)(st|nd|rd|th) day of every (month|week)$/', $delivery_date, $matches ) ) {
            $day = $matches[1];
            $unit = $matches[2];

            if ( $unit == 'month' ) {
                $date_string = "first day of next month + " . ($day - 1) . " days";
            } elseif ( $unit == 'week' ) {
                $date_string = "next week + " . ($day - 1) . " days";
            }
            return DateInterval::createFromDateString($date_string);
        }
        return null;
    }

    public function enqueue_scripts() {
        wp_enqueue_script( 'wc-delivery-date', plugin_dir_url( __FILE__ ) . '../assets/wc-delivery-date.js', [ 'jquery' ], null, true );
        wp_localize_script( 'wc-delivery-date', 'wc_delivery_date', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
        ] );
    }

    public function update_delivery_date_callback() {
        if ( isset( $_POST['delivery_date'] ) ) {
            WC()->session->set( 'delivery_date', sanitize_text_field( $_POST['delivery_date'] ) );
            wp_send_json_success();
        } else {
            wp_send_json_error();
        }
    }
}

WC_Delivery_Date::instance();
