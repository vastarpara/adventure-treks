<?php
/**
 * Custom Database Handler
 *
 * @package    AdventureTreks
 * @subpackage AdventureTreks/Includes
 * @author     Nilesh Vastarpara
 */

namespace AdventureTreks\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Database manager class.
 */
class Database {

	/**
	 * Create plugin custom database tables.
	 *
	 * Uses dbDelta to safely construct and modify schema.
	 *
	 * @return void
	 */
	public static function create_tables() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// 1. Core Trek attributes (extending standard wp_posts CPT info).
		$table_treks = $wpdb->prefix . 'at_treks';
		$sql_treks   = "CREATE TABLE $table_treks (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			post_id bigint(20) unsigned NOT NULL,
			difficulty varchar(50) DEFAULT '' NOT NULL,
			duration varchar(50) DEFAULT '' NOT NULL,
			altitude varchar(50) DEFAULT '' NOT NULL,
			region varchar(100) DEFAULT '' NOT NULL,
			season varchar(100) DEFAULT '' NOT NULL,
			distance varchar(50) DEFAULT '' NOT NULL,
			fitness_level varchar(100) DEFAULT '' NOT NULL,
			age_limit varchar(50) DEFAULT '' NOT NULL,
			group_size varchar(50) DEFAULT '' NOT NULL,
			highlights longtext DEFAULT NULL,
			things_to_carry longtext DEFAULT NULL,
			faq longtext DEFAULT NULL,
			policies longtext DEFAULT NULL,
			gallery text DEFAULT NULL,
			seo_fields longtext DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY post_id (post_id)
		) $charset_collate;";
		dbDelta( $sql_treks );

		// 2. Departure Cities.
		$table_cities = $wpdb->prefix . 'at_departure_cities';
		$sql_cities   = "CREATE TABLE $table_cities (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			trek_id bigint(20) unsigned NOT NULL,
			city_name varchar(150) NOT NULL,
			base_price decimal(10,2) DEFAULT '0.00' NOT NULL,
			offer_price decimal(10,2) DEFAULT '0.00' NOT NULL,
			transport_type varchar(100) DEFAULT '' NOT NULL,
			reporting_time varchar(50) DEFAULT '' NOT NULL,
			google_map_link text DEFAULT NULL,
			booking_deadline int(11) DEFAULT 0 NOT NULL,
			status varchar(50) DEFAULT 'active' NOT NULL,
			menu_order int(11) DEFAULT 0 NOT NULL,
			PRIMARY KEY  (id),
			KEY trek_id (trek_id)
		) $charset_collate;";
		dbDelta( $sql_cities );

		// 3. Departure Dates.
		$table_dates = $wpdb->prefix . 'at_departure_dates';
		$sql_dates   = "CREATE TABLE $table_dates (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			city_id bigint(20) unsigned NOT NULL,
			trek_id bigint(20) unsigned NOT NULL,
			departure_date date NOT NULL,
			status varchar(50) DEFAULT 'open' NOT NULL,
			notes text DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY city_id (city_id),
			KEY trek_id (trek_id),
			KEY departure_date (departure_date)
		) $charset_collate;";
		dbDelta( $sql_dates );

		// 4. Itineraries (Days).
		$table_itineraries = $wpdb->prefix . 'at_itineraries';
		$sql_itineraries   = "CREATE TABLE $table_itineraries (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			city_id bigint(20) unsigned NOT NULL,
			trek_id bigint(20) unsigned NOT NULL,
			day_number int(11) NOT NULL,
			title varchar(255) NOT NULL,
			description text DEFAULT NULL,
			menu_order int(11) DEFAULT 0 NOT NULL,
			PRIMARY KEY  (id),
			KEY city_id (city_id),
			KEY trek_id (trek_id)
		) $charset_collate;";
		dbDelta( $sql_itineraries );

		// 5. Itinerary Timeline Items.
		$table_itinerary_items = $wpdb->prefix . 'at_itinerary_items';
		$sql_itinerary_items   = "CREATE TABLE $table_itinerary_items (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			itinerary_id bigint(20) unsigned NOT NULL,
			item_time varchar(50) DEFAULT '' NOT NULL,
			title varchar(255) NOT NULL,
			description text DEFAULT NULL,
			icon varchar(100) DEFAULT '' NOT NULL,
			image_url text DEFAULT NULL,
			menu_order int(11) DEFAULT 0 NOT NULL,
			PRIMARY KEY  (id),
			KEY itinerary_id (itinerary_id)
		) $charset_collate;";
		dbDelta( $sql_itinerary_items );

		// 6. Pickup Points.
		$table_pickup_points = $wpdb->prefix . 'at_pickup_points';
		$sql_pickup_points   = "CREATE TABLE $table_pickup_points (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			city_id bigint(20) unsigned NOT NULL,
			trek_id bigint(20) unsigned NOT NULL,
			location_name varchar(255) NOT NULL,
			pickup_time varchar(50) DEFAULT '' NOT NULL,
			google_maps_url text DEFAULT NULL,
			instructions text DEFAULT NULL,
			menu_order int(11) DEFAULT 0 NOT NULL,
			PRIMARY KEY  (id),
			KEY city_id (city_id),
			KEY trek_id (trek_id)
		) $charset_collate;";
		dbDelta( $sql_pickup_points );

		// 7. Pricing Table (Adult/Child price variations, Group discounts, Extra charges & Add-ons as JSON structures).
		$table_pricing = $wpdb->prefix . 'at_pricing';
		$sql_pricing   = "CREATE TABLE $table_pricing (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			city_id bigint(20) unsigned NOT NULL,
			date_id bigint(20) unsigned DEFAULT 0 NOT NULL,
			trek_id bigint(20) unsigned NOT NULL,
			adult_price decimal(10,2) DEFAULT '0.00' NOT NULL,
			child_price decimal(10,2) DEFAULT '0.00' NOT NULL,
			offer_price decimal(10,2) DEFAULT '0.00' NOT NULL,
			group_discount longtext DEFAULT NULL,
			extra_charges longtext DEFAULT NULL,
			optional_addons longtext DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY city_id (city_id),
			KEY date_id (date_id),
			KEY trek_id (trek_id)
		) $charset_collate;";
		dbDelta( $sql_pricing );

		// 8. Seat Availability.
		$table_availability = $wpdb->prefix . 'at_availability';
		$sql_availability   = "CREATE TABLE $table_availability (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			date_id bigint(20) unsigned NOT NULL,
			total_seats int(11) DEFAULT 0 NOT NULL,
			booked_seats int(11) DEFAULT 0 NOT NULL,
			available_seats int(11) DEFAULT 0 NOT NULL,
			PRIMARY KEY  (id),
			KEY date_id (date_id)
		) $charset_collate;";
		dbDelta( $sql_availability );

		// 9. Bookings Table.
		$table_bookings = $wpdb->prefix . 'at_bookings';
		$sql_bookings   = "CREATE TABLE $table_bookings (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			trek_id bigint(20) unsigned NOT NULL,
			city_id bigint(20) unsigned NOT NULL,
			date_id bigint(20) unsigned NOT NULL,
			cust_name varchar(255) NOT NULL,
			cust_email varchar(255) NOT NULL,
			cust_phone varchar(255) NOT NULL,
			seats int(11) DEFAULT 1 NOT NULL,
			num_adults int(11) DEFAULT 1 NOT NULL,
			num_children int(11) DEFAULT 0 NOT NULL,
			pickup_point varchar(255) DEFAULT '' NOT NULL,
			addons longtext DEFAULT NULL,
			total_amount decimal(10,2) DEFAULT '0.00' NOT NULL,
			status varchar(50) DEFAULT 'confirmed' NOT NULL,
			payment_status varchar(20) DEFAULT 'pending' NOT NULL,
			created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			PRIMARY KEY  (id),
			KEY trek_id (trek_id),
			KEY city_id (city_id),
			KEY date_id (date_id)
		) $charset_collate;";
		dbDelta( $sql_bookings );
	}

	/**
	 * Drop plugin custom database tables.
	 *
	 * @return void
	 */
	public static function drop_tables() {
		global $wpdb;

		$tables = array(
			'at_availability',
			'at_pricing',
			'at_pickup_points',
			'at_itinerary_items',
			'at_itineraries',
			'at_departure_dates',
			'at_departure_cities',
			'at_treks',
			'at_bookings',
		);

		foreach ( $tables as $table ) {
			$table_name = $wpdb->prefix . $table;
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange
			$wpdb->query( "DROP TABLE IF EXISTS $table_name" );
		}
	}
}
