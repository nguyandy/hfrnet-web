/*
	gmapsDistanceTool.js for Google Maps -- Basic library for calculating distance(s) 
		between clicked points (IE11 javascript compatible)

	Required dependencies:
		Google Maps v3+
		jQuery v3+ (older versions have not been tested)
		Bootstrap v5+ (older versions have not been tested)

	Install:
		1.  Load gmapsDistanceTool.js after the required dependencies are loaded
		2.  Create a div outside of the Google Map container, eg:
			<div id="map"></div>
			<div id="distanceTool"></div>
		3.  Initiate distance tool after Google Maps object is available, 
		    in this case 'map' is the Google Map object, ie:
			var map = new google.maps.Map(document.getElementById('map'), {});
			var distanceTool = new gmapsDistanceTool(map, 'distanceTool'); 

	HTML Element class/id's used by this library:
		ID's:
			gdtDistanceDiv
			gdtLineDistance
			gdtUnitLabel
		class:
			gdtDistanceUnits
			gdtLineDisplay
		Check to make sure class/id's do not conflict

	Use:
		Right-click on map to set initial point.
		Right-click on point to delete a previously set point.
		
	Updated:
		2019-Feb-21 -- v0.1.0 -- Epoch
		2019-Mar-03 -- v0.1.1 -- Updated for Boostrap 4 only 
		2020-Oct-19 -- v0.1.2 -- Updated div container instructions
		2021-Aug-02 -- v0.1.3 -- Added top offset option for info window, fixed dropdown for Bootstrap 5 
*/

/* ===============================================================================
   gmapsDistanceTool function
   ===============================================================================
	Paramters:
		map:  	Google Maps object
		id:	Element ID for total distance display and options
		opt:	options object {
				top_offset: 10, // offset of infowindow from the top of the div, in pixels, default is 10px
			}

	Returns:
		Object with properties:
			distance:	Current total distance of segment(s) drawn
			units:		Currently selected units
   =============================================================================== */
var gmapsDistanceTool = function(map, id, opt) {
	var top_offset = typeof opt.top_offset === undefined ? 10 : opt.top_offset;
	// =======================
	// Set Internal Parameters 
	// =======================
	// Basic template for drop down menu and real time update on total line distance.
	// Placed at the top of the map, centered.
	var template = '<div id="gdtDistanceDiv" class="input-group mb-3 bg-white invisible " style="min-width: 100px; max-width: 350px; margin: 10px; margin-top: ' + top_offset + 'px;">'+
			'<div class="input-group-prepend">'+
				'<button type="button" class="btn btn-default dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Total Distance <span class="caret"></span></button>'+
				'<div class="dropdown-menu">' +
					'<a class="dropdown-item gdtLineDisplay" data-display="hide" href="#">Hide Distance Markers</a>'+
					'<a class="dropdown-item gdtLineDisplay" data-display="show" href="#">Show Distance Markers</a>'+
					'<div role="separator" class="dropdown-divider"></div>'+
					'<a class="dropdown-item gdtLineDisplay" data-display="clear" href="#">Clear Distance Markers</a>'+
				'</div>'+
			'</div>'+
			'<input type="text" id="gdtLineDistance" class="form-control" aria-label="" style="min-width: 75px;">'+
			'<div class="input-group-btn">'+
				'<button type="button" id="gdtUnitLabel" class="btn btn-default dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Meters <span class="caret"></span></button>'+
				'<div class="dropdown-menu">'+
					'<a class="dropdown-item gdtDistanceUnits" data-units="meter" href="#">Meters</a>'+
					'<a class="dropdown-item gdtDistanceUnits" data-units="kilometer" href="#">Kilometers</a>'+
					'<a class="dropdown-item gdtDistanceUnits" data-units="feet" href="#">Feet</a>'+
					'<a class="dropdown-item gdtDistanceUnits" data-units="yard" href="#">Yards</a>'+
					'<a class="dropdown-item gdtDistanceUnits" data-units="mile" href="#">Miles</a>' +
					'<a class="dropdown-item gdtDistanceUnits" data-units="nm" href="#">Nautical Miles</a>'+
				'</div>'+
			'</div>'+
		'</div>';
	// Polyline editor used to determine distance
	var polyLine = new google.maps.Polyline({
		map: map,
		editable: true,
		strokeColor: "#C0C0C0",
		strokeOpacity: 1.0,
		strokeWeight: 2,
		icons: [{
			icon: { path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW },
			offset: '100%'
		}]
	});
	// Keep track of whether or not infowindow has been opened
	var infowindowOpened = false;
	// infowindow container
	var infowindow = new google.maps.InfoWindow({ content: null });

	// ======================================================
	// "Class" Distance for calculating and updating distance
	// ======================================================
	var Distance = function() {
		// Load html, check to see if jQuery is available, put distance menu onto map. 
		var init = (function() {
			if(typeof($) === 'undefined' && typeof(jQuery) === 'undefined') {
				console.log('Please load jQuery');
			}
			else {
				var $ = typeof(jQuery) === 'undefined' ? $ : jQuery;
				// Load template into div
				// id is available when gmapsDistanceTool is called
				$('#' + id).html(template);
				let gdtDistanceDiv = document.getElementById('gdtDistanceDiv');
				// Place template onto Google Maps
				// map is available when gmapsDistanceTool is called
				map.controls[google.maps.ControlPosition.TOP_CENTER].push(gdtDistanceDiv);
			}
		})();

		var resultObj = {
			distance: 0,
			units: 'meter',
			// Showing clicked segment distance
			showSegmentDistance: function(event) {
				var segmentLinesArr = polyLine.getPath().getArray(); 
				var updateDistanceUnits = this.updateDistanceUnits;

				for(let i=1; i<segmentLinesArr.length; i++) {
					// Temporarily create new line segment to see if what was clicked on was close enough to the distance line
					var tempLine = new google.maps.Polyline({
						map: map,
						editable: true,
						strokeColor: "#ADFF2F",
						strokeOpacity: 1.0,
						strokeWeight: 2,
						path: [segmentLinesArr[i-1], segmentLinesArr[i]] 
					});

					if(google.maps.geometry.poly.isLocationOnEdge(event.latLng, tempLine, .1)) {
						// If clicked on at the end of a segment, show infowindow to that distance
						if(segmentLinesArr[i] == event.latLng) {
							var distance = google.maps.geometry.spherical.computeDistanceBetween(segmentLinesArr[i-1], segmentLinesArr[i]);
							    distance = this.updateDistanceUnits(distance).toLocaleString();
							var content = "<div>Segment Distance: <br>" + distance + ' ' + this.units + "s</div>";

							infowindow.setPosition(event.latLng);
							// For some reason, when a previous offset infowindow has been opened, can't seem to get it to reset
							// Seems to be fixed for now, maybe a google maps update.
							if(infowindowOpened) {
								//infowindow.setOptions({pixelOffset: new google.maps.Size(-115,0), maxWidth: 150, disableAutoPan: true});
								infowindow.setOptions({pixelOffset: new google.maps.Size(0,0), maxWidth: 150, disableAutoPan: true});
							}
							else {
								infowindow.setOptions({pixelOffset: new google.maps.Size(0,0), maxWidth: 150, disableAutoPan: true});
							}

							infowindow.setContent(content);
							infowindow.open(map);
						}
					}

					// Clear temporary line
					tempLine.setMap(null);
					tempLine.getPath().clear();
					tempLine = '';
				}
			},
			// Updating distance display
			updateDistanceDisplay: function(display) {
				switch(display) {
					case 'hide':
						this.closeInfowindow();
						polyLine.setMap(null);
						break;
					case 'show':
						polyLine.setMap(map);
						break;
					case 'clear':
						this.closeInfowindow();
						polyLine.setMap(null);
						polyLine.getPath().clear();
						$('#gdtDistanceDiv').addClass('invisible');
						break;
				}
			},
			// Updating distance units
			updateDistanceUnits: function(distance) {
				switch(this.units) {
					case 'kilometer':
						distance = distance/1000;
						break;
					case 'mile':
						distance = distance*0.000621371;
						break;
					case 'yard':
						distance = distance*1.09361;
						break;
					case 'feet':
						distance = distance*3.28084;
						break;
					case 'nm':
						distance = distance/1852;	
					default:
						// Default unit is meter
						distance = distance;
				}

				return distance
			},
			// Updating distance between markers
			updateDistance: function () {
				var totalDistance = 0.0;
				var last = undefined;

				$.each(polyLine.getPath().getArray(), function(index, val) {
					if(last) {
						totalDistance += google.maps.geometry.spherical.computeDistanceBetween(last, val);
					}

					last = val;
				});

				totalDistance = this.updateDistanceUnits(totalDistance);
				$("#gdtLineDistance").val(totalDistance.toLocaleString());

				this.distance = totalDistance;

				this.closeInfowindow();
			},
			// Close infowindow
			closeInfowindow: function() {
				infowindow.close();
				// Reset infowindow
				infowindow = null
				infowindow = new google.maps.InfoWindow({content: null});
			}
		}

		return resultObj;
	}

	// =======================
	// Initiate Distance Class
	// =======================
	var d = new Distance();

	// ===============
	// Event Listeners
	// ===============
	// Add marker for distance measurement upon right click
	map.addListener('rightclick', function(event) {
		polyLine.setMap(map);
		polyLine.getPath().push(event.latLng);
		d.updateDistance();

		if($('#gdtDistanceDiv').hasClass('invisible')) {
			$('#gdtDistanceDiv').removeClass('invisible');
		}
	});
	// Upon dragging, update distance of polyline
	polyLine.getPath().addListener('remove_at', function(){
		d.updateDistance();
	});
	polyLine.getPath().addListener('insert_at', function(){
		d.updateDistance();
	});
	polyLine.getPath().addListener('set_at', function(){
		d.updateDistance();
	});
	// Delete distance marker (if clicked on a vertex), update distance
	polyLine.addListener('rightclick', function(event) {
		if(event.vertex != undefined) {
			polyLine.getPath().removeAt(event.vertex);
		}

		d.updateDistance();

		// If no points, hide distance box
		if(polyLine.getPath().getLength() < 1) { 
			$('#gdtDistanceDiv').addClass('invisible');
		}
	});
	// Show segment distnace
	polyLine.addListener('click', function(event) {
		d.showSegmentDistance(event);
	});
	// Update units for measuring distance
	$('.gdtDistanceUnits').click(function() {
		d.units = $(this).data('units');
		var label = $(this).html() + ' <span class="caret"></span>'; 
		$('#gdtUnitLabel').html(label);
		d.updateDistance();
	});
	// Update distance marker display
	$('.gdtLineDisplay').click(function() {
		d.updateDistanceDisplay($(this).data('display'));
	});

	// =====================
	// Return Distance Class
	// =====================
	return d;
}
