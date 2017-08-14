<?php
/**
 * Plugin Name: Custom XML Exporter
 * Plugin URI: http://www.rayflores.com/plugins/xml-exporter-modify/
 * Description: 
 * Author: Ray Flores
 * Author URI: http://www.rayflores.com
 * Version: 1.0
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */
 
/**
 * Change the date format for the filename from YYYY_MM_DD_HH_SS to YYYY-MM-DD
*/

add_filter( 'wc_customer_order_xml_export_suite_filename', 'rf_custom_xml_edit_file_name', 10,3 );
function rf_custom_xml_edit_file_name( $post_replace_file_name, $pre_replace_file_name, $ids ) {
	// define your variables here - they can be entered in the WooCommerce > XML Export Suite > Settings tab
	$variables = array( '%%timestamp%%', '%%order_ids%%' );
	// define the replacement for each of the variables in the same order
	$replacement = array( date( 'Y-m-d' ), implode( '-', $ids ) );
	// return the filename with the variables replaced
	return str_replace( $variables, $replacement, $pre_replace_file_name );
}

add_filter( 'wc_customer_order_xml_export_suite_orders_header', 'rf_custom_xml_header', 20, 1);
function rf_custom_xml_header( $outputMemory ){
	$header = '';
	return $header;
}

add_filter( 'wc_customer_order_xml_export_suite_xml_root_element', 'rf_custom_xml_order_root_element', 1,1);
function rf_custom_xml_order_root_element( $export_type ) {
	return 'RequestBatch';
}

add_filter( 'wc_customer_order_xml_export_suite_orders_xml_data', 'rf_custom_xml_data', 10, 2);

function rf_custom_xml_data( $xml_array, $orders ){
	$xml_array = array( 'RequestBatch' => array ( 
										'@attributes' => array( 
															'ConsumerKey' => 'BOSCHWEBSV',
															'Password' => 'BOSCH$14'
														), 
										
										'Request' => array ( 
														'@attributes' => array ( 
															'Company' => '01',
															'RequestID' => 'OrderLoad',
															'SerialID' => '',
														), 
										'Orders' => array(
													'Order' => $orders
													),	
										)
					)
				);
		// log errors 
		// error_log( print_r( $xml_array, true ) );
		return $xml_array;
}

add_filter( 'wc_customer_order_xml_export_suite_order_data', 'rf_custom_xml_order_data', 20, 2) ;
function rf_custom_xml_order_data( $order_data, $order ){
	$order_id = $order->get_order_number();
	$customer_number = get_post_meta( $order_id, '_customer_user', true );
	$ship_fullname = SV_WC_Order_Compatibility::get_prop( $order, 'shipping_first_name' ) .' '. SV_WC_Order_Compatibility::get_prop( $order, 'shipping_last_name' );
	$order_data = array (
				'OrderHeader' => array (
									'BillToCity' => SV_WC_Order_Compatibility::get_prop( $order, 'billing_city' ),
									'BillToCntryCode' => SV_WC_Order_Compatibility::get_prop( $order, 'billing_country' ),
									'BillToContact' => $order->get_formatted_billing_full_name(),
									'BillToPhone' => SV_WC_Order_Compatibility::get_prop( $order, 'billing_phone' ),
									'BillToState' => SV_WC_Order_Compatibility::get_prop( $order, 'billing_state' ),
									'BillToZipCode' => SV_WC_Order_Compatibility::get_prop( $order, 'billing_postcode' ),
									'CarrierCode' => $order->get_shipping_method(),
									'CustomerAddress1' => SV_WC_Order_Compatibility::get_prop( $order, 'billing_address_1' ),
									'CustomerAddress2' => SV_WC_Order_Compatibility::get_prop( $order, 'billing_address_2' ),
									'CustomerCountry' => SV_WC_Order_Compatibility::get_prop( $order, 'billing_country' ),
									'CustomerName' => $order->get_formatted_billing_full_name(),
									'CustomerNumber' => 'BMBOSCHMIX',
									'PoNumber' => $order->get_order_number(),
									'RequestedShipDate' => date('Y-m-d', strtotime($order->order_date) ),
									'ReviewOrderHold' => 'S',
									'ShipToAddress1' => SV_WC_Order_Compatibility::get_prop( $order, 'shipping_address_1' ),
									'ShipToAddress2' => SV_WC_Order_Compatibility::get_prop( $order, 'shipping_address_2' ),
									'ShipToCity' => SV_WC_Order_Compatibility::get_prop( $order, 'shipping_city' ),
									'ShipToCntryCode' => SV_WC_Order_Compatibility::get_prop( $order, 'shipping_country' ),
									'ShipToContact' => $ship_fullname,
									'ShipToCountry' => SV_WC_Order_Compatibility::get_prop( $order, 'shipping_country' ),
									'ShipToName' => $ship_fullname,
									'ShipToNumber' => 'TEMP',
									'ShipToPhone' => SV_WC_Order_Compatibility::get_prop( $order, 'billing_phone' ),
									'ShipToState' => SV_WC_Order_Compatibility::get_prop( $order, 'shipping_state' ),
									'ShipToZipCode' => SV_WC_Order_Compatibility::get_prop( $order, 'shipping_postcode' ),
									'WarehouseID' => 'SL',
								),
				'OrderDetail' => array(
									'LineItemInfo' => rf_custom_xml_order_line_item( $order ),
									),
			);
	
		unset($order_data['ShipmentTracking']);
	//error_log( print_r( $orders, true ) );
	return $order_data;
}
function rf_custom_xml_order_line_item( $order ){
		$order_id = $order->get_order_number();
		$tracking_meta = get_post_meta( $order_id, '_wc_shipment_tracking_items', true);
		$tracking_number = '';
		if ($tracking_meta){
			$tracking_number = $tracking_meta[0]['tracking_number'];
		}	
		
		$items = array();
		// loop through each item in order
		foreach ( $order->get_items( 'line_item' ) as $item_id => $item ) {
			$product = $order->get_product_from_item( $item );
			 
			$items[] = array(
							'ActualSellPrice' => wc_format_decimal( $order->get_item_total( $item ), 2 ),
							'ItemDescription1' => $product->get_title(),
							'ItemDescription2' => '',
							'ItemNumber' => $product->get_sku(),
							'LineItemType' => '',
							'OrderQty' => $item['qty'],
							'trackingnumber' => $tracking_number,
							'ShipInstructionType' => 'false',
							'WarehouseID' => 'SL',												
						);
			
				
			}
			$items[] = array ( 
							'ActualSellPrice' => $order->get_total_shipping(),
							'ItemDescription1' => 'BoschMixers.com Shipping Fee',
							'ItemDescription2' => '',
							'ItemNumber' => 'BSF',
							'LineItemType' => '',
							'OrderQty' => '1',
							'trackingnumber' => '',
							'ShipInstructionType' => 'false',
							'WarehouseID' => 'SL',
				);
			
	return $items;
}
