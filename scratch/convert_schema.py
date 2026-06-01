import re

with open('backend/database/schema.sql', 'r', encoding='utf-8') as f:
    sql = f.read()

# 1. Remove MySQL specific SETs
sql = re.sub(r'SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";\n', '', sql)
sql = sql.replace('SET time_zone = "+00:00";', "SET TIME ZONE 'UTC';")

# 2. Convert INT AUTO_INCREMENT to SERIAL
sql = sql.replace('INT AUTO_INCREMENT PRIMARY KEY', 'SERIAL PRIMARY KEY')

# 3. TINYINT(1) to SMALLINT
sql = sql.replace('TINYINT(1)', 'SMALLINT')

# 4. DATETIME to TIMESTAMP
sql = sql.replace('DATETIME DEFAULT NULL', 'TIMESTAMP DEFAULT NULL')
sql = sql.replace('DATETIME', 'TIMESTAMP')

# 5. Remove ENGINE=InnoDB...
sql = re.sub(r'\) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;', ');', sql)

# 6. ON UPDATE CURRENT_TIMESTAMP -> Not supported in PG directly without triggers, remove for now or let application handle it.
sql = sql.replace('ON UPDATE CURRENT_TIMESTAMP', '')

# 7. ON DUPLICATE KEY UPDATE
# patients table ON DUPLICATE KEY UPDATE
sql = re.sub(
    r'ON DUPLICATE KEY UPDATE\s+password=VALUES\(password\), full_name=VALUES\(full_name\), gender=VALUES\(gender\), age=VALUES\(age\), phone=VALUES\(phone\), disease=VALUES\(disease\), address=VALUES\(address\), care_area=VALUES\(care_area\), hospital=VALUES\(hospital\), ward=VALUES\(ward\), surgery_status=VALUES\(surgery_status\), high_watch=VALUES\(high_watch\);',
    r'''ON CONFLICT (hn) DO UPDATE SET
password=EXCLUDED.password, full_name=EXCLUDED.full_name, gender=EXCLUDED.gender, age=EXCLUDED.age, phone=EXCLUDED.phone, disease=EXCLUDED.disease, address=EXCLUDED.address, care_area=EXCLUDED.care_area, hospital=EXCLUDED.hospital, ward=EXCLUDED.ward, surgery_status=EXCLUDED.surgery_status, high_watch=EXCLUDED.high_watch;''',
    sql
)

# doctors table
sql = re.sub(
    r'ON DUPLICATE KEY UPDATE\s+password=VALUES\(password\), full_name=VALUES\(full_name\), license_no=VALUES\(license_no\), department=VALUES\(department\), hospital=VALUES\(hospital\);',
    r'''ON CONFLICT (username) DO UPDATE SET
password=EXCLUDED.password, full_name=EXCLUDED.full_name, license_no=EXCLUDED.license_no, department=EXCLUDED.department, hospital=EXCLUDED.hospital;''',
    sql
)

# admin_users
sql = re.sub(
    r'ON DUPLICATE KEY UPDATE password=VALUES\(password\), full_name=VALUES\(full_name\);',
    r'ON CONFLICT (username) DO UPDATE SET password=EXCLUDED.password, full_name=EXCLUDED.full_name;',
    sql
)

# 8. IF(cond, true, false)
sql = sql.replace(
    "IF(p.gender='หญิง','มี/สอบถามแล้ว','ไม่เกี่ยวข้อง')",
    "CASE WHEN p.gender='หญิง' THEN 'มี/สอบถามแล้ว' ELSE 'ไม่เกี่ยวข้อง' END"
)
sql = sql.replace(
    "IF(p.gender='หญิง','2026-05-01',NULL)",
    "CASE WHEN p.gender='หญิง' THEN CAST('2026-05-01' AS DATE) ELSE NULL END"
)
sql = sql.replace(
    "IF(p.high_watch=1, 82, 58)",
    "CASE WHEN p.high_watch=1 THEN 82 ELSE 58 END"
)
sql = sql.replace(
    "IF(p.high_watch=1, 'High', 'Medium')",
    "CASE WHEN p.high_watch=1 THEN 'High' ELSE 'Medium' END"
)

# 9. CONCAT in Postgres allows NULL to make the whole string NULL, better to use || or keep CONCAT (PG has CONCAT)
# PG CONCAT is fine.

with open('backend/database/schema_pg.sql', 'w', encoding='utf-8') as f:
    f.write(sql)
