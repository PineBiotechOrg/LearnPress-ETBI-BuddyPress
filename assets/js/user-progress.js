
(function ($, user_progress) {
    "use strict";

    function getRandomColor() {
	    var letters = '0123456789ABCDEF'.split('');
	    var color = '#';
	    for (var i = 0; i < 6; i++ ) {
	        color += letters[Math.floor(Math.random() * 16)];
	    }
	    return color;
	}

	function removeData(chart) {
	    chart.data.labels.pop();
	    chart.data.datasets = [];
	    chart.options.scales.xAxes[0].time.min = null;
	    chart.options.scales.xAxes[0].time.max = null;
	    chart.update();
	}

	function updateTable( data ) {

		var $progress_table = $('#progress-table'),
			$member_search = $( "#members_search" ),
			options = {
				valueNames: [ 'name', 'edu-points', 'server-points', 'period-total' ],
				page: 15,
				pagination: true
			};

		$progress_table.replaceWith( data.progress_table );

		var memberList = new List( 'members-progress-table-data', options );

		memberList.sort( 'period-total', {
			order: 'desc'
		} );

		$member_search.on( 'keyup', function() {

			 var searchString = $(this).val();
  			memberList.search(searchString);

		} );

	}


	function addData( chart, data ) {

		console.log( data );

		var datasets = chart.data.datasets,
			events = [],
			edu_points = [],
			server_points = [],
			max_points = 100;

		$.each( data.edu_points, function( date, value ){		

		    edu_points.push( {
				x: date,
				y: value
			} );

			if( value > max_points ) {
				max_points = value + 100;
			}

		} );

		$.each( data.server_points, function( date, value ){		

		    server_points.push( {
				x: date,
				y: value
			} );

			if( value > max_points ) {
				max_points = value + 100;
			}

		} );

		datasets.push( { 
			label: "Server Daily Points",
			data: server_points, 
			borderWidth: 3, 
			backgroundColor: "#cccccc60",
			borderColor: "#ccc",

		} );

		datasets.push( { 
			label: "Edu Daily Points",
			data: edu_points, 
			borderWidth: 3, 
			backgroundColor: "#ffb60660",
			borderColor: "#ffb60660",

		} );

		console.log( 'MAX POINTS:', max_points );


		chart.options.scales.yAxes[0].ticks.suggestedMax = max_points;

		chart.options.scales.xAxes[0].time.min = data.from;
		chart.options.scales.xAxes[0].time.max = data.until;

		console.log( datasets );

		chart.update();
	}
    //console.log( etbi.total_points );

    $(document).ready(function() {

    	console.log( user_progress.edu_points );

		var ctx = document.getElementById("user-overview-chart").getContext("2d"),
			$progress_dashboard = $("#progress-dashboard"),
			max_points = (user_progress.edu_points > user_progress.server_points ) ? user_progress.edu_points : user_progress.server_points,
			options = {
				valueNames: [ 'name', 'edu-points', 'server-points', 'period-total' ],
				page: 15,
				pagination: true
			};

			if( Number.isInteger( max_points ) ) {

				user_progress['max'] = max_points;

			} else {

				user_progress['max'] = 0;

			}

    	

    	console.log( user_progress );

		// var memberList = new List( 'members-progress-table-data', options ),
		// 	$member_search = $( "#members_search" );

		// memberList.sort( 'period-total', {
		// 	order: 'desc'
		// } );

		// $member_search.on( 'keyup', function() {

		// 	 var searchString = $(this).val();
  // 			memberList.search(searchString);

		// } );

		var overview_chart = new Chart( ctx, {
		    type: "bar",
		    data: {
		        datasets: [],
		    },
		    options: {
		        scales: {
		            yAxes: [{
		            	label: 'Points',
		            	stacked: false,
		                ticks: {
		                    beginAtZero:true,
		                    suggestedMax: max_points + 100
		                }
		            }],
		            xAxes: [{
						type: 'time',
		                time: {
		                	parser: 'YYYY-MM-DD',
		                	unit: 'day',
		                	displayFormats: {
		                		day: 'MMM Do'
		                	},
		                	min: user_progress.from,
		                	max: user_progress.until
		                },
		                stacked: false,
		                ticks: {
		                	min: 0
		                }
		            }]
		        }
		    },
		    plugins: [{
		         beforeInit: function(chart) {
		            var time = chart.options.scales.xAxes[0].time, // 'time' object reference
		               timeDiff = moment(time.max).diff(moment(time.min), 'd'); // difference (in days) between min and max date
		            // populate 'labels' array
		            // (create a date string for each date between min and max, inclusive)
		            for (var i = 0; i <= timeDiff; i++) {
		               var _label = moment(time.min).add(i, 'd').format('YYYY-MM-DD');
		               chart.data.labels.push(_label);
		            }
		         }
		      }]
		});

		 //addData( overview_chart, etbi );	

		 addData( overview_chart, user_progress );	


		$('#date-range-picker').daterangepicker( {

			startDate: moment( user_progress.from ),
			endDate: moment( user_progress.until )

		}, function( start, end, label ) {

			console.log("A new date selection was made: " + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD'));

			$.ajax({
                url: ajaxurl,
                type: 'post',
                dataType : 'html',
                data: {

                	'action'	: 'get_user_progress',
                	'from' 		: start.format('YYYY-MM-DD'),
                	'until'		: end.format('YYYY-MM-DD'),
                	'user_id'  : user_progress.user_id

                },
                beforeSend: function() {
                	$progress_dashboard.addClass("loading");
					//$( "#overview-chart" ).opacity(0.5);        			

                },
                error: function ( error, data, y) {
                	console.log( error, data, y );
                },
                success: function (data) {

                   var response = $.parseJSON( data );

                   console.log( response );

                    removeData( overview_chart );

                    //addData( overview_chart, response );
                    addData( overview_chart, response );
                    $progress_dashboard.removeClass("loading");

                    //updateTable( response );
                }
            });

		} ); //date range pivker

    } );


})(jQuery, user_progress );