import requests
from db_config import DynamicsDB, serialize_row, APIConfig

# The endpoint for the Status Counts handler
ENDPOINT = f"{APIConfig.BASE_URL}/order_status.php"

# The table configuration
TABLE_NAME = 'ATHSALESORDERCOUNTS02'

def run_sync_order_status():
    # Constructing the query with aliases for the status columns
    sql_query = f"""
        SELECT 
            DESCRIPTION,
            FROMDATE,
            TODATE,
            Status1 as 'OpenOrder',
            Status2 as 'Delivered',
            Status3 as 'invoiced',
            Status4 as 'cancelled'
        FROM {TABLE_NAME}
    """
    
    # Matching the keys to the SQL aliases for JSON serialization
    JSON_KEYS = [
        'DESCRIPTION', 
        'FROMDATE', 
        'TODATE', 
        'OpenOrder', 
        'Delivered', 
        'invoiced', 
        'cancelled'
    ]
    
    data_list = []

    try:
        # Utilize the DynamicsDB context manager
        with DynamicsDB() as cursor:
            print(f"Fetching status count data from {TABLE_NAME}...")
            cursor.execute(sql_query)
            rows = cursor.fetchall()
            
            # Map the rows to a list of dictionaries
            data_list = [serialize_row(r, JSON_KEYS) for r in rows]
            print(f"Read {len(data_list)} rows from Dynamics.")

        if data_list:
            print(f"Posting data to {ENDPOINT}...")
            # Use centralized headers and timeout logic
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
                print(f"Sync successful! Status records processed: {result.get('inserted')}")
            else:
                print(f"Server Error: {result.get('error')}")
        else:
            print(f"No records found in {TABLE_NAME}.")

    except Exception as e:
        print(f"Error during sync for {TABLE_NAME}: {e}")

if __name__ == "__main__":
    run_sync_order_status()