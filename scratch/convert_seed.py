import re

with open('backend/database/seed_longitudinal.sql', 'r', encoding='utf-8') as f:
    sql = f.read()

# Replace INSERT IGNORE INTO patients ... VALUES (...) with INSERT INTO patients ... VALUES (...) ON CONFLICT (id) DO NOTHING;
# We need to match the whole statement until the semicolon.
sql = re.sub(
    r'INSERT IGNORE INTO patients (.*?) VALUES (.*?);',
    r'INSERT INTO patients \1 VALUES \2 ON CONFLICT (id) DO NOTHING;',
    sql
)

with open('backend/database/seed_longitudinal_pg.sql', 'w', encoding='utf-8') as f:
    f.write(sql)
