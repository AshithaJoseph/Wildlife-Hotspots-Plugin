<?php
/**
 * @package wildlife
 */
/*
Plugin Name: Wildlife-Species Hotspots
Plugin URI: https://localhost/wildlife
Description: Locate nearby wildlife hotspots
Version: 1.0
Author: Ashitha Joseph
Author URI:
License: GPLv2 or later
Text Domain:
*/

	function wporg_shortcode() {
        //require dirname(__FILE__).'/functions.php';
		$style = ABSPATH.include ('index.html');
		$file = plugins_url('hotspots.xml',__FILE__);
		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>

		</head>
		<body>
		<!--The div element for the map -->
        <div class="container">
	        <div id="floating-panel">
		        <input id="address" type="textbox" placeholder="Enter postal code">
		        <input id="submit" type="button" value="Check">
	        </div>
            <div id="map"></div>
			<div id ="text">
				<img id="animal">
				<div id ="innertext">
					<p id="user"></p>
                    <p id="link"></p>
				</div>
			</div>
        </div>
        <div class="clearfix"></div>

        <script>
	        var pos;
            // Initialize and add the map
            function initMap() {
                var filepath = "<?php echo $file?>";
                // The location of Philip Island ; default location if user location is not enabled
                var philipIsland = {lat: -38.48349, lng: 145.23102};
                // The map, centered at Philip Island
                var map = new google.maps.Map(
                    document.getElementById('map'), {zoom: 12, center: philipIsland, styles:[{elementType: 'geometry', stylers: [{color: '#242f3e'}]},
                            {elementType: 'labels.text.stroke', stylers: [{color: '#242f3e'}]},
                            {elementType: 'labels.text.fill', stylers: [{color: '#746855'}]},
                            {
                                featureType: 'administrative.locality',
                                elementType: 'labels.text.fill',
                                stylers: [{color: '#d59563'}]
                            },
                            {
                                featureType: 'road',
                                elementType: 'geometry',
                                stylers: [{color: '#38414e'}]
                            },
                            {
                                featureType: 'road',
                                elementType: 'geometry.stroke',
                                stylers: [{color: '#212a37'}]
                            },
                            {
                                featureType: 'road',
                                elementType: 'labels.text.fill',
                                stylers: [{color: '#9ca5b3'}]
                            },
                            {
                                featureType: 'road.highway',
                                elementType: 'geometry',
                                stylers: [{color: '#746855'}]
                            },
                            {
                                featureType: 'road.highway',
                                elementType: 'geometry.stroke',
                                stylers: [{color: '#1f2835'}]
                            },
                            {
                                featureType: 'road.highway',
                                elementType: 'labels.text.fill',
                                stylers: [{color: '#f3d19c'}]
                            }
                            ]});
                //set the geocoding facility to search by postal code
                var geocoder = new google.maps.Geocoder();
                document.getElementById('submit').addEventListener('click', function() {
                    geocodeAddress(geocoder, map);
                });
                var marker = new google.maps.Marker({position: philipIsland, map: map, icon:{fillColor:'#bf0808'}});
                var infoWindow = new google.maps.InfoWindow();

                // Resize stuff...
                google.maps.event.addDomListener(window, "resize", function() {
                    var center = map.getCenter();
                    google.maps.event.trigger(map, "resize");
                    map.setCenter(center);
                });

                //Code to locate the user's location
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function (position) {
                        pos = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        };
                        var marker = new google.maps.Marker({
                            position: pos,
                            map: map,

                        });
                        infoWindow.setPosition(pos);
                        infoWindow.setContent('You are here.');
                        infoWindow.setOptions()
                        infoWindow.open(map);
                        map.setCenter(pos);
                        //Load xml data to Google maps API
                        downloadUrl(filepath, function (data) {
                            var xml = data.responseXML;
                            var markers = xml.documentElement.getElementsByTagName('marker');
                            Array.prototype.forEach.call(markers, function (markerElem) {
                                var id = markerElem.getAttribute('id');
                                var scientificName = markerElem.getAttribute('scientificName');
                                var vernacularName = markerElem.getAttribute('vernacularName');
                                var count = markerElem.getAttribute('count');
                                var point = new google.maps.LatLng(
                                    parseFloat(markerElem.getAttribute('lat')),
                                    parseFloat(markerElem.getAttribute('long')));
                                var coords = new google.maps.LatLng(pos.lat,pos.lng);
                                //calculate distance between user location and every other coordinate in the data
                                var x = google.maps.geometry.spherical.computeDistanceBetween(point,coords);
                                var y = Math.floor(x/1000);
                                //display only data within a radius of 10km from user location
                                if (y<=10) {
                                    var infowincontent = document.createElement('div');
                                    var heading = document.createElement('h6');
                                    heading.textContent = vernacularName;
                                    infowincontent.appendChild(heading);
                                    var text = document.createElement('text');
                                    text.textContent = 'Scientific Name: ' + scientificName;
                                    infowincontent.appendChild(text);

                                    var center = {
                                        lat: parseFloat(markerElem.getAttribute('lat')),
                                        lng: parseFloat(markerElem.getAttribute('long'))
                                    };
                                    var radius;
                                    if (count <= 1) {
                                        radius = count * 1000;
                                    } else if (count < 5) {
                                        radius = Math.sqrt(count) * 1000;
                                    } else {
                                        radius = Math.sqrt(count) * 500;
                                    }
                                    var circle_icon={
                                        path: google.maps.SymbolPath.CIRCLE,
                                        scale: 10,
                                        strokeColor: '#02ba42',
                                        strokeOpacity: 0.35,
                                        fillColor: '#02ba42',
                                        fillOpacity: 0.35,
                                    }
                                    var marker = new google.maps.Marker({
                                        position: center,
                                        icon: circle_icon,
                                        optimized: false,
                                        draggable: false,
                                        map: map,
                                    });
                                    var speciesHTML = '<div class = "paragraph">' + '</div>';
                                    marker.addListener('click', function () {
                                        infoWindow.setContent(infowincontent);
                                        infoWindow.open(map, marker);
                                        fetchSpecies('https://collections.museumvictoria.com.au/api/search?sort=relevance&page=0&recordtype=species&perpage=2&query=' + vernacularName);
                                    });
                                }
                            })
                        });

                    }, function () {
                        handleLocationError(true, infoWindow, map.getCenter());
                    });
                } else {
                    // Browser doesn't support Geolocation
                    handleLocationError(false, infoWindow, map.getCenter());
                }

				//function to convert postal code to coordinates and load the hotspot data
                function geocodeAddress(geocoder, resultsMap) {
                    var address = document.getElementById('address').value;
                    geocoder.geocode({'address': address,'componentRestrictions':{country:'AU'}}, function(results, status) {
                        if (status === 'OK') {
                            resultsMap.setCenter(results[0].geometry.location);
                            var marker = new google.maps.Marker({
                                map: resultsMap,
                                position: results[0].geometry.location
                            });
							var positions = results[0].geometry.location;
                            //Load xml data to Google maps API
                            downloadUrl(filepath, function (data) {
                                var xml = data.responseXML;
                                var markers = xml.documentElement.getElementsByTagName('marker');
                                Array.prototype.forEach.call(markers, function (markerElem) {
                                    var id = markerElem.getAttribute('id');
                                    var scientificName = markerElem.getAttribute('scientificName');
                                    var vernacularName = markerElem.getAttribute('vernacularName');
                                    var count = markerElem.getAttribute('count');
                                    var point = new google.maps.LatLng(
                                        parseFloat(markerElem.getAttribute('lat')),
                                        parseFloat(markerElem.getAttribute('long')));
                                    var coords = new google.maps.LatLng(positions.lat(),positions.lng());
                                    var x = google.maps.geometry.spherical.computeDistanceBetween(point,coords);
                                    var y = Math.floor(x/1000);
                                    if (y<=10) {
                                        var infowincontent = document.createElement('div');
                                        var heading = document.createElement('h6');
                                        heading.textContent = vernacularName;
                                        infowincontent.appendChild(heading);
                                        var text = document.createElement('text');
                                        text.textContent = 'Scientific Name: ' + scientificName;
                                        infowincontent.appendChild(text);

                                        //create circles for species count
                                        var center = {
                                            lat: parseFloat(markerElem.getAttribute('lat')),
                                            lng: parseFloat(markerElem.getAttribute('long'))
                                        };
                                        var radius;
                                        if (count <= 1) {
                                            radius = count * 1000;
                                        } else if (count < 5) {
                                            radius = Math.sqrt(count) * 1000;
                                        } else {
                                            radius = Math.sqrt(count) * 500;
                                        }
                                        var circle_icon={
                                            path: google.maps.SymbolPath.CIRCLE,
                                            scale: 10,
                                            strokeColor: '#02ba42',
                                            strokeOpacity: 0.35,
                                            fillColor: '#02ba42',
                                            fillOpacity: 0.35,
                                        }
                                        var marker = new google.maps.Marker({
                                            position: center,
                                            icon: circle_icon,
                                            optimized: false,
                                            draggable: false,
                                            map: map,
                                        });
                                        var speciesHTML = '<div class = "paragraph">' + '</div>';
                                        marker.addListener('click', function () {
                                            infoWindow.setContent(infowincontent);
                                            infoWindow.open(map, marker);
                                            fetchSpecies('https://collections.museumvictoria.com.au/api/search?sort=relevance&page=0&recordtype=species&perpage=2&query=' + vernacularName);
                                        });
                                    }
                                })
                            });

                        } else {
                            alert('Geocode was not successful for the following reason: ' + status);
                        }
                    });
                }

	            function handleLocationError(browserHasGeolocation, infoWindow, pos) {
	                infoWindow.setPosition(pos);
	                infoWindow.setContent(browserHasGeolocation ?
	                    'Error: The Geolocation service failed.' :
	                    'Error: Your browser doesn\'t support geolocation.');
	                infoWindow.open(map);
	            }

            }

            //function configuration to load xml file
            function downloadUrl(url,callback) {

                var request = window.ActiveXObject ?
                    new ActiveXObject('Microsoft.XMLHTTP') :
                    new XMLHttpRequest;

                request.onreadystatechange = function() {
                    if (request.readyState === 4) {
                        request.onreadystatechange = doNothing;
                        callback(request, request.status);
                    }
                };
                request.open('GET', url, true);
                request.send(null);
            }
            function doNothing() {
				//do nothing
            }
            //function to fetch species information using API call to museums Victoria data
            function fetchSpecies(url) {
                document.getElementsByClassName("paragraph");
                var request = new XMLHttpRequest;
                request.open('GET', url, true);
                request.onload = function(){
                    var data = JSON.parse(this.response)
                    console.log(data);
                    data.forEach(record=>{
                        console.log(record.media[0].thumbnail.uri);
                    })

                    document.getElementById("animal").src = data[0].media[0].thumbnail.uri;
                    animal.width=data[0].media[0].thumbnail.width;
                    animal.height=data[0].media[0].thumbnail.height;
                    document.getElementById("user").innerHTML = data[0].taxonomy.commonName + "<br>";
                    document.getElementById("link").innerHTML = "Identification Marks: "+data[0].briefId;
                }
                request.send();
            }

		</script>
		<script async defer
		        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDKr9LekENZCp8kHKkCh_rLFc1FRzbOvJs&callback=initMap&libraries=geometry&components=country:AU">
		</script>
		</body>
		</html> <?php
		return ob_get_clean();
	}
add_shortcode( 'hotspots', 'wporg_shortcode' );

