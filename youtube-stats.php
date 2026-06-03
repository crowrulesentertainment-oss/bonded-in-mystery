<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>YouTube Live Stats Overlay</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700&display=swap" rel="stylesheet">

<style>
body {
    margin: 0;
    background: transparent;
    overflow: hidden;
    font-family: 'Orbitron', sans-serif;
}

/* MAIN BAR */
.stats-bar {
    position: absolute;
    bottom: 20px;
    left: 20px;
    display: flex;
    gap: 18px;
    padding: 12px 18px;
    border-radius: 12px;
    background: rgba(0,0,0,0.65);
    backdrop-filter: blur(8px);
    color: white;
    align-items: center;
    box-shadow: 0 0 20px rgba(0,0,0,0.4);
}

/* ITEM */
.stat {
    display: flex;
    flex-direction: column;
    text-align: left;
    min-width: 110px;
}

.label {
    font-size: 10px;
    opacity: 0.7;
    letter-spacing: 1px;
}

.value {
    font-size: 18px;
    font-weight: 700;
}

/* LIVE BADGE */
.live {
    background: red;
    color: white;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: bold;
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

/* SUBTLE ANIMATION */
.fade {
    transition: all 0.4s ease;
}
</style>
</head>

<body>

<div class="stats-bar">

    <div class="live" id="liveStatus">OFFLINE</div>

    <div class="stat">
        <div class="label">SUBSCRIBERS</div>
        <div class="value fade" id="subs">0</div>
    </div>

    <div class="stat">
        <div class="label">LIVE VIEWERS</div>
        <div class="value fade" id="viewers">0</div>
    </div>

    <div class="stat">
        <div class="label">PEAK</div>
        <div class="value fade" id="peak">0</div>
    </div>

</div>

<script>
const API_URL = "youtube-stats.php"; // your PHP endpoint

let lastData = {};

/* FORMAT NUMBER */
function format(n) {
    return n.toLocaleString();
}

/* UPDATE UI */
function updateUI(data) {

    if (!data) return;

    document.getElementById("subs").textContent = format(data.subscribers || 0);
    document.getElementById("viewers").textContent = format(data.liveViewers || 0);
    document.getElementById("peak").textContent = format(data.peakViewers || 0);

    const status = document.getElementById("liveStatus");
    status.textContent = data.isLive ? "LIVE" : "OFFLINE";
    status.style.background = data.isLive ? "red" : "gray";

    lastData = data;
}

/* FETCH DATA */
async function fetchStats() {
    try {
        const res = await fetch(API_URL + "?t=" + Date.now(), {
            cache: "no-store"
        });

        const data = await res.json();
        updateUI(data);

    } catch (err) {
        console.log("Fetch error:", err);
    }
}

/* LOOP (SAFE QUOTA FRIENDLY) */
fetchStats();
setInterval(fetchStats, 5000); // 5s refresh (safe for quota)
</script>

</body>
</html>
