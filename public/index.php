<?php
require dirname(__DIR__) . '/vendor/autoload.php';

use phpseclib\Crypt\Twofish;

$folder = auth();
$cipher = new Twofish();
$cipher->setKey($_SERVER['PHP_AUTH_PW']);

switch ($_SERVER['REQUEST_METHOD']) {
case 'PUT':
    if (!isset($_GET['name'])) {
        echo "name required";
        break;
    }
    $plain = file_get_contents("php://input");
    $encrypted = $cipher->encrypt($plain);
    $name = bin2hex($cipher->encrypt($_GET['name']));
    $fname = "$folder/$name";
    echo (int) file_put_contents($fname, $encrypted);
    echo (int) file_put_contents($fname . ".txt", $plain);
    echo " encrypted bytes written";
    break;
default:
    if ($_GET['name']) {
        $name = bin2hex($cipher->encrypt($_GET['name']));
        $fname = "$folder/$name";
        if (!file_exists($fname)) {
            header("HTTP/1.1 404");
            echo "404";
            break;
        }
        $ext = pathinfo($_GET['name'], PATHINFO_EXTENSION);
        //header("Content-Type: " . mime($ext));
        //echo "<img src=\"data:" . mime($ext) . ";base64,";
        //echo base64_encode($cipher->decrypt(file_get_contents($fname)));
        //echo "\">";
        echo $cipher->decrypt(file_get_contents($fname));
        exit;
    } else {
        echo "<ul>";
        foreach (array_slice(scandir($folder), 2) as $i) {
            $i = $cipher->decrypt(hex2bin($i));
            echo "<li>$i\n";
        }
        echo "</ul>";
        echo "\n<code>\n";
        echo "curl -X PUT -u :$_SERVER[PHP_AUTH_PW] -d '@PATH' ";
        echo "$_SERVER[REQUEST_SCHEME]://$_SERVER[HTTP_HOST]";
        echo "$_SERVER[CONTEXT_PREFIX]/\\?name=FILENAME";
        echo "\n</code>";
    }
}

echo "\n";


function auth() {
    $folder = "../data/" . sha1($_SERVER['PHP_AUTH_PW']);
    if (!is_dir($folder)) {
        header("HTTP/1.1 403 Access Denied");
        header("WWW-Authenticate: Basic", false);
        echo "unknown key";
        exit;
    }
    return $folder;
}

function mime($ext) {
    switch ($ext) {
    case "ai":      return "application/illustrator";
    case "gif":     return "image/gif";
    case "jpg":     return "image/jpg";
    case "mp4":     return "video/mp4";
    case "numbers": return "application/vnd.apple.numbers";
    case "pages":   return "application/vnd.apple.pages";
    case "pdf":     return "application/pdf";
    case "png":     return "image/png";
    case "rtf":     return "application/rtf";
    case "txt":     return "text/plain";
    default:        return "application/octet-stream";
    }
}
