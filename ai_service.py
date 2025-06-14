# ai_service.py
from flask import Flask, request, jsonify
from flask_cors import CORS # Untuk mengizinkan CORS di Flask
import random

app = Flask(__name__)
CORS(app) # Aktifkan CORS untuk aplikasi Flask

# Contoh sederhana logika rekomendasi AI (bisa diganti dengan model ML sungguhan)
def generate_recommendations(user_data):
    # Di sini kamu bisa mengimplementasikan model AI-mu.
    # Contoh sederhana: Rekomendasi berdasarkan input atau random
    if user_data and user_data.get('mood') == 'energic':
        lunch_menu = {
            "title": "Salad Ayam Panggang & Quinoa",
            "description": "Protein tinggi, serat, dan vitamin. Cocok untuk energi sepanjang siang.",
            "button_text": "Lihat Resep"
        }
        exercise = {
            "title": "Latihan Kardio Intensitas Tinggi (30 menit)",
            "description": "Berfokus pada peningkatan stamina dan pembakaran kalori. Baik untuk menjaga kesehatan jantung.",
            "button_text": "Mulai Latihan"
        }
    else:
        # Rekomendasi acak atau default
        lunch_options = [
            {"title": "Sup Krim Jamur", "description": "Hangat dan menenangkan, cocok untuk cuaca dingin.", "button_text": "Lihat Resep"},
            {"title": "Nasi Goreng Spesial", "description": "Favorit Indonesia, lengkap dengan protein dan sayuran.", "button_text": "Lihat Resep"},
            {"title": "Smoothie Buah Naga & Pisang", "description": "Penuh vitamin dan antioksidan untuk vitalitas.", "button_text": "Lihat Resep"},
        ]
        exercise_options = [
            {"title": "Yoga untuk Relaksasi (20 menit)", "description": "Menenangkan pikiran dan meregangkan otot.", "button_text": "Mulai Latihan"},
            {"title": "Jalan Santai (45 menit)", "description": "Pembakar lemak ringan dan menenangkan.", "button_text": "Mulai Latihan"},
            {"title": "Meditasi Terpandu (15 menit)", "description": "Fokus pada ketenangan batin dan mengurangi stres.", "button_text": "Mulai Latihan"},
        ]
        lunch_menu = random.choice(lunch_options)
        exercise = random.choice(exercise_options)

    return {
        "lunch": lunch_menu,
        "exercise": exercise
    }

@app.route('/predict', methods=['POST'])
def predict():
    user_data = request.json # Data dari Node.js
    recommendations = generate_recommendations(user_data)
    return jsonify(recommendations)

if __name__ == '__main__':
    app.run(port=5000) # Jalankan server Flask di port 5000