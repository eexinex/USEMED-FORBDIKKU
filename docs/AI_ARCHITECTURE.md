# สถาปัตยกรรม AI ในระบบ USE MED (AI & Machine Learning Architecture)

เอกสารนี้สรุปภาพรวมของการนำเทคโนโลยี AI และ Machine Learning (ML) มาประยุกต์ใช้ในระบบ USE MED เพื่อช่วยในการพยากรณ์ความเสี่ยงและแนะนำการรักษาสำหรับผู้ป่วย

---

## 1. จุดที่ AI ถูกนำมาใช้งานในระบบ (AI Integration Points)

AI ถูกฝังอยู่ในระบบหลักๆ ดังนี้:

### 👨‍⚕️ หน้าจอของแพทย์ (Doctor Portal)
1. **Population Health Dashboard (`doctor/population-health.php`)**
   - แสดงผลการจัดกลุ่มผู้ป่วยตามความเสี่ยง (Risk Stratification) ที่คำนวณโดย AI
   - แสดงรายชื่อผู้ป่วยที่มีความเสี่ยงสูง (High Risk) และต้องได้รับการดูแลเป็นพิเศษ (P1, P2, P3 Priority)
2. **Patient Profile (`doctor/patient-profile.php`)**
   - แสดง **AI Predictive Score** (ความเสี่ยงในอนาคต 60 วัน)
   - แสดง **AI Treatment Recommendation** (ข้อเสนอแนะในการปรับเปลี่ยนยา เช่น ควรเปลี่ยนจาก CCB เป็น ARB)

### ⚙️ ฝั่งระบบหลังบ้าน (Backend & ML Service)
1. **การคำนวณคะแนนความเสี่ยงอัตโนมัติ (Risk Scoring)**
   - เมื่อมีการโหลดข้อมูลผู้ป่วย ระบบ PHP จะส่งข้อมูล Vitals, ประวัติผลแล็บ, และยาที่ใช้อยู่ ไปให้ ML Service ประมวลผลแบบ Real-time
2. **การวิเคราะห์แนวโน้ม (Trend Analysis)**
   - วิเคราะห์ข้อมูลตามแกนเวลา (Longitudinal data) เพื่อดูแนวโน้มของ HbA1c (เบาหวาน) และ Systolic BP (ความดันโลหิตสูง)

---

## 2. เทคโนโลยีที่ใช้ (Tech Stack)

ระบบ AI ถูกออกแบบแยกส่วนเป็น **Microservice** เพื่อความรวดเร็วและรองรับการขยายตัว โดยมีองค์ประกอบดังนี้:

### 🧠 ฝั่ง Machine Learning (Python)
- **FastAPI**: ใช้สร้าง RESTful API ที่รวดเร็วมาก เพื่อรับส่งข้อมูลกับ PHP (`ml_service/main.py`)
- **Uvicorn**: Web Server สำหรับรัน FastAPI
- **Pydantic**: ใช้ทำ Data Validation ให้แน่ใจว่าข้อมูลผู้ป่วยที่ส่งเข้ามาถูกต้องก่อนเข้า Model
- **Scikit-learn & XGBoost**: ไลบรารีสำหรับสร้างโมเดล Machine Learning (Classification เพื่อแยกประเภทโรค, Regression เพื่อพยากรณ์ค่า HbA1c/BP ล่วงหน้า)
- **Pandas/NumPy**: ใช้จัดการและทำความสะอาดข้อมูล (Data Preprocessing)

### 🔌 ฝั่ง Backend Client (PHP)
- **`backend/shared/ai_engine.php`**: เป็นสะพานเชื่อม (Client) ที่คอยดึงข้อมูลจากตาราง `patients`, จัดรูปแบบข้อมูลให้อยู่ในรูป JSON (Feature Extraction) และส่ง HTTP POST Request ไปที่โมเดล Python

### 🐳 ฝั่ง Deployment (Docker)
- **Supervisor (`supervisord.conf`)**: ทำหน้าที่รันทั้ง **Apache (PHP)** และ **Uvicorn (Python)** ให้ทำงานพร้อมกันภายใน Docker Container เดียวกัน ทำให้การรับส่งข้อมูลผ่าน `localhost:8000` เร็วมากระดับ Milliseconds (Zero Network Latency)

---

## 3. หลักการทำงานของโมเดลปัจจุบัน (Current Model Logic)

ปัจจุบัน API ใน `ml_service/main.py` เป็นโครงสร้างเตรียมพร้อมสำหรับเชื่อมต่อโมเดลจริง (Mock ML Inference) โดยมีลอจิกดังนี้:

1. **Diabetes Model (โมเดลเบาหวาน)**
   - รับค่า HbA1c ปัจจุบัน และประวัติ 2 รอบล่าสุด
   - จำแนก (Classify) Type 1 vs Type 2 จากค่า C-peptide
   - พยากรณ์ค่า HbA1c ล่วงหน้า 60 วัน โดยนำประสิทธิภาพของยาที่กำลังทาน (Insulin, Metformin) มาคำนวณหักล้าง
   - ออกคำแนะนำหาก HbA1c มีแนวโน้ม > 9.0%

2. **Hypertension Model (โมเดลความดันโลหิตสูง)**
   - รับค่า Systolic BP และข้อมูลยาลดความดัน (CCB, ARB, ACEI)
   - จำลองผลลัพธ์ (Treatment Effect Simulation) หากมีการเปลี่ยนชนิดยา
   - ออกคำแนะนำหากความดันโลหิตในอนาคตยังมีแนวโน้มสูงกว่า 160 mmHg

3. **Risk Stratification (การประเมินความฉุกเฉิน)**
   - รวมคะแนนความเสี่ยงจากทุกโรค (Overall Score Modifier) แล้วจัดลำดับความสำคัญ (P1 = วิกฤต, P2 = เฝ้าระวัง, P3 = ปกติ)
