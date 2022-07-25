<?php

/** include WordPress defaults */
include($_SERVER['DOCUMENT_ROOT']."/wp-config.php");


if ( ! empty( $_POST ) && check_admin_referer( 'submit', 'valid' ) ) {

	if ( "ht-body-exporter" !== $_REQUEST['page'] ) {
			wp_die();
	}

	// CSV column titles.
	$columnLabels = array();
	$columnLabels[] = 'Body Name';
	$columnLabels[] = 'Body ID';
	$columnLabels[] = 'Website (WordPress) ID';

	// Create the CSV object.
	$csvData = fopen( 'php://temp', 'w' );
	// Add headers.
	fputcsv( $csvData, $columnLabels );

	$args = array(
		'post_type'              => array( 'auditedbody' ),
		'post_status'            => array( 'publish',),
		'posts_per_page'         => -1,
		'orderby'				 => 'title',
		'order'				     => 'asc'
	);
	
	$bodies = new WP_Query( $args );

	if ( $bodies->have_posts() ) {
		while ( $bodies->have_posts() ) {
			$bodies->the_post();

			$columnValues = array(
				get_the_title(),
				get_field( 'body_id' ),
				get_the_ID(),
			);
			// Write this posts data into the CSV.
			fputcsv( $csvData, $columnValues );
		}
	}

	// Get the CSV data into a variable.
	rewind( $csvData );
	$csv = stream_get_contents( $csvData );
	// Close the CSV.
	fclose( $csvData );

	$csvname = 'audited_bodies_' . date( 'Ymd', time() ) . '.csv';

    header( 'Content-Type: application/csv');
	header( 'Content-disposition: attachment; filename=' . $csvname );
	//header( 'Content-Length: ' . filesize( $csvname ) );
	echo($csv);

	/*
	// Create the zip.
	chdir( sys_get_temp_dir() ); // Zip always get's created in current working dir so move to tmp.

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
	*/

}
