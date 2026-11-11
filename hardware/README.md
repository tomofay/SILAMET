# Panduan Hardware Gate Security UHF RFID & Relay Sirine — SILAMET

Dokumen ini merupakan panduan implementasi teknis integrasi perangkat keras pintu gerbang laboratorium (*Gate Security*) sesuai rancangan pada `prd.md`, `logic.md`, dan `DATABASE_SCHEMA.md`.

---

## 1. Spesifikasi Perangkat Keras

| Perangkat | Model / Spesifikasi | Antarmuka / Protokol | Fungsi Utama |
|---|---|---|---|
| **RFID Reader Gate** | HW-VX6330 Integrated Reader (Antena 9 dBi Circular) | RS232 / USB Virtual COM (Baudrate 57600) | Membaca EPC Tag ISO18000-6C dalam radius 1 - 4 meter |
| **Microcontroller / Relay** | Modul Relay 1 Channel 5V / Optocoupler | Serial DTR/RTS pin / USB Relay / Arduino | Memutus/menyambung daya Sirine 12V DC |
| **Sirine Alarm** | Sirine Klakson Mini 12V DC (105-115 dB) | Jalur DC 12V via Kontak NO (Normally Open) Relay | Peringatan audio jika ada barang keluar tanpa izin |
| **Kamera Pengawas** | USB Webcam HD 1080p / 720p | USB UVC (DirectShow / V4L2) | Mengambil foto wajah siswa pembawa barang ilegal |
| **Power Supply** | Adaptor DC 12V 3A - 5A | Jack DC 5.5mm | Catu daya reader HW-VX6330 dan Sirine |

---

## 2. Diagram Perkabelan (*Wiring Diagram*)

```
[ Adaptor 12V DC ] (+) --------------------------+
                   (-) --------------+           |
                                     |           |
                                     v           v
[ HW-VX6330 Reader ] <--- Daya ---> GND         VCC
   |
   +--> RX/TX (RS232) ---> [ USB-to-RS232 Converter ] ---> PC Server Lab (COM3)
                                     |
                                   (DTR/RTS)
                                     |
                                     v
                           [ Modul Relay 5V/12V ]
                               [ NO ]     [ COM ]
                                 |           |
                                 v           +---> (+) Adaptor 12V
                         (+) [ Sirine 12V ]
                         (-) ---------------> (-) Adaptor 12V
```

---

## 3. Instalasi dan Persiapan Lingkungan Python

1. Masuk ke direktori hardware:
   ```bash
   cd hardware
   ```
2. Pasang dependensi python:
   ```bash
   pip install -r requirements.txt
   ```

---

## 4. Menjalankan Daemon Produksi

Jalankan script daemon listener gate:
```bash
python gate_listener.py --port COM3 --baud 57600 --api http://127.0.0.1:8000/api/v1/gate/check-tag --camera 0
```

### Parameter yang Tersedia:
- `--port`: Port serial reader (contoh di Windows: `COM3`, di Linux: `/dev/ttyUSB0`).
- `--baud`: Kecepatan baudrate reader (default: `57600`).
- `--api`: URL endpoint validasi tag gate pada Laravel backend SILAMET.
- `--camera`: Indeks webcam USB pintu (default: `0`).
- `--debounce`: Waktu interval pengabaian tag yang sama agar tidak spam request (default: `2.0` detik).

---

## 5. Menjalankan Pengujian Virtual (Simulator Tanpa Hardware)

Jika reader fisik belum tersambung ke PC, Anda dapat menguji seluruh skenario validasi gerbang menggunakan simulator interaktif:

```bash
python simulate_gate.py
```

Pilihan menu simulator:
1. **Masukkan EPC Tag Manual**: Mengirimkan kode EPC langsung ke server untuk melihat respons ALLOWED atau DENIED.
2. **Simulasi Tag Legal**: Mensimulasikan barang yang sedang dalam masa peminjaman aktif.
3. **Simulasi Tag Ilegal**: Mensimulasikan barang berstatus 'tersedia' yang nyelonong dibawa keluar lab (memicu sirine dan kamera).
4. **Simulasi Tag Asing**: Tag tidak terdaftar dalam inventaris SILAMET.
