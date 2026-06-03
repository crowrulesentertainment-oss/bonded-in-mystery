<?php
header("Content-Type: application/json");

$API_KEY = "AIzaSyArQ6fqOxl8jenWZME2z1qwhd8qBNPD0KI";
$CHANNEL_ID = "UCOSm_4z9LIQUWHOc8yzPgkw";

/* DEFAULT OUTPUT (ALWAYS SAFE) */
$output = [
    "viewers" => 0,
    "subs" => 0,
    "goal" => 10000,
    "status" => "offline"
];

/* =========================
   1. GET SUBSCRIBER COUNT (CHANNEL)
========================= */
$channelUrl = "https://www.googleapis.com/youtube/v3/channels?part=statistics&id=$CHANNEL_ID&key=$API_KEY";

$channelResponse = @file_get_contents($channelUrl);

if ($channelResponse !== false) {
    $channelData = json_decode($channelResponse, true);

    if (!empty($channelData["items"][0]["statistics"])) {
        $stats = $channelData["items"][0]["statistics"];

        $output["subs"] = isset($stats["subscriberCount"])
            ? (int)$stats["subscriberCount"]
            : 0;
    }
}

/* =========================
   2. TRY TO FIND LIVE VIDEO (OPTIONAL)
========================= */
$searchUrl = "https://www.googleapis.com/youtube/v3/search?part=snippet&channelId=$CHANNEL_ID&type=video&eventType=live&key=$API_KEY";

$searchResponse = @file_get_contents($searchUrl);

if ($searchResponse !== false) {
    $searchData = json_decode($searchResponse, true);

    if (!empty($searchData["items"][0]["id"]["videoId"])) {

        $videoId = $searchData["items"][0]["id"]["videoId"];

        /* GET LIVE VIEWERS */
        $videoUrl = "https://www.googleapis.com/youtube/v3/videos?part=liveStreamingDetails&id=$videoId&key=$API_KEY";

        $videoResponse = @file_get_contents($videoUrl);

        if ($videoResponse !== false) {
            $videoData = json_decode($videoResponse, true);

            if (!empty($videoData["items"][0]["liveStreamingDetails"]["concurrentViewers"])) {
                $output["viewers"] =
                    (int)$videoData["items"][0]["liveStreamingDetails"]["concurrentViewers"];

                $output["status"] = "live";
            }
        }
    }
}

/* =========================
   3. ALWAYS INCLUDE GOAL
========================= */
$output["goal"] = 10000;

/* OUTPUT JSON */
echo json_encode($output);
?>
