import pyodbc
import requests
import json

# ---------------- CONFIG ----------------
server = 'localhost\\SQLEXPRESS'
database = 'YOUR_DATABASE_NAME'
table_name = 'ZZZ_vw_SevkiyatPlanlama_App_01'
endpoint = 'https://ycl.co.ke/dynamics-shipment-status.php'

columns = [
    "SALESID",
    "REMAININGQTY",
    "SALESNAME",
    "REMAINSALESPHYSICAL",
    "SHIPPINGDATEREQUESTED",
    "SHIPPINGDATECONFIRMED",
    "DELIVERYCITY",
    "KUTU_SAYISI"
]

# ---------------- CONNECT TO SQL SERVER (Windows Auth) ----------------
conn_str = (
    'DRIVER={ODBC Driver 17 for SQL Server};'
    f'SERVER={server};'
    f'DATABASE={database};'
    'Trusted_Connection=yes;'
)

try:
    conn = pyodbc.connect(conn_str)
    cursor = conn.cursor()
    print("Connected to SQL Server successfully!")
except Exception as e:
    print("Failed to connect to SQL Server:", e)
    exit(1)

# ---------------- READ DATA ----------------
sql_query = f"SELECT {', '.join(columns)} FROM {table_name}"

try:
    cursor.execute(sql_query)
    rows = cursor.fetchall()
    data_list = []

    for row in rows:
        row_dict = {}
        for i, col in enumerate(columns):
            value = row[i]
            if hasattr(value, 'isoformat'):
                value = value.isoformat()
            row_dict[col] = value
        data_list.append(row_dict)

    print(f"Read {len(data_list)} rows from the database.")

except Exception as e:
    print("Failed to read data:", e)
    cursor.close()
    conn.close()
    exit(1)
finally:
    cursor.close()
    conn.close()

# ---------------- SEND POST REQUEST ----------------
try:
    headers = {'Content-Type': 'application/json'}
    response = requests.post(endpoint, json=data_list, headers=headers)

    print("POST request sent.")
    print("Response status code:", response.status_code)
    print("Response body:", response.text)

except Exception as e:
    print("Failed to send POST request:", e)