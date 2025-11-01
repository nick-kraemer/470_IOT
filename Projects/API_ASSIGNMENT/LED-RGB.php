<?php
// LED.php — control a single LED (ON/OFF) + an RGB LED via sliders.
// Stores everything in results.txt as JSON:
//
// {
//   "led": "ON"|"OFF",
//   "rgb": {"r":0-255,"g":0-255,"b":0-255},
//   "ts": 1730500000
// }

$path = __DIR__ . '/results.txt';
$req  = $_SERVER['REQUEST_METHOD'];

// -- ensure file exists with defaults
if (!file_exists($path)) {
  file_put_contents($path, json_encode(
    ["led"=>"OFF","rgb"=>["r"=>0,"g"=>0,"b"=>0],"ts"=>time()],
    JSON_UNESCAPED_SLASHES
  ));
}

// Load current state
function load_state($path){
  $j = @file_get_contents($path);
  $st = json_decode($j, true);
  if (!is_array($st)) $st = ["led"=>"OFF","rgb"=>["r"=>0,"g"=>0,"b"=>0],"ts"=>time()];
  foreach (["r","g","b"] as $k) if (!isset($st["rgb"][$k])) $st["rgb"][$k]=0;
  $st["led"] = (strtoupper($st["led"])==="ON") ? "ON":"OFF";
  return $st;
}

// Save state
function save_state($path,$st){
  $st["ts"] = time();
  file_put_contents($path, json_encode($st, JSON_UNESCAPED_SLASHES));
}

$state = load_state($path);

// ---------- API: PUT writes JSON ------------
if ($req === 'PUT') {
  $raw = file_get_contents('php://input');
  $asJson = json_decode($raw, true);

  // Also accept a plain "ON"/"OFF" body (backward compatible)
  if (!is_array($asJson)) {
    $up = strtoupper(trim($raw));
    if ($up==="ON" || $up==="OFF") $asJson = ["led"=>$up];
  }

  if (!is_array($asJson)) { http_response_code(400); exit("Invalid body"); }

  // merge incoming fields
  if (isset($asJson["led"])) {
    $state["led"] = (strtoupper($asJson["led"])==="ON") ? "ON" : "OFF";
  }
  if (isset($asJson["rgb"]) && is_array($asJson["rgb"])) {
    foreach (["r","g","b"] as $k) {
      if (isset($asJson["rgb"][$k])) {
        $v = max(0, min(255, (int)$asJson["rgb"][$k]));
        $state["rgb"][$k] = $v;
      }
    }
  }

  save_state($path,$state);
  header('Content-Type: application/json');
  echo json_encode(["ok"=>true,"state"=>$state], JSON_UNESCAPED_SLASHES);
  exit;
}

// ---------- GET: optional raw JSON ----------
if (isset($_GET['json'])) {
  header('Content-Type: application/json');
  echo json_encode($state, JSON_UNESCAPED_SLASHES);
  exit;
}

// ---------- GET: render page ----------
$led = $state["led"]; $r=(int)$state["rgb"]["r"]; $g=(int)$state["rgb"]["g"]; $b=(int)$state["rgb"]["b"];
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>IoT Course — LED & RGB Control</title>
<style>
  body{font-family: system-ui, Arial; background:#f6f7f9; margin:24px}
  .card{max-width:720px; margin:auto; background:#fff; border-radius:14px; padding:18px 20px; box-shadow:0 2px 10px rgba(0,0,0,.08)}
  h1{margin:0 0 12px}
  .row{display:flex; gap:12px; flex-wrap:wrap}
  button{padding:10px 14px; border:0; border-radius:10px; font-size:1rem; cursor:pointer}
  .on{background:#1a8f2b; color:#fff}
  .off{background:#c62828; color:#fff}
  .ghost{background:#eee}
  .grid{display:grid; grid-template-columns:160px 1fr 44px; gap:10px; align-items:center; margin-top:14px}
  input[type=range]{width:100%}
  .swatch{width:100%; height:48px; border-radius:10px; border:1px solid #ddd; margin-top:10px}
  .muted{color:#666}
  code{background:#f0f0f0; padding:1px 6px; border-radius:6px}
</style>
</head>
<body>
  <div class="card">
    <h1>IoT Course — LED & RGB</h1>

    <p><b>Single LED:</b> <span id="ledStatus"><?=htmlspecialchars($led)?></span></p>
    <div class="row">
      <button class="on"  onclick="setLED('ON')">ON</button>
      <button class="off" onclick="setLED('OFF')">OFF</button>
      <a class="ghost" href="results.txt" target="_blank" style="text-decoration:none;padding:10px 14px;border-radius:10px;color:#222">Open results.txt</a>
    </div>

    <hr style="margin:18px 0;border:none;border-top:1px solid #eee" />

    <p><b>RGB LED (0–255)</b></p>
    <div class="grid">
      <label for="r">Slider 1 (Red):</label>
      <input id="r" type="range" min="0" max="255" value="<?=$r?>" oninput="syncVals()" />
      <span id="rv"><?=$r?></span>

      <label for="g">Slider 2 (Green):</label>
      <input id="g" type="range" min="0" max="255" value="<?=$g?>" oninput="syncVals()" />
      <span id="gv"><?=$g?></span>

      <label for="b">Slider 3 (Blue):</label>
      <input id="b" type="range" min="0" max="255" value="<?=$b?>" oninput="syncVals()" />
      <span id="bv"><?=$b?></span>
    </div>

    <div class="swatch" id="swatch"></div>

    <div class="row" style="margin-top:12px">
      <button class="ghost" onclick="submitRGB()">Submit Values</button>
      <button class="ghost" onclick="reloadState()">Refresh</button>
    </div>

    <p class="muted" style="margin-top:14px">
      Stored as JSON in <code>results.txt</code>.  
      
    </p>
  </div>

<script>
  const statusEl = document.getElementById('ledStatus');
  const rs = document.getElementById('r'), gs = document.getElementById('g'), bs = document.getElementById('b');
  const rv = document.getElementById('rv'), gv = document.getElementById('gv'), bv = document.getElementById('bv');
  const sw = document.getElementById('swatch');

  function rgbCss(){ return `rgb(${rs.value},${gs.value},${bs.value})`; }
  function syncVals(){
    rv.textContent = rs.value; gv.textContent = gs.value; bv.textContent = bs.value;
    sw.style.background = rgbCss();
  }
  syncVals();

  async function setLED(state){
    await fetch(location.href, {method:'PUT', body: JSON.stringify({led: state})});
    statusEl.textContent = state;
  }

  async function submitRGB(){
    const body = { rgb: { r: +rs.value, g: +gs.value, b: +bs.value } };
    await fetch(location.href, {method:'PUT', body: JSON.stringify(body)});
  }

  async function reloadState(){
    const r = await fetch('?json=1&v='+Date.now());
    const j = await r.json();
    statusEl.textContent = j.led;
    rs.value = j.rgb.r; gs.value = j.rgb.g; bs.value = j.rgb.b;
    syncVals();
  }
</script>
</body>
</html>
