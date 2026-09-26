# SuperSmile Production Notes

เวอร์ชันนี้ปรับด้านเทคนิคสำหรับใช้งานจริงแล้ว: internal links, favicon/manifest, dependency-free slider, unique SEO metadata, structured data, responsive navigation, performance images, accessibility และทีมงาน 3 ตำแหน่ง

## ก่อนเผยแพร่สู่สาธารณะ
- หน้า Production ถอดรูปตัวละครเดิมออกแล้วและใช้ `dentist-placeholder.svg` เพื่อหลีกเลี่ยงการใช้ภาพที่ไม่เหมาะกับเว็บจริง; เปลี่ยนเป็นรูปทันตแพทย์จริงที่ได้รับอนุญาตเมื่อพร้อม และตรวจชื่อ/คุณวุฒิให้ถูกต้อง
- เปลี่ยน `staff-placeholder.svg` เป็นรูปบุคลากรจริงเมื่อพร้อม และใส่ชื่อจริงหากต้องการ
- ตรวจยืนยันราคาบริการ เวลาทำการ ที่อยู่ เบอร์โทร และข้อความทั้งหมดกับคลินิก
- เมื่อทราบโดเมนจริง ให้เพิ่ม canonical URL, `og:url`, absolute `og:image` และ sitemap.xml ตามโดเมนนั้น
- Instagram/TikTok แสดงไอคอนไว้แล้ว แต่ยังไม่ผูกลิงก์จนกว่าจะมี URL บัญชีจริง เพื่อไม่ให้ผู้ใช้ถูกพาไปหน้าทั่วไปของแพลตฟอร์ม
- จัดทำ/ตรวจข้อความ Privacy/PDPA ตามวิธีที่คลินิกเก็บข้อมูลจริง โดยเฉพาะหากเพิ่มฟอร์มนัดหมายในเว็บไซต์

## Slider
Slider ใหม่ไม่พึ่ง Flickity/CDN และรองรับหลาย `.carousel-cell[data-slide]` ได้อัตโนมัติ หากมีเพียง 1 สไลด์จะไม่สร้างจุด/ไม่ autoplay ซึ่งเป็นพฤติกรรมที่ถูกต้อง

## Admin + ตารางหมอ (เพิ่มในเวอร์ชันนี้)

- หลังบ้านอยู่ที่ `/admin/`
- ครั้งแรกให้เปิด `/admin/setup.php` เพื่อสร้างบัญชีผู้ดูแล
- ตารางหมอที่เปิดสถานะจะแสดงที่ `pages/dentists.html#schedule`
- ระบบนี้ต้องใช้ PHP 8.1+ และโฟลเดอร์ `storage/` ต้องเขียนไฟล์ได้
- VS Code Live Server / GitHub Pages ไม่รองรับ Admin เพราะเป็น static server
- อ่านขั้นตอนทั้งหมดได้ที่ `ADMIN_SETUP.md`
