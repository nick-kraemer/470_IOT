#include <Arduino.h>
#include "config.h"
#include <ESP8266HTTPClient.h>
#include <WiFiClientSecureBearSSL.h>

// From net.cpp (reused Wi-Fi connect)
void wifiConnect();

/* ---------- Time Zone Picker (via USB/Serial) ---------- */

// 1..7 per Appendix A, mapped to IANA tz names
static const char* TZ_MENU[] = {
  /*1*/ "America/New_York",      // ET
  /*2*/ "America/Chicago",       // CT
  /*3*/ "America/Denver",        // MT
  /*4*/ "America/Los_Angeles",   // PT (default)
  /*5*/ "America/Anchorage",     // AKT
  /*6*/ "Pacific/Honolulu",      // HAT
  /*7*/ "America/Puerto_Rico"    // AT
};
static const char* TZ_LABEL[] = {
  "Eastern Time (ET) – New York, NY",
  "Central Time (CT) – Chicago, IL",
  "Mountain Time (MT) – Denver, CO",
  "Pacific Time (PT) – Los Angeles, CA",
  "Alaska Time (AKT) – Anchorage, AK",
  "Hawaii–Aleutian (HAT) – Honolulu, HI",
  "Atlantic Time (AT) – San Juan, PR"
};

// Default = option 4 (PT)
static int g_tz_index = 4;  // 1..7



// Show the menu and allow user to pick 1–7. Times out to default PT.
void promptTimeZoneViaSerial(unsigned long wait_ms = 15000) {
  // tzLoadFromEEPROM();  // optional persistence

  Serial.println();
  Serial.println(F("Select Your Time Zone (default is 4 = PT):"));
  for (int i = 1; i <= 7; ++i) {
    Serial.printf("  %d) %s\n", i, TZ_LABEL[i-1]);
  }
  Serial.print(F("--> Enter 1..7 (waiting "));
  Serial.print(wait_ms/1000);
  Serial.println(F("s):"));

  unsigned long t0 = millis();
  String line;
  while (millis() - t0 < wait_ms) {
    if (Serial.available()) {
      char c = Serial.read();
      if (c == '\r' || c == '\n') {
        // parse if something typed
        if (line.length()) break;
      } else if (isDigit(c)) {
        line += c;
      }
    }
  }

  if (line.length()) {
    int v = line.toInt();
    if (v >= 1 && v <= 7) {
      g_tz_index = v;
      Serial.printf("Time zone set to [%d] %s (%s)\n",
                    g_tz_index, TZ_LABEL[g_tz_index-1], TZ_MENU[g_tz_index-1]);
      // tzSaveToEEPROM();  // optional persistence
      return;
    }
  }
  // fallback: keep default/current
  Serial.printf("No valid input. Using default [%d] %s (%s)\n",
                g_tz_index, TZ_LABEL[g_tz_index-1], TZ_MENU[g_tz_index-1]);
}

/* ---------- Time API using selected zone ---------- */

String read_time() {
  wifiConnect();

  std::unique_ptr<BearSSL::WiFiClientSecure> client(new BearSSL::WiFiClientSecure);
  client->setInsecure();
  client->setTimeout(20000);

  HTTPClient http;
  http.setTimeout(20000);
  

  // Build URL with the selected IANA zone
  String url = "https://timeapi.io/api/Time/current/zone?timeZone=";
  url += TZ_MENU[g_tz_index - 1];

  if (!http.begin(*client, url)) {
    Serial.println("timeapi: begin() failed");
    return "";
  }

  int code = http.GET();
  if (code != HTTP_CODE_OK) { http.end(); return ""; }

  String body = http.getString();
  http.end();

  // Extract "dateTime":"YYYY-MM-DDTHH:MM:SS(.fraction)"
  int i = body.indexOf("\"dateTime\":\"");
  if (i < 0) return "";
  i += 12;
  int j = body.indexOf('"', i);
  if (j < 0) return "";

  String dt = body.substring(i, j);
  dt.replace('T', ' ');
  if (dt.length() > 19) dt = dt.substring(0, 19);
  return dt; // "YYYY-MM-DD HH:MM:SS"
}
