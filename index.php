<?php
/**
	Plugin Name: Flexible Invoices Exporter
	Plugin URI: https://github.com/marsjaninzmarsa/flexible-invoices-exporter/
	Description: Export all invoices from Flexible Invoices plugin to XML.
	Author: marsjaninzmarsa
	Version: 0.0.6
	Author URI: http://niewiarowski.it
	License: Under GPL2

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

class Flexible_Invoices_Exporter {

	function __construct() {
		add_filter( 'views_edit-inspire_invoice', [$this, 'add_export_button' ] );
		add_action( 'wp_ajax_flexible_invoices_export', [ $this, 'invoices_export' ] );
	}

	public function add_export_button( $views ) {
		if( current_user_can( 'administrator' ) ) {
			$views[ 'flexible-invoices-export' ] = sprintf( '<a href="%s?%s">Eksportuj faktury do XML-a</a>',
				admin_url( 'admin-ajax.php' ),
				http_build_query( [ 'action' =>  'flexible_invoices_export' ] )
			);
		}
		return $views;
	}

	public function invoices_export() {
		if( ! current_user_can( 'administrator' )) {
			wp_die('Nie posiadasz uprawnień do wykonania tej operacji.', 'Nie powinno cię tu być!');
		}
		$this->send_headers();

		$xml = new XMLWriter();
		$xml->openMemory();
		$xml->setIndent( true );
		$xml->startDocument( '1.0', get_option( 'blog_charset' ) );
		$xml->startElement( 'export' );
		
		$query = new WP_Query( [
			'post_type'   => 'inspire_invoice',
			'post_status' => 'publish',

			'order'       => 'DESC',
			'orderby'     => 'date',

			'nopaging'    => true,
		] );

		foreach ($query->posts as $post) {
			$xml->startElement( 'invoice' );
			$xml->writeAttribute( 'currency', get_post_meta( $post->ID, '_currency', true ) );

			$xml->startElement( 'number' );
			$xml->writeAttribute( 'numeric', get_post_meta( $post->ID, '_number', true ) );
			$xml->text( get_post_meta ( $post->ID, '_formatted_number', true ) );
			$xml->endElement(); //number

			$xml->startElement( 'date' );
			foreach (['issue', 'sale', 'pay', 'paid'] as $key) { // not sure why there is no paid date in db
				if( $value = get_post_meta( $post->ID, '_date_' . $key, true ) ) {
					$xml->writeElement( $key, $value );
				}
			}
			$xml->endElement(); //date

			$xml->startElement( 'client' );
			$client = get_post_meta( $post->ID, '_client', true );
			foreach ($client as $key => $value) {
				$xml->writeElement( $key, $value );
			}
			$xml->endElement(); //client

			$xml->startElement( 'products' );
			$products = get_post_meta( $post->ID, '_products', true );
			foreach ($products as $product) {
				$xml->startElement( 'product' );

				$xml->writeElement( 'name', $product['name'] );

				$xml->startElement( 'quantity' );
				$xml->writeAttribute( 'unit', $product['unit'] );
				$xml->text( $product['quantity'] );
				$xml->endElement(); //quantity

				$xml->writeElement( 'netPrice', $product['net_price'] );

				$xml->writeElement( 'discount', $product['discount'] );

				$xml->writeElement( 'netPriceDiscount', $product['net_price_discount'] );
				
				$xml->writeElement( 'netPriceSum', $product['net_price_sum'] );
				
				$xml->startElement( 'vatType' );
				$xml->writeAttribute( 'numeric', $product['vat_type'] );
				$xml->text( $product['vat_type_name'] );
				$xml->endElement(); //vatType
				
				$xml->writeElement( 'vatRate', $product['vat_rate'] );
				
				$xml->writeElement( 'vatSum', $product['vat_sum'] );
				
				$xml->writeElement( 'totalPrice', $product['total_price'] );
				
				$xml->endElement(); //product
			}
			$xml->endElement(); //products
			
			$xml->writeElement( 'totalPrice', get_post_meta( $post->ID, '_total_price', true ) );
			
			$xml->endElement(); //invoice
		}
		
		$xml->endElement();
		$xml->endDocument();
		echo $xml->outputMemory();

		wp_die();
	}

	public function send_headers() {
		$sitename = sanitize_key( get_bloginfo( 'name' ) );
		if( empty( $sitename ) ) {
			$sitename = 'wordpress';
		}

		$filename = sprintf('%s-invoices-%s.xml',
			$sitename,
			date( 'Y-m-d' )
		);

		// header( 'Content-Description: File Transfer' );
		// header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Content-Type: text/xml; charset=' . get_option( 'blog_charset' ), true );
	}

} new Flexible_Invoices_Exporter();
