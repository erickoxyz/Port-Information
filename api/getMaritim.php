<?php
// public_html/api/getMaritim.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Helper cURL
function curlFetch(string $url): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER  => true,
        CURLOPT_FOLLOWLOCATION  => true,
        CURLOPT_TIMEOUT         => 20,
        CURLOPT_USERAGENT       => 'Mozilla/5.0',
    ]);
    $body = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['body'=>$body,'http'=>$http];
}

// 1) Ambil HTML daftar perairan
$listUrl = "https://peta-maritim.bmkg.go.id/public_api/perairan";
$res1 = curlFetch($listUrl);
if ($res1['http'] !== 200) {
    http_response_code($res1['http']?:500);
    echo json_encode(["error"=>"Gagal fetch daftar perairan","http"=>$res1['http']]);
    exit;
}
$html = $res1['body'];

// 2) Extract semua <a href="/public_api/cuaca/perairan/SLUG.json">
if (!preg_match_all(
    '#href="(/public_api/cuaca/perairan/([^"]+)\.json)"#i',
    $html,
    $m
)) {
    http_response_code(500);
    echo json_encode(["error"=>"Link JSON cuaca perairan tidak ditemukan"]);
    exit;
}
$paths = $m[1];    // array of "/public_api/cuaca/perairan/xxx.json"
$slugs = $m[2];    // array of "xxx"

// 3) Pilih slug "sungai-musi" (case-insensitive) bila ada, else index 0
$target = 'sungai-musi';
$idx = null;
foreach ($slugs as $i => $slug) {
    if (stripos($slug, $target) !== false) {
        $idx = $i;
        break;
    }
}
if ($idx === null) {
    $idx = 0;
}
$jsonUrl = "https://peta-maritim.bmkg.go.id" . $paths[$idx];

// 4) Fetch JSON cuaca perairan terpilih
$res2 = curlFetch($jsonUrl);
if ($res2['http'] !== 200) {
    http_response_code($res2['http']?:500);
    echo json_encode(["error"=>"Gagal fetch cuaca perairan","http"=>$res2['http']]);
    exit;
}

// 5) Kirim JSON murni
echo $res2['body'];
