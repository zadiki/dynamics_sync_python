import requests
from db_config import DynamicsDB, serialize_row, APIConfig

# The endpoint for the Sales Order Summary handler
ENDPOINT = f"{APIConfig.BASE_URL}/unfullfillment_amount.php"

# The table and column configuration
TABLE_NAME = 'ATHSALESORDERSUMMARY01A'
# We use the internal Dynamics column names for the fetch
COLUMNS = [
    "ATHCUSTOMERDESCRIPTION", 
    "SUMOFUNFULFILLEDAMOUNT", 
    "SUMOFSALESAMOUNT", 
    "AVGOFPERCENTUNFULFILLED"
]

def run_sync_unfullfilled_amount():
    # Constructing the query using the specific aliases from your requirements
    sql_query = f"""
        SELECT 
            ATHCUSTOMERDESCRIPTION as 'Description',
            SUMOFUNFULFILLEDAMOUNT as 'UnfulfilledAmount',
            SUMOFSALESAMOUNT as 'OrderAmount',
            AVGOFPERCENTUNFULFILLED as 'FulfillmentPercent'
        FROM {TABLE_NAME}
    """
    
    # These match the aliases in the SQL query for the serialization step
    JSON_KEYS = ['Description', 'UnfulfilledAmount', 'OrderAmount', 'FulfillmentPercent']
    data_list = []

    try:
        # Utilize the DynamicsDB context manager
        with DynamicsDB() as cursor:
            print(f"Fetching summary data from {TABLE_NAME}...")
            cursor.execute(sql_query)
            rows = cursor.fetchall()
            
            # Map the rows using the aliases defined in the SQL
            data_list = [serialize_row(r, JSON_KEYS) for r in rows]
            print(f"Read {len(data_list)} rows from Dynamics.")

        if data_list:
            print(f"Posting data to {ENDPOINT}...")
            # Using your APIConfig for headers and centralizing the request
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
                print(f"Sync successful! Processed {result.get('inserted', 0)} summary records.")
            else:
                print(f"Server Error: {result.get('error')}")
        else:
            print("No data found in the summary table.")

    except Exception as e:
        print(f"Error during summary sync: {e}")

if __name__ == "__main__":
    run_sync_unfullfilled_amount()