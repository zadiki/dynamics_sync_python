import requests
from db_config import DynamicsDB, serialize_row

ENDPOINT = 'https://ycl.co.ke/dynamics-shipment-status.php'
TABLE_NAME = 'ZZZ_vw_SevkiyatPlanlama_App_01'
COLUMNS = [
    "SALESID", "REMAININGQTY", "SALESNAME", "REMAINSALESPHYSICAL",
    "SHIPPINGDATEREQUESTED", "SHIPPINGDATECONFIRMED", "DELIVERYCITY", "KUTU_SAYISI"
]

def run_sync():
    sql_query = f"SELECT {', '.join(COLUMNS)} FROM {TABLE_NAME}"
    data_list = []

    # Use the reusable connection
    try:
        with DynamicsDB() as cursor:
            cursor.execute(sql_query)
            rows = cursor.fetchall()
            data_list = [serialize_row(r, COLUMNS) for r in rows]
            print(f"Read {len(data_list)} rows.")

        if data_list:
            response = requests.post(ENDPOINT, json=data_list, timeout=30)
            response.raise_for_status()
            print("Sync successful!")

    except Exception as e:
        print(f"Error during sync: {e}")

if __name__ == "__main__":
    run_sync()