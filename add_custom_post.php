<?php

/*
 * Description : PHP CLI to Add Custom Post To WP
 * Parameters  -t title -c content -e excerpt -u username -p password -s publish/future/draft/pending/private" --ty 'post_type'
 */


// Established Connection
// require '/home/admin/wwwroot/adpost.com/wp/variables.php'; // Variables file
// echo "Hellloooo";exit;

require 'Connection.php';


$shortopts = "f:t:c:e:u:p:s:a:m:";
$longopts = array("ty:", "dt:", "dtg:", "ts:", "sl:", "fm:", "cs:", "ps:", "fr:", "st:", "tl:", "ct:", "tg:", "ll:", "pwd:");
$parameters = getopt($shortopts, $longopts);

$error = validateParams($parameters);
/*
  Checks empty values
 *  */

if (!empty($error)) {
    echo $error;
    exit(1);
}

$parameters = filterInput($parameters);
$request_json = array(); // Create an array
/*
 * Assigns arguments values in array
 */
(!empty($parameters['t'])) ? $request_json['title'] = $parameters['t'] : '';
(!empty($parameters['c'])) ? $request_json['content'] = $parameters['c'] : '';
(!empty($parameters['e'])) ? $request_json['excerpt'] = $parameters['e'] : '';
(!empty($parameters['s'])) ? $request_json['status'] = $parameters['s'] : '';
(!empty($parameters['dt'])) ? $request_json['date'] = $parameters['dt'] : '';
(!empty($parameters['dtg'])) ? $request_json['date_gmt'] = $parameters['dtg'] : '';
(!empty($parameters['sl'])) ? $request_json['slug'] = $parameters['sl'] : '';
(!empty($parameters['a'])) ? $request_json['author'] = $parameters['a'] : '';
(!empty($parameters['fm'])) ? $request_json['featured_media'] = $parameters['fm'] : '';
(!empty($parameters['cs'])) ? $request_json['comment_status'] = $parameters['cs'] : '';
(!empty($parameters['ps'])) ? $request_json['ping_status'] = $parameters['ps'] : '';
(!empty($parameters['fr'])) ? $request_json['format'] = $parameters['fr'] : '';
(!empty($parameters['m'])) ? $request_json['meta'] = $parameters['m'] : '';
(!empty($parameters['st'])) ? $request_json['sticky'] = $parameters['st'] : '';
(!empty($parameters['tl'])) ? $request_json['template'] = $parameters['tl'] : '';
(!empty($parameters['ct'])) ? $request_json['categories'] = $parameters['ct'] : '';
(!empty($parameters['tg'])) ? $request_json['tags'] = $parameters['tg'] : '';
(!empty($parameters['ll'])) ? $request_json['liveblog_likes'] = $parameters['ll'] : '';
(!empty($parameters['pwd'])) ? $request_json['password'] = $parameters['pwd'] : '';

$username = $parameters['u']; // admin	username
$password = $parameters['p']; // admin password

$cvurl = 'http://localhost:80/gsthero-web-wp/wp-json/wp/v2/posts/';
// $cvurl= 'http://35.154.208.8/wp-json/wp/v2/posts/';
$wp_request_headers = array(
    'Authorization: Basic ' . base64_encode($username . ":" . $password)
);
// var_dump($wp_request_headers);exit;
$curl = curl_init();

curl_setopt($curl, CURLOPT_URL, $cvurl);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, $wp_request_headers);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, $request_json);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
$json_response = curl_exec($curl);
// var_dump($json_response);exit;
$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

$arry_response = json_decode($json_response, true);
// var_dump($arry_response);exit;
$post_id = $arry_response['id'];
$post_type = $parameters['ty'];

if (!empty($post_id)) {
   $stmt_post_update = $conn->prepare("UPDATE wp_posts SET post_type = ? WHERE ID = ?");
   $stmt_post_update->bind_param("ss",$post_type, $post_id);
   $status = $stmt_post_update->execute();
   $stmt_post_update->close();
}
echo $post_id;
/**
 * This function validates empty parameters 
 * @param array $parameters
 * @return string $msg
 */
function validateParams($parameters) {
    $msg1 = empty($parameters['t']) ? "ERR :204, TITLE ARGUMENT NEEDED -t title\n" : '';
    $msg2 = empty($parameters['c']) ? "ERR :204, CONTENT ARGUMENT NEEDED -c content\n" : '';
    $msg3 = empty($parameters['e']) ? "ERR :204, EXCERPT ARGUMENT NEEDED -e excerpt\n" : '';
    $msg4 = empty($parameters['u']) ? "ERR :204, USERNAME ARGUMENT NEEDED -u username\n" : '';
    $msg5 = empty($parameters['p']) ? "ERR :204, PASSWORD ARGUMENT NEEDED -p password\n" : '';
    $msg6 = empty($parameters['s']) ? "ERR :204, STATUS ARGUMENT NEEDED -s publish/future/draft/pending/private\n" : '';
    $msg7 = empty($parameters['ty']) ? "ERR :204, CUSTOM POST NAME ARGUMENT NEEDED --ty post_type\n" : '';
    return $msg1 . $msg2 . $msg3 . $msg4 . $msg5 . $msg6 . $msg7;
}

// Replace \r from input
function filterInput($parameters) {
    foreach ($parameters as $key => $value) {
        $new_parameters[$key] = str_replace("\r", "", $value);
    }
    return $new_parameters;
}

?>
