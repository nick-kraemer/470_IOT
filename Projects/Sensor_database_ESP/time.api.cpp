// time_api.cpp
#include <Arduino.h>
#include "config.h"
#include <ESP8266HTTPClient.h>
#include <WiFiClientSecureBearSSL.h>

// Reuse wifiConnect() from net.cpp with a forward declaration:
void wifiConnect();

String read_time() {
  wifiConnect();

  std::unique_ptr<BearSSL::WiFiClientSecure> client(new BearSSL::WiFiClientSecure);
  client->setInsecure();

  HTTPClient http;
  const char* url = "https://timeapi.io/api/Time/current/zone?timeZone=America/Los_Angeles";
  if (!http.begin(*client, url)) return "";

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
