#!/usr/bin/env python3
import urllib.request
import json
import sys
import os
import subprocess
import re
import time
import hashlib
import io
from datetime import datetime
from PIL import Image, ImageDraw, ImageFont

sys.stdout.reconfigure(encoding='utf-8')

# Configuration
SHEET_ID = '1XWYmjcrBCIwIpBSnpCzvkH7sdtVPeIOSP9XoxmvH6r8'
BASE_URL = 'https://propertykoi.com/api/v1'
API_KEY = 'propertykoi_guides_sec_2026'
BOT_TOKEN = '8818620370:AAE8_UnBSal16_7ZzRkjYB8fRqqOUGHpw3A'
CHAT_ID = '6891457989'

# Local Properties Media Directories
LOCAL_PROPERTIES_BASE = r"Y:\real estate\properties"
LOCAL_FLATS_DIR = os.path.join(LOCAL_PROPERTIES_BASE, "flats")
LOCAL_COMMERCIAL_DIR = os.path.join(LOCAL_PROPERTIES_BASE, "commercial")

# Cloudinary Credentials
CLOUDINARY_CLOUD_NAME = 'a3xw53zs'
CLOUDINARY_API_KEY = '182884881585169'
CLOUDINARY_API_SECRET = '70aBRxRNbR3uCXSaP21pM5rqnqI'
CLOUDINARY_FOLDER = 'aftabnagar_marketplace'

VALID_IMG_EXTS = ('.jpg', '.jpeg', '.png', '.webp', '.bmp', '.jfif', '.heic')

def parse_size(val):
    if not val: return 0.0
    s = str(val).strip().lower()
    for i, d in enumerate('০১২৩৪৫৬৭৮৯'): s = s.replace(d, str(i))
    m = re.search(r'([0-9.]+)\s*[\*xX×]\s*([0-9.]+)', s)
    if m: return round(float(m.group(1)) * float(m.group(2)), 2)
    if '=' in s: s = s.split('=')[-1]
    m = re.search(r'([0-9.]+)\s*\+\s*([0-9.]+)', s)
    if m: return round(float(m.group(1)) + float(m.group(2)), 2)
    m = re.search(r'([0-9.]+)\s*(?:bigha|বিঘা)', s)
    if m: return round(float(m.group(1)) * 20, 2)
    m = re.search(r'([0-9.]+)', s)
    if m: return round(float(m.group(1)), 2)
    return 0.0

def parse_money(val):
    if not val: return 0.0
    s = str(val).strip().lower()
    for i, d in enumerate('০১২৩৪৫৬৭৮৯'): s = s.replace(d, str(i))
    m = re.search(r'([0-9.]+)\s*(?:cr|crore|কোটি)', s)
    if m: return float(m.group(1)) * 10000000.0
    m = re.search(r'([0-9.]+)\s*(?:lac|lakh|লাখ)', s)
    if m: return float(m.group(1)) * 100000.0
    clean = re.sub(r'[^0-9.]', '', s)
    if clean:
        try:
            v = float(clean)
            if 0 < v < 50: return v * 10000000.0
            return v
        except: pass
    return 0.0

def to_bool(val):
    if isinstance(val, bool): return val
    s = str(val).strip().lower()
    return s in ['true', '1', 'yes', 'y', 'হ্যাঁ', 'আছে']

def call_api(endpoint, method='GET', data=None):
    url = f"{BASE_URL}/{endpoint.lstrip('/')}"
    req_data = json.dumps(data).encode('utf-8') if data else None
    req = urllib.request.Request(url, data=req_data, headers={
        'X-API-KEY': API_KEY,
        'Content-Type': 'application/json',
        'User-Agent': 'PropertyKoi-Daily-Automation/1.0'
    }, method=method)
    try:
        with urllib.request.urlopen(req) as resp:
            return {'status': resp.status, 'data': json.loads(resp.read().decode('utf-8'))}
    except urllib.error.HTTPError as e:
        error_body = e.read().decode('utf-8')
        try:
            parsed = json.loads(error_body)
        except:
            parsed = error_body
        return {'status': e.code, 'error': parsed}
    except Exception as e:
        return {'status': 500, 'error': str(e)}

def send_telegram_alert(text):
    url = f"https://api.telegram.org/bot{BOT_TOKEN}/sendMessage"
    payload = json.dumps({
        'chat_id': CHAT_ID,
        'text': text,
        'parse_mode': 'HTML',
        'disable_web_page_preview': True
    }).encode('utf-8')
    req = urllib.request.Request(url, data=payload, headers={'Content-Type': 'application/json'})
    try:
        with urllib.request.urlopen(req) as resp:
            data = json.loads(resp.read().decode('utf-8'))
            return data.get('ok', False)
    except Exception as e:
        print(f"Error sending Telegram alert: {e}")
        return False

def read_sheet_tab(tab_name, range_str):
    try:
        cmd = ['gsheets.cmd', 'readonly', 'read', SHEET_ID, f'{tab_name}!{range_str}', '--json']
        out = subprocess.check_output(cmd, shell=True).decode('utf-8')
        return json.loads(out)
    except Exception as e:
        print(f"gsheets CLI read failed for {tab_name} ({e}), falling back to visualization export...")
        url = f"https://docs.google.com/spreadsheets/d/{SHEET_ID}/gviz/tq?tqx=out:json&sheet={tab_name}"
        req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
        with urllib.request.urlopen(req) as resp:
            raw = resp.read().decode('utf-8')
            start = raw.find('{')
            end = raw.rfind('}') + 1
            js = json.loads(raw[start:end])
            cols = [c.get('label') for c in js['table']['cols']]
            rows = [cols]
            for row in js['table']['rows']:
                r_vals = [c.get('v') if c else '' for c in row['c']]
                rows.append(r_vals)
            return rows

GSHEETS_EXE = r'C:\Users\Mithu\.gemini\antigravity\plugin_data\gsheets\bin\gsheets-986250228.exe'

def write_sheet_cells(tab_name, cell_range, csv_values):
    """Write arbitrary CSV values back to specified cell range in sheet"""
    try:
        val_str = str(csv_values)
        if os.path.exists(GSHEETS_EXE):
            cmd = [GSHEETS_EXE, 'mutate', 'write', SHEET_ID, f'{tab_name}!{cell_range}', val_str, '--json']
            res = subprocess.run(cmd, capture_output=True, text=True, shell=False)
        else:
            cmd = ['gsheets.cmd', 'mutate', 'write', SHEET_ID, f'{tab_name}!{cell_range}', val_str, '--json']
            res = subprocess.run(cmd, capture_output=True, text=True, shell=True)
            
        if 'Wrote' in (res.stdout or ''):
            print(f"[{tab_name}] Range {cell_range}: Successfully wrote to Google Sheet.")
            return True
        else:
            print(f"[{tab_name}] Range {cell_range}: Failed to write to sheet: {res.stdout} {res.stderr}")
            return False
    except Exception as e:
        print(f"[{tab_name}] Exception writing to sheet for range {cell_range}: {e}")
        return False

def write_sheet_property_id(tab_name, cell_range, code, prop_id):
    """Write generated property code and id back to specified columns in sheet"""
    return write_sheet_cells(tab_name, cell_range, f'{code},{prop_id}')

def make_agent_hyperlink_formula(agent_id):
    """Generates clickable Google Sheets formula linking directly to agent row in agents tab"""
    if not agent_id:
        return ''
    clean_id = str(agent_id).strip().replace('.0', '')
    raw_formula = f'=HYPERLINK("#gid=500673710&range=H" & MATCH({clean_id}, agents!$H:$H, 0), INDEX(agents!$H:$H, MATCH({clean_id}, agents!$H:$H, 0)))'
    return raw_formula.replace(',', r'\,')

AGENTS_DIRECTORY = None

def clean_phone_digits(p):
    if not p:
        return ''
    digits = re.sub(r'\D', '', str(p))
    if digits.startswith('880'):
        digits = digits[2:]
    elif digits.startswith('88'):
        digits = digits[2:]
    if len(digits) >= 10:
        return digits[-10:]
    return digits

def load_agents_directory(force_reload=False):
    global AGENTS_DIRECTORY
    if AGENTS_DIRECTORY is not None and not force_reload:
        return AGENTS_DIRECTORY

    res = subprocess.run(
        ['gsheets.cmd', 'readonly', 'read', SHEET_ID, 'agents!A1:H100', '--json'],
        capture_output=True, text=True, shell=True
    )
    if res.returncode != 0:
        print("[agents] Failed to read agents tab:", res.stderr)
        return {'by_id': {}, 'by_phone': {}, 'max_id': 0, 'total_rows': 0}

    rows = json.loads(res.stdout) if res.stdout else []
    by_id = {}
    by_phone = {}
    max_id = 0
    total_rows = len(rows) if rows else 0

    if rows and len(rows) > 1:
        for i, r in enumerate(rows[1:], start=2):
            if not r or not any(r):
                continue
            name = r[0] if len(r) > 0 else ''
            contact = r[1] if len(r) > 1 else ''
            working_area = r[3] if len(r) > 3 else ''
            remarks = r[4] if len(r) > 4 else ''
            extra = r[5] if len(r) > 5 else ''
            channel = r[6] if len(r) > 6 else ''
            raw_id = r[7] if len(r) > 7 else ''

            clean_id = str(raw_id).strip().replace('.0', '')
            if clean_id.isdigit():
                max_id = max(max_id, int(clean_id))

            agent_data = {
                'row_num': i,
                'name': name,
                'contact': contact,
                'working_area': working_area,
                'remarks': remarks,
                'extra': extra,
                'channel': channel,
                'id': clean_id
            }

            if clean_id:
                by_id[clean_id] = agent_data

            phone_key = clean_phone_digits(contact)
            if phone_key:
                by_phone[phone_key] = agent_data

    AGENTS_DIRECTORY = {
        'by_id': by_id,
        'by_phone': by_phone,
        'max_id': max_id,
        'total_rows': max(total_rows, 1)
    }
    print(f"[agents] Loaded {len(by_id)} agents from 'agents' tab. Highest Agent ID: {max_id}")
    return AGENTS_DIRECTORY

def sync_or_create_agent(name, phone, area='', remarks=''):
    """
    Finds agent in 'agents' tab by phone (or ID).
    If not found, creates a new agent row in 'agents' tab and assigns next ID.
    Returns (agent_id, agent_name, formatted_phone).
    """
    p_digits = clean_phone_digits(phone)
    if not p_digits and not name:
        return None, name or '', phone or ''

    agents_dir = load_agents_directory()
    by_phone = agents_dir['by_phone']

    # 1. Check existing agent by phone digits
    if p_digits and p_digits in by_phone:
        existing = by_phone[p_digits]
        ag_id = existing.get('id')
        ag_name = existing.get('name') or name or f"Agent {p_digits[-4:]}"
        ag_contact = existing.get('contact') or (f"0{p_digits}" if not str(phone).startswith('0') else str(phone))
        return ag_id, ag_name, ag_contact

    # 2. Check if name is given and is an integer ID referencing agents tab
    clean_n = str(name).strip().replace('.0', '')
    if clean_n.isdigit() and clean_n in agents_dir['by_id']:
        existing = agents_dir['by_id'][clean_n]
        return existing.get('id'), existing.get('name'), existing.get('contact')

    # 3. Create brand new agent in 'agents' tab if phone number exists
    if p_digits:
        new_id = agents_dir['max_id'] + 1
        new_row_idx = agents_dir['total_rows'] + 1
        formatted_phone = f"0{p_digits}" if len(p_digits) == 10 and not str(phone).startswith('0') else str(phone).strip()
        agent_name = name.strip() if name and str(name).strip() else f"Agent {p_digits[-4:]}"
        working_area = area.strip() if area else ''
        agent_remarks = remarks.strip() if remarks else ''

        def esc(val): return str(val or '').replace(',', r'\,')
        csv_row = f"{esc(agent_name)},{esc(formatted_phone)},,{esc(working_area)},{esc(agent_remarks)},,,{new_id}"
        write_sheet_cells('agents', f'A{new_row_idx}:H{new_row_idx}', csv_row)

        new_agent = {
            'row_num': new_row_idx,
            'name': agent_name,
            'contact': formatted_phone,
            'working_area': working_area,
            'remarks': agent_remarks,
            'extra': '',
            'channel': '',
            'id': str(new_id)
        }
        agents_dir['by_id'][str(new_id)] = new_agent
        agents_dir['by_phone'][p_digits] = new_agent
        agents_dir['max_id'] = new_id
        agents_dir['total_rows'] = new_row_idx

        print(f"[agents] Created new Agent #{new_id} ({agent_name}, {formatted_phone}, Area: {working_area}) in 'agents' tab row {new_row_idx}.")
        return str(new_id), agent_name, formatted_phone

    return None, name or '', phone or ''

def get_local_media_images(base_dir, item_id, prefix=""):
    r"""Find local images corresponding to an id under base_dir (e.g. flats or commercial)"""
    if not item_id or not os.path.exists(base_dir):
        return []
        
    s_id = str(item_id).strip()
    found_files = []
    
    # 1. Check folder: <base_dir>\<item_id>\
    folder_candidates = [
        os.path.join(base_dir, s_id),
        os.path.join(base_dir, f"{prefix}_{s_id}" if prefix else s_id),
        os.path.join(base_dir, f"{prefix}-{s_id}" if prefix else s_id)
    ]
    for fc in folder_candidates:
        if os.path.isdir(fc):
            for root, _, files in os.walk(fc):
                for f in sorted(files):
                    if f.lower().endswith(VALID_IMG_EXTS):
                        found_files.append(os.path.join(root, f))
                        
    # 2. Check files directly in folder: e.g. 1.jpg, 1_view.jpg, 1-space.png
    try:
        for f in sorted(os.listdir(base_dir)):
            full_p = os.path.join(base_dir, f)
            if not os.path.isfile(full_p):
                continue
            base, ext = os.path.splitext(f)
            if ext.lower() not in VALID_IMG_EXTS:
                continue
            if base == s_id or base.startswith(f"{s_id}_") or base.startswith(f"{s_id}-"):
                if full_p not in found_files:
                    found_files.append(full_p)
    except Exception as e:
        print(f"Error reading local directory ({base_dir}): {e}")
        
    return found_files

def get_local_flat_images(flat_id):
    return get_local_media_images(LOCAL_FLATS_DIR, flat_id, "flat")

def get_local_commercial_images(comm_id):
    return get_local_media_images(LOCAL_COMMERCIAL_DIR, comm_id, "comm")

WATERMARK_CONFIG = None
CACHED_LOGO_IMG = None
CACHED_LOGO_URL = None

def get_watermark_config():
    global WATERMARK_CONFIG
    if WATERMARK_CONFIG is not None:
        return WATERMARK_CONFIG
    
    try:
        res = call_api('/settings/watermark', method='GET')
        if res.get('status') == 200 and res.get('data', {}).get('success'):
            WATERMARK_CONFIG = res.get('data', {}).get('data', {})
            print(f"[watermark] Fetched live watermark config: {WATERMARK_CONFIG.get('text')} (type: {WATERMARK_CONFIG.get('type')}, pos: {WATERMARK_CONFIG.get('position')})")
            return WATERMARK_CONFIG
    except Exception as e:
        print(f"[watermark] Notice: Could not fetch watermark config from API ({e}), using default PropertyKoi.com badge.")

    WATERMARK_CONFIG = {
        'enabled': True,
        'type': 'text',
        'text': 'PropertyKoi.com',
        'position': 'bottom-right',
        'opacity': 80,
        'scale': 20,
        'logo_url': None
    }
    return WATERMARK_CONFIG

def apply_propertykoi_watermark(img, cfg=None):
    global CACHED_LOGO_IMG, CACHED_LOGO_URL
    if cfg is None:
        cfg = get_watermark_config()

    if not cfg.get('enabled', True):
        return img

    src_w, src_h = img.size
    if src_w < 220 or src_h < 150:
        return img

    position = cfg.get('position') or 'bottom-right'
    opacity = max(10, min(100, int(cfg.get('opacity') or 80)))
    opacity_factor = opacity / 100.0

    overlay = Image.new('RGBA', img.size, (0, 0, 0, 0))
    draw = ImageDraw.Draw(overlay)

    logo_drawn = False
    if cfg.get('type') == 'image' and cfg.get('logo_url'):
        try:
            logo_url = cfg['logo_url']
            if CACHED_LOGO_IMG is None or CACHED_LOGO_URL != logo_url:
                req = urllib.request.Request(logo_url, headers={'User-Agent': 'PropertyKoiAutomation/1.0'})
                with urllib.request.urlopen(req, timeout=10) as l_resp:
                    logo_raw = l_resp.read()
                    base_logo = Image.open(io.BytesIO(logo_raw))
                    if base_logo.mode != 'RGBA':
                        base_logo = base_logo.convert('RGBA')
                    CACHED_LOGO_IMG = base_logo
                    CACHED_LOGO_URL = logo_url

            logo_img = CACHED_LOGO_IMG.copy()
            scale_pct = max(8, min(50, int(cfg.get('scale') or 20))) / 100.0
            target_w = int(round(src_w * scale_pct))
            aspect = logo_img.height / max(1, logo_img.width)
            target_h = int(round(target_w * aspect))
            max_h = int(round(src_h * 0.35))
            if target_h > max_h:
                target_h = max_h
                target_w = int(round(target_h / aspect))

            logo_img = logo_img.resize((target_w, target_h), Image.Resampling.LANCZOS)

            if opacity_factor < 0.99:
                r, g, b, a = logo_img.split()
                a = a.point(lambda p: int(round(p * opacity_factor)))
                logo_img = Image.merge('RGBA', (r, g, b, a))

            margin = max(16, int(round(src_w * 0.02)))
            if position == 'bottom-right':
                pos_x = src_w - target_w - margin
                pos_y = src_h - target_h - margin
            elif position == 'bottom-left':
                pos_x = margin
                pos_y = src_h - target_h - margin
            elif position == 'top-right':
                pos_x = src_w - target_w - margin
                pos_y = margin
            elif position == 'top-left':
                pos_x = margin
                pos_y = margin
            else: # center
                pos_x = (src_w - target_w) // 2
                pos_y = (src_h - target_h) // 2

            pos_x = max(8, min(pos_x, src_w - target_w - 8))
            pos_y = max(8, min(pos_y, src_h - target_h - 8))

            overlay.paste(logo_img, (pos_x, pos_y), logo_img)
            logo_drawn = True
        except Exception:
            logo_drawn = False

    if not logo_drawn:
        text = cfg.get('text') or 'PropertyKoi.com'
        font_size = max(15, min(48, int(round(src_w * 0.024))))

        font_candidates = [
            r'd:\laragon\www\projects\real-estate\laravel-site\public\assets\fonts\watermark.ttf',
            r'C:\Windows\Fonts\arialbd.ttf',
            r'C:\Windows\Fonts\verdana.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
        ]
        font = None
        for fc in font_candidates:
            if os.path.exists(fc):
                try:
                    font = ImageFont.truetype(fc, font_size)
                    break
                except Exception:
                    pass
        if font is None:
            font = ImageFont.load_default()

        bbox = draw.textbbox((0, 0), text, font=font)
        text_w = bbox[2] - bbox[0]
        text_h = bbox[3] - bbox[1]

        pad_x = max(14, int(font_size * 0.6))
        pad_y = max(8, int(font_size * 0.35))
        box_w = text_w + pad_x * 2
        box_h = text_h + pad_y * 2
        margin = max(16, int(round(src_w * 0.02)))

        if position == 'bottom-right':
            pos_x = src_w - box_w - margin
            pos_y = src_h - box_h - margin
        elif position == 'bottom-left':
            pos_x = margin
            pos_y = src_h - box_h - margin
        elif position == 'top-right':
            pos_x = src_w - box_w - margin
            pos_y = margin
        elif position == 'top-left':
            pos_x = margin
            pos_y = margin
        else: # center
            pos_x = (src_w - box_w) // 2
            pos_y = (src_h - box_h) // 2

        pos_x = max(8, min(pos_x, src_w - box_w - 8))
        pos_y = max(8, min(pos_y, src_h - box_h - 8))

        bg_alpha = int(round(255 * opacity_factor * 0.88))
        text_alpha = int(round(255 * opacity_factor))

        corner_radius = max(6, int(box_h * 0.25))
        draw.rounded_rectangle([pos_x, pos_y, pos_x + box_w, pos_y + box_h], radius=corner_radius, fill=(15, 23, 42, bg_alpha))

        text_x = pos_x + pad_x - bbox[0]
        text_y = pos_y + pad_y - bbox[1]
        draw.text((text_x, text_y), text, font=font, fill=(255, 255, 255, text_alpha))

    if img.mode != 'RGBA':
        img = img.convert('RGBA')
    watermarked = Image.alpha_composite(img, overlay)
    return watermarked.convert('RGB')

def convert_and_upload_image_to_cloudinary(filepath):
    """Convert local image to WebP with PropertyKoi watermark and upload directly to Cloudinary"""
    try:
        img = Image.open(filepath)
        # Apply PropertyKoi watermark
        img = apply_propertykoi_watermark(img)

        if img.mode in ('RGBA', 'LA') or (img.mode == 'P' and 'transparency' in img.info):
            pass
        else:
            img = img.convert('RGB')
        
        buffer = io.BytesIO()
        img.save(buffer, format='WEBP', quality=86)
        webp_data = buffer.getvalue()
        
        timestamp = int(time.time())
        to_sign = f"folder={CLOUDINARY_FOLDER}&timestamp={timestamp}{CLOUDINARY_API_SECRET}"
        signature = hashlib.sha1(to_sign.encode('utf-8')).hexdigest()
        
        boundary = '----WebKitFormBoundary' + hashlib.md5(str(time.time()).encode('utf-8')).hexdigest()
        body = bytearray()
        
        def add_field(name, value):
            body.extend(f'--{boundary}\r\nContent-Disposition: form-data; name="{name}"\r\n\r\n{value}\r\n'.encode('utf-8'))
            
        add_field('api_key', CLOUDINARY_API_KEY)
        add_field('timestamp', str(timestamp))
        add_field('folder', CLOUDINARY_FOLDER)
        add_field('signature', signature)
        
        filename = os.path.splitext(os.path.basename(filepath))[0] + '.webp'
        body.extend(f'--{boundary}\r\nContent-Disposition: form-data; name="file"; filename="{filename}"\r\nContent-Type: image/webp\r\n\r\n'.encode('utf-8'))
        body.extend(webp_data)
        body.extend(f'\r\n--{boundary}--\r\n'.encode('utf-8'))
        
        req = urllib.request.Request(
            f"https://api.cloudinary.com/v1_1/{CLOUDINARY_CLOUD_NAME}/image/upload",
            data=bytes(body),
            headers={'Content-Type': f'multipart/form-data; boundary={boundary}'}
        )
        with urllib.request.urlopen(req, timeout=30) as resp:
            res = json.loads(resp.read().decode('utf-8'))
            secure_url = res.get('secure_url')
            print(f"Uploaded {os.path.basename(filepath)} ➔ {secure_url}")
            return secure_url
    except Exception as e:
        print(f"Failed to convert/upload {filepath} to Cloudinary: {e}")
        return None

def fetch_all_live_properties():
    props = []
    page = 1
    while True:
        res = call_api(f'/properties?per_page=100&page={page}')
        if res.get('status') != 200:
            break
        data = res.get('data', {})
        items = data.get('data', [])
        props.extend(items)
        if page >= data.get('pagination', {}).get('last_page', 1) or not items:
            break
        page += 1
    return props

def sync_plots(props_by_id, props_by_code):
    print("\n--- Scanning [plots] Tab ---")
    sheet_data = read_sheet_tab('plots', 'A:R')
    if not sheet_data or len(sheet_data) <= 1:
        print("No plots data found in Google Sheet.")
        return [], [], 0
        
    headers = [str(h).strip().lower() for h in sheet_data[0]]
    
    def get_val(r, col_name, default=''):
        idx = headers.index(col_name) if col_name in headers else -1
        if 0 <= idx < len(r) and r[idx] is not None:
            return str(r[idx]).strip()
        return default

    changes = []
    new_created = []
    checked_count = 0
    
    for row_num, row in enumerate(sheet_data[1:], 2):
        if not any(row):
            continue
            
        p_code = get_val(row, 'property code').upper()
        p_id = get_val(row, 'property id')
        sheet_status = get_val(row, 'sell status').lower()
        place = get_val(row, 'place')
        block = get_val(row, 'block')
        sector = get_val(row, 'sector')
        road = get_val(row, 'road')
        plot = get_val(row, 'plot number')
        katha = get_val(row, 'katha')
        agent = get_val(row, 'agent')
        contact = get_val(row, 'contact')
        price = get_val(row, 'price')
        total_price = get_val(row, 'total price')
        desc = get_val(row, 'description')
        private_note = get_val(row, 'private note')
        agents_col = get_val(row, 'agents')
        
        # Resolve or create agent in 'agents' tab
        ag_id, ag_name, ag_contact = sync_or_create_agent(
            agent or agents_col,
            contact,
            area=place,
            remarks=f"Plot {plot} Block {block}"
        )
        if ag_id and not agents_col:
            write_sheet_cells('plots', f'O{row_num}', make_agent_hyperlink_formula(ag_id))
            agents_col = ag_id

        prop = None
        if p_id and p_id in props_by_id:
            prop = props_by_id[p_id]
        elif p_code and p_code in props_by_code:
            prop = props_by_code[p_code]
            
        target_status = 'live'
        if any(w in sheet_status for w in ['sold', 'বিক্রি', 'বিক্রিত', 'sell']):
            target_status = 'sold'
        elif any(w in sheet_status for w in ['booked', 'বুক', 'বুকড', 'advance']):
            target_status = 'booked'
        elif any(w in sheet_status for w in ['draft', 'directory', 'ড্রাফট']):
            target_status = 'draft'
        elif any(w in sheet_status for w in ['live', 'active', 'available', 'চলমান', 'চালু']):
            target_status = 'live'

        # CASE 1: Brand new plot in sheet with no ID / Code
        if not prop:
            if place or block or plot or katha:
                print(f"[plots] Row {row_num}: New plot detected ({place}, Block {block}, Plot {plot}, {katha}). Ingesting...")
                new_payload = {
                    'place': place or 'ঢাকা',
                    'block': block,
                    'sector': sector,
                    'road': road,
                    'plot_number': plot,
                    'katha': katha,
                    'contact': ag_contact or contact,
                    'agent': ag_name or agent or agents_col,
                    'price_per_katha': price,
                    'total_price': total_price,
                    'description': desc,
                    'hidden_note': private_note,
                    'source_type': 'owner' if any(w in (desc + ' ' + private_note).lower() for w in ['owner', 'direct', 'মালিক']) else 'agent',
                    'status': target_status,
                    'property_type': 'land'
                }
                create_res = call_api('/properties', method='POST', data=new_payload)
                if create_res.get('status') in [200, 201]:
                    prop_data = create_res.get('data', {}).get('data', {})
                    created_id = str(prop_data.get('id', ''))
                    created_code = str(prop_data.get('property_code', '')).upper()
                    print(f"[plots] Row {row_num}: Successfully created -> #{created_code} (ID: {created_id})")
                    
                    # Write back to Google Sheet (plots columns O, P, Q)
                    if ag_id or agents_col:
                        write_sheet_cells('plots', f'O{row_num}', make_agent_hyperlink_formula(ag_id or agents_col))
                    write_sheet_property_id('plots', f'P{row_num}:Q{row_num}', created_code, created_id)
                    
                    new_created.append({
                        'code': created_code,
                        'id': created_id,
                        'title': prop_data.get('title'),
                        'type': 'প্লট/জমি',
                        'area': prop_data.get('area'),
                        'block': prop_data.get('block'),
                        'status': target_status,
                        'sheet_row': row_num
                    })
                else:
                    print(f"[plots] Row {row_num}: Failed to create plot: {create_res.get('error')}")
            continue
            
        checked_count += 1
        live_status = str(prop.get('status') or '').strip().lower()
            
        # CASE 2: Column & Status update for existing plot
        field_changes = []
        update_payload = {
            'place': place or prop.get('area'),
            'block': block or prop.get('block'),
            'sector': sector,
            'road': road,
            'plot_number': plot,
            'katha': katha,
            'price_per_katha': price,
            'total_price': total_price,
            'description': desc,
            'hidden_note': private_note,
            'contact': contact,
            'agent': agent or agents_col,
        }

        # Size change detection
        if katha:
            sheet_size = parse_size(katha)
            live_size = float(prop.get('size') or 0.0)
            if sheet_size > 0 and abs(sheet_size - live_size) > 0.05:
                field_changes.append(f"আয়তন: {live_size:g} কাঠা ➔ {sheet_size:g} কাঠা")

        # Price change detection
        sheet_total_money = parse_money(total_price)
        if sheet_total_money <= 0 and price:
            sheet_size = parse_size(katha)
            if sheet_size > 0:
                sheet_total_money = parse_money(price) * sheet_size
        live_total = float(prop.get('price_total') or 0.0)
        if sheet_total_money > 0 and abs(sheet_total_money - live_total) > 1000:
            field_changes.append(f"মূল্য: ৳{live_total/10000000:.2f} কোটি ➔ ৳{sheet_total_money/10000000:.2f} কোটি")

        # Status change detection
        if sheet_status:
            update_payload['status'] = target_status
            if target_status != live_status:
                field_changes.append(f"স্ট্যাটাস: {live_status} ➔ {target_status.upper()}")

        if field_changes:
            print(f"[plots] Row {row_num}: Updates detected for #{prop.get('property_code')} (ID: {prop.get('id')}): {', '.join(field_changes)}")
            update_res = call_api(f"/properties/{prop.get('id')}", method='POST', data=update_payload)
            if update_res.get('status') in [200, 201]:
                res_data = update_res.get('data', {}).get('data', {})
                changes.append({
                    'code': prop.get('property_code'),
                    'id': prop.get('id'),
                    'title': res_data.get('title') or prop.get('title'),
                    'type': 'প্লট/জমি',
                    'sheet_row': row_num,
                    'details': field_changes
                })
            else:
                print(f"[plots] Failed to update property #{prop.get('property_code')}: {update_res.get('error')}")

    return new_created, changes, checked_count

def sync_flats(props_by_id, props_by_code):
    print("\n--- Scanning [flats] Tab ---")
    sheet_data = read_sheet_tab('flats', 'A:AG')
    if not sheet_data or len(sheet_data) <= 1:
        print("No flats data found in Google Sheet.")
        return [], [], 0
        
    headers = [str(h).strip().lower() for h in sheet_data[0]]
    
    def get_val(r, col_name, default=''):
        idx = headers.index(col_name) if col_name in headers else -1
        if 0 <= idx < len(r) and r[idx] is not None:
            return str(r[idx]).strip()
        return default

    changes = []
    new_created = []
    checked_count = 0
    
    for row_num, row in enumerate(sheet_data[1:], 2):
        if not any(row):
            continue
            
        flat_id = get_val(row, 'flat_id')
        p_code = get_val(row, 'property code').upper()
        p_id = get_val(row, 'property id')
        sheet_status = get_val(row, 'sell status').lower()
        
        area = get_val(row, 'area')
        block = get_val(row, 'block')
        sector = get_val(row, 'sector')
        road_num = get_val(row, 'road_number')
        plot_num = get_val(row, 'plot_number')
        holding_num = get_val(row, 'holding_number')
        flat_size = get_val(row, 'flat_size_sqft')
        bedrooms = get_val(row, 'bedrooms')
        bathrooms = get_val(row, 'bathrooms')
        balconies = get_val(row, 'balconies')
        floor_num = get_val(row, 'floor_number')
        facing = get_val(row, 'facing')
        completion_status = get_val(row, 'completion_status')
        has_lift = get_val(row, 'has_lift')
        parking_avail = get_val(row, 'parking_available')
        has_gas = get_val(row, 'has_gas')
        has_gen = get_val(row, 'has_generator')
        price_sqft = get_val(row, 'price_per_sqft')
        garage_price = get_val(row, 'garage_price')
        utility_price = get_val(row, 'utility_price')
        asking_price = get_val(row, 'asking_price')
        is_neg = get_val(row, 'is_negotiable')
        owner_name = get_val(row, 'owner_name')
        owner_phone = get_val(row, 'owner_phone')
        source_type = get_val(row, 'source_type')
        title = get_val(row, 'title')
        desc = get_val(row, 'description')
        hidden_note = get_val(row, 'hidden_note')
        other_features = get_val(row, 'other_features')
        agents_col = get_val(row, 'agents')

        # Phone number present -> treat by default as agent and sync to 'agents' tab
        ag_id, ag_name, ag_phone = sync_or_create_agent(
            owner_name or agents_col,
            owner_phone,
            area=area,
            remarks=f"{flat_size} sft flat Block {block}"
        )
        if ag_id and not agents_col:
            write_sheet_cells('flats', f'AE{row_num}', make_agent_hyperlink_formula(ag_id))
            agents_col = ag_id

        prop = None
        if p_id and p_id in props_by_id:
            prop = props_by_id[p_id]
        elif p_code and p_code in props_by_code:
            prop = props_by_code[p_code]

        target_status = 'live'
        if any(w in sheet_status for w in ['sold', 'বিক্রি', 'বিক্রিত', 'sell']):
            target_status = 'sold'
        elif any(w in sheet_status for w in ['booked', 'বুক', 'বুকড', 'advance']):
            target_status = 'booked'
        elif any(w in sheet_status for w in ['draft', 'directory', 'ড্রাফট']):
            target_status = 'draft'
        elif any(w in sheet_status for w in ['live', 'active', 'available', 'চলমান', 'চালু']):
            target_status = 'live'

        # Check local images for this flat_id
        local_images = get_local_flat_images(flat_id)
        uploaded_image_urls = []

        # CASE 1: Brand new flat in sheet
        if not prop:
            if area or block or flat_size or flat_id:
                print(f"[flats] Row {row_num}: New flat detected (ID: {flat_id}, {area}, Block {block}, {flat_size} sqft).")
                
                # Convert and upload local images to Cloudinary
                if local_images:
                    print(f"[flats] Found {len(local_images)} local image(s) for flat_id '{flat_id}'. Uploading to Cloudinary...")
                    for imp in local_images:
                        c_url = convert_and_upload_image_to_cloudinary(imp)
                        if c_url:
                            uploaded_image_urls.append(c_url)
                
                new_payload = {
                    'property_type': 'flat',
                    'area': area or 'ঢাকা',
                    'block': block,
                    'sector': sector,
                    'road_number': road_num,
                    'plot_number': plot_num,
                    'holding_number': holding_num,
                    'flat_size_sqft': flat_size,
                    'bedrooms': bedrooms or 3,
                    'bathrooms': bathrooms or 3,
                    'balconies': balconies or 2,
                    'floor_number': floor_num or 1,
                    'facing': facing,
                    'completion_status': completion_status or 'ready',
                    'has_lift': to_bool(has_lift),
                    'parking_available': to_bool(parking_avail),
                    'has_gas': to_bool(has_gas),
                    'has_generator': to_bool(has_gen),
                    'price_per_sqft': price_sqft,
                    'garage_price': garage_price,
                    'utility_price': utility_price,
                    'asking_price': asking_price,
                    'is_negotiable': to_bool(is_neg),
                    'owner_name': ag_name or owner_name,
                    'owner_phone': ag_phone or owner_phone,
                    'source_type': 'agent',
                    'title': title,
                    'description': desc,
                    'hidden_note': hidden_note,
                    'other_features': other_features,
                    'status': target_status,
                    'images': uploaded_image_urls
                }
                
                create_res = call_api('/properties', method='POST', data=new_payload)
                if create_res.get('status') in [200, 201]:
                    prop_data = create_res.get('data', {}).get('data', {})
                    created_id = str(prop_data.get('id', ''))
                    created_code = str(prop_data.get('property_code', '')).upper()
                    print(f"[flats] Row {row_num}: Successfully created flat -> #{created_code} (ID: {created_id}) with {len(uploaded_image_urls)} photo(s)")
                    
                    # Write back to Google Sheet (flats columns AE:AG - agents, property code, property id)
                    if ag_id or agents_col:
                        write_sheet_cells('flats', f'AE{row_num}', make_agent_hyperlink_formula(ag_id or agents_col))
                    write_sheet_property_id('flats', f'AF{row_num}:AG{row_num}', created_code, created_id)
                    
                    new_created.append({
                        'code': created_code,
                        'id': created_id,
                        'title': prop_data.get('title'),
                        'type': 'ফ্ল্যাট',
                        'area': prop_data.get('area'),
                        'block': prop_data.get('block'),
                        'photos_count': len(uploaded_image_urls),
                        'status': target_status,
                        'sheet_row': row_num
                    })
                else:
                    print(f"[flats] Row {row_num}: Failed to create flat: {create_res.get('error')}")
            continue

        checked_count += 1
        live_status = str(prop.get('status') or '').strip().lower()

        # CASE 2: Column & Status update for existing flat
        field_changes = []
        update_payload = {
            'place': area or prop.get('area'),
            'block': block or prop.get('block'),
            'sector': sector,
            'road_number': road_num,
            'plot_number': plot_num,
            'holding_number': holding_num,
            'flat_size_sqft': flat_size,
            'bedrooms': bedrooms,
            'bathrooms': bathrooms,
            'balconies': balconies,
            'floor_number': floor_num,
            'facing': facing,
            'completion_status': completion_status,
            'has_lift': to_bool(has_lift),
            'parking_available': to_bool(parking_avail),
            'has_gas': to_bool(has_gas),
            'has_generator': to_bool(has_gen),
            'price_per_sqft': price_sqft,
            'garage_price': garage_price,
            'utility_price': utility_price,
            'asking_price': asking_price,
            'description': desc,
            'hidden_note': hidden_note,
            'owner_name': owner_name,
            'owner_phone': owner_phone,
            'other_features': other_features,
        }

        # Size change detection
        if flat_size:
            sheet_size = parse_size(flat_size)
            live_size = float(prop.get('size') or 0.0)
            if sheet_size > 0 and abs(sheet_size - live_size) > 1.0:
                field_changes.append(f"আয়তন: {live_size:g} sqft ➔ {sheet_size:g} sqft")

        # Price change detection
        sheet_total_money = parse_money(asking_price)
        if sheet_total_money <= 0 and price_sqft:
            sheet_size = parse_size(flat_size)
            if sheet_size > 0:
                sheet_total_money = parse_money(price_sqft) * sheet_size
        live_total = float(prop.get('price_total') or 0.0)
        if sheet_total_money > 0 and abs(sheet_total_money - live_total) > 1000:
            field_changes.append(f"মূল্য: ৳{live_total/100000:.2f} লাখ ➔ ৳{sheet_total_money/100000:.2f} লাখ")

        # Status change detection
        if sheet_status:
            update_payload['status'] = target_status
            if target_status != live_status:
                field_changes.append(f"স্ট্যাটাস: {live_status} ➔ {target_status.upper()}")

        # Check for new local images to attach
        if local_images:
            existing_media = prop.get('media') or []
            existing_urls = [m.get('file_path') for m in existing_media if isinstance(m, dict)]
            if not existing_urls:
                print(f"[flats] Found {len(local_images)} new image(s) for existing flat #{prop.get('property_code')}. Uploading...")
                for imp in local_images:
                    c_url = convert_and_upload_image_to_cloudinary(imp)
                    if c_url:
                        uploaded_image_urls.append(c_url)
                if uploaded_image_urls:
                    update_payload['images'] = uploaded_image_urls
                    field_changes.append(f"{len(uploaded_image_urls)}টি নতুন ছবি সংযুক্ত করা হয়েছে")

        if field_changes:
            print(f"[flats] Row {row_num}: Updates detected for #{prop.get('property_code')} (ID: {prop.get('id')}): {', '.join(field_changes)}")
            update_res = call_api(f"/properties/{prop.get('id')}", method='POST', data=update_payload)
            if update_res.get('status') in [200, 201]:
                res_data = update_res.get('data', {}).get('data', {})
                changes.append({
                    'code': prop.get('property_code'),
                    'id': prop.get('id'),
                    'title': res_data.get('title') or prop.get('title'),
                    'type': 'ফ্ল্যাট',
                    'sheet_row': row_num,
                    'details': field_changes
                })
            else:
                print(f"[flats] Failed to update flat #{prop.get('property_code')}: {update_res.get('error')}")

    return new_created, changes, checked_count

def sync_commercial(props_by_id, props_by_code):
    print("\n--- Scanning [commercial] Tab ---")
    sheet_data = read_sheet_tab('commercial', 'A:AI')
    if not sheet_data or len(sheet_data) <= 1:
        print("No commercial data found in Google Sheet.")
        return [], [], 0
        
    headers = [str(h).strip().lower() for h in sheet_data[0]]
    
    def get_val(r, col_name, default=''):
        idx = headers.index(col_name) if col_name in headers else -1
        if 0 <= idx < len(r) and r[idx] is not None:
            return str(r[idx]).strip()
        return default

    changes = []
    new_created = []
    checked_count = 0
    
    for row_num, row in enumerate(sheet_data[1:], 2):
        if not any(row):
            continue
            
        comm_id = get_val(row, 'commercial_id')
        p_code = get_val(row, 'property code').upper()
        p_id = get_val(row, 'property id')
        sheet_status = get_val(row, 'sell status').lower()
        
        comm_type = get_val(row, 'commercial_type')
        market_name = get_val(row, 'market_name')
        shop_number = get_val(row, 'shop_number')
        area = get_val(row, 'area')
        block = get_val(row, 'block')
        sector = get_val(row, 'sector')
        road_num = get_val(row, 'road_number')
        size_sqft = get_val(row, 'size_sqft')
        floor_num = get_val(row, 'floor_number')
        total_floors = get_val(row, 'total_floors')
        frontage_ft = get_val(row, 'frontage_ft')
        completion_status = get_val(row, 'completion_status')
        suitable_for = get_val(row, 'suitable_for')
        monthly_rent = get_val(row, 'monthly_rent_yield')
        has_lift = get_val(row, 'has_lift')
        has_gen = get_val(row, 'has_generator')
        has_ac = get_val(row, 'has_central_ac')
        has_parking = get_val(row, 'has_parking')
        has_fire = get_val(row, 'has_fire_safety')
        has_cctv = get_val(row, 'has_cctv')
        has_escalator = get_val(row, 'has_escalator')
        price_sqft = get_val(row, 'price_per_sqft')
        asking_price = get_val(row, 'asking_price')
        is_neg = get_val(row, 'is_negotiable')
        owner_name = get_val(row, 'owner_name')
        owner_phone = get_val(row, 'owner_phone')
        source_type = get_val(row, 'source_type')
        title = get_val(row, 'title')
        desc = get_val(row, 'description')
        hidden_note = get_val(row, 'hidden_note')
        other_features = get_val(row, 'other_features')
        agents_col = get_val(row, 'agents')

        # Phone number present -> treat by default as agent and sync to 'agents' tab
        ag_id, ag_name, ag_phone = sync_or_create_agent(
            owner_name or agents_col,
            owner_phone,
            area=area,
            remarks=f"{size_sqft} sft {comm_type} Market: {market_name}"
        )
        if ag_id and not agents_col:
            write_sheet_cells('commercial', f'AG{row_num}', make_agent_hyperlink_formula(ag_id))
            agents_col = ag_id

        prop = None
        if p_id and p_id in props_by_id:
            prop = props_by_id[p_id]
        elif p_code and p_code in props_by_code:
            prop = props_by_code[p_code]

        target_status = 'live'
        if any(w in sheet_status for w in ['sold', 'বিক্রি', 'বিক্রিত', 'sell']):
            target_status = 'sold'
        elif any(w in sheet_status for w in ['booked', 'বুক', 'বুকড', 'advance']):
            target_status = 'booked'
        elif any(w in sheet_status for w in ['draft', 'directory', 'ড্রাফট']):
            target_status = 'draft'
        elif any(w in sheet_status for w in ['live', 'active', 'available', 'চলমান', 'চালু']):
            target_status = 'live'

        # Check local commercial images
        local_images = get_local_commercial_images(comm_id)
        uploaded_image_urls = []

        # CASE 1: Brand new commercial space in sheet
        if not prop:
            if area or market_name or size_sqft or comm_id:
                print(f"[commercial] Row {row_num}: New commercial space detected (ID: {comm_id}, {area}, Market: {market_name}, {size_sqft} sqft).")
                
                # Convert and upload local images to Cloudinary
                if local_images:
                    print(f"[commercial] Found {len(local_images)} local image(s) for commercial_id '{comm_id}'. Uploading to Cloudinary...")
                    for imp in local_images:
                        c_url = convert_and_upload_image_to_cloudinary(imp)
                        if c_url:
                            uploaded_image_urls.append(c_url)
                
                new_payload = {
                    'property_type': 'commercial',
                    'commercial_type': comm_type or 'shop',
                    'market_name': market_name,
                    'shop_number': shop_number,
                    'area': area or 'ঢাকা',
                    'block': block,
                    'sector': sector,
                    'road_number': road_num,
                    'size_sqft': size_sqft,
                    'floor_number': floor_num or 1,
                    'total_floors': total_floors,
                    'frontage_ft': frontage_ft,
                    'completion_status': completion_status or 'ready',
                    'suitable_for': suitable_for,
                    'monthly_rent_yield': monthly_rent,
                    'has_lift': to_bool(has_lift),
                    'has_generator': to_bool(has_gen),
                    'has_central_ac': to_bool(has_ac),
                    'has_parking': to_bool(has_parking),
                    'has_fire_safety': to_bool(has_fire),
                    'has_cctv': to_bool(has_cctv),
                    'has_escalator': to_bool(has_escalator),
                    'price_per_sqft': price_sqft,
                    'asking_price': asking_price,
                    'is_negotiable': to_bool(is_neg),
                    'owner_name': ag_name or owner_name,
                    'owner_phone': ag_phone or owner_phone,
                    'source_type': 'agent',
                    'title': title,
                    'description': desc,
                    'hidden_note': hidden_note,
                    'other_features': other_features,
                    'status': target_status,
                    'images': uploaded_image_urls
                }
                
                create_res = call_api('/properties', method='POST', data=new_payload)
                if create_res.get('status') in [200, 201]:
                    prop_data = create_res.get('data', {}).get('data', {})
                    created_id = str(prop_data.get('id', ''))
                    created_code = str(prop_data.get('property_code', '')).upper()
                    print(f"[commercial] Row {row_num}: Successfully created commercial space -> #{created_code} (ID: {created_id}) with {len(uploaded_image_urls)} photo(s)")
                    
                    # Write back to Google Sheet (commercial columns AG:AI - agents, property code, property id)
                    if ag_id or agents_col:
                        write_sheet_cells('commercial', f'AG{row_num}', make_agent_hyperlink_formula(ag_id or agents_col))
                    write_sheet_property_id('commercial', f'AH{row_num}:AI{row_num}', created_code, created_id)
                    
                    new_created.append({
                        'code': created_code,
                        'id': created_id,
                        'title': prop_data.get('title'),
                        'type': 'বাণিজ্যিক স্পেস',
                        'area': prop_data.get('area'),
                        'block': prop_data.get('block'),
                        'photos_count': len(uploaded_image_urls),
                        'status': target_status,
                        'sheet_row': row_num
                    })
                else:
                    print(f"[commercial] Row {row_num}: Failed to create commercial property: {create_res.get('error')}")
            continue

        checked_count += 1
        live_status = str(prop.get('status') or '').strip().lower()

        # CASE 2: Column & Status update for existing commercial space
        field_changes = []
        update_payload = {
            'place': area or prop.get('area'),
            'block': block or prop.get('block'),
            'sector': sector,
            'road_number': road_num,
            'commercial_type': comm_type,
            'market_name': market_name,
            'shop_number': shop_number,
            'size_sqft': size_sqft,
            'floor_number': floor_num,
            'total_floors': total_floors,
            'frontage_ft': frontage_ft,
            'completion_status': completion_status,
            'suitable_for': suitable_for,
            'monthly_rent_yield': monthly_rent,
            'has_lift': to_bool(has_lift),
            'has_generator': to_bool(has_gen),
            'has_central_ac': to_bool(has_ac),
            'has_parking': to_bool(has_parking),
            'has_fire_safety': to_bool(has_fire),
            'has_cctv': to_bool(has_cctv),
            'has_escalator': to_bool(has_escalator),
            'price_per_sqft': price_sqft,
            'asking_price': asking_price,
            'description': desc,
            'hidden_note': hidden_note,
            'owner_name': owner_name,
            'owner_phone': owner_phone,
            'other_features': other_features,
        }

        # Size change detection
        if size_sqft:
            sheet_size = parse_size(size_sqft)
            live_size = float(prop.get('size') or 0.0)
            if sheet_size > 0 and abs(sheet_size - live_size) > 1.0:
                field_changes.append(f"আয়তন: {live_size:g} sqft ➔ {sheet_size:g} sqft")

        # Price change detection
        sheet_total_money = parse_money(asking_price)
        if sheet_total_money <= 0 and price_sqft:
            sheet_size = parse_size(size_sqft)
            if sheet_size > 0:
                sheet_total_money = parse_money(price_sqft) * sheet_size
        live_total = float(prop.get('price_total') or 0.0)
        if sheet_total_money > 0 and abs(sheet_total_money - live_total) > 1000:
            field_changes.append(f"মূল্য: ৳{live_total/100000:.2f} লাখ ➔ ৳{sheet_total_money/100000:.2f} লাখ")

        # Status change detection
        if sheet_status:
            update_payload['status'] = target_status
            if target_status != live_status:
                field_changes.append(f"স্ট্যাটাস: {live_status} ➔ {target_status.upper()}")

        # Check for new local images to attach
        if local_images:
            existing_media = prop.get('media') or []
            existing_urls = [m.get('file_path') for m in existing_media if isinstance(m, dict)]
            if not existing_urls:
                print(f"[commercial] Found {len(local_images)} new image(s) for existing commercial #{prop.get('property_code')}. Uploading...")
                for imp in local_images:
                    c_url = convert_and_upload_image_to_cloudinary(imp)
                    if c_url:
                        uploaded_image_urls.append(c_url)
                if uploaded_image_urls:
                    update_payload['images'] = uploaded_image_urls
                    field_changes.append(f"{len(uploaded_image_urls)}টি নতুন ছবি সংযুক্ত করা হয়েছে")

        if field_changes:
            print(f"[commercial] Row {row_num}: Updates detected for #{prop.get('property_code')} (ID: {prop.get('id')}): {', '.join(field_changes)}")
            update_res = call_api(f"/properties/{prop.get('id')}", method='POST', data=update_payload)
            if update_res.get('status') in [200, 201]:
                res_data = update_res.get('data', {}).get('data', {})
                changes.append({
                    'code': prop.get('property_code'),
                    'id': prop.get('id'),
                    'title': res_data.get('title') or prop.get('title'),
                    'type': 'বাণিজ্যিক স্পেস',
                    'sheet_row': row_num,
                    'details': field_changes
                })
            else:
                print(f"[commercial] Failed to update commercial #{prop.get('property_code')}: {update_res.get('error')}")

    return new_created, changes, checked_count

def main():
    print(f"[{datetime.now().strftime('%Y-%m-%d %H:%M:%S')}] Starting PropertyKoi Daily Automation (Plots + Flats + Commercial)...")
    
    live_props = fetch_all_live_properties()
    print(f"Fetched {len(live_props)} properties currently on live site.")
    
    props_by_id = {str(p.get('id')): p for p in live_props if p.get('id')}
    props_by_code = {str(p.get('property_code')).strip().upper(): p for p in live_props if p.get('property_code')}

    # 1. Sync Plots
    plot_new, plot_changes, plot_checked = sync_plots(props_by_id, props_by_code)

    # 2. Sync Flats
    flat_new, flat_changes, flat_checked = sync_flats(props_by_id, props_by_code)

    # 3. Sync Commercial
    comm_new, comm_changes, comm_checked = sync_commercial(props_by_id, props_by_code)

    all_new = plot_new + flat_new + comm_new
    all_changes = plot_changes + flat_changes + comm_changes
    total_checked = plot_checked + flat_checked + comm_checked

    print(f"\nAutomation Summary: Checked {total_checked} properties ({plot_checked} plots, {flat_checked} flats, {comm_checked} commercial). Newly created: {len(all_new)}. Existing updated: {len(all_changes)}.")

    # 4. Prepare and Send Telegram Notification
    now_str = datetime.now().strftime("%d %B %Y, %I:%M %p")
    
    if all_new or all_changes:
        msg_lines = [
            f"🔔 <b>PropertyKoi Sheet Sync Alert (Plots + Flats + Commercial)</b>",
            f"📅 <i>{now_str}</i>\n"
        ]
        if all_new:
            msg_lines.append(f"✨ <b>নতুন প্রপার্টি লাইভ সাইটে যুক্ত ও শিটে আইডি আপডেট: ({len(all_new)}টি)</b>")
            for n in all_new:
                extra = f" [{n.get('photos_count')}টি ছবি]" if n.get('photos_count') else ""
                msg_lines.append(f"• <b>#{n['code']}</b> (ID: {n['id']}) - <b>{n.get('type')}</b>: {n['title']}{extra} (Row #{n['sheet_row']})")
            msg_lines.append("")
            
        if all_changes:
            msg_lines.append(f"🔄 <b>বিদ্যমান প্রপার্টি ও স্ট্যাটাস আপডেট: ({len(all_changes)}টি)</b>")
            for c in all_changes:
                msg_lines.append(f"• <b>#{c['code']}</b> (ID: {c['id']}) - <b>{c.get('type')}</b>: {c['title']} (Row #{c['sheet_row']})")
                for d in c.get('details', []):
                    msg_lines.append(f"   ↳ {d}")
                msg_lines.append("")
                
        msg_lines.append(f"✅ লাইভ সাইট এবং গুগল শিট ১০০% সিঙ্ক্রোনাইজড।")
        send_telegram_alert("\n".join(msg_lines))
        print("Telegram alert sent.")
    else:
        # Daily heartbeat summary
        msg = (
            f"✅ <b>PropertyKoi Daily Status Check</b>\n"
            f"📅 <i>{now_str}</i>\n\n"
            f"• মোট প্রপার্টি স্ক্যানকৃত: <b>{total_checked}টি</b> (প্লট: {plot_checked}, ফ্ল্যাট: {flat_checked}, বাণিজ্যিক: {comm_checked})\n"
            f"• নতুন প্রপার্টি / তথ্যের পরিবর্তন: <b>কোনো পরিবর্তন নেই</b>\n"
            f"• লোকাল ইমেজ ডিরেক্টরি: <code>{LOCAL_PROPERTIES_BASE}</code> (সংযুক্ত ও সক্রিয়)\n"
            f"• লাইভ সাইট ও গুগল শিট ১০০% সিঙ্ক অবস্থায় রয়েছে।"
        )
        send_telegram_alert(msg)
        print("Heartbeat Telegram notification sent.")

if __name__ == '__main__':
    main()
