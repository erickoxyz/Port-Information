<?php
// public_html/api/getBMKG.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Utility cURL
function curlFetch(string $url): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER  => true,
        CURLOPT_FOLLOWLOCATION  => true,
        CURLOPT_CONNECTTIMEOUT  => 10,
        CURLOPT_TIMEOUT         => 30,
        CURLOPT_USERAGENT       => 'Mozilla/5.0',
        CURLOPT_HTTPHEADER      => ['Accept: application/json'],
        CURLOPT_ENCODING        => ''
    ]);
    $body = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['body'=>$body, 'http'=>$http];
}

$station = $_GET['perairan'] ?? '';
$listUrl = "https://peta-maritim.bmkg.go.id/public_api/perairan";
$res1 = curlFetch($listUrl);

if ($res1['http'] !== 200 || !$res1['body']) {
    http_response_code($res1['http']?:500);
    echo json_encode(["error"=>"Gagal fetch daftar perairan","http"=>$res1['http']]);
    exit;
}

// Cari link JSON perairan yang sesuai nama (case-insensitive)
$html = $res1['body'];
if (!preg_match_all('/<a\s+href="([^"]+\.json)">\s*([^<]+)\s*<\/a>/i', $html, $m)) {
    http_response_code(500);
    echo json_encode(["error"=>"Daftar JSON perairan tak ditemukan"]);
    exit;
}
$urls = $m[1];   // URL relative
$names= $m[2];   // Nama perairan
$matchUrl = null;
foreach ($names as $i => $name) {
    if (stripos($name, $station) !== false) {
        $matchUrl = $urls[$i];
        break;
    }
}
if (!$matchUrl) {
    http_response_code(404);
    echo json_encode(["error"=>"Perairan '{$station}' tidak ada di daftar"]);
    exit;
}
$jsonUrl = (strpos($matchUrl,'http')===0)
         ? $matchUrl
         : "https://peta-maritim.bmkg.go.id".$matchUrl;
$res2 = curlFetch($jsonUrl);
if ($res2['http']!==200 || !$res2['body']) {
    http_response_code($res2['http']?:500);
    echo json_encode(["error"=>"Gagal fetch JSON perairan","http"=>$res2['http']]);
    exit;
}
// Sukses: kirim data JSON mentah
echo $res2['body'];
