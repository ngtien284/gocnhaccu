import os
import shutil
import time
import hashlib
from icrawler.builtin import BingImageCrawler

# Đường dẫn thư mục uploads của bạn (Sửa 'web' thành 'gocnhaccu' nếu cần)
output_dir = r"C:\xampp\htdocs\web\uploads"
os.makedirs(output_dir, exist_ok=True)

# Bộ lưu mã Hash để phát hiện và loại bỏ ảnh bị Bing chặn trả về trùng lặp
downloaded_hashes = set()

def get_file_hash(file_path):
    """Tính mã MD5 của file ảnh"""
    hasher = hashlib.md5()
    with open(file_path, 'rb') as f:
        hasher.update(f.read())
    return hasher.hexdigest()

# Danh sách sản phẩm kèm từ khóa chi tiết
products = [
    # --- GUITAR ---
    ("nc11", "Guitar Acoustic Yamaha FX370C Natural"),
    ("nc12", "Guitar Classic Yamaha C40 Nylon"),
    ("nc13", "Guitar Classic Yamaha C70 Natural"),
    ("nc14", "Guitar Acoustic Yamaha FG800M Matte"),
    ("nc15", "Guitar Acoustic Yamaha FS820 Crimson"),
    ("nc16", "Guitar Electric Yamaha Pacifica 112V Black"),
    ("nc17", "Guitar Electric Yamaha Revstar RSE20 Swift Blue"),
    ("nc18", "Guitar Bass Yamaha TRBX174 Red"),
    ("nc19", "Guitar Bass Yamaha TRBX304 White"),
    ("nc20", "Guitar Acoustic Yamaha APX600 Thin Body"),
    ("nc21", "Guitar Electric Fender Player Stratocaster Black"),
    ("nc22", "Guitar Electric Fender Player Telecaster Butterscotch"),
    ("nc23", "Guitar Acoustic Fender FA-125 Dreadnought"),
    ("nc24", "Guitar Acoustic Fender CC-60S Concert"),
    ("nc25", "Guitar Bass Fender Player Precision Bass"),
    ("nc26", "Guitar Bass Fender Player Jazz Bass Sunburst"),
    ("nc27", "Guitar Electric Fender Vintera 60s Stratocaster"),
    ("nc28", "Guitar Acoustic Fender Newporter Player Gold"),
    ("nc29", "Guitar Acoustic Fender Redondo Player Slate"),
    ("nc30", "Guitar Electric Fender Squier Affinity Stratocaster"),
    ("nc31", "Guitar Electric Fender Squier Telecaster Custom"),
    ("nc32", "Guitar Classic Fender Educational Traditional"),
    ("nc33", "Guitar Acoustic Fender CD-60SCE Natural"),

    # --- PIANO ---
    ("nc34", "Piano Điện Yamaha P-45B Digital Piano"),
    ("nc35", "Piano Điện Yamaha P-125a Black"),
    ("nc36", "Piano Điện Yamaha ARIUS YDP-145 Rosewood"),
    ("nc37", "Piano Điện Yamaha ARIUS YDP-165 Black"),
    ("nc38", "Piano Điện Yamaha Clavinova CLP-735 Polished Ebony"),
    ("nc39", "Piano Cơ Upright Yamaha U1J PE Ebony"),
    ("nc40", "Piano Điện Roland FP-30X Black"),
    ("nc41", "Piano Điện Roland FP-60X White"),
    ("nc42", "Piano Điện Roland RP-107 Digital Piano"),
    ("nc43", "Piano Điện Roland HP-702 Charcoal Black"),
    ("nc44", "Piano Điện Roland LX-705 Dark Rosewood"),
    ("nc45", "Piano Điện Casio CDP-S360 Compact"),
    ("nc46", "Piano Điện Casio Privia PX-S1100 White"),
    ("nc47", "Piano Điện Casio Privia PX-S3100 Black"),
    ("nc48", "Piano Điện Casio Celviano AP-470 Black"),
    ("nc49", "Piano Điện Korg LP-380U Black"),

    # --- TRỐNG ---
    ("nc50", "Bộ Trống Cơ Pearl Export EXX725FP Smokey Chrome"),
    ("nc51", "Bộ Trống Cơ Pearl Roadshow RS525SC Jet Black"),
    ("nc52", "Bộ Trống Cơ Pearl Decade Maple DMP925 Satin Brown"),
    ("nc53", "Bộ Trống Cơ Pearl Session Studio Select Gloss Barnwood"),
    ("nc54", "Bộ Trống Cơ Tama Imperialstar IP52H6 Hairline Black"),
    ("nc55", "Bộ Trống Cơ Tama Rhythm Mate RM52KH6 Red"),
    ("nc56", "Bộ Trống Cơ Tama Superstar Classic CL52KR Blue"),
    ("nc57", "Trống Cajon Meinl Headliner HCAJ1NT Natural Wood"),
    ("nc58", "Bộ Trống Conga Meinl Marathon Series Amber"),
    ("nc59", "Bộ Trống Điện Tử Yamaha DTX6K3-X Electronic Drum"),
    ("nc60", "Bộ Trống Cơ Yamaha Stage Custom Birch Cranberry Red"),
    ("nc61", "Bộ Trống Cơ Yamaha Rydeen RDP2F5 Fine Blue"),
    ("nc62", "Bộ Trống Điện Tử Roland V-Drums TD-07DMK"),
    ("nc63", "Bộ Trống Điện Tử Roland V-Drums TD-17KVX2"),

    # --- UKULELE ---
    ("nc64", "Đàn Ukulele Kala Soprano KA-15S Mahogany"),
    ("nc65", "Đàn Ukulele Kala Concert KA-C Satin"),
    ("nc66", "Đàn Ukulele Kala Tenor KA-T Mahogany"),
    ("nc67", "Đàn Ukulele Kala Baritone KA-B Wood"),
    ("nc68", "Đàn Ukulele Kala Waterman Soprano Matte"),
    ("nc69", "Đàn Ukulele Kala Exotic Mahogany Concert"),
    ("nc70", "Đàn Ukulele Kala Solid Spruce Top Concert"),
    ("nc71", "Đàn Ukulele Kala Gloss Mahogany Tenor"),
    ("nc72", "Đàn Ukulele Kala Striped Ebony Concert"),
    ("nc73", "Đàn Ukulele Kala Archtop Tenor KA-JTE"),

    # --- VIOLIN ---
    ("nc74", "Đàn Violin Cremona SV-175 4/4 Outfit"),
    ("nc75", "Đàn Violin Cremona SV-500 4/4 Premier"),
    ("nc76", "Đàn Violin Cremona SV-130 3/4 Student"),
    ("nc77", "Đàn Violin Cremona SV-130 1/2 Junior"),
    ("nc78", "Đàn Violin Cremona SV-800 4/4 Master"),
    ("nc79", "Đàn Violin Yamaha V5SC 4/4 Student Acoustic"),
    ("nc80", "Đàn Violin Yamaha V7SG 4/4 Handcrafted"),
    ("nc81", "Đàn Violin Điện Yamaha YEV-104 Natural Wood"),

    # --- ORGAN ---
    ("nc82", "Đàn Organ Casio CT-S1 Black Portable Keyboard"),
    ("nc83", "Đàn Organ Casio CT-S300 Black Keyboard"),
    ("nc84", "Đàn Organ Casio CT-X700 High Grade Keyboard"),
    ("nc85", "Đàn Organ Casio CT-X3000 Portable Keyboard"),
    ("nc86", "Đàn Organ Casio CT-X5000 Flagship Keyboard"),
    ("nc87", "Đàn Organ Casio LK-S450 Lighting Key"),
    ("nc88", "Đàn Organ Yamaha PSR-E273 Portable Keyboard"),
    ("nc89", "Đàn Organ Yamaha PSR-E373 61-Key"),
    ("nc90", "Đàn Organ Yamaha PSR-E473 Portable Keyboard"),
    ("nc91", "Đàn Organ Yamaha PSR-SX600 Arranger Keyboard"),
    ("nc92", "Đàn Organ Roland E-X10 Interactive Keyboard"),
    ("nc93", "Đàn Organ Roland E-X50 Arranger Keyboard"),
    ("nc94", "Đàn Organ Korg EK-50 Entertainer Keyboard"),
    ("nc95", "Đàn Organ Korg Pa300 Professional Arranger"),

    # --- SÁO FLUTE ---
    ("nc96", "Sáo Flute Muramatsu EX-CCE Silver Flute"),
    ("nc97", "Sáo Flute Muramatsu EX-RCE Ring Key"),
    ("nc98", "Sáo Flute Muramatsu GX-CCE Handmade"),
    ("nc99", "Sáo Flute Muramatsu GX-RCE Silver"),
    ("nc100", "Sáo Flute Muramatsu DS-CCE Solid Silver"),
    ("nc101", "Sáo Flute Muramatsu DS-RCE Professional"),
    ("nc102", "Sáo Flute Muramatsu PTP-RCE Platinum Plated"),
    ("nc103", "Sáo Flute Yamaha YFL-312 Intermediate Flute"),
    ("nc104", "Sáo Flute Yamaha YFL-472 Open Hole Flute"),
    ("nc105", "Sáo Alt Flute Yamaha YFL-A421 Alto Flute"),

    # --- PHỤ KIỆN ---
    ("pk4", "DAddario EJ16 Phosphor Bronze guitar strings pack"),
    ("pk5", "Alice AT-01A Digital Clip-On Guitar Tuner"),
    ("pk6", "Fender Deluxe Poly Guitar Strap Black"),
    ("pk7", "Fender Premium Celluloid Guitar Picks Mix"),
    ("pk8", "DAddario EXL110 Nickel Wound Electric Strings"),
    ("pk9", "Yamaha FC4A Piano Sustain Pedal"),
    ("pk10", "Double X Heavy Duty Keyboard Stand"),
    ("pk11", "Adjustable Padded Piano Bench Leather Black"),
    ("pk12", "Casio SP-20 Piano Sustain Pedal"),
    ("pk13", "Roland KS-10Z Heavy-Duty Z Stand"),
    ("pk14", "Korg Folding Sheet Music Stand Black"),
    ("pk15", "Tama Traditional Oak 5A Wood Tip Drumsticks"),
    ("pk16", "Pearl Coated Ambassador 14 Snare Drum Head"),
    ("pk17", "Meinl MSTP 12 Inch Practice Pad"),
    ("pk18", "Tama Iron Cobra HP200P Single Bass Pedal"),
    ("pk19", "Meinl Concert HD1 Hickory Drumsticks 5B"),
    ("pk20", "Pearl K-080 Standard Tuning Drum Key"),
    ("pk21", "Cremona VP-50 Brazilwood Violin Bow 4/4"),
    ("pk22", "Alice A013 Natural Resin Violin Rosin"),
    ("pk23", "Alice A016 Adjustable Violin Shoulder Rest"),
    ("pk24", "Alice A703 Stainless Steel Violin Strings Set"),
    ("pk25", "Alice A206 Clear Nylon Ukulele Strings"),
    ("pk26", "Kala Concert Ukulele Padded Gig Bag"),
    ("pk27", "Kala Ukulele Shoulder Strap Clip-on"),
    ("pk28", "Muramatsu Flute Cleaning Swab Cloth"),
    ("pk29", "Yamaha Microfiber Instrument Polishing Cloth"),
    ("pk30", "Yamaha Deluxe Flute Hard Storage Case")
]

print("--- BẮT ĐẦU TẢI ẢNH THẬT (ĐÃ CHỐNG CHẶN BING & CHỐNG TRÙNG ẢNH) ---")

for code, query in products:
    temp_dir = os.path.join(output_dir, "_temp")
    if os.path.exists(temp_dir):
        shutil.rmtree(temp_dir)
    
    # Lấy 3 ảnh ứng viên đầu tiên
    bing_crawler = BingImageCrawler(storage={'root_dir': temp_dir}, log_level=40)
    bing_crawler.crawl(keyword=query, max_num=3)

    if os.path.exists(temp_dir):
        files = os.listdir(temp_dir)
        success = False
        
        for file_name in files:
            temp_file_path = os.path.join(temp_dir, file_name)
            file_hash = get_file_hash(temp_file_path)
            
            # Chỉ lưu nếu bức ảnh chưa từng bị dính trùng
            if file_hash not in downloaded_hashes:
                downloaded_hashes.add(file_hash)
                dest_file = os.path.join(output_dir, f"{code}.jpg")
                shutil.move(temp_file_path, dest_file)
                print(f"✓ Đã lưu: {code}.jpg ({query})")
                success = True
                break
                
        if not success:
            print(f"⚠️ {code}.jpg: Bị trùng ảnh bot Bing! Đang bỏ qua...")
            
        shutil.rmtree(temp_dir)
        
    # Thời gian nghỉ 1.5s giữa các yêu cầu để tránh bị khóa IP
    time.sleep(1.5)

print("\n🎉 ĐÃ TẢI XONG BỘ ẢNH THẬT ĐỘC LẬP TỪNG SẢN PHẨM!")