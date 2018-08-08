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
		$views[ 'flexible-invoices-export' ] = sprintf( '<a href="%s?%s">Eksportuj faktury do XML-a</a>',
			admin_url( 'admin-ajax.php' ),
			http_build_query( [ 'action' =>  'flexible_invoices_export' ] )
		);
		return $views;
	}

	public function invoices_export() {
		echo true;
		wp_die();
	}

} new Flexible_Invoices_Exporter();
