
// Funkcija za graf pri kvizu
function init_quiz_results_chart(all, correct, incorrect, correct_title, incorrect_title){
	
	var horizontalBarChartData = {
		datasets: [{
			label: correct_title,
			backgroundColor: "rgba(102, 194, 74, 0.8)",
			borderColor: "rgba(102, 194, 74, 0.8)",
			borderWidth: 1,
			data: [correct]
		}, {
			label: incorrect_title,
			backgroundColor: "rgba(220, 0, 0, 0.7)",
			borderColor: "rgba(220, 0, 0, 0.7)",
			data: [incorrect]
		}]

	};

	// Define a plugin to provide data labels
	Chart.plugins.register({
		afterDatasetsDraw: function(chart, easing) {
			// To only draw at the end of animation, check for easing === 1
			var ctx = chart.ctx;

			chart.data.datasets.forEach(function (dataset, i) {
				var meta = chart.getDatasetMeta(i);
				if (!meta.hidden) {
					meta.data.forEach(function(element, index) {
						// Draw the text in black, with the specified font
						ctx.fillStyle = 'rgb(0, 0, 0)';

						var fontSize = 15;
						var fontStyle = 'bold';
						var fontFamily = 'Arial';
						ctx.font = Chart.helpers.fontString(fontSize, fontStyle, fontFamily);

						// Just naively convert to string for now
						var dataString = dataset.data[index].toString() + ' (' + Math.round((dataset.data[index] / all * 1000) / 10) + '%)';

						// Make sure alignment settings are correct
						ctx.textAlign = 'center';
						ctx.textBaseline = 'middle';

						var padding = 0;
						var position = element.tooltipPosition();
						
						var x_pos = position.x / 2;
						if(x_pos < 10)
							x_pos = 45;
						var y_pos = position.y - (fontSize / 2) - padding + 10;
						
						ctx.fillText(dataString, x_pos, y_pos);
					});
				}
			});
		}
	});
	
	var ctx = document.getElementById("quiz_results_chart").getContext("2d");
	window.myHorizontalBar = new Chart(ctx, {
		type: 'horizontalBar',
		data: horizontalBarChartData,
		options: {
			elements: {
				rectangle: {
					borderWidth: 1,
				}
			},		
			responsive: true,
			maintainAspectRatio: false,
			scales: {
				xAxes: [{
					ticks: {
						//display: false,
						padding: 5,
						min: 0,
						max: all,
						stepSize: 1,
						mirror: true
					}
				}]
			},
			legend: {
				display: false
			}	
		}
	});
}

// Funkcija za poseben modul excelleration matrix
function init_excell_matrix(x_axis, y_axis, rad){
	
	// Radius prilagodimo ce je prevelik/premajhen
	var radius = rad / 12.5;
	if(radius > 80) 
		radius = 80;
	if(radius <  8)
		radius = 8;
	
	// Inicializiramo graf
	var ctx = document.getElementById("excell_matrix_chart");

	// Nastavimo podatke
	var popData = {
		datasets: [{
			label: ['Blagovna znamka'],
			data: [{
			  x: x_axis,
			  y: y_axis,
			  r: radius
			}],
			backgroundColor: "rgba(220, 0, 0, 0.7)",
			hoverBackgroundColor: "rgba(220, 0, 0, 0.8)",
		}]
	};

	var myChart = new Chart(ctx, {
		type: 'bubble',
		data: popData,
		options: {
			tooltips: {
				custom: function(tooltip) {
					if (!tooltip) return;
					// disable displaying the color box;
					tooltip.displayColors = false;
				},
				callbacks: {
					label: function(t, d) {
						//var multistringText = ['Blagovna znamka:'];
						//multistringText.push(' Excelleration: ' + x_axis);
						var multistringText = ['Odličnost: ' + x_axis];
						multistringText.push('Marža: ' + y_axis);
						multistringText.push('Letni promet: ' + rad);
						
						return multistringText;
						/*/return 'Blagovna znamka:<br > ' + x_axis + ', ' + y_axis + ', ' + rad;*/
						/*return d.datasets[t.datasetIndex].label + 
							': (Day:' + t.xLabel + ', Total:' + t.rLabel + ')';*/
					}
				}
			},
			scales: {
				yAxes: [{
					ticks: {
						display: false,
						padding: 5,
						min: 0,
						max: 6,
						stepSize: 0.5,
						/*min: 0,
						max: 6,*/
						callback: function(value, index, values) {
							if(value == 3)
								return value;
							else 
								return; 
						}
					},
					scaleLabel: {
						display: true,
						labelString: 'Odličnost blagovne znamke',
						fontStyle: 'bold',
						fontSize: '14',
						
					},
					gridLines: {
						color: "rgba(10, 10, 10, 1)",
						drawBorder: false
					},
					position: 'right'
				}],
				xAxes: [{
					ticks: {
						display: false,
						padding: 5,
						min: 0,
						max: 6,
						stepSize: 0.5,
						/*min: 0,
						max: 6,*/
						callback: function(value, index, values) {
							if(value == 3)
								return value;
							else 
								return; 
						}
					},
					scaleLabel: {
						display: true,
						labelString: 'Marža blagovne znamke',
						fontStyle: 'bold',
						fontSize: '14'
					},
					gridLines: {
						color: "rgba(10, 10, 10, 1)",
						drawBorder: false
					},
					position: 'top'
				}]
			},
			legend: {
				display: false
			},
			layout: {
				padding: {
					left: 20,
					right: 40,
					top: 30,
					bottom: 30
				}
			}
		}
	});
}

// Funkcija za poseben modul radar chart - skavti
function init_skavti_radar(labels, pohvale, izzivi){
		
	// Inicializiramo graf
	var ctx = document.getElementById("skavti_radar_chart");

    // Podatki grafa
    var data = {
        labels: labels,
        datasets: [{
            data: pohvale,
            label: "Pohvale",
            backgroundColor: "rgba(30,136,229,0.6)" 
        },
        {
            data: izzivi,
            label: "Izzivi",
            backgroundColor: "rgba(200,0,0,0.5)"
        }]
    };

    // Nastavitve grafa
    var options = {
        scale: {
            angleLines: {
                display: false
            },
            ticks: {
                max: 10,
                min: 0,
                stepSize: 1
            },
        }
    };

    /*Chart.defaults.global.defaultFontSize = 15;*/

	var radarChart = new Chart(ctx, {
        type: 'radar',
        data: data,
        options: options
    });
}
