<?php
// backend/shared/ai_engine.php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';

function ai_calculate_risk(array $data): array
{
    $score = 0;
    $factors = [];
    $recommendations = [];

    $age = (float) ($data['age'] ?? 0);
    $systolic = (float) ($data['systolic'] ?? 0);
    $diastolic = (float) ($data['diastolic'] ?? 0);
    $glucose = (float) ($data['glucose'] ?? 0);
    $hba1c = (float) ($data['hba1c'] ?? 0);
    $bmi = (float) ($data['bmi'] ?? 0);
    $cholesterol = (float) ($data['cholesterol'] ?? 0);

    if ($age >= 60) {
        $score += 10;
        $factors[] = 'อายุมากกว่า 60 ปี';
    }

    if ($systolic >= 160 || $diastolic >= 100) {
        $score += 25;
        $factors[] = 'ความดันโลหิตสูงมาก';
        $recommendations[] = 'ควรติดตามความดันอย่างใกล้ชิด';
    } elseif ($systolic >= 140 || $diastolic >= 90) {
        $score += 15;
        $factors[] = 'ความดันโลหิตสูง';
    }

    if ($glucose >= 200) {
        $score += 25;
        $factors[] = 'ระดับน้ำตาลสูงมาก';
        $recommendations[] = 'ควรประเมินการควบคุมเบาหวานและการใช้ยา';
    } elseif ($glucose >= 126) {
        $score += 15;
        $factors[] = 'ระดับน้ำตาลสูง';
    }

    if ($hba1c >= 9) {
        $score += 25;
        $factors[] = 'HbA1c สูงมาก';
        $recommendations[] = 'ควรปรับแผนการรักษาและติดตามในระยะสั้น';
    } elseif ($hba1c >= 7) {
        $score += 15;
        $factors[] = 'HbA1c สูงกว่าค่าเป้าหมาย';
    }

    if ($bmi >= 30) {
        $score += 15;
        $factors[] = 'BMI อยู่ในกลุ่มอ้วน';
        $recommendations[] = 'แนะนำควบคุมน้ำหนัก อาหาร และออกกำลังกาย';
    } elseif ($bmi >= 25) {
        $score += 8;
        $factors[] = 'BMI อยู่ในกลุ่มน้ำหนักเกิน';
    }

    if ($cholesterol >= 240) {
        $score += 10;
        $factors[] = 'ไขมันในเลือดสูง';
    }

    $score = max(0, min(100, $score));

    if ($score >= 70) {
        $level = 'High';
        $level_th = 'สูง';
        $color = 'red';
        $summary = 'ผู้ป่วยมีความเสี่ยงสูง ควรติดตามอย่างใกล้ชิด';
    } elseif ($score >= 40) {
        $level = 'Medium';
        $level_th = 'ปานกลาง';
        $color = 'orange';
        $summary = 'ผู้ป่วยมีความเสี่ยงปานกลาง ควรติดตามต่อเนื่อง';
    } else {
        $level = 'Low';
        $level_th = 'ต่ำ';
        $color = 'green';
        $summary = 'ผู้ป่วยมีความเสี่ยงต่ำ แต่ควรดูแลสุขภาพต่อเนื่อง';
    }

    if (empty($factors)) {
        $factors[] = 'ไม่พบปัจจัยเสี่ยงเด่นจากข้อมูลที่กรอก';
    }

    if (empty($recommendations)) {
        $recommendations[] = 'ติดตามผลตามนัด';
        $recommendations[] = 'ควบคุมอาหาร ออกกำลังกาย และรับประทานยาตามแพทย์สั่ง';
    }

    return [
        'score' => $score,
        'level' => $level,
        'level_th' => $level_th,
        'color' => $color,
        'summary' => $summary,
        'factors' => $factors,
        'recommendations' => $recommendations,
    ];
}

function ai_demo_result(): array
{
    return ai_calculate_risk([
        'age' => 58,
        'systolic' => 148,
        'diastolic' => 92,
        'glucose' => 142,
        'hba1c' => 7.8,
        'bmi' => 27.4,
        'cholesterol' => 218,
    ]);
}