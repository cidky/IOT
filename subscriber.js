const mqtt = require("mqtt");
const mysql = require("mysql");

// === Koneksi Database ===
const db = mysql.createConnection({
  host: "localhost",
  user: "root",
  password: "",
  database: "iot_project",
});

db.connect((err) => {
  if (err) {
    console.error("❌ Gagal koneksi ke database:", err.message);
  } else {
    console.log("✅ Terhubung ke database MySQL");
  }
});

// === Tes Query DB ===
function testDatabaseConnection() {
  return new Promise((resolve, reject) => {
    db.query("SELECT NOW() AS `current_time`", (err, results) => {
      if (err) reject(err);
      else {
        console.log("✅ Waktu DB:", results[0].current_time);
        resolve();
      }
    });
  });
}

// === Koneksi MQTT ===
const clientId = "G.231.22.0182-server";
const brokerUrl = "mqtt://mqtt.revolusi-it.com";
const topic = "iot/G.231.22.0182";

const client = mqtt.connect(brokerUrl, {
  clientId,
  username: "usm",
  password: "usmjaya1",
});

client.on("connect", () => {
  console.log("✅ Terhubung ke broker MQTT");

  testDatabaseConnection().catch((err) => {
    console.error("❌ Gagal tes DB:", err);
  });

  client.subscribe(topic, (err) => {
    if (err) {
      console.error("❌ Gagal subscribe:", err.message);
    } else {
      console.log(`📡 Subscribe ke topik: ${topic}`);
    }
  });
});

// === Saat Terima Pesan ===
client.on("message", (topic, message) => {
  const payload = message.toString();
  console.log(`📥 Pesan dari ${topic}: ${payload}`);

  try {
    const data = JSON.parse(payload);
    const suhu = data.suhu;
    const kelembaban = data.kelembaban; // ejaan dari ESP: kelembaban

    if (typeof suhu !== "number" || typeof kelembaban !== "number") {
      throw new Error("Format data tidak valid");
    }

    const sql =
      "INSERT INTO sensor_log (suhu, kelembaban, waktu) VALUES (?, ?, NOW())";
    db.query(sql, [suhu, kelembaban], (err, result) => {
      if (err) {
        console.error("❌ Gagal simpan:", err.message);
      } else {
        console.log("✅ Data disimpan:", { suhu, kelembaban });
      }
    });
  } catch (err) {
    console.error("❌ Error parsing payload:", err.message);
  }
});
