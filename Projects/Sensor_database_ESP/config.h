// config.h
#pragma once
#include <Arduino.h>

// ---- WiFi + Server ----
extern const char* WIFI_SSID;
extern const char* WIFI_PASSWORD;
extern const char* BASE_URL;  // e.g. "https://nickkaemer.com/Sensor_data2.php"

// ---- Pins (ESP8266 NodeMCU labels in comments) ----
constexpr int DHTPIN     = 4;   // GPIO4  (D2)
constexpr int DHTTYPE    = 11;  // DHT11
constexpr int BUTTON_PIN = 14;  // GPIO14 (D5) pushbutton -> GND
constexpr int TILT_PIN   = 12;  // GPIO12 (D6) 3-wire tilt (S)

// ---- Debounce ----
constexpr unsigned long DEBOUNCE_MS = 200;
