<?php

$awtenantcode = "awtenantcode"; //Go to Groups & Settings > System > Advanced > API > REST API
$awconsole = "asxxx.awmdm.com"; 
$awcredential = base64_encode("api-user:password");
$headers = [
    "aw-tenant-code: $awtenantcode",
    "Content-Type: application/json",
    "Authorization: Basic $awcredential"
];

$postdata = file_get_contents("php://input");
$jsondata = json_decode($postdata);
$deviceID = $jsondata->DeviceId;

//Get the IP address from the received device ID in Workspace ONE UEM
$deviceIP = "0.0.0.0";
$s = 0;
while($deviceIP === "0.0.0.0") {
    $deviceIP = getDeviceIpAddress($deviceID);
    //file_put_contents('log-DemoEnrollment.txt', PHP_EOL . "Running for $deviceID for $s seconds. IP: $deviceIP", FILE_APPEND);
    if($deviceIP === "0.0.0.0") {
        $s = ($s +5);
        sleep(5);
    } else { break; }
    if ($s == 30) {
        queryDevice($deviceID);
    }
} 

//Place the device ID inside an array with an object of BulkValues
$arrayofid = [$deviceID];
$bulkarray = array('Value' => $arrayofid);
$object = (object) ['BulkValues' => $bulkarray];
$bulkdata = json_encode($object);

//Check if the IP belongs to the Utrecht or Amsterdam network range
if (strpos($deviceIP, '192.168.178') === 0) {
   //Amsterdam
    assignTagToDevice("408461", $bulkdata);
} else if (strpos($deviceIP, '192.168.2') === 0) {
    //Utrecht
    assignTagToDevice("408460", $bulkdata);
}

function getDeviceIpAddress($id) {
    global $headers, $awconsole;
	$curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, "https://$awconsole/API/mdm/devices/$id/network");
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $data = curl_exec($curl);
    //file_put_contents('log-DemoEnrollment-AWData.txt', PHP_EOL . $data, FILE_APPEND);
    $json = json_decode($data, true);
    $ip = $json['IPAddress'];
    if(isset($ip['WifiIPAddress'])) {
        $ip = $ip['WifiIPAddress'];
    } else {
        $ip = "0.0.0.0";
    }
    curl_close($curl);
    return $ip;
}

function assignTagToDevice($tagid, $deviceid) {
    global $headers, $awconsole;
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, "https://$awconsole/API/mdm/tags/$tagid/adddevices");
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $deviceid);
    curl_exec($curl);
    curl_close($curl);    
}

function queryDevice($deviceid) {
    global $awtenantcode, $awcredential, $awconsole;
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, "https://$awconsole/API/mdm/devices/$deviceid/commands?command=DeviceQuery");
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        "aw-tenant-code: $awtenantcode",
        "Content-Type: application/json",
        "Content-Length: 0",
        "Authorization: Basic $awcredential"
    ));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, "");
    curl_exec($curl);
    curl_close($curl);    
}

?>
