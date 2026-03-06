import requests
from db_config import DynamicsDB, serialize_row,APIConfig

# The endpoint for the Job Line handler
ENDPOINT = f"{APIConfig.BASE_URL}/athcountingjobline.php"

# The table configuration
LINE_TABLE = 'ATHCOUNTINGJOBLINE'
HEADER_TABLE = 'ATHCOUNTINGJOBTABLE'

COLUMNS = [
    "ATHCOUNTINGJOBID", "SALESID", "ITEMID", "INVENTTRANSID",
    "QTY", "SENTQTY", "NAME", "SALESUNIT", "ORDERED", "REMAIN",
    "QTYAX", "QTYDIFFERENCE", "MODIFIEDDATETIME", "MODIFIEDBY",
    "MODIFIEDTRANSACTIONID", "CREATEDBY", "CREATEDTRANSACTIONID",
    "DATAAREAID", "RECVERSION", "RECID"
]

def run_sync():
    # Using an INNER JOIN or subquery to filter by the header's Location ID
    sql_query = f"""
        SELECT {', '.join([f'L.{col}' for col in COLUMNS])}
        FROM {LINE_TABLE} AS L
        WHERE L.ATHCOUNTINGJOBID IN (
            SELECT ATHCOUNTINGJOBID 
            FROM {HEADER_TABLE} 
            WHERE COUNTEDINVENTLOCATIONID LIKE 'AC%'
        )
    """
    
    data_list = []

    try:
        with DynamicsDB() as cursor:
            print("Fetching filtered Job Line data from Dynamics...")
            cursor.execute(sql_query)
            rows = cursor.fetchall()
            
            # Serialize rows into dictionaries
            data_list = [serialize_row(r, COLUMNS) for r in rows]
            print(f"Read {len(data_list)} rows matching the 'AC%' job filter.")

        if data_list:
            print(f"Posting data to {ENDPOINT}...")
            # Using a generous timeout as lines can be numerous
            response = requests.post(ENDPOINT, json=data_list,headers=APIConfig.get_headers(), 
                timeout=120)
            print("POST request sent.")
            print("Response status code:", response.status_code)
            print("Response body:", response.text)
            response.raise_for_status()
            
            result = response.json()
            if result.get("success"):
                print(f"Sync successful! Inserted {result.get('inserted')} lines.")
            else:
                print(f"Server Error: {result.get('error')}")
        else:
            print("No lines found for the specified jobs.")

    except Exception as e:
        print(f"Error during sync: {e}")

if __name__ == "__main__":
    run_sync()