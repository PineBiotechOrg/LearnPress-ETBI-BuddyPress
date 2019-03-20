
<div id="progress-dashboard" class="user-progress-dashboard">

<?php

	if( $mycred_active ) {

		$pagination = 10;

		$args = array( 'number' => $pagination, 'user_id' => $user_id );

		if( ( isset( $time ) && ! empty( $time ) ) && array_key_exists( 'dates', $time ) ) {

			$args['time'] = $time;

		}

		$log = ( $query_log ) ? $query_log : new myCRED_Query_Log( $args );

		if( $log->have_entries() ) {

?>

	<canvas id="user-overview-chart" width="836" height="450"></canvas>

	<div class="progress-control-area">
	
		<input type="text" name="date-picker" id="date-range-picker">

		<div class="entry-search " style="max-width:250px;">
			<form id="search-entries-form">
				<input type="text" id="entries_search" class="search" placeholder="Search Entries..." />
				<input type="submit" id="entries_search_submit" name="entries_search_submit" value="Search">
			</form>
		</div>

	</div>

	<div id="progress-table" class="wrap-table100">

	<!-- 	<div class="table-info">
			<h5><?php //echo date( 'F d, Y', strtotime( $from ) ) . ' to ' . date( 'F d, Y', strtotime( $until ) ); ?></h5>
			<p>The data for this table is only for the current week</p>
		</div> -->

		<div id="members-progress-table-data" class="table100">

			<div class="action-container top">
				<div id='entries-pagination' class='pagination-links progress-entries-table-pagination'>
					 <!-- <ul class="pagination"></ul> -->
					<?php $log->front_navigation( 'top', $pagination ); ?>
				</div>

				<div class="progress-csv-download-btn">
					<a href="<?php echo esc_url( $csv_link ) ?>" target="_blank" class="button"><i class="fa fa-download"></i> <?php _e( 'Download CSV', 'etbi' ); ?></a>
				</div>
			</div>

			<table class="user-progress-table">
				<thead>
					<!-- <tr class="table100-head"> -->
					<th class="column1"><button class="sort" data-sort="date">Date</button></th>
					<th class="column2"><button class="sort" data-sort="points">Points</button></th>
					<th class="column3">Entry</th>
					<!-- </tr> -->
				</thead>
				<tbody class="list">

					<?php 

						foreach ( $log->results as $entry ) { 

							$date_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );

					?>

					    <tr>
							<td class="date column1"><?php esc_html_e( date_i18n( $date_format, $entry->time ) ); ?></td>
							<td class="points column2"><?php esc_html_e( $entry->creds ); ?></td>
							<td class="entry-log column3"><?php esc_html_e( $log->core->parse_template_tags( $entry->entry ) ); ?></td>
						</tr>

					<?php
					
						 } 

					?>
						
				</tbody>
			</table>

		</div>
	</div>

	<div class="cssload-container"><div class="cssload-loading"><i></i><i></i><i></i><i></i></div></div>


<?php

			$log->front_navigation( 'bottom', $pagination );

		} else {

			echo '<span class="alert alert-primary">'.__( 'No points', 'etbi' ).'</span>';

		}

	}

?>

</div>