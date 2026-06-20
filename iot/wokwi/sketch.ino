#include <WiFi.h>
#include <PubSubClient.h>
#include <ArduinoJson.h>

const char* WIFI_SSID = "Wokwi-GUEST";
const char* MQTT_BROKER = "broker.hivemq.com";
const int   MQTT_PORT = 1883;
const char* ZONE_ID = "zone5";
const char* BIN_ID = "BIN-Z5-01";

const float BIN_LATITUDE = -6.1200;  // sesuaikan dengan location_coordinate bin tujuan
const float BIN_LONGITUDE = 106.8100;

#define TRIG_PIN 12
#define ECHO_PIN 14
#define GAS_PIN 34
#define LED_PIN 2

const float MAX_SENSOR_RANGE_CM = 150.0;
float calibratedHeight = 0;

const float FILL_WARNING = 70.0;
const float FILL_CRITICAL = 90.0;
const int GAS_WARNING = 400;
const int GAS_CRITICAL = 800;

const int PUBLISH_INTERVAL = 2000;

WiFiClient wifiClient;
PubSubClient mqtt(wifiClient);
unsigned long lastPublish = 0;

void setup() {
  Serial.begin(115200);
  delay(1000);
  Serial.println("========================================");
  Serial.println("  TrashTrack Smart Bin v1.0");
  Serial.println("  Zona: " + String(ZONE_ID));
  Serial.println("  Interval: " + String(PUBLISH_INTERVAL) + "ms");
  Serial.println("========================================\n");

  pinMode(TRIG_PIN, OUTPUT);
  pinMode(ECHO_PIN, INPUT);
  pinMode(LED_PIN, OUTPUT);
  digitalWrite(LED_PIN, LOW);

  Serial.print("[WiFi] Connecting");
  WiFi.begin(WIFI_SSID, "");
  int tries = 0;
  while (WiFi.status() != WL_CONNECTED && tries < 20) {
    Serial.print(".");
    delay(500);
    tries++;
  }
  if (WiFi.status() == WL_CONNECTED) {
    Serial.println(" OK");
    Serial.println("[WiFi] IP: " + WiFi.localIP().toString());
  } else {
    Serial.println(" GAGAL — lanjut offline");
  }

  mqtt.setServer(MQTT_BROKER, MQTT_PORT);
  mqtt.setCallback(onMessage);
  Serial.println("[MQTT] Server: " + String(MQTT_BROKER));

  runCalibration(false);

  Serial.println("[Setup] Selesai!\n");

  lastPublish = 0;
}

void loop() {
  if (!mqtt.connected()) reconnectMQTT();
  mqtt.loop();

  if (millis() - lastPublish >= PUBLISH_INTERVAL) {
    lastPublish = millis();
    publishData();
  }
}

void runCalibration(bool isRecalibration) {
  float total = 0;
  for (int i = 0; i < 5; i++) {
    total += readUltrasonic();
    delay(200);
  }
  calibratedHeight = total / 5.0;

  if (calibratedHeight <= 0) calibratedHeight = 100.0;

  if (isRecalibration) {
    Serial.printf("[Re-Calibration] Tinggi baru: %.1f cm\n", calibratedHeight);
  } else {
    Serial.printf("[Calibration] Tinggi tong: %.1f cm\n", calibratedHeight);
  }
}

void publishData() {
  float dist = readUltrasonic();
  float fill = constrain(((calibratedHeight - dist) / calibratedHeight) * 100.0, 0, 100);
  int raw = analogRead(GAS_PIN);
  int gas = map(raw, 0, 4095, 0, 1500);

  String status = "normal";
  if (fill > FILL_CRITICAL || gas > GAS_CRITICAL) status = "critical";
  else if (fill > FILL_WARNING || gas > GAS_WARNING) status = "warning";

  digitalWrite(LED_PIN, status == "critical" ? HIGH : LOW);

  StaticJsonDocument<384> doc;
  doc["zone"] = ZONE_ID;
  doc["bin_id"] = BIN_ID;
  doc["fill_level"] = (float)((int)(fill * 10)) / 10.0;
  doc["gas_level"] = gas;
  doc["temperature"] = 32.0;
  doc["calibrated_height"] = calibratedHeight;     
  doc["is_calibration"] = (millis() < 5000);   
  doc["latitude"] = BIN_LATITUDE;
  doc["longitude"] = BIN_LONGITUDE;
  doc["status"] = status;
  doc["timestamp"] = millis();
  char payload[384];
  serializeJson(doc, payload);

  String topic = "city/" + String(ZONE_ID) + "/waste";
  bool ok = mqtt.publish(topic.c_str(), payload);

  Serial.printf("[%lu ms] %s | fill=%.1f%% gas=%d %s | tinggi=%.1fcm | %s\n",
    millis(), topic.c_str(), fill, gas, status.c_str(), calibratedHeight, ok ? "OK✓" : "GAGAL✗");
}

float readUltrasonic() {
  digitalWrite(TRIG_PIN, LOW);  delayMicroseconds(2);
  digitalWrite(TRIG_PIN, HIGH); delayMicroseconds(10);
  digitalWrite(TRIG_PIN, LOW);
  long dur = pulseIn(ECHO_PIN, HIGH, 30000);
  if (dur == 0) return MAX_SENSOR_RANGE_CM;
  return constrain(dur / 58.0, 2.0, MAX_SENSOR_RANGE_CM);
}

void reconnectMQTT() {
  if (WiFi.status() != WL_CONNECTED) return;
  Serial.print("[MQTT] Connecting...");
  String cid = "trashtrack-" + String(ZONE_ID) + "-" + String(random(0xffff), HEX);
  if (mqtt.connect(cid.c_str())) {
    Serial.println(" OK ✓");
    String cmdTopic = "city/" + String(ZONE_ID) + "/command";
    mqtt.subscribe(cmdTopic.c_str());
    Serial.println("[MQTT] Subscribe: " + cmdTopic);
  } else {
    Serial.printf(" GAGAL (rc=%d)\n", mqtt.state());
  }
}

void onMessage(char* topic, byte* payload, unsigned int length) {
  StaticJsonDocument<128> doc;
  DeserializationError err = deserializeJson(doc, payload, length);
  if (err) {
    Serial.println("[Command] Payload bukan JSON valid, diabaikan.");
    return;
  }

  String action = doc["action"] | "";
  if (action == "recalibrate") {
    Serial.println("[Command] Menerima 'recalibrate' dari topic " + String(topic));
    runCalibration(true);
  }
}