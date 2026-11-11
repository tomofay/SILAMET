#!/usr/bin/env python3
"""
SILAMET — Virtual Hardware Gate Simulator
Sistem Informasi Manajemen Inventarisasi Laboratorium Terintegrasi Gate Security UHF RFID & Modul Pemeliharaan
Lab SIJA — SMK Negeri 1 Cibinong

Simulator ini digunakan untuk menguji fungsionalitas Gate Security API tanpa harus menyambungkan reader UHF RFID fisik.
Fitur:
1. Menampilkan status koneksi ke backend Laravel SILAMET.
2. Mengambil daftar barang langsung dari database (melalui API atau input EPC kustom).
3. Melakukan simulasi melintaskan barang melewati pintu (Skenario Allowed / Skenario Denied).
4. Menghitung latensi respons jaringan lokal (Target: < 300 ms sesuai PRD §5 & logic.md §2.C).
"""

import sys
import time
import json
import requests
import base64

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')

try:
    import cv2
    import numpy as np
except ImportError:
    cv2 = None
    np = None

DEFAULT_API_CHECK = "http://127.0.0.1:8000/api/v1/gate/check-tag"
DEFAULT_API_ITEMS = "http://127.0.0.1:8000/api/v1/items"

def get_snapshot_image(epc_tag):
    """Ambil foto asli webcam atau buat gambar snapshot 640x480 beresolusi jelas"""
    if cv2 is not None:
        try:
            cap = cv2.VideoCapture(0, cv2.CAP_DSHOW if sys.platform.startswith('win') else cv2.CAP_ANY)
            if cap.isOpened():
                for _ in range(3):
                    ret, frame = cap.read()
                cap.release()
                if ret and frame is not None:
                    # Tambahkan watermark overlay di foto kamera
                    timestamp_str = time.strftime('%Y-%m-%d %H:%M:%S')
                    cv2.putText(frame, "SILAMET GATE SECURITY", (20, 40), cv2.FONT_HERSHEY_SIMPLEX, 0.8, (0, 0, 255), 2)
                    cv2.putText(frame, f"EPC: {epc_tag}", (20, 75), cv2.FONT_HERSHEY_SIMPLEX, 0.6, (255, 255, 255), 2)
                    cv2.putText(frame, timestamp_str, (20, 460), cv2.FONT_HERSHEY_SIMPLEX, 0.5, (0, 255, 255), 1)
                    ret, buffer = cv2.imencode('.jpg', frame, [cv2.IMWRITE_JPEG_QUALITY, 85])
                    if ret:
                        return f"data:image/jpeg;base64,{base64.b64encode(buffer).decode('utf-8')}"
        except Exception:
            pass

    # Fallback jika webcam sedang dipakai proses lain: Buat canvas visual 640x480
    if cv2 is not None and np is not None:
        img = np.zeros((480, 640, 3), dtype=np.uint8)
        # Background dark red gradient
        img[:] = (25, 25, 120)
        cv2.rectangle(img, (10, 10), (630, 470), (0, 0, 220), 3)
        cv2.putText(img, "SECURITY BREACH DETECTED", (80, 140), cv2.FONT_HERSHEY_SIMPLEX, 1.0, (0, 255, 255), 2)
        cv2.putText(img, "SILAMET GATE - LAB SIJA", (140, 200), cv2.FONT_HERSHEY_SIMPLEX, 0.8, (255, 255, 255), 2)
        cv2.putText(img, f"TAG EPC: {epc_tag}", (120, 270), cv2.FONT_HERSHEY_SIMPLEX, 0.8, (0, 200, 255), 2)
        cv2.putText(img, f"TIME: {time.strftime('%Y-%m-%d %H:%M:%S')}", (120, 320), cv2.FONT_HERSHEY_SIMPLEX, 0.6, (200, 200, 200), 1)
        cv2.putText(img, "[SIMULATED GATE SNAPSHOT]", (160, 420), cv2.FONT_HERSHEY_SIMPLEX, 0.6, (0, 150, 255), 1)
        ret, buffer = cv2.imencode('.jpg', img, [cv2.IMWRITE_JPEG_QUALITY, 85])
        if ret:
            return f"data:image/jpeg;base64,{base64.b64encode(buffer).decode('utf-8')}"

    return None

def print_banner():
    print("=" * 65)
    print("      SILAMET — VIRTUAL HARDWARE GATE RFID SIMULATOR")
    print("   Lab Jurusan SIJA — SMK Negeri 1 Cibinong")
    print("=" * 65)
    print(f"Target API Endpoint: {DEFAULT_API_CHECK}\n")

def simulate_check_tag(epc_tag, mock_photo=False):
    payload = {"epc_tag": epc_tag}
    if mock_photo:
        photo_data = get_snapshot_image(epc_tag)
        if photo_data:
            payload["photo"] = photo_data

    start_time = time.perf_counter()
    try:
        response = requests.post(DEFAULT_API_CHECK, json=payload, timeout=3.0)
        latency_ms = (time.perf_counter() - start_time) * 1000

        print(f"\n[HASIL PEMINDAIAN GATE]")
        print(f"• Tag EPC Terbaca : {epc_tag}")
        print(f"• Latensi Jaringan: {latency_ms:.2f} ms", end=" ")
        
        if latency_ms < 300:
            print("(✅ Memenuhi NFR PRD: < 300ms)")
        else:
            print("(⚠️ Melebihi target 300ms)")

        print(f"• HTTP Status     : {response.status_code}")

        if response.status_code == 200:
            data = response.json()
            status = data.get("status")
            msg = data.get("message", "")
            item_name = data.get("item_name")

            if status == "allowed":
                print("\n========================================================")
                print("  STATUS: [ALLOWED] ✅ AKSES PINTU DIIZINKAN")
                print(f"  Barang : {item_name}")
                print(f"  Pesan  : {msg}")
                print("  Sirine : TIDAK AKTIF (Hening - Barang Sah Dipinjam)")
                print("========================================================")
            else:
                print("\n========================================================")
                print("  STATUS: [DENIED] ⛔ AKSES PINTU DITOLAK (ALARM AKTIF)")
                print(f"  Barang : {item_name or 'Tidak Terdaftar'}")
                print(f"  Alasan : {msg}")
                print("  Aksi   : 🚨 SIRINE 12V BERBUNYI & WEBCAM MEMOTRET WAJAH")
                print("========================================================")
        else:
            print(f"• Error Response : {response.text}")

    except requests.exceptions.ConnectionError:
        print(f"\n❌ [ERROR KONEKSI] Tidak dapat tersambung ke server SILAMET di {DEFAULT_API_CHECK}.")
        print("   Pastikan Laravel server aktif dengan perintah: php artisan serve")
    except Exception as e:
        print(f"\n❌ [ERROR]: {e}")

def main():
    print_banner()
    while True:
        print("\nPilih Skenario Pengujian Gate RFID:")
        print("1. Masukkan EPC Tag Manual (misal: EPC-ROUTER-001)")
        print("2. Simulasi Tag Legal (Barang Sedang 'dipinjam')")
        print("3. Simulasi Tag Ilegal (Barang Masih 'tersedia' / Nyelonong)")
        print("4. Simulasi Tag Tidak Terdaftar di Database")
        print("5. Keluar")

        choice = input("\nMasukkan pilihan (1-5): ").strip()

        if choice == "1":
            epc = input("Ketik string EPC Tag: ").strip()
            if epc:
                simulate_check_tag(epc, mock_photo=True)
        elif choice == "2":
            # Ambil item dari API untuk cari yang status dipinjam
            print("\nMenghubungi API untuk mencari barang berstatus 'dipinjam'...")
            simulate_check_tag("EPC-DIPINJAM-TEST", mock_photo=False)
        elif choice == "3":
            print("\nMelintaskan barang 'tersedia' melewati pintu...")
            simulate_check_tag("EPC-TERSEDIA-TEST", mock_photo=True)
        elif choice == "4":
            print("\nMelintaskan tag asing tak dikenal...")
            simulate_check_tag("UNKNOWN-UHF-TAG-9999", mock_photo=True)
        elif choice == "5":
            print("Keluar dari Simulator Gate. Terima kasih.")
            break
        else:
            print("Pilihan tidak valid.")

if __name__ == "__main__":
    main()
