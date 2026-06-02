<?php

header("Content-Type: application/json");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

$API_KEY = "AIzaSyBAK7ltFPFDX-k0FjBMnKzf0pq_WSJGhGY";
$CHANNEL_ID = "UCOSm_4z9LIQUWHOc8yzPgkw";

/* =========================
   CURL HELPER (ROBUST)
========================= */
function getJson($url) {

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 4,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER => [
            "Cache-Control: no-cache"
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$response) {
        return null;
    }

    $json = json_decode($response, true);
    return is_array($json) ? $json : null;
}

/* =========================
   SUBSCRIBERS (ALWAYS FRESH)
   - NEVER trust local cache alone
   - cache only as fallback safety
========================= */
$subsFile = "subs.json";
$cachedSubs = file_exists($subsFile) ? (int)file_get_contents($subsFile) : 0;

/* ALWAYS fetch fresh */
$channelUrl = "https://www.googleapis.com/youtube/v3/channels?part=statistics&id={$CHANNEL_ID}&key={$API_KEY}";
$channelData = getJson($channelUrl);

$subs = $cachedSubs; // fallback

if (!empty($channelData['items'][0]['statistics']['subscriberCount'])) {

    $apiSubs = (int)$channelData['items'][0]['statistics']['subscriberCount'];

    // ONLY update if valid AND not zero AND reasonable
    if ($apiSubs > 0) {
        $subs = $apiSubs;
        file_put_contents($subsFile, $subs, LOCK_EX);
    }
}

/* =========================
   LIVE DETECTION (IMPROVED)
========================= */
$videoId = null;
$isLive = false;

/* better endpoint: videos.list via search (cleaner) */
$searchUrl = "https://www.googleapis.com/youtube/v3/search?part=id&type=video&eventType=live&channelId={$CHANNEL_ID}&maxResults=1&key={$API_KEY}";
$searchData = getJson($searchUrl);

if (!empty($searchData['items'][0]['id']['videoId'])) {
    $videoId = $searchData['items'][0]['id']['videoId'];
    $isLive = true;
}

/* =========================
   LIVE VIEWERS
========================= */
$liveViewers = 0;

if ($videoId) {

    $videoUrl = "https://www.googleapis.com/youtube/v3/videos?part=liveStreamingDetails&id={$videoId}&key={$API_KEY}";
    $videoData = getJson($videoUrl);

    if (!empty($videoData['items'][0]['liveStreamingDetails']['concurrentViewers'])) {
        $liveViewers = (int)$videoData['items'][0]['liveStreamingDetails']['concurrentViewers'];
    }
}

/* =========================
   PEAK VIEWERS (SAFE UPDATE)
========================= */
$peakFile = "peak.json";
$peak = file_exists($peakFile) ? (int)file_get_contents($peakFile) : 0;

/* only update when valid live session */
if ($liveViewers > $peak && $liveViewers > 0) {
    $peak = $liveViewers;
    file_put_contents($peakFile, $peak, LOCK_EX);
}

/* =========================
   FINAL OUTPUT
========================= */
echo json_encode([
    "subscribers" => $subs,
    "liveViewers" => $liveViewers,
    "peakViewers" => $peak,
    "videoId" => $videoId,
    "isLive" => $isLive,
    "status" => $isLive ? "LIVE" : "OFFLINE"
]);

?>
