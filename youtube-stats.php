<?php

header("Content-Type: application/json");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

$API_KEY = "AIzaSyBAK7ltFPFDX-k0FjBMnKzf0pq_WSJGhGY";
$CHANNEL_ID = "UCOSm_4z9LIQUWHOc8yzPgkw";

/* -------------------------
   FAST CURL REQUEST
--------------------------*/
function getJson($url) {

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 3,
        CURLOPT_CONNECTTIMEOUT => 1,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER => [
            "Cache-Control: no-cache"
        ]
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    if (!$response) return [];

    $json = json_decode($response, true);
    return is_array($json) ? $json : [];
}

/* -------------------------
   SUBSCRIBERS (STABLE CACHE)
--------------------------*/
$subsFile = "subs.json";
$subs = file_exists($subsFile) ? (int)file_get_contents($subsFile) : 0;

$channelUrl = "https://www.googleapis.com/youtube/v3/channels?part=statistics&id={$CHANNEL_ID}&key={$API_KEY}";
$channelData = getJson($channelUrl);

if (!empty($channelData['items'][0]['statistics']['subscriberCount'])) {

    $newSubs = (int)$channelData['items'][0]['statistics']['subscriberCount'];

    if ($newSubs > 0) {
        $subs = $newSubs;
        file_put_contents($subsFile, $subs);
    }
}

/* -------------------------
   LIVE VIDEO DETECTION (FIXED)
--------------------------*/
$videoId = null;

$searchUrl = "https://www.googleapis.com/youtube/v3/search?part=id,snippet&channelId={$CHANNEL_ID}&eventType=live&type=video&order=date&maxResults=5&key={$API_KEY}";
$searchData = getJson($searchUrl);

/* find ACTIVE live broadcast */
if (!empty($searchData['items'])) {
    foreach ($searchData['items'] as $item) {

        if (!empty($item['id']['videoId'])) {

            $vid = $item['id']['videoId'];

            /* confirm it's actually live */
            if (
                isset($item['snippet']['liveBroadcastContent']) &&
                $item['snippet']['liveBroadcastContent'] === "live"
            ) {
                $videoId = $vid;
                break;
            }

            /* fallback candidate */
            if (!$videoId) {
                $videoId = $vid;
            }
        }
    }
}

/* -------------------------
   LIVE VIEWERS FETCH (SAFE)
--------------------------*/
$liveViewers = 0;

if ($videoId) {

    $videoUrl = "https://www.googleapis.com/youtube/v3/videos?part=liveStreamingDetails&id={$videoId}&key={$API_KEY}";
    $videoData = getJson($videoUrl);

    if (!empty($videoData['items'][0]['liveStreamingDetails']['concurrentViewers'])) {

        $liveViewers = (int)$videoData['items'][0]['liveStreamingDetails']['concurrentViewers'];
    }
}

/* -------------------------
   PEAK VIEWERS (NO RESET BUG)
--------------------------*/
$peakFile = "peak.json";
$peak = file_exists($peakFile) ? (int)file_get_contents($peakFile) : 0;

/* only update when valid + higher */
if ($liveViewers > $peak && $liveViewers > 0) {
    $peak = $liveViewers;
    file_put_contents($peakFile, $peak);
}

/* -------------------------
   FINAL OUTPUT
--------------------------*/
echo json_encode([
    "subscribers" => $subs,
    "liveViewers" => $liveViewers,
    "peakViewers" => $peak,
    "videoId" => $videoId
]);