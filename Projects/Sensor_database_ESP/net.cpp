// net.cpp
#include <Arduino.h>
#include "config.h"
#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <WiFiClientSecureBearSSL.h>

void wifiConnect() {
  if (WiFi.status() == WL_CONNECTED) return;
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  Serial.printf("WiFi connecting to %s", WIFI_SSID);
  while (WiFi.status() != WL_CONNECTED) { delay(400); Serial.print("."); }
  Serial.printf("\nWiFi connected, IP=%s\n", WiFi.localIP().toString().c_str());
}

String urlEncode(const String& s) {
  String out;
  const char HEXCHARS[] = "0123456789ABCDEF";
  for (uint16_t i = 0; i < s.length(); i++) {
    unsigned char c = s[i];
    if (isalnum(c) || c=='-' || c=='_' || c=='.' || c=='~') out += char(c);
    else if (c == ' ') out += "%20";
    else { out += '%'; out += HEXCHARS[(c>>4)&0x0F]; out += HEXCHARS[c&0x0F]; }
  }
  return out;
}

bool transmit(const char* nodeId, float t, float h, const String& ts) {
  wifiConnect();

  String url = String(BASE_URL) +
               "?nodeId="       + urlEncode(nodeId) +
               "&nodeTemp="     + String(t, 1) +
               "&humidity="     + String(h, 1) +
               "&timeReceived=" + urlEncode(ts);

  Serial.println("GET -> " + url);

  std::unique_ptr<BearSSL::WiFiClientSecure> client(new BearSSL::WiFiClientSecure);
  client->setInsecure(); // testing only

  HTTPClient http;
  if (!http.begin(*client, url)) { Serial.println("HTTP begin failed"); return false; }

  int code = http.GET();
  String resp = http.getString();
  http.end();

  Serial.printf("<- HTTP %d\n", code);
  Serial.println(resp);

  return (code == HTTP_CODE_OK);
}
