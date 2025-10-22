// sensors.cpp
#include <Arduino.h>
#include "config.h"
#include <DHT.h>

static DHT dht(DHTPIN, DHTTYPE);

void sensorsBegin() {
  dht.begin();
  delay(2000); // allow DHT to stabilize
}

// Both nodes read same DHT11 (kept separate for assignment style)
bool read_sensor_1(float& t, float& h) {
  t = dht.readTemperature();
  h = dht.readHumidity();
  return !(isnan(t) || isnan(h));
}

bool read_sensor_2(float& t, float& h) {
  t = dht.readTemperature();
  h = dht.readHumidity();
  return !(isnan(t) || isnan(h));
}
