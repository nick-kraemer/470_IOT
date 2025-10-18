#include <Arduino.h>
#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <WiFiClientSecureBearSSL.h>
#include <DHT.h>

// ---------- WiFi Settings ----------
const char* WIFI_SSID     = "my wifi";
const char* WIFI_PASSWORD = "my wifi password";

// ---------- PHP Endpoint ----------
const char* BASE_URL = "https://nickkaemer.com/Sensor_data2.php";

// ---------- Pins ----------
#define DHTPIN      4       // GPIO4 (D2)
#define DHTTYPE     DHT11
#define BUTTON_PIN  14      // GPIO14 (D5)
#define TILT_PIN    12      // GPIO12

DHT dht(DHTPIN, DHTTYPE);

// ---------- Debounce ----------
bool lastBtnState  = HIGH;
bool lastTiltState = HIGH;
unsigned long lastBtnTime  = 0;
unsigned long lastTiltTime = 0;
const unsigned long DEBOUNCE_MS = 200;

// ---------- Connect to WiFi ----------
void connectWiFi() {
  if (WiFi.status() == WL_CONNECTED) return;
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  Serial.printf("WiFi connecting to %s", WIFI_SSID);
  while (WiFi.status() != WL_CONNECTED) {
    delay(400);
    Serial.print(".");
  }
  Serial.printf("\nWiFi connected, IP=%s\n", WiFi.localIP().toString().c_str());
}

// ---------- URL Encoder ----------
String urlEncode(const String& s) {
  String out;
  const char HEXCHARS[] = "0123456789ABCDEF";   // renamed to avoid conflict
  for (uint16_t i = 0; i < s.length(); i++) {
    char c = s[i];
    if (isalnum(c) || c == '-' || c == '_' || c == '.' || c == '~') {
      out += c;
    } else if (c == ' ') {
      out += "%20";
    } else {
      out += '%';
      out += HEXCHARS[(c >> 4) & 0x0F];   // upper nibble
      out += HEXCHARS[c & 0x0F];          // lower nibble
    }
  }
  return out;
}

// ---------- Send Data ----------
void sendReading(const char* nodeId) {
  float t = dht.readTemperature();
  float h = dht.readHumidity();

  if (isnan(t) || isnan(h)) {
    Serial.printf("[%s] ❌ DHT read failed\n", nodeId);
    return;
  }

  // Hardcoded time (adjust as needed)
  String timeReceived = "2025-10-18 15:31:00";

  // Build full GET URL
  String url = String(BASE_URL) +
               "?nodeId=" + urlEncode(nodeId) +
               "&nodeTemp=" + String(t, 1) +
               "&humidity=" + String(h, 1) +
               "&timeReceived=" + urlEncode(timeReceived);

  Serial.println("Sending -> " + url);

  connectWiFi();
  std::unique_ptr<BearSSL::WiFiClientSecure> client(new BearSSL::WiFiClientSecure);
  client->setInsecure();  // skip HTTPS certificate check for testing

  HTTPClient http;
  if (!http.begin(*client, url)) {
    Serial.println("HTTP begin failed");
    return;
  }

  int code = http.GET();
  Serial.printf("<- HTTP %d\n", code);
  String resp = http.getString();
  Serial.println(resp);
  http.end();
}

// ---------- Setup ----------
void setup() {
  Serial.begin(9600);
  Serial.println("\nBooting...");
  dht.begin();

  pinMode(BUTTON_PIN, INPUT_PULLUP);
  pinMode(TILT_PIN, INPUT);  // 3-wire tilt module (S pin)

  delay(2000);
  connectWiFi();
  Serial.println("Ready! Press button (node_1) or tilt (node_2).");
}

// ---------- Loop ----------
void loop() {
  unsigned long now = millis();

  // Button → Node 1
  bool btnState = digitalRead(BUTTON_PIN);
  if (lastBtnState == HIGH && btnState == LOW && (now - lastBtnTime > DEBOUNCE_MS)) {
    lastBtnTime = now;
    sendReading("node_1");
  }
  lastBtnState = btnState;

  // Tilt → Node 2
  bool tiltState = digitalRead(TILT_PIN);
  if (lastTiltState == HIGH && tiltState == LOW && (now - lastTiltTime > DEBOUNCE_MS)) {
    lastTiltTime = now;
    sendReading("node_2");
  }
  lastTiltState = tiltState;

  delay(5);
}
