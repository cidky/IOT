<!DOCTYPE html>
<html>

<head>
    <title>Monitoring IoT - G.231.22.0182</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>
    body {
        font-family: Arial, sans-serif;
        background-color: #1e1e2f;
        color: #e0e0e0;
        margin: 20px;
    }

    h1,
    h2,
    h3 {
        color: #ffffff;
    }

    .container {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
    }

    .chart-container {
        width: 60%;
        min-width: 500px;
    }

    .status-container {
        width: 35%;
        min-width: 300px;
    }

    .card {
        background: #2a2a3d;
        padding: 15px;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    }

    button {
        margin: 5px 10px 5px 0;
        padding: 10px 15px;
        font-weight: bold;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        color: #fff;
        background-color: #3a7bd5;
        transition: background-color 0.3s ease;
    }

    button:hover {
        background-color: #295cb5;
    }

    button:active {
        background-color: #1d3e80;
    }

    button.off {
        background-color: #d9534f;
    }

    button.off:hover {
        background-color: #c9302c;
    }

    #beepAudio {
        display: none;
    }

    #statusLed {
        margin-top: 10px;
        font-size: 14px;
        color: #b0b0b0;
    }
    </style>

</head>

<body>
    <h1>Monitoring Suhu & Kelembaban</h1>

    <div class="container">
        <div class="chart-container card">
            <h2>Grafik Sensor</h2>
            <canvas id="chartSensor" height="250"></canvas>
        </div>
        <div class="status-container card">
            <h2>Status Terkini</h2>
            <div id="latestData">
                <p>Memuat data...</p>
            </div>

            <h3>Kontrol LED</h3>
            <button onclick="controlLED(1, 'ON')">LED 1 ON</button>
            <button class="off" onclick="controlLED(1, 'OFF')">LED 1 OFF</button>
            <button onclick="controlLED(2, 'ON')">LED 2 ON</button>
            <button class="off" onclick="controlLED(2, 'OFF')">LED 2 OFF</button>
            <button onclick="controlLED(3, 'ON')">LED 3 ON</button>
            <button class="off" onclick="controlLED(3, 'OFF')">LED 3 OFF</button>
            <p id="statusLed">Status LED: -</p>
        </div>
    </div>

    <audio id="beepAudio" src="beep.mp3"></audio>

    <script>
    let chart;
    const beepAudio = document.getElementById('beepAudio');

    function initChart() {
        const ctx = document.getElementById('chartSensor').getContext('2d');
        chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                        label: 'Suhu (°C)',
                        data: [],
                        borderColor: 'rgba(255, 99, 132, 1)',
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        yAxisID: 'ySuhu',
                        tension: 0.3
                    },
                    {
                        label: 'Kelembaban (%)',
                        data: [],
                        borderColor: 'rgba(54, 162, 235, 1)',
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        yAxisID: 'yKelembaban',
                        tension: 0.3
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    ySuhu: {
                        type: 'linear',
                        position: 'left',
                        min: 20,
                        max: 40,
                        title: {
                            display: true,
                            text: 'Suhu (°C)'
                        }
                    },
                    yKelembaban: {
                        type: 'linear',
                        position: 'right',
                        min: 40,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Kelembaban (%)'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Waktu'
                        }
                    }
                }
            }
        });
    }

    async function fetchData() {
        try {
            const response = await axios.get('data.php');
            const data = response.data;

            chart.data.labels = data.map(item => item.waktu);
            chart.data.datasets[0].data = data.map(item => item.suhu);
            chart.data.datasets[1].data = data.map(item => item.kelembaban);
            chart.update();

            const latest = data[data.length - 1];
            document.getElementById('latestData').innerHTML = `
                    <p><strong>Waktu:</strong> ${new Date().toLocaleTimeString()}</p>
                    <p><strong>Suhu:</strong> ${latest.suhu} °C</p>
                    <p><strong>Kelembaban:</strong> ${latest.kelembaban} %</p>
                    <p><strong>Status:</strong> ${getStatus(latest.suhu, latest.kelembaban)}</p>
                `;

            handleBeep(latest.suhu, latest.kelembaban);

        } catch (error) {
            console.error('Error fetching data:', error);
        }
    }

    function controlLED(ledNum, action) {
        axios.post('control.php', {
                led: ledNum,
                action: action
            })
            .then(response => {
                if (response.data.success) {
                    alert(`✅ ${response.data.message} berhasil dikirim!`);
                    document.getElementById('statusLed').innerText = `Status LED${ledNum}: ${action}`;
                } else {
                    alert(`⚠️ Gagal: ${response.data.error}`);
                }
            })
            .catch(error => {
                console.error('❌ Error:', error);
                alert('❌ Gagal menghubungi server kontrol.');
            });
    }

    function getStatus(suhu, kelembaban) {
        let status = [];

        if (suhu < 29) status.push("Suhu Normal");
        else if (suhu >= 29 && suhu < 30) status.push("Suhu Mulai Panas");
        else if (suhu >= 30 && suhu <= 31) status.push("Suhu Panas!");
        else status.push("Suhu Sangat Panas!!");

        if (kelembaban >= 30 && kelembaban < 60) status.push("Kelembaban Aman");
        else if (kelembaban >= 60 && kelembaban < 70) status.push("Kelembaban Tinggi");
        else if (kelembaban >= 70) status.push("Kelembaban Sangat Tinggi!");

        return status.join(" | ");
    }

    function handleBeep(suhu, kelembaban) {
        let beepCount = 0;

        if (suhu > 29 && suhu < 30) beepCount = 1;
        else if (suhu >= 30 && suhu <= 31) beepCount = 2;
        else if (suhu > 31) beepCount = 3;

        if (kelembaban >= 60 && kelembaban < 70) beepCount += 1;
        else if (kelembaban >= 70) beepCount += 3;

        if (beepCount > 0) {
            for (let i = 0; i < beepCount; i++) {
                setTimeout(() => {
                    beepAudio.currentTime = 0;
                    beepAudio.play();
                }, i * 500);
            }
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        initChart();
        fetchData();
        setInterval(fetchData, 5000);
    });
    </script>
</body>

</html>