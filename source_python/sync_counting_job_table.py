import requests
from db_config import DynamicsDB, serialize_row,APIConfig
from unfullfillment_amount import run_sync_unfullfilled_amount
from pending_invoice_amount import run_sync_pending_invoice_amount
from dates import run_sync_dates
from unfullfillement_reason import run_sync_reasons
from sales_order_count import run_sync_no_of_order

# The endpoint for the Job Table handler we just created
ENDPOINT = f"{APIConfig.BASE_URL}/athcountingjobtable.php"

# The table and column configuration
TABLE_NAME = 'ATHCOUNTINGJOBTABLE'
COLUMNS = [
    "ATHCOUNTINGJOBID", "ATHCOUNTINGJOBSTATUS", "COUNTEDINVENTLOCATIONID",
    "INVENTLOCATIONIDFILLEDFROM", "TRANSFERITEM", "PACKINGSLIPID",
    "SYSTEMENTERDATE", "QTY", "MODIFIEDDATETIME", "MODIFIEDBY",
    "MODIFIEDTRANSACTIONID", "CREATEDBY", "CREATEDTRANSACTIONID",
    "DATAAREAID", "RECVERSION", "RECID", "SALESID", "COUNTINGDATE",
    "REALTRANSDATE"
]

def run_sync():
    # Constructing the query with the specific filter provided
    # Note: Removed "TOP 1000" to fetch all matching records
    sql_query = f"""
        SELECT {', '.join(COLUMNS)} 
        FROM {TABLE_NAME} 
        WHERE COUNTEDINVENTLOCATIONID LIKE 'AC%'
    """
    
    data_list = []

    try:
        # Utilize the DynamicsDB context manager from your db_config
        with DynamicsDB() as cursor:
            print("Fetching data from Dynamics...")
            cursor.execute(sql_query)
            rows = cursor.fetchall()
            
            # Map the rows to a list of dictionaries for JSON serialization
            data_list = [serialize_row(r, COLUMNS) for r in rows]
            print(f"Read {len(data_list)} rows from Dynamics.")

        if data_list:
            print(f"Posting data to {ENDPOINT}...")
            # Increase timeout for larger data sets
            response = requests.post(ENDPOINT, json=data_list,headers=APIConfig.get_headers(), 
                timeout=120)
            print("POST request sent.")
            print("Response status code:", response.status_code)
            print("Response body:", response.text)
            response.raise_for_status()
            
            result = response.json()
            if result.get("success"):
                print(f"Sync successful! Inserted {result.get('inserted')} rows.")
            else:
                print(f"Server Error: {result.get('error')}")
        else:
            print("No data found matching the criteria.")

    except Exception as e:
        print(f"Error during sync: {e}")

if __name__ == "__main__":
    run_sync()
    run_sync_unfullfilled_amount()
    run_sync_pending_invoice_amount()
    run_sync_dates()
    run_sync_reasons()
    run_sync_no_of_order()