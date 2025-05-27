<?php
// PRIMARY REFERENCE:
// https://api.docs.cpanel.net/whm/use-whm-api-to-call-cpanel-api-and-uapi/
$user = "root";
// Janus13 Token
$token = "GGWKWFGGCBIGJZ9RJV8Z7H47TM8YA1EG";
echo "Testing connection to janus13.easyonnet.io with token...\n";
// First, try a simple version API call which should always work
$version_query = "https://janus13.easyonnet.io:2087/json-api/version?api.version=1";
$curl = curl_init();
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($curl, CURLOPT_TIMEOUT, 30);
$header[0] = "Authorization: whm $user:$token";
curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
curl_setopt($curl, CURLOPT_URL, $version_query);
$result = curl_exec($curl);
$http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
echo "Version API call result: HTTP Status $http_status\n";
if ($http_status == 200) {
    $json = json_decode($result);
    echo "Version info: " . $json->data->version . "\n\n";
} else {
    echo "Error: " . curl_error($curl) . "\n";
    echo "Response: " . $result . "\n\n";
}
curl_close($curl);
// Now try to add the email forwarder using the correct account (dev477)
echo "Now trying to add email forwarder for dev477...\n";
// Build the API query
$cpanel_query = "https://janus13.easyonnet.io:2087/json-api/cpanel?api.version=1";
$params = [
    'cpanel_jsonapi_user' => 'dev477',  // Use dev477 instead of dev771
    'cpanel_jsonapi_module' => 'Email',
    'cpanel_jsonapi_func' => 'add_forwarder',
    'cpanel_jsonapi_apiversion' => 3,
    'email' => 'jeremyirons@dev477.easyonnet.io',
    'fwdemail' => 'cbroumley@easyonnet.com',
    'domain' => 'dev477.easyonnet.io',
    'fwdopt' => 'fwd',
];
foreach($params as $key => $value) {
    $cpanel_query .= '&'.sprintf("%s=%s", $key, $value);
}
echo "API URL: $cpanel_query\n";
$curl = curl_init();
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($curl, CURLOPT_TIMEOUT, 30);
$header[0] = "Authorization: whm $user:$token";
curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
curl_setopt($curl, CURLOPT_URL, $cpanel_query);
$result = curl_exec($curl);
$http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
echo "Email forwarder API call result: HTTP Status $http_status\n";
if ($http_status == 200) {
    $json = json_decode($result);
    echo "Response: " . print_r($json, true) . "\n";
} else {
    echo "Error: " . curl_error($curl) . "\n";
    echo "Response: " . $result . "\n";
}
curl_close($curl); 