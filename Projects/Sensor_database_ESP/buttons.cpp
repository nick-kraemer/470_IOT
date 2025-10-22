// buttons.cpp
#include <Arduino.h>
#include "config.h"

// Internal state (not visible elsewhere)
static bool lastBtn  = HIGH;
static bool lastTilt = HIGH;
static unsigned long lastBtnEdge  = 0;
static unsigned long lastTiltEdge = 0;

// Public functions (will be forward-declared in main.cpp)
void buttonsBegin() {
  pinMode(BUTTON_PIN, INPUT_PULLUP); // button to GND
  pinMode(TILT_PIN,   INPUT);        // 3-wire tilt module drives the pin
  lastBtn  = digitalRead(BUTTON_PIN);
  lastTilt = digitalRead(TILT_PIN);
  lastBtnEdge  = millis();
  lastTiltEdge = millis();
}

// Return values for trigger (shared enum lives only in main.cpp)
int buttonsCheckSwitch() {
  // 0 = none, 1 = button, 2 = tilt
  int trig = 0;
  unsigned long now = millis();

  bool b = digitalRead(BUTTON_PIN);
  if (lastBtn == HIGH && b == LOW && (now - lastBtnEdge > DEBOUNCE_MS)) {
    lastBtnEdge = now;
    trig = 1;
  }
  lastBtn = b;

  bool s = digitalRead(TILT_PIN);
  if (lastTilt == HIGH && s == LOW && (now - lastTiltEdge > DEBOUNCE_MS)) {
    lastTiltEdge = now;
    if (trig == 0) trig = 2; // prefer button if both hit same cycle
  }
  lastTilt = s;

  return trig;
}
