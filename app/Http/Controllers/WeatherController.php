<?php

namespace App\Http\Controllers;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WeatherController extends Controller
{
    public function index(Request $request)  // potential danger sql query
    {
        //dd($request->all());
        //$request->validate([
        //    'city' => 'required|string|max:255',
        //]);
        // Retrieve the city from the request, or default to 'London'
        $city = $request->input('city', 'London');
        $apiKey = env('OPENWEATHER_API_KEY');

        try {
            $response = Http::withQueryParameters([ // breaks down the url
                    'q' => $city,
                    'appid' => $apiKey,
                    'units' => 'metric'
            ])->get("http://api.openweathermap.org/data/2.5/weather");

            // The q parameter is a query string key that stands for "query."
            // The appid parameter is the API key required to authenticate the request with the OpenWeatherMap API. The $apiKey holds your unique API key, which is generated when you sign up for the API.
            // The units parameter defines the unit system for the temperature and other weather-related data.

            $weatherData = json_decode($response->body(), true);

            // Check if the API returned valid weather data
            if (isset($weatherData['weather'][0]['id'])) {
                $weatherCode = $weatherData['weather'][0]['id'];
            } else {
                // Log and return error if weather data is missing
                Log::error('Invalid weather data response', ['response' => $weatherData]);
                return back()->withErrors('Failed to retrieve weather data');
            }

            // Get the emoji based on weather condition
            $emoji = $this->weatherEmoji($weatherCode);

        } catch (Exception $e) {
            Log::error('Failed to fetch weather data: '.$e->getMessage());
            return back()->withErrors('Failed to fetch weather data');
        }

        // Fetch location data for Google Maps
        $mapLocation = $this->getMapLocation($city);
        if (is_null($mapLocation)) {
            Log::error('Failed to retrieve map location for city: ' . $city);
        }

        // Log everything for debugging
        $this->logWeatherData($city, $weatherData, $emoji, $mapLocation);

        // Pass data to the view
        return view('weather', [
            'weatherData' => $weatherData,
            'emoji' => $emoji,
            'mapLocation' => $mapLocation,
        ]);

        // $response->getBody():
        //
        //    This method retrieves the body of the HTTP response, which is typically a JSON-encoded string when interacting with APIs.

        // json_decode($response->getBody(), true):
        //
        //    json_decode is a PHP function used to convert a JSON-encoded string into a PHP variable.
        //    The first argument is the JSON string you want to decode. In this case, it’s the body of the HTTP response.
        //    The second argument, true, indicates that the JSON should be decoded into an associative array. If this argument is omitted or set to false, json_decode will return an object instead.

        // Extract the weather ID from the data
        //$weatherCode = $weatherData['weather'][0]['id'] ?? null; // otherwise null

        // Determine the emoji based on the weather ID
        //$emoji = $this->weatherEmoji($weatherCode);

        // Fetch location data for Google Maps
        //$mapLocation = $this->getMapLocation($city);

        //Log::info('City entered: ' . $city);
        //Log::info('Weather Data:', ['data' => $weatherData]);
        //Log::info('Weather Emoji:', ['emoji' => $emoji]);
        //Log::info('Map Location:', ['location' => $mapLocation]);

        // Pass both weather data and emoji to the view
        //return view('weather', ['weatherData' => $weatherData, 'emoji' => $emoji, 'mapLocation' => $mapLocation]);
    }

    /**
     * Logs the weather data, emoji, and map location.
     */
    private function logWeatherData($city, $weatherData, $emoji, $mapLocation)
    {
        Log::info('City entered: ' . $city);
        Log::info('Weather Data:', ['data' => $weatherData]);
        Log::info('Weather Emoji:', ['emoji' => $emoji]);
        Log::info('Map Location:', ['location' => $mapLocation]);
    }

    public function weatherEmoji($weatherCode)
    {
        switch (true) {       // true - if the following cases are true
            case ($weatherCode >= 200 && $weatherCode < 300):
                return '🌩️';
            case ($weatherCode >= 300 && $weatherCode < 400):
                return '🌧';
            case ($weatherCode >= 500 && $weatherCode < 600):
                return '☔️️';
            case ($weatherCode >= 600 && $weatherCode < 700):
                return '❄️';
            case ($weatherCode >= 700 && $weatherCode < 800):
                return '🌁';
            case ($weatherCode === 800):
                return '☀️';
            case ($weatherCode >= 801 && $weatherCode < 810):
                return '☁️';
            default:
                return '❓';
        }
    }


    /**
     * Fetches the map location based on the city.
     */
    private function getMapLocation($city)   // need to convert city into coordinates
    {
        $apiKey = env('GOOGLE_MAPS_API_KEY');
        try {
            $response = Http::get("https://maps.googleapis.com/maps/api/geocode/json", [
                'address' => $city,
                'key' => $apiKey,
            ]);

            $data = json_decode($response->body(), true);
            // the true parameter tells PHP to return the decoded JSON as an associative array instead of an object.

            // Check if the location data is valid
            if ($data['status'] === 'OK') {
                $location = $data['results'][0]['geometry']['location'];
                return [
                    'lat' => $location['lat'],  //  assign key =>  assign value in a new associative array
                    'lng' => $location['lng'],
                ];
            } else {
                Log::error('Geocoding API error: ' . $data['status']);
            }
        } catch (Exception $e) {
            Log::error('Failed to fetch map location: ' . $e->getMessage());
        }

        return null;  // indicate that the function was unable to retrieve or compute a valid value
    }
}
