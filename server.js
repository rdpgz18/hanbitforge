// server.js
const express = require('express');
const bodyParser = require('body-parser');
const axios = require('axios'); // Untuk memanggil server Python


const app = express();
const port = 3000; // Port untuk server Node.js

app.use(bodyParser.json());
app.use(express.static('public')); // Jika kamu menyimpan file HTML di folder 'public'

// Izinkan CORS (Cross-Origin Resource Sharing)
// Ini penting agar frontend bisa mengakses API dari domain yang berbeda
app.use((req, res, next) => {
    res.header('Access-Control-Allow-Origin', '*'); // Izinkan dari semua origin
    res.header('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Type, Accept');
    next();
});

// Endpoint untuk mendapatkan rekomendasi AI
app.post('/api/get-recommendations', async (req, res) => {
    const userData = req.body; // Data yang dikirim dari frontend, misalnya preferensi pengguna

    try {
        // Panggil server Python untuk mendapatkan rekomendasi
        // Pastikan server Python berjalan di port 5000 (atau port yang kamu tentukan)
        const pythonResponse = await axios.post('http://localhost:5000/predict', userData);
        const recommendations = pythonResponse.data;

        res.json(recommendations);
    } catch (error) {
        console.error('Error saat memanggil server Python:', error.message);
        res.status(500).json({ error: 'Gagal mendapatkan rekomendasi dari AI' });
    }
});



app.listen(port, () => {
    console.log(`Server Node.js berjalan di http://localhost:${port}`);
});