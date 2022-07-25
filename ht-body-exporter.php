<?php
/*
Plugin Name: HT Audited Body Exporter
Plugin URI: http://www.helpfultechnology.com
Description: Export events
Author: Phil Banks, Steph Gray | Helpful Technology
Version: 0.3
Author URI: http://www.helpfultechnology.com
*/


add_action( 'admin_menu', 'ht_pw_event_exporter_menu' );
function ht_pw_event_exporter_menu() {
	add_submenu_page( 'tools.php','HT Audited Body Exporter', 'HT Audited Body Exporter', 'edit_users', 'ht-body-exporter', 'ht_body_exporter_options' );
}

function ht_body_exporter_options() {
	if ( ! current_user_can('edit_users' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}

	if ( ! empty( $_POST ) && check_admin_referer( 'submit', 'valid' ) ) {

		if ( $_REQUEST['page'] != "ht-body-exporter" ) {
			echo "<div class='wrap'>";
			screen_icon();
			echo "<h2>" . __( ' HT Audited Body Exporter' ) . "</h2>";
			echo '<p><strong>Something is wrong. Try again.</strong></p>';
			echo "</div>";
 			wp_die();
		}


		// CSV column titles.
		$columnLabels = array();
		$columnLabels[] = 'Body Name';
		$columnLabels[] = 'Body ID';
		$columnLabels[] = 'Website (WordPress) ID';

		// Data for CSV rows
		$columnValues = array();

		// Get Events.
		$args = array(
			'post_type'              => array( 'auditedbody' ),
			'post_status'            => array( 'publish',),
			'posts_per_page'         => -1,
		);
		$bodies = new WP_Query( $args );
		if ( $bodies->have_posts() ) {
			while ( $bodies->have_posts() ) {
				$bodies->the_post();

				// Populate $columnValues with Event data.
				$columnValues[] = array(
					get_the_title(),
					get_field( 'body_id' ),
					get_the_ID(),
				);

			}
		}
		wp_reset_postdata();


		// Create the CSV object.
		ob_start();
		$csvData = fopen( 'php://output', 'w' );
		fputcsv( $csvData, $columnLabels );


		// Inject the data rows.
		foreach ( $columnValues as $values ) {
			fputcsv( $csvData, $values );
		}
		// Close the CSV.
		fclose( $csvData );
		$csvData = ob_get_clean();


		// Create the zip.
		$zip = new ZipArchive;
		$zipname = 'audited_bodies_' . date( 'Ymd', time() ) . '.zip';
		$csvDownload = $zip->open( $zipname, ZipArchive::CREATE );
		if ( true === $csvDownload ) {
		    $zip->addFromString( 'bodies.csv', $csvData );
		    $zip->close();

		    header( 'Content-Type: application/zip');
			header( 'Content-disposition: attachment; filename=' . $zipname );
			header( 'Content-Length: ' . filesize( $zipname ) );
			readfile( $zipname );
			unlink( $zipname );
			exit;
		}


	} else {
		echo "<div class='wrap'>";
		screen_icon();
		echo "<h2>" . __( ' HT Audited Body Exporter' ) . "</h2>";
		echo '<p>Click the button below to download a zip file containing a CSV export of all audited bodies</p>
		 <form method="post" action="' . esc_url( plugin_dir_url( __FILE__ ) ) . 'ht-body-exporter_download.php">
			<p><input type="submit" value="Download" class="button-primary" /></p>
			<input type="hidden" name="page" value="ht-body-exporter" />
				' . wp_nonce_field( 'submit', 'valid', true, false ) . '
		  </form><br />';
		echo "</div>";
	}

}
