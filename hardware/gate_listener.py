#!/usr/bin/env python3
"""
SILAMET — HW-VX6330 UHF RFID Gate Security Listener Daemon
Sistem Informasi Manajemen Inventarisasi Laboratorium Terintegrasi Gate Security UHF RFID & Modul Pemeliharaan
Lab SIJA — SMK Negeri 1 Cibinong

Tugas Daemon:
1. Membaca Serial Port (RS232/USB Converter CH340/FTDI) dari Integrated Reader HW-VX6330.
2. Mendukung Answer Mode (Inventory_G2 polling) & Active Mode dengan multi-tag unpacking.
3. Debouncing pintar agar meletakkan banyak tag di atas sensor tidak membuat sistem freeze.
4. Mengirim request HTTP POST ke backend SILAMET: /api/v1/gate/check-tag secara asinkron.
5. Jika status == 'denied':
   - Mengaktifkan Pin Relay Sirine 12V (DTR/RTS) secara single-instance thread-safe.
   - Mengambil frame snapshot dari Webcam USB dengan throttling & caching foto.
"""

import re
import sys
import json
import time
import base64
import logging
import argparse
import threading
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from urllib.parse import urlparse
from concurrent.futures import ThreadPoolExecutor
import requests

try:
    import serial
    import serial.tools.list_ports
except ImportError:
    serial = None

try:
    import cv2
except ImportError:
    cv2 = None

# Konfigurasi Logging
logging.basicConfig(
    level=logging.INFO,
    format='[%(asctime)s] [%(levelname)s] [GATE-DAEMON] %(message)s',
    datefmt='%Y-%m-%d %H:%M:%S'
)
logger = logging.getLogger("SILAMET-Gate")

class ReusableThreadingHTTPServer(ThreadingHTTPServer):
    allow_reuse_address = True
    daemon_threads = True


class SharedCameraRequestHandler(BaseHTTPRequestHandler):
    """HTTP handler ringan untuk berbagi 1 kamera fisik ke Kiosk web (MJPEG stream & snapshot)."""

    def log_message(self, format, *args):
        # Hindari spam access log ke konsol serial reader.
        return

    def _send_cors_headers(self):
        self.send_header("Access-Control-Allow-Origin", "*")
        self.send_header("Access-Control-Allow-Methods", "GET, OPTIONS")
        self.send_header("Access-Control-Allow-Headers", "Content-Type, Accept")
        self.send_header("Access-Control-Allow-Private-Network", "true")
        self.send_header("Cache-Control", "no-store, no-cache, must-revalidate, max-age=0")

    def _send_json(self, status_code, payload):
        body = json.dumps(payload).encode("utf-8")
        self.send_response(status_code)
        self._send_cors_headers()
        self.send_header("Content-Type", "application/json; charset=utf-8")
        self.send_header("Content-Length", str(len(body)))
        self.end_headers()
        self.wfile.write(body)

    def do_OPTIONS(self):
        self.send_response(204)
        self._send_cors_headers()
        self.send_header("Content-Length", "0")
        self.end_headers()

    def do_GET(self):
        daemon = getattr(self.server, "gate_daemon", None)
        path = urlparse(self.path).path

        if daemon is None:
            self._send_json(503, {"success": False, "message": "Daemon kamera belum siap."})
            return

        if path == "/health":
            ready, frame_age_ms = daemon.get_camera_status()
            self._send_json(200, {
                "success": True,
                "camera_index": daemon.camera_index,
                "camera_ready": ready,
                "frame_age_ms": frame_age_ms,
            })
            return

        if path == "/snapshot":
            _, photo_b64 = daemon.get_latest_jpeg(max_age=2.0, quality=85)
            if not photo_b64:
                self._send_json(503, {
                    "success": False,
                    "message": "Kamera belum memiliki frame aktif.",
                })
                return

            self._send_json(200, {
                "success": True,
                "photo": photo_b64,
            })
            return

        if path == "/snapshot.jpg":
            jpeg_bytes, _ = daemon.get_latest_jpeg(max_age=2.0, quality=85)
            if not jpeg_bytes:
                self._send_json(503, {"success": False, "message": "Frame kamera belum tersedia."})
                return

            self.send_response(200)
            self._send_cors_headers()
            self.send_header("Content-Type", "image/jpeg")
            self.send_header("Content-Length", str(len(jpeg_bytes)))
            self.end_headers()
            self.wfile.write(jpeg_bytes)
            return

        if path == "/stream.mjpg":
            self.send_response(200)
            self._send_cors_headers()
            self.send_header("Content-Type", "multipart/x-mixed-replace; boundary=SILAMETFRAME")
            self.end_headers()

            try:
                while not daemon.stop_event.is_set():
                    jpeg_bytes, _ = daemon.get_latest_jpeg(max_age=2.5, quality=75)
                    if jpeg_bytes:
                        part_header = (
                            b"--SILAMETFRAME\r\n"
                            b"Content-Type: image/jpeg\r\n"
                            + f"Content-Length: {len(jpeg_bytes)}\r\n\r\n".encode("ascii")
                        )
                        self.wfile.write(part_header)
                        self.wfile.write(jpeg_bytes)
                        self.wfile.write(b"\r\n")
                        self.wfile.flush()
                    time.sleep(0.066)
            except (BrokenPipeError, ConnectionResetError, ConnectionAbortedError, OSError):
                pass
            return

        self._send_json(404, {"success": False, "message": "Endpoint tidak ditemukan."})


class GateSecurityListener:
    def __init__(self, port="COM2", baudrate=57600, api_url="http://127.0.0.1:8000/api/v1/gate/check-tag",
                 camera_index=0, debounce_sec=4.0, mode="answer", poll_interval=0.1,
                 relay_port="COM4", relay_baud=9600,
                 camera_host="127.0.0.1", camera_port=8765, batch_window=0.15):
        self.port = port
        self.baudrate = baudrate
        self.api_url = api_url
        self.camera_index = camera_index
        self.debounce_sec = debounce_sec
        self.mode = mode.lower()  # 'answer' atau 'active'
        self.poll_interval = poll_interval
        self.relay_port = relay_port
        self.relay_baud = relay_baud
        self.camera_host = camera_host
        self.camera_port = camera_port
        self.batch_window = max(0.0, float(batch_window))

        self.last_scans = {}  # {epc: last_time}
        self.pending_epcs = []
        self.batch_timer = None

        self.ser = None
        self.relay_ser = None
        self.cap = None
        self.camera_http_server = None
        self._thread_local = threading.local()
        self.executor = ThreadPoolExecutor(max_workers=4)
        self.stop_event = threading.Event()

        # Thread safety locks & state
        self.serial_lock = threading.Lock()
        self.relay_lock = threading.Lock()
        self.camera_lock = threading.Lock()
        self.frame_lock = threading.Lock()
        self.encode_lock = threading.Lock()
        self.alarm_lock = threading.Lock()
        self.scan_lock = threading.Lock()

        # Alarm state machine
        self.alarm_active = False
        self.alarm_until = 0.0

        # Live camera frame & RAM JPEG cache (untuk instant snapshot gate + Kiosk MJPEG stream)
        self.latest_frame = None
        self.latest_frame_time = 0.0
        self._cached_frame_time = 0.0
        self._cached_jpeg_bytes = None
        self._cached_photo_b64 = None
        self.last_camera_retry = 0.0

    def get_http_session(self):
        """Ambil HTTP Session terisolasi per worker thread agar thread-safe."""
        sess = getattr(self._thread_local, "session", None)
        if sess is None:
            sess = requests.Session()
            self._thread_local.session = sess
        return sess

    def init_relay(self):
        """Inisialisasi Serial Port ke Arduino Relay Buzzer Controller (COM4 - 9600 baud)"""
        if not self.relay_port or self.relay_port.lower() == "none":
            logger.info("Arduino Relay Port dinonaktifkan (mode 'none').")
            return False

        if serial is None:
            logger.warning("Modul pyserial tidak tersedia untuk Arduino Relay.")
            return False

        try:
            logger.info(f"Menghubungkan ke Arduino Relay Buzzer di {self.relay_port} ({self.relay_baud} baud)...")
            self.relay_ser = serial.Serial(self.relay_port, self.relay_baud, timeout=1.0)
            # Beri jeda 1.5 detik agar Arduino selesai auto-reset DTR
            time.sleep(1.5)
            # Baca pesan sambutan jika ada
            try:
                if self.relay_ser.in_waiting > 0:
                    welcome_msg = self.relay_ser.readline().decode('utf-8', errors='ignore').strip()
                    if welcome_msg:
                        logger.info(f"Arduino Respon: {welcome_msg}")
            except Exception:
                pass

            logger.info(f"✅ [ARDUINO RELAY] Sukses terhubung ke Arduino Buzzer di {self.relay_port}!")
            return True
        except Exception as e:
            logger.warning(f"⚠️ Gagal menghubungkan ke Arduino Relay di {self.relay_port}: {e}")
            logger.info("Listener tetap berjalan dengan fallback DTR/RTS reader.")
            return False

    def init_camera(self):
        """Inisialisasi OpenCV Webcam untuk snapshot gate + warm-up sensor"""
        if cv2 is None:
            logger.warning("Modul opencv-python tidak terpasang. Snapshot kamera gate dinonaktifkan.")
            return False
        try:
            if self.cap is not None:
                try:
                    self.cap.release()
                except Exception:
                    pass
                self.cap = None

            logger.info(f"Membuka Webcam Gate pada indeks {self.camera_index}...")
            backend = cv2.CAP_DSHOW if sys.platform.startswith('win') else cv2.CAP_ANY
            self.cap = cv2.VideoCapture(self.camera_index, backend)
            if self.cap.isOpened():
                self.cap.set(cv2.CAP_PROP_FRAME_WIDTH, 640)
                self.cap.set(cv2.CAP_PROP_FRAME_HEIGHT, 480)
                try:
                    self.cap.set(cv2.CAP_PROP_BUFFERSIZE, 1)
                except Exception:
                    pass
                # Buang 5 frame pemanasan agar auto-exposure siap & frame pertama tidak hitam/delay lama
                for _ in range(5):
                    self.cap.read()
                logger.info("Webcam Gate berhasil terhubung dan siap menangkap snapshot.")
                return True
            else:
                logger.warning(f"Tidak dapat membuka kamera pada index {self.camera_index}.")
                return False
        except Exception as e:
            logger.warning(f"Error inisialisasi kamera: {e}")
            return False

    def capture_photo_throttled(self):
        """
        Ambil snapshot JPEG dari frame kamera live di RAM (<3ms, tanpa stall OpenCV).
        """
        _, photo_b64 = self.get_latest_jpeg(max_age=2.0, quality=80)
        return photo_b64

    def get_camera_status(self):
        """Cek ketersediaan frame live webcam untuk probe /health Kiosk."""
        now = time.time()
        with self.frame_lock:
            has_frame = self.latest_frame is not None and self.latest_frame_time > 0.0
            age_sec = (now - self.latest_frame_time) if has_frame else None

        if not has_frame or age_sec is None or age_sec > 3.0:
            return False, None

        return True, int(age_sec * 1000)

    def get_latest_jpeg(self, max_age=2.0, quality=80):
        """Encode frame terbaru di RAM menjadi bytes JPEG + Data URL base64 dengan caching per frame."""
        now = time.time()
        with self.frame_lock:
            frame = self.latest_frame
            frame_time = self.latest_frame_time

        if frame is None or (now - frame_time) > max_age:
            return None, None

        with self.encode_lock:
            if (
                self._cached_frame_time == frame_time
                and self._cached_jpeg_bytes is not None
                and self._cached_photo_b64 is not None
            ):
                return self._cached_jpeg_bytes, self._cached_photo_b64

            if cv2 is None:
                return None, None

            try:
                ok, buf = cv2.imencode('.jpg', frame, [cv2.IMWRITE_JPEG_QUALITY, quality])
                if ok and buf is not None:
                    jpeg_bytes = buf.tobytes()
                    b64_str = base64.b64encode(jpeg_bytes).decode('ascii')
                    data_url = f"data:image/jpeg;base64,{b64_str}"
                    self._cached_frame_time = frame_time
                    self._cached_jpeg_bytes = jpeg_bytes
                    self._cached_photo_b64 = data_url
                    return jpeg_bytes, data_url
            except Exception as e:
                logger.debug(f"Gagal encode JPEG frame kamera: {e}")

        return None, None

    def _camera_grabber_loop(self):
        """Thread dedicated pembaca frame webcam terus-menerus agar bebas buffer DirectShow & siap instan."""
        if cv2 is None:
            return

        consecutive_failures = 0
        while not self.stop_event.is_set():
            cap = self.cap
            if cap is None or not cap.isOpened():
                if self.stop_event.wait(2.0):
                    break
                with self.camera_lock:
                    self.init_camera()
                consecutive_failures = 0
                continue

            try:
                t0 = time.time()
                ret, frame = cap.read()
                elapsed = time.time() - t0
            except Exception:
                ret, frame, elapsed = False, None, 0.0

            if ret and frame is not None:
                consecutive_failures = 0
                with self.frame_lock:
                    self.latest_frame = frame
                    self.latest_frame_time = time.time()
                # Bila driver tidak mem-pacing FPS, beri jeda ringan agar CPU tetap dingin.
                if elapsed < 0.025:
                    time.sleep(0.025 - elapsed)
            else:
                consecutive_failures += 1
                if consecutive_failures >= 15:
                    logger.warning("Koneksi webcam terputus atau gagal membaca frame. Mencoba buka ulang...")
                    with self.camera_lock:
                        self.init_camera()
                    consecutive_failures = 0
                    if self.stop_event.wait(1.5):
                        break
                else:
                    time.sleep(0.05)

    def start_camera_services(self):
        """Mulai thread grabber live webcam dan server HTTP lokal untuk Kiosk (/stream.mjpg & /snapshot)."""
        if cv2 is not None:
            with self.camera_lock:
                self.init_camera()
            threading.Thread(target=self._camera_grabber_loop, daemon=True, name="CameraGrabber").start()

        if self.camera_port and self.camera_port > 0:
            try:
                server = ReusableThreadingHTTPServer((self.camera_host, self.camera_port), SharedCameraRequestHandler)
                server.gate_daemon = self
                self.camera_http_server = server
                threading.Thread(target=server.serve_forever, daemon=True, name="SharedCameraHTTP").start()
                logger.info(
                    f"📷 [CAMERA HUB AKTIF] Shared webcam Kiosk & Gate siap di "
                    f"http://{self.camera_host}:{self.camera_port} (/stream.mjpg, /snapshot)"
                )
            except OSError as e:
                logger.warning(
                    f"Port Camera Hub {self.camera_host}:{self.camera_port} tidak tersedia ({e}). "
                    "Gate RFID tetap berjalan normal."
                )

    def _send_arduino_trigger(self):
        """Kirim byte '1' ke Arduino Uno di COM4 untuk memicu relay buzzer 2000ms"""
        with self.relay_lock:
            if self.relay_ser and self.relay_ser.is_open:
                try:
                    self.relay_ser.write(b'1')
                    logger.warning("⚡ [ARDUINO RELAY COM4] Sinyal '1' dikirim ke Arduino (Buzzer Alarm 2 Detik)")
                    # Baca respon status Arduino secara non-blocking jika ada
                    try:
                        time.sleep(0.05)
                        if self.relay_ser.in_waiting > 0:
                            resp = self.relay_ser.readline().decode('utf-8', errors='ignore').strip()
                            if resp:
                                logger.info(f"Arduino Event: {resp}")
                    except Exception:
                        pass
                except Exception as e:
                    logger.error(f"Gagal kirim sinyal ke Arduino di {self.relay_port}: {e}")

    def trigger_relay_alarm(self, duration_sec=3.0):
        """
        Aktifkan relay sirine/buzzer secara aman tanpa saling tumpang tindih.
        1. Mengirim karakter '1' ke Arduino Uno Relay Buzzer di COM4.
        2. Mengaktifkan DTR/RTS reader COM2 sebagai redundansi fallback ganda.
        """
        # Kirim sinyal alarm '1' ke Arduino Uno
        threading.Thread(target=self._send_arduino_trigger, daemon=True).start()

        now = time.time()
        with self.alarm_lock:
            self.alarm_until = max(self.alarm_until, now + duration_sec)
            if self.alarm_active:
                # Alarm sudah berjalan, durasi diperpanjang
                return

            self.alarm_active = True
            logger.warning(f"🚨 [ALARM SIRINE AKTIF] Menyalakan Sirine/Buzzer selama {duration_sec} detik!")
            threading.Thread(target=self._alarm_worker, daemon=True).start()

    def _alarm_worker(self):
        """Worker background yang menjaga pin relay sirine tetap aktif sampai alarm_until"""
        try:
            # Nyalakan pin relay (DTR/RTS) reader dengan perlindungan lock
            with self.serial_lock:
                if self.ser and self.ser.is_open:
                    try:
                        self.ser.dtr = True
                        self.ser.rts = True
                    except Exception:
                        pass

            while True:
                time.sleep(0.1)
                with self.alarm_lock:
                    if time.time() >= self.alarm_until:
                        break

            # Matikan pin relay reader
            with self.serial_lock:
                if self.ser and self.ser.is_open:
                    try:
                        self.ser.dtr = False
                        self.ser.rts = False
                    except Exception:
                        pass
            logger.info("🚨 [ALARM SIRINE MATI] Kondisi pintu kembali normal.")
        except Exception as e:
            logger.error(f"Error kontrol relay sirine: {e}")
        finally:
            with self.alarm_lock:
                self.alarm_active = False

    def process_batch_worker(self, epc_tags):
        """Worker async terpadu untuk 1 atau banyak EPC tag sekaligus (single/multi-tag burst)."""
        if not epc_tags:
            return

        start_time = time.time()
        http_session = self.get_http_session()
        photo_base64 = self.capture_photo_throttled()

        try:
            payload = {"epc_tags": epc_tags} if len(epc_tags) > 1 else {"epc_tag": epc_tags[0]}
            if photo_base64:
                payload["photo"] = photo_base64

            res = http_session.post(self.api_url, json=payload, timeout=4.0)
            latency_ms = (time.time() - start_time) * 1000

            if res.status_code == 200:
                data = res.json()
                status = data.get("status")
                msg = data.get("message", "")
                item_name = data.get("item_name") or "Unknown"
                denied_gate_log_ids = data.get("denied_gate_log_ids") or []
                if not denied_gate_log_ids and data.get("gate_log_id") is not None:
                    denied_gate_log_ids = [data.get("gate_log_id")]

                if status == "allowed":
                    if len(epc_tags) > 1:
                        logger.info(f"✅ [AKSES DIIZINKAN] ({latency_ms:.1f}ms) {len(epc_tags)} tag berstatus DIPINJAM ({item_name}). Silakan lewat.")
                    else:
                        logger.info(f"✅ [AKSES DIIZINKAN] ({latency_ms:.1f}ms) Barang: '{item_name}' (Status: DIPINJAM). Silakan lewat.")
                else:
                    if len(epc_tags) > 1:
                        logger.warning(f"⛔ [BURST AKSES DITOLAK] ({latency_ms:.1f}ms) {msg} [Item: {item_name}]")
                    else:
                        item_display = f"Barang: '{item_name}' — " if item_name and item_name != "Unknown" else ""
                        logger.warning(f"⛔ [AKSES DITOLAK] ({latency_ms:.1f}ms) {item_display}{msg}")

                    # Bunyikan alarm sirine segera
                    self.trigger_relay_alarm(duration_sec=3.0)

                    if data.get("photo_attached"):
                        logger.info(f"📸 Foto pelanggar & notifikasi Telegram langsung diproses untuk {len(denied_gate_log_ids)} log.")
                    elif data.get("trigger_capture"):
                        # Fallback Stage-2 bila kamera belum siap saat Stage-1 berlangsung
                        time.sleep(0.15)
                        retry_photo = self.capture_photo_throttled()
                        if retry_photo and denied_gate_log_ids:
                            try:
                                payload_with_photo = {
                                    "gate_log_ids": denied_gate_log_ids,
                                    "photo": retry_photo,
                                }
                                photo_res = http_session.post(self.api_url, json=payload_with_photo, timeout=5.0)
                                if photo_res.status_code == 200:
                                    logger.info(f"📸 Foto pelanggar susulan tercatat untuk {len(denied_gate_log_ids)} tag.")
                                else:
                                    logger.warning(f"Upload foto gate ditolak server ({photo_res.status_code}): {photo_res.text}")
                            except Exception as e:
                                logger.warning(f"Gagal mengunggah foto gate susulan: {e}")
                        else:
                            logger.warning("Tidak ada frame webcam yang dapat dikirim untuk alert pelanggaran.")
            else:
                logger.error(f"Server API mengembalikan status {res.status_code}: {res.text}")

        except requests.exceptions.RequestException as e:
            logger.error(f"Gagal menghubungi server SILAMET API ({self.api_url}): {e}")

    def _flush_pending_batch(self):
        """Kirim seluruh EPC tag yang terkumpul dalam burst window ke worker thread."""
        with self.scan_lock:
            self.batch_timer = None
            batch = list(self.pending_epcs)
            self.pending_epcs.clear()

        if batch:
            logger.info(f"📡 Mengirim {len(batch)} tag dalam 1 batch ke API: {batch}")
            self.executor.submit(self.process_batch_worker, batch)

    def enqueue_epcs(self, epc_list):
        """Validasi debouncing dan agregasi multi-tag burst sebelum mengirim 1 request API."""
        if not epc_list:
            return

        now = time.time()
        accepted = []

        with self.scan_lock:
            if len(self.last_scans) > 100:
                self.last_scans = {k: v for k, v in self.last_scans.items() if (now - v) < 60.0}

            for raw_epc in epc_list:
                epc_tag = str(raw_epc).strip()
                if not epc_tag:
                    continue

                if epc_tag in self.last_scans and (now - self.last_scans[epc_tag]) < self.debounce_sec:
                    continue

                self.last_scans[epc_tag] = now
                if epc_tag not in self.pending_epcs:
                    self.pending_epcs.append(epc_tag)
                    accepted.append(epc_tag)

            if not accepted:
                return

            if self.batch_window <= 0 or len(self.pending_epcs) >= 25:
                if self.batch_timer is not None:
                    self.batch_timer.cancel()
                    self.batch_timer = None
                batch = list(self.pending_epcs)
                self.pending_epcs.clear()
                logger.info(f"📡 Mengirim {len(batch)} tag langsung ke API: {batch}")
                self.executor.submit(self.process_batch_worker, batch)
                return

            if self.batch_timer is None:
                timer = threading.Timer(self.batch_window, self._flush_pending_batch)
                timer.daemon = True
                self.batch_timer = timer
                timer.start()

    def process_epc(self, epc_tag):
        """Kompatibilitas tunggal: delegasikan ke antrean batch."""
        self.enqueue_epcs([epc_tag])

    def extract_epcs_and_consume(self, buffer):
        """
        Parser Frame HW-VX6330 dengan konsumsi buffer otomatis.
        Mendukung:
        1. Answer Mode (Inventory_G2):
           - Frame No-Tag: [05 00 01 FB CRC1 CRC2] -> dihapus dari buffer langsung
           - Frame Multi-Tag: [Len, 00, 01, Status(01-04), CardNum, Len1, EPC1..., Len2, EPC2..., CRC1, CRC2]
        2. Active Mode:
           - Frame: [07 00 EE 00] [EPC...]
        """
        found_epcs = []

        while len(buffer) >= 5:
            # 1. Cek pola Active Mode
            if buffer[:4] == b'\x07\x00\xEE\x00':
                if len(buffer) >= 9:
                    epc_bytes = buffer[4:9]
                    found_epcs.append(epc_bytes.hex().upper())
                    del buffer[:9]
                    continue
                else:
                    break

            # 2. Cek pola Answer Mode (HW-VX6330)
            pkt_len = buffer[0] + 1
            if buffer[1] == 0x00 and buffer[2] == 0x01:
                if pkt_len < 5 or pkt_len > 128:
                    # Frame corrupt / out of bounds, buang byte pertama untuk resinkronisasi
                    del buffer[0]
                    continue

                if len(buffer) < pkt_len:
                    # Paket belum lengkap di buffer serial, tunggu pembacaan berikutnya
                    break

                status = buffer[3]
                # Status 0x01..0x04 menandakan pembacaan tag berhasil
                if status in (0x01, 0x02, 0x03, 0x04) and pkt_len >= 7:
                    card_num = buffer[4]
                    curr = 5
                    for _ in range(card_num):
                        if curr < pkt_len - 2:
                            epc_len = buffer[curr]
                            curr += 1
                            if curr + epc_len <= pkt_len - 2:
                                epc_hex = bytes(buffer[curr:curr + epc_len]).hex().upper()
                                found_epcs.append(epc_hex)
                                curr += epc_len

                # Frame ini (baik NoTag 0xFB maupun Tag) telah selesai diproses.
                # HAPUS DARI BUFFER SEGERA agar tidak menghambat frame berikutnya!
                del buffer[:pkt_len]
                continue

            # 3. Jika bukan awal frame yang dikenal, cari kandidat header terdekat
            idx_ans = buffer.find(b'\x00\x01', 1)
            idx_act = buffer.find(b'\x07\x00\xEE\x00', 1)
            candidates = [i for i in (idx_ans - 1 if idx_ans > 0 else -1, idx_act) if i > 0]
            if candidates:
                del buffer[:min(candidates)]
            else:
                if len(buffer) > 4:
                    del buffer[:-4]
                break

        return found_epcs

    def run(self):
        """Loop Utama Listener"""
        logger.info("=" * 60)
        logger.info("  SILAMET — GATE SECURITY LISTENER DAEMON RUNNING")
        logger.info(f"  Port Serial: {self.port} | Baudrate: {self.baudrate}")
        logger.info(f"  Mode Operasi: {self.mode.upper()} MODE")
        logger.info(f"  Debounce Tag: {self.debounce_sec} detik | Burst Window: {self.batch_window * 1000:.0f}ms")
        logger.info(f"  API Endpoint: {self.api_url}")
        logger.info("=" * 60)

        self.start_camera_services()
        self.init_relay()

        if getattr(self, 'simulate', False):
            logger.info("⚡ [MODE SIMULASI AKTIF] Berjalan tanpa hardware reader fisik.")
            logger.info("Ketik 1 EPC atau banyak EPC dipisah koma/spasi (tekan Enter kosong untuk keluar):")
            try:
                while True:
                    sim_input = input("\n[SIMULASI] Masukkan EPC tag: ").strip()
                    if not sim_input:
                        break
                    sim_tags = [t.strip() for t in re.split(r'[\s,]+', sim_input) if t.strip()]
                    self.enqueue_epcs(sim_tags)
            except (KeyboardInterrupt, EOFError):
                logger.info("Simulasi dihentikan.")
            finally:
                self._cleanup()
            return

        if serial is None:
            logger.critical("Modul 'pyserial' belum terpasang. Jalankan: pip install -r hardware/requirements.txt")
            sys.exit(1)

        try:
            self.ser = serial.Serial(self.port, self.baudrate, timeout=0.05)
            logger.info(f"Berhasil membuka Serial Port {self.port}.")
        except Exception as e:
            logger.warning(f"Percobaan standar membuka {self.port} terkendala: {e}")
            logger.info(f"Mencoba membuka {self.port} dengan mode kompatibilitas driver CH340...")
            try:
                self.ser = serial.Serial()
                self.ser.port = self.port
                self.ser.baudrate = self.baudrate
                self.ser.timeout = 0.05
                self.ser._reconfigure_port = lambda force=False: None
                self.ser.open()
                logger.info(f"✅ Berhasil membuka Serial Port {self.port} via mode kompatibilitas CH340!")
            except Exception as e2:
                logger.error(f"Gagal membuka port serial {self.port}: {e2}")
                logger.info("Gunakan opsi --simulate jika ingin menguji tanpa kabel hardware reader.")
                return

        # Frame command HW-VX6330 untuk Answer Mode Inventory_G2
        cmd_inventory_hwvx = bytes([0x04, 0xFF, 0x01, 0x1B, 0xB4])

        buffer = bytearray()
        last_poll_time = 0.0

        try:
            while True:
                now = time.time()

                # Polling Answer Mode secara berkala
                if self.mode == "answer":
                    if (now - last_poll_time) >= self.poll_interval:
                        with self.serial_lock:
                            try:
                                # Kirim query command tanpa self.ser.flush() agar driver Windows CH340 tidak deadlock
                                self.ser.write(cmd_inventory_hwvx)
                            except Exception as write_err:
                                logger.debug(f"Gagal kirim command polling: {write_err}")
                        last_poll_time = now

                # Baca data serial yang tersedia
                data = None
                with self.serial_lock:
                    try:
                        in_wait = self.ser.in_waiting if hasattr(self.ser, 'in_waiting') else 0
                        if in_wait > 0:
                            # Baca persis sejumlah byte yang tersedia tanpa memblokir
                            data = self.ser.read(min(in_wait, 512))
                    except Exception as read_err:
                        logger.debug(f"Error pembacaan buffer serial: {read_err}")

                if data:
                    buffer.extend(data)
                    # Ekstrak EPC dan bersihkan frame yang sudah terbaca dari buffer
                    epcs = self.extract_epcs_and_consume(buffer)
                    if epcs:
                        self.enqueue_epcs(epcs)

                # Jeda sejenak agar CPU tidak 100% dan serial loop sangat responsif
                time.sleep(0.01)

        except KeyboardInterrupt:
            logger.info("Daemon dihentikan oleh pengguna.")
        finally:
            self._cleanup()

    def _cleanup(self):
        self.stop_event.set()
        with self.scan_lock:
            if self.batch_timer is not None:
                self.batch_timer.cancel()
                self.batch_timer = None
        if self.camera_http_server is not None:
            try:
                self.camera_http_server.shutdown()
                self.camera_http_server.server_close()
            except Exception:
                pass
            self.camera_http_server = None
        with self.serial_lock:
            if self.ser and self.ser.is_open:
                try:
                    self.ser.close()
                except Exception:
                    pass
        with self.relay_lock:
            if self.relay_ser and self.relay_ser.is_open:
                try:
                    self.relay_ser.close()
                except Exception:
                    pass
        with self.camera_lock:
            if self.cap:
                try:
                    self.cap.release()
                except Exception:
                    pass
                self.cap = None
        self.executor.shutdown(wait=False)
        logger.info("Listener ditutup.")

def main():
    parser = argparse.ArgumentParser(description="SILAMET Gate UHF RFID Listener")
    parser.add_argument("--port", default="COM2", help="Nama Serial Port Reader RFID (default: COM2 atau /dev/ttyUSB0)")
    parser.add_argument("--baud", type=int, default=57600, help="Baudrate serial reader (default: 57600)")
    parser.add_argument("--relay-port", default="COM4", help="Port serial Arduino Relay Buzzer (default: COM4, atau 'none')")
    parser.add_argument("--relay-baud", type=int, default=9600, help="Baudrate serial Arduino Relay Buzzer (default: 9600)")
    parser.add_argument("--mode", choices=["active", "answer"], default="answer", help="Mode reader: 'answer' (polling query standar) atau 'active' (stream)")
    parser.add_argument("--poll-interval", type=float, default=0.1, help="Interval kirim query inventory pada answer mode dalam detik (default: 0.1)")
    parser.add_argument("--api", default="http://127.0.0.1:8000/api/v1/gate/check-tag", help="Endpoint API Gate")
    parser.add_argument("--camera", type=int, default=0, help="Indeks webcam gate (default: 0)")
    parser.add_argument("--camera-host", default="127.0.0.1", help="Host server Camera Hub lokal untuk Kiosk (default: 127.0.0.1)")
    parser.add_argument("--camera-port", type=int, default=8765, help="Port HTTP Camera Hub lokal untuk Kiosk (default: 8765, 0 untuk matikan)")
    parser.add_argument("--debounce", type=float, default=4.0, help="Interval debounce per tag dalam detik (default: 4.0)")
    parser.add_argument("--batch-window", type=float, default=0.15, help="Jendela agregasi multi-tag burst dalam detik (default: 0.15)")
    parser.add_argument("--simulate", action="store_true", help="Jalankan dalam mode simulasi interaktif tanpa kabel reader")

    args = parser.parse_args()

    daemon = GateSecurityListener(
        port=args.port,
        baudrate=args.baud,
        api_url=args.api,
        camera_index=args.camera,
        debounce_sec=args.debounce,
        mode=args.mode,
        poll_interval=args.poll_interval,
        relay_port=args.relay_port,
        relay_baud=args.relay_baud,
        camera_host=args.camera_host,
        camera_port=args.camera_port,
        batch_window=args.batch_window,
    )
    daemon.simulate = args.simulate
    daemon.run()

if __name__ == "__main__":
    main()
