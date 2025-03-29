<?php
// Simple test script to test the media upload API with folder_id parameter

// Your API URL and token
$apiUrl = 'http://localhost:8000/api/media/store'; 
$token = 'YOUR_API_TOKEN'; // Replace with an actual token

// Sample file path - replace with an actual image
$filePath = __DIR__ . '/sample.jpg';

if (!file_exists($filePath)) {
    die("Sample file not found: $filePath");
}

// Create a cURL request
$curl = curl_init();

// Setup the multipart form data
$cfile = new CURLFile($filePath, 'image/jpeg', 'sample.jpg');

$postData = [
    'file' => $cfile,
    'folder_id' => 1 // This is the folder ID we want to test
];

curl_setopt_array($curl, [
    CURLOPT_URL => $apiUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => $postData,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token,
        'Accept: application/json'
    ],
]);

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

// Output the response
echo "HTTP Status Code: $httpCode\n";
echo "Response:\n";
echo $response;
echo "\n";

// Decode the JSON response
$jsonResponse = json_decode($response, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "Folder ID in response: " . 
        (isset($jsonResponse['data']['folder_id']) ? $jsonResponse['data']['folder_id'] : 'Not set') . "\n";
} 