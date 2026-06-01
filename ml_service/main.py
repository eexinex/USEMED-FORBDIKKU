from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from typing import Optional, List

app = FastAPI(title="USE MED - Predictive ML API", version="1.0.0")

class PatientFeatures(BaseModel):
    hn: str
    age: int
    gender: str
    disease_type: str  # "Diabetes", "Hypertension", or "Unknown"
    
    # Current Vitals & Labs
    systolic: Optional[float] = None
    diastolic: Optional[float] = None
    hba1c: Optional[float] = None
    c_peptide: Optional[float] = None
    
    # Longitudinal Vitals (-60 days, -120 days)
    history_systolic: List[float] = []
    history_hba1c: List[float] = []
    
    # Current Medications
    med_metformin: int = 0
    med_insulin: int = 0
    med_ccb: int = 0
    med_arb: int = 0
    med_acei: int = 0

class PredictionResponse(BaseModel):
    hn: str
    primary_condition: str
    
    # Diabetes Predictions
    predicted_diabetes_type: Optional[str] = None
    predicted_hba1c_60d: Optional[float] = None
    diabetes_risk_score: int = 0
    diabetes_recommendation: str = ""
    
    # Hypertension Predictions
    predicted_systolic_60d: Optional[float] = None
    hypertension_risk_score: int = 0
    hypertension_recommendation: str = ""
    
    # Combined Overall
    overall_priority: str = "P3"
    overall_score_modifier: int = 0

@app.post("/predict", response_model=PredictionResponse)
def predict_risk(patient: PatientFeatures):
    # นี่คือ Mock Implementation ของ ML Inference.
    # ในการทำงานจริง จะเป็นการโหลด XGBoost/RandomForest models และเรียก model.predict()
    
    response = PredictionResponse(hn=patient.hn, primary_condition=patient.disease_type)
    
    # 1. ลอจิกวิเคราะห์โรคเบาหวาน (Classification & Regression)
    if patient.disease_type in ["Diabetes", "Unknown", "Type 2 Diabetes Mellitus"] or patient.hba1c is not None:
        # จำแนก Type 1 vs Type 2 สำหรับเคส Unknown
        if patient.disease_type == "Unknown":
            if patient.c_peptide is not None and patient.c_peptide < 1.0:
                response.predicted_diabetes_type = "Type 1"
            else:
                response.predicted_diabetes_type = "Type 2"
        else:
            response.predicted_diabetes_type = patient.disease_type
            
        # พยากรณ์ HbA1c ล่วงหน้า 60 วัน โดยอิงจาก Trend ในอดีต
        current_a1c = patient.hba1c if patient.hba1c else 7.0
        trend = 0.0
        if len(patient.history_hba1c) > 0:
            trend = current_a1c - patient.history_hba1c[0]
            
        future_a1c = current_a1c + (trend * 0.5)
        
        # หักลบด้วยประสิทธิภาพยาที่ทานอยู่
        if patient.med_insulin: future_a1c -= 0.5
        elif patient.med_metformin: future_a1c -= 0.2
        
        response.predicted_hba1c_60d = round(future_a1c, 2)
        
        if response.predicted_hba1c_60d > 9.0:
            response.diabetes_risk_score = 30
            response.diabetes_recommendation = "AI พยากรณ์ว่า HbA1c ในอีก 60 วันมีแนวโน้มสูงเกิน 9.0% แนะนำให้พิจารณาปรับโดสยา Insulin หรือเปลี่ยนแผนการรักษา"
        elif response.predicted_hba1c_60d > 8.0:
            response.diabetes_risk_score = 15
            response.diabetes_recommendation = "AI พยากรณ์ว่า HbA1c จะมีแนวโน้มสูงขึ้นในรอบถัดไป ควรเตือนผู้ป่วยให้คุมอาหารอย่างเคร่งครัด"
            
    # 2. ลอจิกวิเคราะห์ความดันโลหิต (Regression & Treatment Effect)
    if patient.disease_type in ["Hypertension"] or patient.systolic is not None:
        current_sys = patient.systolic if patient.systolic else 130
        
        future_sys = current_sys
        # ประเมินประสิทธิภาพยา (Simulation)
        if patient.med_ccb and not patient.med_arb:
            # AI เสนอแนะว่า ARB อาจจะดีกว่าสำหรับเคสนี้
            if current_sys > 150:
                future_sys = current_sys - 5
                response.hypertension_recommendation = "โมเดลวิเคราะห์จาก Longitudinal Data: หากปรับยาจากกลุ่ม CCB เป็น ARB มีแนวโน้มช่วยลดความดันลงได้อีกประมาณ 10-15 mmHg ในรอบถัดไป"
        elif patient.med_arb:
            future_sys = current_sys - 10
            
        response.predicted_systolic_60d = round(future_sys, 2)
        
        if response.predicted_systolic_60d > 160:
            response.hypertension_risk_score = 25
            if not response.hypertension_recommendation:
                response.hypertension_recommendation = "AI พยากรณ์ว่าความดันโลหิตจะอยู่ในระดับอันตราย (>160) ในรอบการรักษาหน้า แนะนำปรับยาลดความดัน"
                
    # 3. รวมคะแนน (Combined Model)
    response.overall_score_modifier = response.diabetes_risk_score + response.hypertension_risk_score
    
    if response.overall_score_modifier >= 30:
        response.overall_priority = "P1"
    elif response.overall_score_modifier >= 15:
        response.overall_priority = "P2"
    else:
        response.overall_priority = "P3"
        
    return response

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)
