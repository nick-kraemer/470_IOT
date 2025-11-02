<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Sensor Dashboard</title>
  <style>
    .wrap{max-width:1200px;margin:40px auto;font-family:system-ui}
    .grid{display:grid;gap:20px;grid-template-columns:repeat(auto-fit,minmax(360px,1fr))}
    .card{background:#fff;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,.08);padding:8px}
    .card iframe{width:100%;height:420px;border:0;border-radius:8px}
  </style>
</head>
<body>
  <div class="wrap">
    <h1>Sensor Dashboard</h1>
    <div class="grid">
      <div class="card">
        <!-- Bar chart -->
        <iframe width="600" height="371" seamless frameborder="0" scrolling="no" src="https://docs.google.com/spreadsheets/d/e/2PACX-1vSDhUs5kRVT95rUAAUlIy4sC2HPOA90t8lcor_W-WUl9C2JkX3Mi5LqI7rndE5-7eAfee8kssiqTNh2/pubchart?oid=375581656&amp;format=interactive"></iframe>
      </div>
      <div class="card">
        <!-- Gauge (or another chart) -->
        <iframe width="525" height="324" seamless frameborder="0" scrolling="no" src="https://docs.google.com/spreadsheets/d/e/2PACX-1vSDhUs5kRVT95rUAAUlIy4sC2HPOA90t8lcor_W-WUl9C2JkX3Mi5LqI7rndE5-7eAfee8kssiqTNh2/pubchart?oid=2044126037&amp;format=interactive"></iframe>
      </div>
    </div>
  </div>
</body>
</html>
