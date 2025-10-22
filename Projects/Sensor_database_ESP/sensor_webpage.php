<?php
/*  SSU IoT Lab –   */

$servername = "localhost";
$username   = "u897319688_db_NickKraemer";
$password   = "Nickkraemer10";
$dbname     = "u897319688_NickKraemer";

/* ADDED: turn mysqli errors into exceptions so we can catch/rollback cleanly */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

/* ADDED: a small notice string to show success/failure without killing the page */
$notice = "";

/* ---------- 0. Optional BASE64-encoded message ---------- */
if (isset($_GET['msg'])) {
    $decoded = base64_decode($_GET['msg'], true);
    if ($decoded !== false) {
        parse_str($decoded, $p);
        if (!empty($p['nodeId']))         $_GET['nodeId'] = $p['nodeId'];
        if (!empty($p['nodeTemp']))       $_GET['nodeTemp'] = $p['nodeTemp'];
        if (isset($p['humidity']))        $_GET['humidity'] = $p['humidity'];
        if (isset($p['timeReceived']))    $_GET['timeReceived'] = $p['timeReceived'];
    }
}

/* ---------- 1. Handle incoming data (optional insert) ---------- */
if (isset($_GET['nodeId']) && isset($_GET['nodeTemp'])) {
    $varId   = $_GET['nodeId'];
    $varTemp = $_GET['nodeTemp'];
    $varHum  = $_GET['humidity']     ?? null;
    $varTime = $_GET['timeReceived'] ?? null;

    try {
        $conn->begin_transaction();

        if ($varHum === null || $varHum === '') {
            $sql  = "INSERT INTO sensor_data (node_name, temperature, time_received)
                     VALUES (?, ?, COALESCE(?, NOW()))";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sds", $varId, $varTemp, $varTime);
        } else {
            $sql  = "INSERT INTO sensor_data (node_name, temperature, humidity, time_received)
                     VALUES (?, ?, ?, COALESCE(?, NOW()))";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sdds", $varId, $varTemp, $varHum, $varTime);
        }

        $stmt->execute();
        $conn->commit();
        $notice = "✅ New record added for {$varId}";
        $stmt->close();

    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        $code = $e->getCode();
        if ($code == 1062) {
            $notice = "⚠️ Duplicate ignored: same node & time already exist.";
        } elseif ($code == 1452) {
            $notice = "❌ Rejected: nodeId '{$varId}' is not registered.";
        } elseif ($code == 3819) {
            $notice = "❌ Rejected: value violates range constraints.";
        } else {
            $notice = "❌ Insert error #{$code}: " . $e->getMessage();
        }
    }
}

/* ---------- 2. Query tables for display ---------- */
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

$nodes = [];
$res1 = $conn->query("SELECT node_name, manufacturer, longitude, latitude
                      FROM sensor_register ORDER BY node_name ASC");
if ($res1) while ($r=$res1->fetch_assoc()) $nodes[]=$r;

$data = [];
$res2 = $conn->query("SELECT node_name, time_received, temperature, humidity
                      FROM sensor_data
                      ORDER BY node_name ASC, time_received ASC");
if ($res2) while ($r=$res2->fetch_assoc()) $data[]=$r;

/* NEW: counts for EVERY registered node (include zeros) */
$node_counts = [];
$res3 = $conn->query("
    SELECT r.node_name, COUNT(d.node_name) AS cnt
    FROM sensor_register r
    LEFT JOIN sensor_data d ON d.node_name = r.node_name
    GROUP BY r.node_name
    ORDER BY r.node_name ASC
");
if ($res3) while ($r=$res3->fetch_assoc()) $node_counts[]=$r;

$conn->close();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>SSU IoT Lab</title>
<style>
body{font-family:system-ui,Arial,sans-serif;margin:24px;background:#fafafa;color:#222}
h1{text-align:center;margin:0 0 12px}
.banner{max-width:1000px;margin:8px auto 0;padding:10px 12px;border-radius:10px}
.ok{background:#e6ffed;border:1px solid #b7ebc6}
.warn{background:#fff7e6;border:1px solid #ffe58f}
.err{background:#fff1f0;border:1px solid #ffa39e}
.card{background:#fff;border:1px solid #e6e6e6;border-radius:12px;padding:12px;margin:16px auto;max-width:1000px}
table{width:100%;border-collapse:collapse;margin:8px 0}
th,td{padding:8px;border-bottom:1px solid #eee;text-align:left}
thead th{border-bottom:2px solid #ccc;background:#f2f2f2}
.muted{color:#666;font-size:0.9em}
</style>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<h1>Welcome to SSU IoT Lab</h1>

<?php if ($notice):
  $cls = (str_starts_with($notice,'✅') ? 'ok' : (str_starts_with($notice,'⚠️') ? 'warn' : 'err')); ?>
  <div class="banner <?=$cls?>"><?=h($notice)?></div>
<?php endif; ?>

<div class="card">
<h3>Registered Sensor Nodes</h3>
<table>
<thead><tr><th>Name</th><th>Manufacturer</th><th>Longitude</th><th>Latitude</th></tr></thead>
<tbody>
<?php foreach($nodes as $n): ?>
<tr>
<td><?=h($n['node_name'])?></td>
<td><?=h($n['manufacturer'])?></td>
<td><?=h($n['longitude'])?></td>
<td><?=h($n['latitude'])?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<div class="muted">Total nodes: <?=count($nodes)?></div>
</div>

<!-- NEW: Counts table for ALL nodes -->
<div class="card">
  <h3>Node Counts</h3>
  <table>
    <thead><tr><th>Node</th><th>Count</th></tr></thead>
    <tbody>
      <?php foreach ($node_counts as $c): ?>
        <tr>
          <td><?=h($c['node_name'])?></td>
          <td><?=h($c['cnt'])?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <div class="muted">Updated when the page loads</div>
</div>

<div class="card">
  <h3>
    <span id="chartTitle">Sensor Node 1 — Temperature vs Time</span>
  </h3>

  <!-- Node selector -->
  <label for="nodeSelect" class="muted">Select node:</label>
  <select id="nodeSelect">
    <?php
      $defaultNode = 'node_1';
      $haveDefault = false;
      foreach ($nodes as $n) { if ($n['node_name'] === $defaultNode) { $haveDefault = true; break; } }
      $selDefault = $haveDefault ? $defaultNode : (isset($nodes[0]['node_name']) ? $nodes[0]['node_name'] : 'node_1');
      foreach ($nodes as $n):
        $name = $n['node_name'];
        $sel  = ($name === $selDefault) ? 'selected' : '';
    ?>
      <option value="<?=h($name)?>" <?=$sel?>><?=h($name)?></option>
    <?php endforeach; ?>
  </select>

  <canvas id="chart1" style="margin-top:12px;"></canvas>
</div>

<!-- SIMPLE SUMMARY LINES (count line REMOVED) -->
<p style="margin:20px 0;font-weight:bold;">
  The Average Temperature for <span id="avgNodeLabel">node_1</span> has been:
  <span id="avgTemp">—</span> °C<br>
  The Average Humidity for <span id="avgNodeLabel2">node_1</span> has been:
  <span id="avgHum">—</span> %
</p>

<div class="card">
<h3>Data Received (sorted by Node, then Time)</h3>
<table>
<thead><tr><th>Node</th><th>Time</th><th>Temperature (°C)</th><th>Humidity (%)</th></tr></thead>
<tbody>
<?php foreach($data as $d): ?>
<tr>
<td><?=h($d['node_name'])?></td>
<td><?=h($d['time_received'])?></td>
<td><?=h($d['temperature'])?></td>
<td><?=h($d['humidity'])?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<div class="muted">Rows: <?=count($data)?></div>
</div>

<script>
const dataFromPHP = <?php echo json_encode($data); ?>;

function getSeriesForNode(nodeName) {
  const times = [];
  const temps = [];
  const hums  = [];
  for (let r of dataFromPHP) {
    if (r.node_name === nodeName) {
      times.push(r.time_received);
      temps.push(r.temperature !== null ? parseFloat(r.temperature) : null);
      hums.push(r.humidity   !== null ? parseFloat(r.humidity)   : null);
    }
  }
  return { times, temps, hums };
}

function avg(arr) {
  const nums = arr.map(Number).filter(v => !isNaN(v));
  if (!nums.length) return null;
  const sum = nums.reduce((a,b)=>a+b,0);
  return Math.round((sum/nums.length)*100)/100;
}

const nodeSelect   = document.getElementById('nodeSelect');
const chartTitleEl = document.getElementById('chartTitle');
const avgNodeLabel = document.getElementById('avgNodeLabel');
const avgNodeLabel2= document.getElementById('avgNodeLabel2');
const avgTempEl    = document.getElementById('avgTemp');
const avgHumEl     = document.getElementById('avgHum');

function updateSummary(node, temps, hums) {
  avgNodeLabel.textContent  = node;
  avgNodeLabel2.textContent = node;
  const t = avg(temps);
  const h = avg(hums);
  avgTempEl.textContent = (t !== null) ? t.toString() : "—";
  avgHumEl.textContent  = (h !== null) ? h.toString() : "—";
}

let chartInstance = null;
function renderChart(nodeName) {
 const { times, temps, hums } = getSeriesForNode(nodeName);


  chartTitleEl.textContent = `${nodeName} — Temperature vs Time`;
  updateSummary(nodeName, temps, hums);

  const ctx = document.getElementById('chart1');
  const config = {
    type: 'bar',
    data: {
      labels: times,
      datasets: [{
        label: 'Temperature (°C)',
        data: temps,
        backgroundColor: 'rgba(70,200,192,0.6)'
      }]
    },
    options: {
      scales: {
        x: { title: { display: true, text: 'Time' }},
        y: { title: { display: true, text: 'Temperature (°C)' }}
      }
    }
  };

  if (chartInstance) {
    chartInstance.data.labels = times;
    chartInstance.data.datasets[0].data = temps;
    chartInstance.update();
  } else {
    chartInstance = new Chart(ctx, config);
  }
}

const initialNode = nodeSelect.value || 'node_1';
document.getElementById('avgNodeLabel').textContent  = initialNode;
document.getElementById('avgNodeLabel2').textContent = initialNode;
renderChart(initialNode);

nodeSelect.addEventListener('change', function() {
  renderChart(this.value);
});
</script>

</body>
</html>
