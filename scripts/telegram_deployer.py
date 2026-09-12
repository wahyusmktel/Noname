#!/usr/bin/env python3
"""
==============================================================================
TELEGRAM DEPLOYMENT BOT DAEMON - BIMBEL NO NAME
Mengontrol deployment bimbelnoname.com & dev.bimbelnoname.com via tombol Telegram.
Standar Keamanan: Whitelist Telegram User ID & Subprocess Isolation.
==============================================================================
"""

import json
import os
import subprocess
import sys
import time
import urllib.parse
import urllib.request

# KONFIGURASI BOT
BOT_TOKEN = os.environ.get("TELEGRAM_BOT_TOKEN", "8767283500:AAFbAdNONStdrT5PXPd0eauGMsVOOxEN5a8")
ALLOWED_USER_ID = int(os.environ.get("ALLOWED_USER_ID", "361760445"))
TELEGRAM_API_URL = f"https://api.telegram.org/bot{BOT_TOKEN}"

# PATH SCRIPT DEPLOYMENT
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
PROD_SCRIPT = os.path.join(BASE_DIR, "deploy-prod.sh")
DEV_SCRIPT = os.path.join(BASE_DIR, "deploy-dev.sh")

def send_telegram(method: str, data: dict) -> dict:
    url = f"{TELEGRAM_API_URL}/{method}"
    headers = {"Content-Type": "application/json"}
    req = urllib.request.Request(url, data=json.dumps(data).encode("utf-8"), headers=headers)
    try:
        with urllib.request.urlopen(req, timeout=25) as response:
            return json.loads(response.read().decode("utf-8"))
    except Exception as e:
        print(f"[{time.strftime('%Y-%m-%d %H:%M:%S')}] Telegram API Error: {e}", file=sys.stderr)
        return {}

def send_message(chat_id: int, text: str, reply_markup: dict = None):
    payload = {
        "chat_id": chat_id,
        "text": text,
        "parse_mode": "HTML"
    }
    if reply_markup:
        payload["reply_markup"] = reply_markup
    return send_telegram("sendMessage", payload)

def get_main_menu():
    return {
        "inline_keyboard": [
            [
                {"text": "🚀 Deploy Production", "callback_data": "deploy_prod"},
                {"text": "🧪 Deploy Dev / Staging", "callback_data": "deploy_dev"}
            ],
            [
                {"text": "📊 Cek Status Server", "callback_data": "server_status"},
                {"text": "🔄 Restart Queue & Nginx", "callback_data": "restart_services"}
            ]
        ]
    }

def run_command(command: str) -> str:
    try:
        res = subprocess.run(
            command,
            shell=True,
            stdout=subprocess.PIPE,
            stderr=subprocess.STDOUT,
            text=True,
            timeout=300
        )
        return res.stdout.strip()
    except subprocess.TimeoutExpired:
        return "❌ Error: Proses timeout melebihi 5 menit!"
    except Exception as e:
        return f"❌ Error eksekusi: {e}"

def get_server_status() -> str:
    uptime = run_command("uptime -p")
    ram = run_command("free -m | awk 'NR==2{printf \"Memory: %sMB / %sMB (%.1f%%)\", $3,$2,$3*100/$2 }'")
    disk = run_command("df -h / | awk 'NR==2{printf \"Disk: %s / %s (%s)\", $3,$2,$5}'")
    nginx_status = run_command("systemctl is-active nginx")
    mysql_status = run_command("systemctl is-active mysql || systemctl is-active mariadb")
    
    return (
        f"<b>📊 STATUS SERVER UBUNTU</b>\n\n"
        f"⏱ <b>Uptime:</b> {uptime}\n"
        f"💾 <b>RAM:</b> {ram}\n"
        f"💽 <b>Penyimpanan:</b> {disk}\n"
        f"🌐 <b>Nginx:</b> <code>{nginx_status}</code>\n"
        f"🗄 <b>MySQL/MariaDB:</b> <code>{mysql_status}</code>\n"
    )

def handle_update(update: dict):
    # 1. Handle Pesan Teks Masuk
    if "message" in update:
        msg = update["message"]
        chat_id = msg["chat"]["id"]
        from_id = msg.get("from", {}).get("id")
        text = msg.get("text", "")

        # Verifikasi Keamanan Whitelist
        if from_id != ALLOWED_USER_ID:
            send_message(chat_id, "⛔ <b>Akses Ditolak.</b> Akun Telegram Anda tidak terdaftar sebagai administrator server.")
            return

        if text in ["/start", "/menu", "menu", "deploy"]:
            welcome_text = (
                "👋 <b>Selamat datang di Panel Deployer Bimbel No Name!</b>\n\n"
                "Silakan pilih aksi deployment atau monitoring server di bawah ini:"
            )
            send_message(chat_id, welcome_text, get_main_menu())
            return

    # 2. Handle Tombol Inline Callback
    elif "callback_query" in update:
        cb = update["callback_query"]
        chat_id = cb["message"]["chat"]["id"]
        from_id = cb.get("from", {}).get("id")
        data = cb.get("data", "")
        cb_id = cb.get("id")

        # Jawab callback agar loading spinner di tombol hilang
        send_telegram("answerCallbackQuery", {"callback_query_id": cb_id})

        if from_id != ALLOWED_USER_ID:
            send_message(chat_id, "⛔ <b>Akses Ditolak.</b>")
            return

        if data == "server_status":
            status_text = get_server_status()
            send_message(chat_id, status_text, get_main_menu())

        elif data == "deploy_prod":
            send_message(chat_id, "⏳ <b>[PRODUCTION] Memulai proses deployment...</b>\nMohon tunggu 1-2 menit hingga build selesai.")
            if os.path.exists(PROD_SCRIPT):
                output = run_command(f"bash {PROD_SCRIPT}")
            else:
                output = f"❌ Script {PROD_SCRIPT} tidak ditemukan!"

            # Potong jika terlalu panjang untuk batasan Telegram (4096 char)
            if len(output) > 3500:
                output = output[-3500:]

            msg_res = (
                f"<b>🚀 LAPORAN DEPLOY PRODUCTION</b>\n"
                f"<pre>{output}</pre>"
            )
            send_message(chat_id, msg_res, get_main_menu())

        elif data == "deploy_dev":
            send_message(chat_id, "⏳ <b>[STAGING/DEV] Memulai proses deployment...</b>\nMohon tunggu sejenak.")
            if os.path.exists(DEV_SCRIPT):
                output = run_command(f"bash {DEV_SCRIPT}")
            else:
                output = f"❌ Script {DEV_SCRIPT} tidak ditemukan!"

            if len(output) > 3500:
                output = output[-3500:]

            msg_res = (
                f"<b>🧪 LAPORAN DEPLOY STAGING/DEV</b>\n"
                f"<pre>{output}</pre>"
            )
            send_message(chat_id, msg_res, get_main_menu())

        elif data == "restart_services":
            send_message(chat_id, "🔄 Merestart Nginx & Queue Worker...")
            res = run_command("sudo systemctl reload nginx && (cd /var/www/bimbel-prod && php artisan queue:restart || true)")
            send_message(chat_id, f"✅ Selesai:\n<code>{res or 'OK'}</code>", get_main_menu())

def main():
    print(f"[{time.strftime('%Y-%m-%d %H:%M:%S')}] Bimbel Telegram Deployer Daemon Started.")
    print(f"Authorized Telegram ID: {ALLOWED_USER_ID}")
    
    # Kirim salam ke owner bahwa bot telah online
    send_message(
        ALLOWED_USER_ID,
        "🤖 <b>Bot Deployer Bimbel No Name Aktif!</b>\nServer siap menerima perintah deployment.",
        get_main_menu()
    )

    offset = 0
    while True:
        try:
            updates = send_telegram("getUpdates", {"offset": offset, "timeout": 30})
            if updates.get("ok"):
                for item in updates.get("result", []):
                    offset = item["update_id"] + 1
                    handle_update(item)
            time.sleep(1)
        except KeyboardInterrupt:
            print("Stopping bot...")
            break
        except Exception as e:
            print(f"Polling loop error: {e}", file=sys.stderr)
            time.sleep(5)

if __name__ == "__main__":
    main()
