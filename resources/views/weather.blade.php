<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weather</title>
    <style>
        #map {
            height: 500px;
            width: 100%;
        }
    </style>
    <script src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAPS_API_KEY') }}"></script>

    <script>
        function initMap() {
            var location = @json($mapLocation); // Pass location data from the controller
            if (location) {
                var mapOptions = {
                    center: { lat: location.lat, lng: location.lng },
                    zoom: 12
                };
                var map = new google.maps.Map(document.getElementById('map'), mapOptions);
                new google.maps.Marker({
                    position: { lat: location.lat, lng: location.lng },
                    map: map,
                    title: '{{ $weatherData['name'] }}'
                });
            } else {
                document.getElementById('map').innerText = 'Location not found.';
            }
        }

        window.onload = initMap;
    </script>
</head>
<body>
<h1>Weather Information</h1>

<form action="{{ url('/weather') }}" method="GET">
    <label for="city">Enter city:</label>
    <input type="text" id="city" name="city" value="{{ request('city', 'London') }}" />
    <button type="submit">Get Weather</button>
</form>

@if(isset($weatherData))
    <h2>City: {{ $weatherData['name'] }}</h2>
    <p>Temperature: {{ $weatherData['main']['temp'] }} °C</p>
    <p>Weather: {{ $weatherData['weather'][0]['description'] }}</p>
    <p>Humidity: {{ $weatherData['main']['humidity'] }}%</p>
    <p>Wind Speed: {{ $weatherData['wind']['speed'] }} m/s</p>
    <p>Weather Emoji: {{ $emoji }}</p>

    <h2>Map Location</h2>
    <div id="map"></div> <!-- Map container -->
@endif
</body>
</html>
