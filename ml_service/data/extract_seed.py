import zipfile
import xml.etree.ElementTree as ET
import os

def col2num(col):
    num = 0
    for c in col:
        if c in '0123456789': break
        num = num * 26 + (ord(c.upper()) - ord('A')) + 1
    return num - 1

def parse_xlsx(file_path, limit=None):
    try:
        with zipfile.ZipFile(file_path, 'r') as z:
            shared_strings = []
            if 'xl/sharedStrings.xml' in z.namelist():
                xml_content = z.read('xl/sharedStrings.xml')
                root = ET.fromstring(xml_content)
                ns = {'ns': root.tag.split('}')[0].strip('{')} if '}' in root.tag else {}
                ns_prefix = 'ns:' if ns else ''
                
                for si in root.findall(f'{ns_prefix}si', ns):
                    t = si.find(f'{ns_prefix}t', ns)
                    if t is not None:
                        shared_strings.append(t.text)
                    else:
                        texts = [r_t.text for r in si.findall(f'{ns_prefix}r', ns) for r_t in r.findall(f'{ns_prefix}t', ns) if r_t.text]
                        shared_strings.append("".join(texts) if texts else "")
            
            # sheet2 contains data
            sheet_xml = z.read('xl/worksheets/sheet2.xml')
            root = ET.fromstring(sheet_xml)
            ns = {'ns': root.tag.split('}')[0].strip('{')} if '}' in root.tag else {}
            ns_prefix = 'ns:' if ns else ''
            
            sheet_data = root.find(f'{ns_prefix}sheetData', ns)
            if sheet_data is None: return [], []
            
            rows = sheet_data.findall(f'{ns_prefix}row', ns)
            
            data = []
            headers = []
            max_col = 0
            
            # First pass to find max columns from header
            if len(rows) > 0:
                header_row = rows[0]
                for c in header_row.findall(f'{ns_prefix}c', ns):
                    col_idx = col2num(c.attrib.get('r', 'A'))
                    if col_idx > max_col: max_col = col_idx
            
            headers = [""] * (max_col + 1)
            if len(rows) > 0:
                for c in rows[0].findall(f'{ns_prefix}c', ns):
                    col_idx = col2num(c.attrib.get('r', 'A'))
                    val_node = c.find(f'{ns_prefix}v', ns)
                    val = ""
                    if val_node is not None:
                        val = val_node.text
                        if c.attrib.get('t') == 's' and val.isdigit():
                            try: val = shared_strings[int(val)]
                            except IndexError: pass
                    headers[col_idx] = val

            rows_to_process = rows[1:limit+1] if limit else rows[1:]
            for row in rows_to_process:
                row_vals = [""] * (max_col + 1)
                for c in row.findall(f'{ns_prefix}c', ns):
                    col_idx = col2num(c.attrib.get('r', 'A'))
                    if col_idx > max_col: continue # ignore extra
                    val_node = c.find(f'{ns_prefix}v', ns)
                    if val_node is not None:
                        val = val_node.text
                        if c.attrib.get('t') == 's' and val.isdigit():
                            try: val = shared_strings[int(val)]
                            except IndexError: pass
                        row_vals[col_idx] = val
                data.append(row_vals)
                    
            return headers, data
    except Exception as e:
        print(f"Error parsing {file_path}: {e}")
        return [], []

def generate_sql():
    base_dir = r"c:\Users\nsand\Documents\GitHub\USEMED-FORBDIKKU"
    dm_path = os.path.join(base_dir, ".agents", "dataset_for_agent", "data_dictionary_diabetes_example.xlsx")
    ht_path = os.path.join(base_dir, ".agents", "dataset_for_agent", "data_dictionary_hypertension_example.xlsx")
    out_path = os.path.join(base_dir, "backend", "database", "seed_longitudinal.sql")
    
    print("Parsing Diabetes data...")
    dm_headers, dm_data = parse_xlsx(dm_path, limit=None)
    
    print("Parsing Hypertension data...")
    ht_headers, ht_data = parse_xlsx(ht_path, limit=None)
    
    sql_lines = ["-- Generated Seed Data from ML Datasets", "START TRANSACTION;"]
    
    patient_id_counter = 100
    
    # Process Diabetes
    for row in dm_data:
        def get_val(col_name):
            try:
                idx = dm_headers.index(col_name)
                return str(row[idx]) if idx < len(row) and row[idx] else ""
            except ValueError:
                return ""
                
        age = get_val('age')
        hba1c = get_val('lab_hba1c_0')
        
        # Skip if unusable
        if not age or not hba1c:
            continue
            
        patient_id_counter += 1
        hn = f"HN{patient_id_counter:04d}"
        gender_raw = get_val('sex')
        gender = "ชาย" if gender_raw == 'MALE' else "หญิง"
        
        sql_lines.append(f"INSERT INTO patients (id, hn, password, full_name, gender, age, disease, care_area) VALUES ({patient_id_counter}, '{hn}', '123456', 'DM Patient {patient_id_counter}', '{gender}', {age}, 'Type 2 Diabetes Mellitus', 'OPD') ON CONFLICT (id) DO NOTHING;")
        sql_lines.append(f"INSERT INTO patient_longitudinal_records (patient_id, hn, period_offset, hba1c, med_metformin) VALUES ({patient_id_counter}, '{hn}', 0, {hba1c}, 1);")

    # Process Hypertension
    for row in ht_data:
        def get_val_ht(col_name):
            try:
                idx = ht_headers.index(col_name)
                return str(row[idx]) if idx < len(row) and row[idx] else ""
            except ValueError:
                return ""
                
        age = get_val_ht('age')
        sys = get_val_ht('vitalsign_sbp_0')
        dia = get_val_ht('vitalsign_dbp_0')
        
        # Skip if unusable
        if not age or not sys or not dia:
            continue
            
        patient_id_counter += 1
        hn = f"HN{patient_id_counter:04d}"
        gender_raw = get_val_ht('sex')
        gender = "ชาย" if gender_raw == 'MALE' else "หญิง"
        
        sql_lines.append(f"INSERT INTO patients (id, hn, password, full_name, gender, age, disease, care_area) VALUES ({patient_id_counter}, '{hn}', '123456', 'HT Patient {patient_id_counter}', '{gender}', {age}, 'Hypertension', 'OPD') ON CONFLICT (id) DO NOTHING;")
        sql_lines.append(f"INSERT INTO patient_longitudinal_records (patient_id, hn, period_offset, systolic, diastolic, med_ccb) VALUES ({patient_id_counter}, '{hn}', 0, {sys}, {dia}, 1);")
        
    sql_lines.append("COMMIT;")
    
    with open(out_path, 'w', encoding='utf-8') as f:
        f.write("\n".join(sql_lines))
        
    print(f"Generated {out_path} with {len(sql_lines)} lines.")

if __name__ == "__main__":
    generate_sql()
