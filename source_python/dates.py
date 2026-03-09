import requests
from db_config import DynamicsDB, serialize_row, APIConfig

# The endpoint for the Summary Report handler
ENDPOINT = f"{APIConfig.BASE_URL}/dates.php"

# The table configuration
TABLE_NAME = 'Athsalesordersummaryreport'

def run_sync_dates():
    # Constructing the query for the report date ranges
    sql_query = f"""
        SELECT 
            DESCRIPTION,
            FROMDATE,
            TODATE 
        FROM {TABLE_NAME}
    """
    
    # Keys matching the column names for serialization
    JSON_KEYS = ['DESCRIPTION', 'FROMDATE', 'TODATE']
    
    data_list = []

    try:
        # Utilize the DynamicsDB context manager from your db_config
        with DynamicsDB() as cursor:
            print(f"Fetching report metadata from {TABLE_NAME}...")
            cursor.execute(sql_query)
            rows = cursor.fetchall()
            
            # Map the rows to a list of dictionaries for JSON serialization
            data_list = [serialize_row(r, JSON_KEYS) for r in rows]
            print(f"Read {len(data_list)} rows from Dynamics.")

    except Exception as db_err:
        print(f"Database error while fetching from {TABLE_NAME}: {db_err}")
        return

    if data_list:
        try:
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

        except Exception as api_err:
            print(f"API Error during sync for {TABLE_NAME}: {api_err}")
    else:
        print(f"No records found in {TABLE_NAME} to sync.")

if __name__ == "__main__":
    run_sync()