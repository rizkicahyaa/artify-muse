# Penjelasan Algoritma Rekomendasi Museum

## 📋 Ringkasan
Sistem ini menggunakan **Collaborative Filtering berbasis User (User-based Collaborative Filtering)** untuk memberikan rekomendasi museum kepada pengguna berdasarkan rating yang telah diberikan.

---

## 🔄 Alur Kerja Sistem Rekomendasi

### **Langkah 1: User Memberikan Rating**
Ketika Anda memberikan rating pada museum:
- Rating disimpan ke database tabel `museum_ratings`
- Data yang disimpan: `user_name`, `museum_name`, `rating`, `review`, `created_at`

**Contoh:**
```
User: "Andi"
Museum: "Museum Nasional"
Rating: 5
Review: "Sangat bagus!"
```

---

### **Langkah 2: Membangun User-Item Matrix**
Sistem mengambil semua rating dari database dan membangun matriks:

```
        Museum A  Museum B  Museum C  Museum D
Andi       5        4         -         3
Budi       4        5         4         -
Cici       5        -         5         4
Dedi       -        4         3         5
```

**Keterangan:**
- Baris = User
- Kolom = Museum
- Nilai = Rating (1-5)
- `-` = Belum direview

---

### **Langkah 3: Menghitung Similarity (Kemiripan) Antar User**

Sistem menghitung seberapa mirip preferensi Anda dengan user lain menggunakan **Cosine Similarity**.

#### Formula Cosine Similarity:
```
Similarity(A, B) = (A · B) / (||A|| × ||B||)
```

**Contoh Perhitungan:**

Misalkan:
- **Andi** memberikan rating: Museum A=5, Museum B=4, Museum D=3
- **Budi** memberikan rating: Museum A=4, Museum B=5

**Langkah perhitungan:**
1. Cari museum yang direview oleh KEDUA user (common items):
   - Museum A: Andi=5, Budi=4
   - Museum B: Andi=4, Budi=5

2. Hitung Dot Product:
   ```
   Dot Product = (5 × 4) + (4 × 5) = 20 + 20 = 40
   ```

3. Hitung Norm (panjang vektor):
   ```
   Norm Andi = √(5² + 4² + 3²) = √(25 + 16 + 9) = √50 ≈ 7.07
   Norm Budi = √(4² + 5²) = √(16 + 25) = √41 ≈ 6.40
   ```

4. Hitung Similarity:
   ```
   Similarity = 40 / (7.07 × 6.40) = 40 / 45.25 ≈ 0.88
   ```

**Hasil:**
- Similarity tinggi (mendekati 1.0) = Preferensi sangat mirip
- Similarity rendah (mendekati 0) = Preferensi berbeda

---

### **Langkah 4: Mencari User yang Paling Mirip**

Sistem mengurutkan semua user berdasarkan similarity score (dari tertinggi ke terendah):

```
User Similarities dengan Andi:
1. Budi     → 0.88 (sangat mirip)
2. Cici     → 0.75 (mirip)
3. Dedi     → 0.45 (agak mirip)
4. ...
```

Sistem mengambil **top 10 user** yang paling mirip untuk efisiensi.

---

### **Langkah 5: Prediksi Rating untuk Museum yang Belum Direview**

Untuk setiap museum yang **belum Anda review**, sistem memprediksi rating yang mungkin Anda berikan.

#### Formula Prediksi:
```
Predicted Rating = Σ(Similarity × Rating) / Σ|Similarity|
```

**Contoh Perhitungan:**

Andi belum review **Museum C**. Sistem akan prediksi:

**Data dari user mirip:**
- Budi (similarity=0.88) → Rating Museum C = 4
- Cici (similarity=0.75) → Rating Museum C = 5
- Dedi (similarity=0.45) → Rating Museum C = 3

**Perhitungan:**
```
Weighted Sum = (0.88 × 4) + (0.75 × 5) + (0.45 × 3)
             = 3.52 + 3.75 + 1.35
             = 8.62

Similarity Sum = |0.88| + |0.75| + |0.45|
                = 2.08

Predicted Rating = 8.62 / 2.08 ≈ 4.14
```

**Artinya:** Sistem memprediksi Andi akan memberikan rating **4.14** untuk Museum C.

---

### **Langkah 6: Menampilkan Rekomendasi**

Sistem mengurutkan semua museum berdasarkan **predicted rating** (dari tertinggi ke terendah) dan menampilkannya:

```
Rekomendasi untuk Andi:
1. Museum C  → Prediksi: 4.14/5.00 (Confidence: 2.08)
2. Museum E  → Prediksi: 3.85/5.00 (Confidence: 1.95)
3. Museum F  → Prediksi: 3.50/5.00 (Confidence: 1.20)
...
```

**Tingkat Keyakinan (Confidence):**
- Semakin tinggi nilai confidence, semakin banyak user mirip yang mereview museum tersebut
- Confidence tinggi = Rekomendasi lebih dapat dipercaya

---

## 🎯 Contoh Skenario Lengkap

### **Situasi Awal:**
```
Database memiliki:
- User "Andi" sudah review: Museum A (5), Museum B (4)
- User "Budi" sudah review: Museum A (4), Museum B (5), Museum C (4)
- User "Cici" sudah review: Museum A (5), Museum C (5), Museum D (4)
```

### **Andi Memberikan Rating Baru:**
```
Andi memberikan rating Museum D = 3
```

### **Proses Rekomendasi:**

1. **Update Matrix:**
   ```
   Andi sekarang: Museum A(5), Museum B(4), Museum D(3)
   ```

2. **Hitung Similarity:**
   - Andi vs Budi: Similarity = 0.85 (keduanya suka Museum A & B)
   - Andi vs Cici: Similarity = 0.92 (keduanya suka Museum A & D)

3. **Prediksi untuk Museum C (belum direview Andi):**
   - Budi (similarity=0.85) → Rating Museum C = 4
   - Cici (similarity=0.92) → Rating Museum C = 5
   
   ```
   Predicted = (0.85×4 + 0.92×5) / (0.85+0.92)
             = (3.4 + 4.6) / 1.77
             = 4.52
   ```

4. **Hasil Rekomendasi:**
   ```
   Museum C direkomendasikan dengan prediksi rating 4.52/5.00
   ```

---

## ⚙️ Fitur Algoritma

### **1. Cosine Similarity**
- Mengukur kemiripan berdasarkan sudut antara vektor rating
- Range: 0 (tidak mirip) sampai 1 (sangat mirip)
- Tidak terpengaruh oleh panjang vektor (jumlah review)

### **2. Weighted Average**
- Rating dari user yang lebih mirip memiliki bobot lebih besar
- Mencegah bias dari user yang tidak relevan

### **3. Top-K Similar Users**
- Hanya menggunakan 10 user paling mirip (untuk efisiensi)
- Mengurangi noise dari user yang tidak relevan

### **4. Confidence Score**
- Mengukur seberapa banyak user mirip yang mereview museum
- Semakin tinggi confidence, semakin dapat dipercaya

---

## 📊 Visualisasi Proses

```
┌─────────────────────────────────────────────────┐
│  1. User Memberikan Rating                      │
│     ↓                                           │
│  2. Build User-Item Matrix                      │
│     ↓                                           │
│  3. Calculate Cosine Similarity                 │
│     (Cari user yang mirip)                      │
│     ↓                                           │
│  4. Find Top 10 Similar Users                  │
│     ↓                                           │
│  5. Predict Rating untuk Museum                 │
│     (yang belum direview)                       │
│     ↓                                           │
│  6. Sort by Predicted Rating                   │
│     ↓                                           │
│  7. Tampilkan Rekomendasi                       │
└─────────────────────────────────────────────────┘
```

---

## 💡 Kelebihan Sistem Ini

1. **Personalized**: Rekomendasi disesuaikan dengan preferensi Anda
2. **Learning**: Semakin banyak review, semakin akurat rekomendasi
3. **Scalable**: Dapat menangani banyak user dan museum
4. **Real-time**: Rekomendasi langsung muncul setelah memberikan review

---

## ⚠️ Catatan Penting

1. **Minimal Review**: Setidaknya perlu 1 review untuk mendapatkan rekomendasi
2. **Common Items**: Perlu ada museum yang direview oleh beberapa user untuk menghitung similarity
3. **Cold Start**: User baru mungkin tidak langsung mendapat rekomendasi yang akurat
4. **Data Quality**: Semakin banyak data rating, semakin baik kualitas rekomendasi

---

## 🔍 Kode Implementasi

Algoritma ini diimplementasikan di file `recommendations.php` dengan fungsi utama:

- `cosineSimilarity()` - Menghitung kemiripan antar user
- Perhitungan weighted average untuk prediksi rating
- Sorting dan filtering untuk menampilkan rekomendasi terbaik

---

**Dibuat untuk membantu memahami sistem rekomendasi Collaborative Filtering User-based**
