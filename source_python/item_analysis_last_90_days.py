import requests
from db_config import DynamicsDB, serialize_row, APIConfig

# The endpoint for the Summary 01 Table handler
ENDPOINT = f"{APIConfig.BASE_URL}/unfullfilled_last_90_days.php"

# The table configuration
TABLE_NAME = 'ATHSALESORDERSUMMARY01'

def run_sync_90_days():
    # Constructing the query with the specific aliases for recent dates and unfulfilled amounts
    sql_query = f"""
        SELECT 
            ITEMNAME,
            MOSTRECENTINVOICEDATE as 'LatInvoiceDate',
            MOSTRECENTORDERDATE as 'LastOrderDate',
            MOSTRECENTPRODDATE,
            UNFULFILLEDAMOUNT90DAYS,
            DAYLASTSALES
        FROM {TABLE_NAME}
    """
    
    # Matching the keys to the SQL selection for accurate JSON mapping
    JSON_KEYS = [
        'ITEMNAME', 
        'LatInvoiceDate', 
        'LastOrderDate', 
        'MOSTRECENTPRODDATE', 
        'UNFULFILLEDAMOUNT90DAYS', 
        'DAYLASTSALES'
    ]
    
    data_list = []

    try:
        # Utilize the DynamicsDB context manager from your db_config
        with DynamicsDB() as cursor:
            print(f"Fetching record data from {TABLE_NAME}...")
            cursor.execute(sql_query)
            rows = cursor.fetchall()
            
            # Map the rows to a list of dictionaries for JSON serialization
            data_list = [serialize_row(r, JSON_KEYS) for r in rows]
            print(f"Read {len(data_list)} rows from Dynamics.")

        if data_list:
            print(f"Posting data to {ENDPOINT}...")
            # Utilizing your standard APIConfig headers and timeout
            response = requests.post(
                ENDPOINT, 
                json=data_list, 
                headers=APIConfig.get_headers(), 
                timeout=120
            )
            
            print("POST request sent.")
            print("Response status code:", response.status_code)
            
            response.raise_for_status()
            
            result = response.json()
            if result.get("success"):
                print(f"Sync successful! Records processed: {result.get('inserted')}")
            else:
                print(f"Server Error: {result.get('error')}")
        else:
            print(f"No records found in {TABLE_NAME} to sync.")

    except Exception as e:
        print(f"Error during sync for {TABLE_NAME}: {e}")

if __name__ == "__main__":
    run_sync_90_days()