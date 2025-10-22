// main.cpp
// Title:ESP-Database
//=======================

//Program Detail: This code takes data from Sensors connected to the ESP and sends 
//the data to my database
//
//=======================
// Purpose: To 
// Inputs: button on pin D5, tiltswtich on pin D6, 
// Outputs:  temp and humidity from senseor to database
// Date: 10/21/2025
// Compiler: PlatformIO (Arduino framework)
// Author: Nick Kraemer
// Versions: V1
//       
//        
//
//=======================
// File Dependencies:
#include <Arduino.h>
#include "config.h"

// Forward declarations for functions implemented in .cpp modules
void buttonsBegin();
int  buttonsCheckSwitch();              // returns 0=none, 1=button, 2=tilt
void sensorsBegin();
bool read_sensor_1(float& t, float& h);
bool read_sensor_2(float& t, float& h);
void wifiConnect();
String urlEncode(const String& s);
bool transmit(const char* nodeId, float t, float h, const String& ts);
String read_time();

void setup() {
  Serial.begin(9600);
  Serial.println("\nBooting...");
  buttonsBegin();
  sensorsBegin();
  wifiConnect(); // optional early connect
  Serial.println("Ready.");
}

void loop() {
  int trig = buttonsCheckSwitch();
  if (trig != 0) {
    float t, h;
    const char* node = (trig == 1) ? "node_1" : "node_2";

    bool ok = (trig == 1) ? read_sensor_1(t, h) : read_sensor_2(t, h);
    if (!ok) {
      Serial.println("❌ DHT read failed.");
    } else {
      String ts = read_time();
      if (ts == "") {
        Serial.println("❌ No time; not sending.");
      } else {
        transmit(node, t, h, ts);
      }
    }
  }
  delay(5);
}
