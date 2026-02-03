<?php
// includes/geo_calc.php
// 'Geospatial helpers' for Grimsby & Cleethorpes local delivery validation
// created to check registered users have an address within 10 miles og Grimsby 

// Calculate distance between two points in miles using Haversine formula
function getDistance($lat1, $lon1, $lat2, $lon2) {
    // Avoid division by zero or log errors on identical points
    if (($lat1 == $lat2) && ($lon1 == $lon2)) return 0;

    $theta = $lon1 - $lon2;
    $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
    
    // Safety check for acos range
    $dist = acos(min(max($dist, -1.0), 1.0));
    
    $dist = rad2deg($dist);
    $miles = $dist * 60 * 1.1515;
    return $miles;
}
// Validate postcode is within 10 miles of Grimsby (DN31 1AA)
// Uses postcodes.io
function isLocalPostcode($userPostcode) {
    // Grimsby Town Hall Coordinates (Anchor Point)
    $targetLat = 53.5677;
    $targetLon = -0.0827;
    
    // Clean the postcode (remove spaces) for the API call
    $cleanPc = urlencode(str_replace(' ', '', $userPostcode));
    $url = "https://api.postcodes.io/postcodes/{$cleanPc}";
    
    // Set a short timeout so the site doesn't hang if the API is slow
    $context = stream_context_create(['http' => ['timeout' => 2]]);
    $response = @file_get_contents($url, false, $context);
    
    if (!$response) return false; 

    $data = json_decode($response, true);
    if (isset($data['status']) && $data['status'] === 200) {
        $userLat = $data['result']['latitude'];
        $userLon = $data['result']['longitude'];
        
        $distance = getDistance($targetLat, $targetLon, $userLat, $userLon);
        
        // Return true if within 10 miles
        return ($distance <= 10); 
    }
    
    return false;
}
?>