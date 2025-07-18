#include <WiFi.h>
#include <esp_now.h>
#include <Fuzzy.h>

// ============= DEFINISI PIN =============
// Relay untuk Pompa Pengkabutan
#define RELAY_PIN 27

// ============= STRUKTUR DATA ESP-NOW =============
typedef struct {
  float temperature;
  float lux;
  bool dataValid;
} SensorData;

typedef struct {
  uint8_t sensorId;      // ID sensor (1, 2, 3, atau 4)
  float moisturePercent; // Persentase kelembaban
  int rawValue;          // Raw value dari ADC
  bool dataValid;        // Status validitas data
  unsigned long timestamp; // Timestamp pengiriman
} SoilMoistureData;

// ============= VARIABEL SENSOR =============
SensorData receivedData;
float currentTemperature = 25.0;  // Default value
float currentLux = 100.0;         // Default value
bool sensorDataReceived = false;

// Data sensor tanah dari ESP32C3
SoilMoistureData soilSensors[4];    // Array untuk menyimpan data dari 4 sensor tanah
float moistureValues[4] = {0.0, 0.0, 0.0, 0.0}; // Array kelembapan dalam persen
bool soilDataReceived[4] = {false, false, false, false}; // Status data diterima
unsigned long lastSoilDataTime[4] = {0, 0, 0, 0}; // Waktu terakhir data diterima

// ============= VARIABEL KONTROL WAKTU =============
unsigned long previousMillis = 0;        // Menyimpan waktu terakhir pembacaan sensor
unsigned long pumpStartTime = 0;         // Waktu mulai pompa aktif
unsigned long pumpDurationMs = 0;        // Durasi pompa dalam milidetik
bool pumpActive = false;                 // Status pompa aktif/tidak
const unsigned long sensorInterval = 5000; // Interval pembacaan sensor (5 detik)

// ============= FUZZY LOGIC SETUP =============
Fuzzy *fuzzy = new Fuzzy();

// FuzzyInput Suhu
FuzzySet *Dingin = new FuzzySet(0, 0, 15, 18);
FuzzySet *Normal = new FuzzySet(15, 18, 30, 33);
FuzzySet *Panas = new FuzzySet(30, 33, 50, 50);

// FuzzyInput Kelembapan
FuzzySet *Kering = new FuzzySet(0, 0, 60, 65);
FuzzySet *Lembab = new FuzzySet(60, 65, 80, 85);
FuzzySet *Basah = new FuzzySet(80, 85, 100, 100);

// FuzzyInput Cahaya
FuzzySet *Redup = new FuzzySet(0, 0, 40, 100);
FuzzySet *Sedang = new FuzzySet(40, 100, 250, 300);
FuzzySet *Terang = new FuzzySet(250, 300, 400, 400);

// FuzzyOutput Pompa (dalam detik)
FuzzySet *Mati = new FuzzySet(0, 0, 0, 0);
FuzzySet *Sebentar = new FuzzySet(240, 300, 300, 360);
FuzzySet *Sedang1 = new FuzzySet(480, 540, 540, 600);
FuzzySet *Lama = new FuzzySet(780, 840, 840, 900);

// ============= CALLBACK ESP-NOW =============
void OnDataRecv(const esp_now_recv_info_t *recv_info, const uint8_t *incomingData, int len) {
  // Cek apakah data dari sensor suhu/cahaya (ESP01s) atau sensor tanah (ESP32C3)
  if (len == sizeof(SensorData)) {
    // Data dari ESP01s (AHT20 + BH1750)
    SensorData tempData;
    memcpy(&tempData, incomingData, sizeof(tempData));
    
    if (tempData.dataValid) {
      currentTemperature = tempData.temperature;
      currentLux = tempData.lux;
      sensorDataReceived = true;
      
      Serial.println("=== Data Diterima dari ESP01s ===");
      Serial.print("Suhu: ");
      Serial.print(currentTemperature);
      Serial.println(" °C");
      Serial.print("Cahaya: ");
      Serial.print(currentLux);
      Serial.println(" lux");
    }
  }
  else if (len == sizeof(SoilMoistureData)) {
    // Data dari ESP32C3 (Soil Moisture)
    SoilMoistureData tempSoilData;
    memcpy(&tempSoilData, incomingData, sizeof(tempSoilData));
    
    if (tempSoilData.dataValid && tempSoilData.sensorId >= 1 && tempSoilData.sensorId <= 4) {
      int index = tempSoilData.sensorId - 1; // Convert to 0-based index
      
      soilSensors[index] = tempSoilData;
      moistureValues[index] = tempSoilData.moisturePercent;
      soilDataReceived[index] = true;
      lastSoilDataTime[index] = millis();
      
      Serial.print("=== Data Diterima dari ESP32C3 Sensor ");
      Serial.print(tempSoilData.sensorId);
      Serial.println(" ===");
      Serial.print("Kelembapan: ");
      Serial.print(tempSoilData.moisturePercent, 1);
      Serial.print("% (Raw: ");
      Serial.print(tempSoilData.rawValue);
      Serial.print(", Timestamp: ");
      Serial.print(tempSoilData.timestamp);
      Serial.println(")");
    }
  }
}

// ============= FUNGSI UNTUK KONVERSI WAKTU =============
void printDuration(float totalSeconds) {
  int minutes = (int)(totalSeconds / 60);
  int seconds = (int)(totalSeconds) % 60;
  
  Serial.print(minutes);
  Serial.print(" menit ");
  Serial.print(seconds);
  Serial.print(" detik");
}

void setup() {
  Serial.begin(115200);
  
  // Setup Relay Pin
  pinMode(RELAY_PIN, OUTPUT);
  digitalWrite(RELAY_PIN, LOW); // Relay OFF saat startup
  
  // Inisialisasi data sensor tanah
  for (int i = 0; i < 4; i++) {
    soilSensors[i].sensorId = i + 1;
    soilSensors[i].moisturePercent = 0.0;
    soilSensors[i].rawValue = 0;
    soilSensors[i].dataValid = false;
    soilSensors[i].timestamp = 0;
    moistureValues[i] = 0.0;
    soilDataReceived[i] = false;
    lastSoilDataTime[i] = 0;
  }
  
  // Set WiFi mode
  WiFi.mode(WIFI_STA);
  
  // Inisialisasi ESP-NOW
  if (esp_now_init() != ESP_OK) {
    Serial.println("Error initializing ESP-NOW");
    return;
  }
  
  // Register callback function
  esp_now_register_recv_cb(OnDataRecv);
  
  // Setup Fuzzy Logic
  fuzzySet();
  fuzzyRule();
  
  Serial.println("========== SISTEM SMART GARDEN DENGAN FUZZY LOGIC ==========");
  Serial.println("ESP32 Master ready - Waiting for sensor data...");
  
  // Print MAC Address
  Serial.print("ESP32 MAC Address: ");
  Serial.println(WiFi.macAddress());
  
  delay(2000);
}

void loop() {
  unsigned long currentMillis = millis();
  
  // ============= KONTROL POMPA NON-BLOCKING =============
  // Cek apakah pompa sedang aktif dan sudah waktunya untuk dimatikan
  if (pumpActive && (currentMillis - pumpStartTime >= pumpDurationMs)) {
    digitalWrite(RELAY_PIN, LOW);
    pumpActive = false;
    Serial.println("Pompa telah dimatikan");
    Serial.println("=====================================");
  }
  
  // ============= PEMBACAAN SENSOR SETIAP 5 DETIK =============
  if (currentMillis - previousMillis >= sensorInterval) {
    previousMillis = currentMillis;
    
    Serial.println("============ PEMBACAAN SENSOR ============");
    
    // =================== TAMPILKAN DATA SUHU & CAHAYA DARI ESP-NOW ===================
    Serial.println("--- KONDISI UDARA & CAHAYA (ESP-NOW) ---");
    Serial.print("Suhu Udara    : ");
    Serial.print(currentTemperature, 2);
    Serial.println(" °C");
    Serial.print("Intensitas Cahaya: ");
    Serial.print(currentLux);
    Serial.println(" lux");
    
    if (!sensorDataReceived) {
      Serial.println("WARNING: Menggunakan nilai default (belum menerima data ESP-NOW)");
    }
    
    // =================== TAMPILKAN DATA SENSOR TANAH DARI ESP32C3 ===================
    Serial.println("--- KONDISI TANAH (ESP32C3) ---");
    
    unsigned long currentTime = millis();
    const unsigned long dataTimeout = 15000; // Timeout 15 detik
    
    for (int i = 0; i < 4; i++) {
      Serial.print("Sensor Tanah ");
      Serial.print(i + 1);
      Serial.print(": ");
      
      // Cek apakah data masih valid (tidak timeout)
      bool dataTimedOut = (currentTime - lastSoilDataTime[i]) > dataTimeout;
      
      if (soilDataReceived[i] && !dataTimedOut) {
        Serial.print(moistureValues[i], 1);
        Serial.print("% (Raw: ");
        Serial.print(soilSensors[i].rawValue);
        Serial.print(", Age: ");
        Serial.print((currentTime - lastSoilDataTime[i]) / 1000);
        Serial.println("s)");
      } else {
        Serial.print("0.0% (Raw: 0) - ");
        if (soilDataReceived[i] && dataTimedOut) {
          Serial.println("DATA TIMEOUT");
          soilDataReceived[i] = false; // Reset status jika timeout
        } else {
          Serial.println("TIDAK ADA DATA");
        }
      }
    }
    
    // Hitung dan tampilkan rata-rata kelembaban tanah
    int validSensors = 0;
    float totalMoisture = 0.0;
    
    for (int i = 0; i < 4; i++) {
      if (soilDataReceived[i]) {
        totalMoisture += moistureValues[i];
        validSensors++;
      }
    }
    
    float avgMoisture = (validSensors > 0) ? (totalMoisture / validSensors) : 0.0;
    
    Serial.print("Rata-rata Kelembaban Tanah: ");
    Serial.print(avgMoisture, 1);
    Serial.print("% (dari ");
    Serial.print(validSensors);
    Serial.println(" sensor aktif)");
    
    if (validSensors == 0) {
      Serial.println("WARNING: Tidak ada data sensor tanah yang valid!");
    }
    
    // =================== FUZZY LOGIC PROCESSING ===================
    Serial.println("--- ANALISIS FUZZY LOGIC ---");
    
    // Set input values untuk fuzzy
    fuzzy->setInput(1, currentTemperature);  // Input Suhu dari ESP-NOW
    fuzzy->setInput(2, avgMoisture);         // Input Kelembaban Tanah dari ESP32C3
    fuzzy->setInput(3, currentLux);          // Input Cahaya dari ESP-NOW
    
    // Proses fuzzy logic
    fuzzy->fuzzify();
    
    // Dapatkan output (durasi pompa dalam detik)
    float pumpDuration = fuzzy->defuzzify(1);
    
    // Tampilkan kondisi lingkungan berdasarkan fuzzy sets
    displayEnvironmentCondition(currentTemperature, avgMoisture, currentLux);
    
    // Tampilkan durasi pompa dalam format menit dan detik
    Serial.print("Durasi Pompa: ");
    printDuration(pumpDuration);
    Serial.println();
    
    // =================== KONTROL POMPA ===================
    // Hanya aktifkan pompa jika tidak sedang aktif
    if (!pumpActive) {
      controlPump(pumpDuration);
    } else {
      // Tampilkan status pompa yang sedang aktif
      unsigned long timeRemaining = pumpDurationMs - (currentMillis - pumpStartTime);
      float timeRemainingSeconds = timeRemaining / 1000.0; // Konversi ke detik
      
      Serial.println("--- KONTROL POMPA ---");
      Serial.print("Status Pompa: MASIH AKTIF (sisa waktu: ");
      printDuration(timeRemainingSeconds);
      Serial.println(")");
    }
    
    if (!pumpActive) {
      Serial.println("=====================================");
    }
  }
}

// ============= FUNGSI FUZZY LOGIC =============
void fuzzySet() {
  // FuzzyInput SUHU
  FuzzyInput *Suhu = new FuzzyInput(1);
  Suhu->addFuzzySet(Dingin);
  Suhu->addFuzzySet(Normal);
  Suhu->addFuzzySet(Panas);
  fuzzy->addFuzzyInput(Suhu);
  
  // FuzzyInput KELEMBABAN
  FuzzyInput *Kelembapan = new FuzzyInput(2);
  Kelembapan->addFuzzySet(Kering);
  Kelembapan->addFuzzySet(Lembab);
  Kelembapan->addFuzzySet(Basah);
  fuzzy->addFuzzyInput(Kelembapan);
  
  // FuzzyInput CAHAYA
  FuzzyInput *cahaya = new FuzzyInput(3);
  cahaya->addFuzzySet(Redup);
  cahaya->addFuzzySet(Sedang);
  cahaya->addFuzzySet(Terang);
  fuzzy->addFuzzyInput(cahaya);
  
  // FuzzyOutput RELAY (POMPA)
  FuzzyOutput *Pompa = new FuzzyOutput(1);
  Pompa->addFuzzySet(Mati);
  Pompa->addFuzzySet(Sebentar);
  Pompa->addFuzzySet(Sedang1);
  Pompa->addFuzzySet(Lama);
  fuzzy->addFuzzyOutput(Pompa);
}

void fuzzyRule() {
 // FuzzyRule 1: Dingin + Kering + Redup = Sedang
  FuzzyRuleAntecedent *dingin_kering_1 = new FuzzyRuleAntecedent();
  dingin_kering_1->joinWithAND(Dingin, Kering);
  FuzzyRuleAntecedent *cahaya_redup = new FuzzyRuleAntecedent();
  cahaya_redup->joinSingle(Redup);
  FuzzyRuleAntecedent *dingin_kering_redup_1 = new FuzzyRuleAntecedent();
  dingin_kering_redup_1->joinWithAND(dingin_kering_1, cahaya_redup);
  FuzzyRuleConsequent *pompa_sedang_1 = new FuzzyRuleConsequent();
  pompa_sedang_1->addOutput(Sedang1);
  FuzzyRule *fuzzyRule1 = new FuzzyRule(1, dingin_kering_redup_1, pompa_sedang_1);
  fuzzy->addFuzzyRule(fuzzyRule1);
  
  // FuzzyRule 2: Dingin + Kering + Sedang = Sebentar
  FuzzyRuleAntecedent *dingin_kering_2 = new FuzzyRuleAntecedent();
  dingin_kering_2->joinWithAND(Dingin, Kering);
  FuzzyRuleAntecedent *cahaya_sedang = new FuzzyRuleAntecedent();
  cahaya_sedang->joinSingle(Sedang);
  FuzzyRuleAntecedent *dingin_kering_sedang_2 = new FuzzyRuleAntecedent();
  dingin_kering_sedang_2->joinWithAND(dingin_kering_2, cahaya_sedang);
  FuzzyRuleConsequent *pompa_sebentar_2 = new FuzzyRuleConsequent();
  pompa_sebentar_2->addOutput(Sebentar);
  FuzzyRule *fuzzyRule2 = new FuzzyRule(2, dingin_kering_sedang_2, pompa_sebentar_2);
  fuzzy->addFuzzyRule(fuzzyRule2);
  
  // FuzzyRule 3: Dingin + Kering + Terang = Lama
  FuzzyRuleAntecedent *dingin_kering_3 = new FuzzyRuleAntecedent();
  dingin_kering_3->joinWithAND(Dingin, Kering);
  FuzzyRuleAntecedent *cahaya_terang = new FuzzyRuleAntecedent();
  cahaya_terang->joinSingle(Terang);
  FuzzyRuleAntecedent *dingin_kering_terang_3 = new FuzzyRuleAntecedent();
  dingin_kering_terang_3->joinWithAND(dingin_kering_3, cahaya_terang);
  FuzzyRuleConsequent *pompa_lama_3 = new FuzzyRuleConsequent();
  pompa_lama_3->addOutput(Lama);
  FuzzyRule *fuzzyRule3 = new FuzzyRule(3, dingin_kering_terang_3, pompa_lama_3);
  fuzzy->addFuzzyRule(fuzzyRule3);
  
  // FuzzyRule 4: Dingin + Lembab + Redup = Mati
  FuzzyRuleAntecedent *dingin_lembab_4 = new FuzzyRuleAntecedent();
  dingin_lembab_4->joinWithAND(Dingin, Lembab);
  FuzzyRuleAntecedent *dingin_lembab_redup_4 = new FuzzyRuleAntecedent();
  dingin_lembab_redup_4->joinWithAND(dingin_lembab_4, cahaya_redup);
  FuzzyRuleConsequent *pompa_mati_4 = new FuzzyRuleConsequent();
  pompa_mati_4->addOutput(Mati);
  FuzzyRule *fuzzyRule4 = new FuzzyRule(4, dingin_lembab_redup_4, pompa_mati_4);
  fuzzy->addFuzzyRule(fuzzyRule4);
  
  // FuzzyRule 5: Dingin + Lembab + Sedang = Mati
  FuzzyRuleAntecedent *dingin_lembab_5 = new FuzzyRuleAntecedent();
  dingin_lembab_5->joinWithAND(Dingin, Lembab);
  FuzzyRuleAntecedent *dingin_lembab_sedang_5 = new FuzzyRuleAntecedent();
  dingin_lembab_sedang_5->joinWithAND(dingin_lembab_5, cahaya_sedang);
  FuzzyRuleConsequent *pompa_mati_5 = new FuzzyRuleConsequent();
  pompa_mati_5->addOutput(Mati);
  FuzzyRule *fuzzyRule5 = new FuzzyRule(5, dingin_lembab_sedang_5, pompa_mati_5);
  fuzzy->addFuzzyRule(fuzzyRule5);
  
  // FuzzyRule 6: Dingin + Lembab + Terang = Sedang
  FuzzyRuleAntecedent *dingin_lembab_6 = new FuzzyRuleAntecedent();
  dingin_lembab_6->joinWithAND(Dingin, Lembab);
  FuzzyRuleAntecedent *dingin_lembab_terang_6 = new FuzzyRuleAntecedent();
  dingin_lembab_terang_6->joinWithAND(dingin_lembab_6, cahaya_terang);
  FuzzyRuleConsequent *pompa_sedang_6 = new FuzzyRuleConsequent();
  pompa_sedang_6->addOutput(Sedang1);
  FuzzyRule *fuzzyRule6 = new FuzzyRule(6, dingin_lembab_terang_6, pompa_sedang_6);
  fuzzy->addFuzzyRule(fuzzyRule6);
  
  // FuzzyRule 7: Dingin + Basah + Redup = Mati
  FuzzyRuleAntecedent *dingin_basah_7 = new FuzzyRuleAntecedent();
  dingin_basah_7->joinWithAND(Dingin, Basah);
  FuzzyRuleAntecedent *dingin_basah_redup_7 = new FuzzyRuleAntecedent();
  dingin_basah_redup_7->joinWithAND(dingin_basah_7, cahaya_redup);
  FuzzyRuleConsequent *pompa_mati_7 = new FuzzyRuleConsequent();
  pompa_mati_7->addOutput(Mati);
  FuzzyRule *fuzzyRule7 = new FuzzyRule(7, dingin_basah_redup_7, pompa_mati_7);
  fuzzy->addFuzzyRule(fuzzyRule7);
  
  // FuzzyRule 8: Dingin + Basah + Sedang = Mati
  FuzzyRuleAntecedent *dingin_basah_8 = new FuzzyRuleAntecedent();
  dingin_basah_8->joinWithAND(Dingin, Basah);
  FuzzyRuleAntecedent *dingin_basah_sedang_8 = new FuzzyRuleAntecedent();
  dingin_basah_sedang_8->joinWithAND(dingin_basah_8, cahaya_sedang);
  FuzzyRuleConsequent *pompa_mati_8 = new FuzzyRuleConsequent();
  pompa_mati_8->addOutput(Mati);
  FuzzyRule *fuzzyRule8 = new FuzzyRule(8, dingin_basah_sedang_8, pompa_mati_8);
  fuzzy->addFuzzyRule(fuzzyRule8);
  
  // FuzzyRule 9: Dingin + Basah + Terang = Sebentar
  FuzzyRuleAntecedent *dingin_basah_9 = new FuzzyRuleAntecedent();
  dingin_basah_9->joinWithAND(Dingin, Basah);
  FuzzyRuleAntecedent *dingin_basah_terang_9 = new FuzzyRuleAntecedent();
  dingin_basah_terang_9->joinWithAND(dingin_basah_9, cahaya_terang);
  FuzzyRuleConsequent *pompa_sebentar_9 = new FuzzyRuleConsequent();
  pompa_sebentar_9->addOutput(Sebentar);
  FuzzyRule *fuzzyRule9 = new FuzzyRule(9, dingin_basah_terang_9, pompa_sebentar_9);
  fuzzy->addFuzzyRule(fuzzyRule9);
  
  // FuzzyRule 10: Normal + Kering + Redup = Sedang
  FuzzyRuleAntecedent *normal_kering_10 = new FuzzyRuleAntecedent();
  normal_kering_10->joinWithAND(Normal, Kering);
  FuzzyRuleAntecedent *normal_kering_redup_10 = new FuzzyRuleAntecedent();
  normal_kering_redup_10->joinWithAND(normal_kering_10, cahaya_redup);
  FuzzyRuleConsequent *pompa_sedang_10 = new FuzzyRuleConsequent();
  pompa_sedang_10->addOutput(Sedang1);
  FuzzyRule *fuzzyRule10 = new FuzzyRule(10, normal_kering_redup_10, pompa_sedang_10);
  fuzzy->addFuzzyRule(fuzzyRule10);
  
  // FuzzyRule 11: Normal + Kering + Sedang = Sedang
  FuzzyRuleAntecedent *normal_kering_11 = new FuzzyRuleAntecedent();
  normal_kering_11->joinWithAND(Normal, Kering);
  FuzzyRuleAntecedent *normal_kering_sedang_11 = new FuzzyRuleAntecedent();
  normal_kering_sedang_11->joinWithAND(normal_kering_11, cahaya_sedang);
  FuzzyRuleConsequent *pompa_sedang_11 = new FuzzyRuleConsequent();
  pompa_sedang_11->addOutput(Sedang1);
  FuzzyRule *fuzzyRule11 = new FuzzyRule(11, normal_kering_sedang_11, pompa_sedang_11);
  fuzzy->addFuzzyRule(fuzzyRule11);
  
  // FuzzyRule 12: Normal + Kering + Terang = Lama
  FuzzyRuleAntecedent *normal_kering_12 = new FuzzyRuleAntecedent();
  normal_kering_12->joinWithAND(Normal, Kering);
  FuzzyRuleAntecedent *normal_kering_terang_12 = new FuzzyRuleAntecedent();
  normal_kering_terang_12->joinWithAND(normal_kering_12, cahaya_terang);
  FuzzyRuleConsequent *pompa_lama_12 = new FuzzyRuleConsequent();
  pompa_lama_12->addOutput(Lama);
  FuzzyRule *fuzzyRule12 = new FuzzyRule(12, normal_kering_terang_12, pompa_lama_12);
  fuzzy->addFuzzyRule(fuzzyRule12);
  
  // FuzzyRule 13: Normal + Lembab + Redup = Mati
  FuzzyRuleAntecedent *normal_lembab_13 = new FuzzyRuleAntecedent();
  normal_lembab_13->joinWithAND(Normal, Lembab);
  FuzzyRuleAntecedent *normal_lembab_redup_13 = new FuzzyRuleAntecedent();
  normal_lembab_redup_13->joinWithAND(normal_lembab_13, cahaya_redup);
  FuzzyRuleConsequent *pompa_mati_13 = new FuzzyRuleConsequent();
  pompa_mati_13->addOutput(Mati);
  FuzzyRule *fuzzyRule13 = new FuzzyRule(13, normal_lembab_redup_13, pompa_mati_13);
  fuzzy->addFuzzyRule(fuzzyRule13);
  
  // FuzzyRule 14: Normal + Lembab + Sedang = Sebentar
  FuzzyRuleAntecedent *normal_lembab_14 = new FuzzyRuleAntecedent();
  normal_lembab_14->joinWithAND(Normal, Lembab);
  FuzzyRuleAntecedent *normal_lembab_sedang_14 = new FuzzyRuleAntecedent();
  normal_lembab_sedang_14->joinWithAND(normal_lembab_14, cahaya_sedang);
  FuzzyRuleConsequent *pompa_sebentar_14 = new FuzzyRuleConsequent();
  pompa_sebentar_14->addOutput(Sebentar);
  FuzzyRule *fuzzyRule14 = new FuzzyRule(14, normal_lembab_sedang_14, pompa_sebentar_14);
  fuzzy->addFuzzyRule(fuzzyRule14);
  
  // FuzzyRule 15: Normal + Lembab + Terang = Sedang
  FuzzyRuleAntecedent *normal_lembab_15 = new FuzzyRuleAntecedent();
  normal_lembab_15->joinWithAND(Normal, Lembab);
  FuzzyRuleAntecedent *normal_lembab_terang_15 = new FuzzyRuleAntecedent();
  normal_lembab_terang_15->joinWithAND(normal_lembab_15, cahaya_terang);
  FuzzyRuleConsequent *pompa_sedang_15 = new FuzzyRuleConsequent();
  pompa_sedang_15->addOutput(Sedang1);
  FuzzyRule *fuzzyRule15 = new FuzzyRule(15, normal_lembab_terang_15, pompa_sedang_15);
  fuzzy->addFuzzyRule(fuzzyRule15);
  
  // FuzzyRule 16: Normal + Basah + Redup = Mati
  FuzzyRuleAntecedent *normal_basah_16 = new FuzzyRuleAntecedent();
  normal_basah_16->joinWithAND(Normal, Basah);
  FuzzyRuleAntecedent *normal_basah_redup_16 = new FuzzyRuleAntecedent();
  normal_basah_redup_16->joinWithAND(normal_basah_16, cahaya_redup);
  FuzzyRuleConsequent *pompa_mati_16 = new FuzzyRuleConsequent();
  pompa_mati_16->addOutput(Mati);
  FuzzyRule *fuzzyRule16 = new FuzzyRule(16, normal_basah_redup_16, pompa_mati_16);
  fuzzy->addFuzzyRule(fuzzyRule16);
  
  // FuzzyRule 17: Normal + Basah + Sedang = Sebentar
  FuzzyRuleAntecedent *normal_basah_17 = new FuzzyRuleAntecedent();
  normal_basah_17->joinWithAND(Normal, Basah);
  FuzzyRuleAntecedent *normal_basah_sedang_17 = new FuzzyRuleAntecedent();
  normal_basah_sedang_17->joinWithAND(normal_basah_17, cahaya_sedang);
  FuzzyRuleConsequent *pompa_sebentar_17 = new FuzzyRuleConsequent();
  pompa_sebentar_17->addOutput(Sebentar);
  FuzzyRule *fuzzyRule17 = new FuzzyRule(17, normal_basah_sedang_17, pompa_sebentar_17);
  fuzzy->addFuzzyRule(fuzzyRule17);

  // FuzzyRule 18: Normal + Basah + Terang = Sedang
  FuzzyRuleAntecedent *normal_basah_18 = new FuzzyRuleAntecedent();
  normal_basah_18->joinWithAND(Normal, Basah);
  FuzzyRuleAntecedent *normal_basah_terang_18 = new FuzzyRuleAntecedent();
  normal_basah_terang_18->joinWithAND(normal_basah_18, cahaya_terang);
  FuzzyRuleConsequent *pompa_sedang_18 = new FuzzyRuleConsequent();
  pompa_sedang_18->addOutput(Sedang1);
  FuzzyRule *fuzzyRule18 = new FuzzyRule(18, normal_basah_terang_18, pompa_sedang_18);
  fuzzy->addFuzzyRule(fuzzyRule18);
  
  // FuzzyRule 19: Panas + Kering + Redup = Sedang
  FuzzyRuleAntecedent *panas_kering_19 = new FuzzyRuleAntecedent();
  panas_kering_19->joinWithAND(Panas, Kering);
  FuzzyRuleAntecedent *panas_kering_redup_19 = new FuzzyRuleAntecedent();
  panas_kering_redup_19->joinWithAND(panas_kering_19, cahaya_redup);
  FuzzyRuleConsequent *pompa_sedang_19 = new FuzzyRuleConsequent();
  pompa_sedang_19->addOutput(Sedang1);
  FuzzyRule *fuzzyRule19 = new FuzzyRule(19, panas_kering_redup_19, pompa_sedang_19);
  fuzzy->addFuzzyRule(fuzzyRule19);
  
  // FuzzyRule 20: Panas + Kering + Sedang = Lama
  FuzzyRuleAntecedent *panas_kering_20 = new FuzzyRuleAntecedent();
  panas_kering_20->joinWithAND(Panas, Kering);
  FuzzyRuleAntecedent *panas_kering_sedang_20 = new FuzzyRuleAntecedent();
  panas_kering_sedang_20->joinWithAND(panas_kering_20, cahaya_sedang);
  FuzzyRuleConsequent *pompa_lama_20 = new FuzzyRuleConsequent();
  pompa_lama_20->addOutput(Lama);
  FuzzyRule *fuzzyRule20 = new FuzzyRule(20, panas_kering_sedang_20, pompa_lama_20);
  fuzzy->addFuzzyRule(fuzzyRule20);
  
  // FuzzyRule 21: Panas + Kering + Terang = Lama
  FuzzyRuleAntecedent *panas_kering_21 = new FuzzyRuleAntecedent();
  panas_kering_21->joinWithAND(Panas, Kering);
  FuzzyRuleAntecedent *panas_kering_terang_21 = new FuzzyRuleAntecedent();
  panas_kering_terang_21->joinWithAND(panas_kering_21, cahaya_terang);
  FuzzyRuleConsequent *pompa_lama_21 = new FuzzyRuleConsequent();
  pompa_lama_21->addOutput(Lama);
  FuzzyRule *fuzzyRule21 = new FuzzyRule(21, panas_kering_terang_21, pompa_lama_21);
  fuzzy->addFuzzyRule(fuzzyRule21);
  
  // FuzzyRule 22: Panas + Lembab + Redup = Sebentar
  FuzzyRuleAntecedent *panas_lembab_22 = new FuzzyRuleAntecedent();
  panas_lembab_22->joinWithAND(Panas, Lembab);
  FuzzyRuleAntecedent *panas_lembab_redup_22 = new FuzzyRuleAntecedent();
  panas_lembab_redup_22->joinWithAND(panas_lembab_22, cahaya_redup);
  FuzzyRuleConsequent *pompa_sebentar_22 = new FuzzyRuleConsequent();
  pompa_sebentar_22->addOutput(Sebentar);
  FuzzyRule *fuzzyRule22 = new FuzzyRule(22, panas_lembab_redup_22, pompa_sebentar_22);
  fuzzy->addFuzzyRule(fuzzyRule22);
  
  // FuzzyRule 23: Panas + Lembab + Sedang = Sedang
  FuzzyRuleAntecedent *panas_lembab_23 = new FuzzyRuleAntecedent();
  panas_lembab_23->joinWithAND(Panas, Lembab);
  FuzzyRuleAntecedent *panas_lembab_sedang_23 = new FuzzyRuleAntecedent();
  panas_lembab_sedang_23->joinWithAND(panas_lembab_23, cahaya_sedang);
  FuzzyRuleConsequent *pompa_sedang_23 = new FuzzyRuleConsequent();
  pompa_sedang_23->addOutput(Sedang1);
  FuzzyRule *fuzzyRule23 = new FuzzyRule(23, panas_lembab_sedang_23, pompa_sedang_23);
  fuzzy->addFuzzyRule(fuzzyRule23);
  
  // FuzzyRule 24: Panas + Lembab + Terang = Lama
  FuzzyRuleAntecedent *panas_lembab_24 = new FuzzyRuleAntecedent();
  panas_lembab_24->joinWithAND(Panas, Lembab);
  FuzzyRuleAntecedent *panas_lembab_terang_24 = new FuzzyRuleAntecedent();
  panas_lembab_terang_24->joinWithAND(panas_lembab_24, cahaya_terang);
  FuzzyRuleConsequent *pompa_lama_24 = new FuzzyRuleConsequent();
  pompa_lama_24->addOutput(Lama);
  FuzzyRule *fuzzyRule24 = new FuzzyRule(24, panas_lembab_terang_24, pompa_lama_24);
  fuzzy->addFuzzyRule(fuzzyRule24);
  
  // FuzzyRule 25: Panas + Basah + Redup = Sebentar
  FuzzyRuleAntecedent *panas_basah_25 = new FuzzyRuleAntecedent();
  panas_basah_25->joinWithAND(Panas, Basah);
  FuzzyRuleAntecedent *panas_basah_redup_25 = new FuzzyRuleAntecedent();
  panas_basah_redup_25->joinWithAND(panas_basah_25, cahaya_redup);
  FuzzyRuleConsequent *pompa_sebentar_25 = new FuzzyRuleConsequent();
  pompa_sebentar_25->addOutput(Sebentar);
  FuzzyRule *fuzzyRule25 = new FuzzyRule(25, panas_basah_redup_25, pompa_sebentar_25);
  fuzzy->addFuzzyRule(fuzzyRule25);
  
  // FuzzyRule 26: Panas + Basah + Sedang = Sedang
  FuzzyRuleAntecedent *panas_basah_26 = new FuzzyRuleAntecedent();
  panas_basah_26->joinWithAND(Panas, Basah);
  FuzzyRuleAntecedent *panas_basah_sedang_26 = new FuzzyRuleAntecedent();
  panas_basah_sedang_26->joinWithAND(panas_basah_26, cahaya_sedang);
  FuzzyRuleConsequent *pompa_sedang_26 = new FuzzyRuleConsequent();
  pompa_sedang_26->addOutput(Sedang1);
  FuzzyRule *fuzzyRule26 = new FuzzyRule(26, panas_basah_sedang_26, pompa_sedang_26);
  fuzzy->addFuzzyRule(fuzzyRule26);
  
  // FuzzyRule 27: Panas + Basah + Terang = Sedang
  FuzzyRuleAntecedent *panas_basah_27 = new FuzzyRuleAntecedent();
  panas_basah_27->joinWithAND(Panas, Basah);
  FuzzyRuleAntecedent *panas_basah_terang_27 = new FuzzyRuleAntecedent();
  panas_basah_terang_27->joinWithAND(panas_basah_27, cahaya_terang);
  FuzzyRuleConsequent *pompa_sedang_27 = new FuzzyRuleConsequent();
  pompa_sedang_27->addOutput(Sedang1);
  FuzzyRule *fuzzyRule27 = new FuzzyRule(27, panas_basah_terang_27, pompa_sedang_27);
  fuzzy->addFuzzyRule(fuzzyRule27);
}

// ============= FUNGSI KONTROL POMPA =============
void controlPump(float duration) {
  Serial.println("--- KONTROL POMPA ---");
  
  if (duration <= 0.5) {
    Serial.println("Status Pompa: MATI");
    digitalWrite(RELAY_PIN, LOW);
    pumpActive = false;
  } else {
    // Konversi durasi dari detik ke menit untuk tampilan
    float durationMinutes = duration / 60.0;
    
    Serial.print("Status Pompa: AKTIF selama ");
    Serial.print(durationMinutes, 2);
    Serial.println(" menit");
    
    // Set variabel untuk kontrol non-blocking
    pumpStartTime = millis();
    pumpDurationMs = duration * 1000; // Konversi ke milidetik
    pumpActive = true;
    
    // Nyalakan relay (pompa)
    digitalWrite(RELAY_PIN, HIGH);
    
    Serial.println("Pompa mulai beroperasi...");
  }
}

// ============= FUNGSI DISPLAY KONDISI LINGKUNGAN =============
void displayEnvironmentCondition(float temperature, float avgMoisture, float lux) {
  Serial.println("--- KONDISI LINGKUNGAN ---");
  
  // Analisis Suhu
  Serial.print("Kondisi Suhu: ");
  if (temperature < 18) {
    Serial.println("DINGIN");
  } else if (temperature >= 18 && temperature <= 33) {
    Serial.println("NORMAL");
  } else {
    Serial.println("PANAS");
  }
  
  // Analisis Kelembaban
  Serial.print("Kondisi Kelembaban: ");
  if (avgMoisture < 65) {
    Serial.println("KERING");
  } else if (avgMoisture >= 65 && avgMoisture <= 85) {
    Serial.println("LEMBAB");
  } else {
    Serial.println("BASAH");
  }
  
  // Analisis Cahaya
  Serial.print("Kondisi Cahaya: ");
  if (lux < 100) {
    Serial.println("REDUP");
  } else if (lux >= 100 && lux <= 300) {
    Serial.println("SEDANG");
  } else {
    Serial.println("TERANG");
  }
}