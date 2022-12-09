<?php

$dbServer = 'localhost';
$dbUser = 'psql-user';
$dbPassword = 'xxx';
$dbDatabase = 'db_phonenumbers';
$dbPort = '5432';
$table_name = 'tbl_phone_number_details';
$accountsid = 'xxx';
$authtoken = 'xxx';

$db = pg_connect("host=$dbServer port=$dbPort dbname=$dbDatabase user=$dbUser password=$dbPassword") or die('Something went wrong | CONNECTION-FAILED');;

if ($_GET['phone'] == "") {
    echo "404";
    die();
}

$phone_number = $_GET['phone'];
$isPhoneNum = false;
$phone_number = preg_replace("/[^0-9]/", '', $phone_number);
if (strlen($phone_number) == 11) $phone_number = preg_replace("/^1/", '', $phone_number);
if (strlen($phone_number) == 10) $isPhoneNum = true;
if(!$isPhoneNum){
    echo "INTERNATIONAL";
    die();
}

echo get_number_name($db,$table_name,$phone_number, $accountsid, $authtoken);

function get_number_name($db, $table_name, $phone_number, $accountsid, $authtoken)
{
    $current_date = date('Y-m-d');
    $new_expiry_date = date('Y-m-d', strtotime("+90 days"));

    $selectQuery = pg_query($db, "SELECT * FROM $table_name where phone_number = '$phone_number' and expiry_date::date > '$current_date'");
    $selectResult = pg_fetch_assoc($selectQuery);

    if ($selectResult) {
        echo $selectResult['name'];
    } else {
        $name = get_api_data($phone_number, $accountsid, $authtoken);
        if ($name) {
            $checkRecordExists = pg_query($db, "SELECT * FROM $table_name where phone_number = '$phone_number'");
            $checkRecordExistCount = pg_num_rows($checkRecordExists);
            if ($checkRecordExistCount) {
                $addUpdateQuery = "UPDATE $table_name SET name='$name', expiry_date= '$new_expiry_date' where phone_number = '$phone_number'";
            } else {
                $addUpdateQuery = "INSERT INTO $table_name(phone_number,name,expiry_date) VALUES ('$phone_number','$name','$new_expiry_date')";
            }
            $executeQuery = pg_query($addUpdateQuery);
            if ($executeQuery) {
                echo $name;
            } else {
                echo "Something went wrong | INSERT-UPDATE-FAILED";
            }
        }
    }
}

function get_api_data($phone, $accountsid, $authtoken)
{
      $url = "https://api.opencnam.com/v3/phone/$phone(src)?account_sid=$accountsid&auth_token=$authtoken&service-level=plus";

    $curl = curl_init();

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, false);

    $data = curl_exec($curl);

    curl_close($curl);

    return $data;
}
