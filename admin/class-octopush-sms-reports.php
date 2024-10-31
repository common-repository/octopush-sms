<?php
/* 
 * Copyright (C) 2014 octopush
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

/**
 * Octopush SMS Reports Class
 * Refactoring of WooCommerce API Reports Class
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Octopush_Sms_Reports {

	/** @var WC_Admin_Report instance */
	private $report;

	
	/**
	 * Get the sales report
	 *
	 * @since 2.1
	 * @param string $fields fields to include in response
	 * @param array $filter date filtering
	 * @return array
	 */
	public function get_sales_report( $fields = null, $filter = array() ) {

		// set date filtering
		$this->setup_report( $filter );

		// total sales, taxes, shipping, and order count
		$totals = $this->report->get_order_report_data( array(
			'data' => array(
				'_order_total' => array(
					'type'     => 'meta',
					'function' => 'SUM',
					'name'     => 'sales'
				),
				'_order_tax' => array(
					'type'            => 'meta',
					'function'        => 'SUM',
					'name'            => 'tax'
				),
				'_order_shipping_tax' => array(
					'type'            => 'meta',
					'function'        => 'SUM',
					'name'            => 'shipping_tax'
				),
				'_order_shipping' => array(
					'type'     => 'meta',
					'function' => 'SUM',
					'name'     => 'shipping'
				),
				'ID' => array(
					'type'     => 'post_data',
					'function' => 'COUNT',
					'name'     => 'order_count'
				)
			),
			'filter_range' => true,
		) );

		// total items ordered
		$total_items = absint( $this->report->get_order_report_data( array(
			'data' => array(
				'_qty' => array(
					'type'            => 'order_item_meta',
					'order_item_type' => 'line_item',
					'function'        => 'SUM',
					'name'            => 'order_item_qty'
				)
			),
			'query_type' => 'get_var',
			'filter_range' => true,
		) ) );

		// total discount used
		$total_discount = $this->report->get_order_report_data( array(
			'data' => array(
				'discount_amount' => array(
					'type'            => 'order_item_meta',
					'order_item_type' => 'coupon',
					'function'        => 'SUM',
					'name'            => 'discount_amount'
				)
			),
			'where' => array(
				array(
					'key'      => 'order_item_type',
					'value'    => 'coupon',
					'operator' => '='
				)
			),
			'query_type' => 'get_var',
			'filter_range' => true,
		) );

		// new customers
		$users_query = new WP_User_Query(
			array(
				'fields'  => array( 'user_registered' ),
				'role'    => 'customer',
			)
		);

		$customers = $users_query->get_results();

		foreach ( $customers as $key => $customer ) {
			if ( strtotime( $customer->user_registered ) < $this->report->start_date || strtotime( $customer->user_registered ) > $this->report->end_date )
				unset( $customers[ $key ] );
		}

		$total_customers = count( $customers );

		// get order totals grouped by period
		$orders = $this->report->get_order_report_data( array(
			'data' => array(
				'_order_total' => array(
					'type'     => 'meta',
					'function' => 'SUM',
					'name'     => 'total_sales'
				),
				'_order_shipping' => array(
					'type'     => 'meta',
					'function' => 'SUM',
					'name'     => 'total_shipping'
				),
				'_order_tax' => array(
					'type'     => 'meta',
					'function' => 'SUM',
					'name'     => 'total_tax'
				),
				'_order_shipping_tax' => array(
					'type'     => 'meta',
					'function' => 'SUM',
					'name'     => 'total_shipping_tax'
				),
				'ID' => array(
					'type'     => 'post_data',
					'function' => 'COUNT',
					'name'     => 'total_orders',
					'distinct' => true,
				),
				'post_date' => array(
					'type'     => 'post_data',
					'function' => '',
					'name'     => 'post_date'
				),
			),
			'group_by'     => $this->report->group_by_query,
			'order_by'     => 'post_date ASC',
			'query_type'   => 'get_results',
			'filter_range' => true,
		) );

		// get order item totals grouped by period
		$order_items = $this->report->get_order_report_data( array(
			'data' => array(
				'_qty' => array(
					'type'            => 'order_item_meta',
					'order_item_type' => 'line_item',
					'function'        => 'SUM',
					'name'            => 'order_item_count'
				),
				'post_date' => array(
					'type'     => 'post_data',
					'function' => '',
					'name'     => 'post_date'
				),
			),
			'where' => array(
				array(
					'key'      => 'order_item_type',
					'value'    => 'line_item',
					'operator' => '='
				)
			),
			'group_by'     => $this->report->group_by_query,
			'order_by'     => 'post_date ASC',
			'query_type'   => 'get_results',
			'filter_range' => true,
		) );

		// get discount totals grouped by period
		$discounts = $this->report->get_order_report_data( array(
			'data' => array(
				'discount_amount' => array(
					'type'            => 'order_item_meta',
					'order_item_type' => 'coupon',
					'function'        => 'SUM',
					'name'            => 'discount_amount'
				),
				'post_date' => array(
					'type'     => 'post_data',
					'function' => '',
					'name'     => 'post_date'
				),
			),
			'where' => array(
				array(
					'key'      => 'order_item_type',
					'value'    => 'coupon',
					'operator' => '='
				)
			),
			'group_by'     => $this->report->group_by_query . ', order_item_name',
			'order_by'     => 'post_date ASC',
			'query_type'   => 'get_results',
			'filter_range' => true,
		) );

		$period_totals = array();

		// setup period totals by ensuring each period in the interval has data
		for ( $i = 0; $i <= $this->report->chart_interval; $i ++ ) {

			switch ( $this->report->chart_groupby ) {
				case 'day' :
					$time = date( 'Y-m-d', strtotime( "+{$i} DAY", $this->report->start_date ) );
					break;
				case 'month' :
					$time = date( 'Y-m', strtotime( "+{$i} MONTH", $this->report->start_date ) );
					break;
			}

			// set the customer signups for each period
			$customer_count = 0;
			foreach ( $customers as $customer ) {

				if ( date( ( 'day' == $this->report->chart_groupby ) ? 'Y-m-d' : 'Y-m', strtotime( $customer->user_registered ) ) == $time ) {
					$customer_count++;
				}
 			}

			$period_totals[ $time ] = array(
				'sales'     => wc_format_decimal( 0.00, 2 ),
				'orders'    => 0,
				'items'     => 0,
				'tax'       => wc_format_decimal( 0.00, 2 ),
				'shipping'  => wc_format_decimal( 0.00, 2 ),
				'discount'  => wc_format_decimal( 0.00, 2 ),
				'customers' => $customer_count,
			);
		}

		// add total sales, total order count, total tax and total shipping for each period
		foreach ( $orders as $order ) {

			$time = ( 'day' === $this->report->chart_groupby ) ? date( 'Y-m-d', strtotime( $order->post_date ) ) : date( 'Y-m', strtotime( $order->post_date ) );

			if ( ! isset( $period_totals[ $time ] ) )
				continue;

			$period_totals[ $time ]['sales']    = wc_format_decimal( $order->total_sales, 2 );
			$period_totals[ $time ]['orders']   = (int) $order->total_orders;
			$period_totals[ $time ]['tax']      = wc_format_decimal( $order->total_tax + $order->total_shipping_tax, 2 );
			$period_totals[ $time ]['shipping'] = wc_format_decimal( $order->total_shipping, 2 );
		}

		// add total order items for each period
		foreach ( $order_items as $order_item ) {

			$time = ( 'day' === $this->report->chart_groupby ) ? date( 'Y-m-d', strtotime( $order_item->post_date ) ) : date( 'Y-m', strtotime( $order_item->post_date ) );

			if ( ! isset( $period_totals[ $time ] ) )
				continue;

			$period_totals[ $time ]['items'] = (int) $order_item->order_item_count;
		}

		// add total discount for each period
		foreach ( $discounts as $discount ) {

			$time = ( 'day' === $this->report->chart_groupby ) ? date( 'Y-m-d', strtotime( $discount->post_date ) ) : date( 'Y-m', strtotime( $discount->post_date ) );

			if ( ! isset( $period_totals[ $time ] ) )
				continue;

			$period_totals[ $time ]['discount'] = wc_format_decimal( $discount->discount_amount, 2 );
		}

		$sales_data = array(
			'total_sales'       => wc_format_decimal( $totals->sales, 2 ),
			'average_sales'     => wc_format_decimal( $totals->sales / ( $this->report->chart_interval + 1 ), 2 ),
			'total_orders'      => (int) $totals->order_count,
			'total_items'       => $total_items,
			'total_tax'         => wc_format_decimal( $totals->tax + $totals->shipping_tax, 2 ),
			'total_shipping'    => wc_format_decimal( $totals->shipping, 2 ),
			'total_discount'    => is_null( $total_discount ) ? wc_format_decimal( 0.00, 2 ) : wc_format_decimal( $total_discount, 2 ),
			'totals_grouped_by' => $this->report->chart_groupby,
			'totals'            => $period_totals,
			'total_customers'   => $total_customers,
		);

		return array( 'sales' => $sales_data );
	}

	/**
	 * Get the top sellers report
	 *
	 * @since 2.1
	 * @param string $fields fields to include in response
	 * @param array $filter date filtering
	 * @return array
	 */
	public function get_top_sellers_report( $fields = null, $filter = array() ) {

		// set date filtering
		$this->setup_report( $filter );

		$top_sellers = $this->report->get_order_report_data( array(
			'data' => array(
				'_product_id' => array(
					'type'            => 'order_item_meta',
					'order_item_type' => 'line_item',
					'function'        => '',
					'name'            => 'product_id'
				),
				'_qty' => array(
					'type'            => 'order_item_meta',
					'order_item_type' => 'line_item',
					'function'        => 'SUM',
					'name'            => 'order_item_qty'
				)
			),
			'order_by'     => 'order_item_qty DESC',
			'group_by'     => 'product_id',
			'limit'        => isset( $filter['limit'] ) ? absint( $filter['limit'] ) : 12,
			'query_type'   => 'get_results',
			'filter_range' => true,
		) );

		$top_sellers_data = array();

		foreach ( $top_sellers as $top_seller ) {

			$product = wc_get_product( $top_seller->product_id );

			if ( $product ) {
				$top_sellers_data[] = array(
					'title'      => $product->get_title(),
					'product_id' => $top_seller->product_id,
					'quantity'   => $top_seller->order_item_qty,
				);
			}
		}

		return array( 'top_sellers' => $top_sellers_data );
	}

	/**
	 * Setup the report object and parse any date filtering
	 *
	 * @since 2.1
	 * @param array $filter date filtering
	 */
	private function setup_report( $filter ) {

		include_once( WC()->plugin_path() . '/includes/admin/reports/class-wc-admin-report.php' );

		$this->report = new WC_Admin_Report();

		if ( empty( $filter['period'] ) ) {

			// custom date range
			$filter['period'] = 'custom';

			if ( ! empty( $filter['date_min'] ) || ! empty( $filter['date_max'] ) ) {

				// overwrite _GET to make use of WC_Admin_Report::calculate_current_range() for custom date ranges
				$_GET['start_date'] = $this->server->parse_datetime( $filter['date_min'] );
				$_GET['end_date'] = isset( $filter['date_max'] ) ? $this->server->parse_datetime( $filter['date_max'] ) : null;

			} else {

				// default custom range to today
				$_GET['start_date'] = $_GET['end_date'] = date( 'Y-m-d', current_time( 'timestamp' ) );
			}

		} else {

			// ensure period is valid
			if ( ! in_array( $filter['period'], array( 'week', 'month', 'last_month', 'year' ) ) ) {
				$filter['period'] = 'week';
			}

			// TODO: change WC_Admin_Report class to use "week" instead, as it's more consistent with other periods
			// allow "week" for period instead of "7day"
			if ( 'week' === $filter['period'] ) {
				$filter['period'] = '7day';
			}
		}

		$this->report->calculate_current_range( $filter['period'] );
	}


}
