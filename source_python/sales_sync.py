import requests
from db_config import DynamicsDB, serialize_row, APIConfig

# Update this to match your actual PHP filename
ENDPOINT = f"{APIConfig.BASE_URL}/sales_sync.php"

def run_sales_sync():
    # Your specific SQL query
    sql_query = """
        SELECT 
            SALESID, SALESNAME, TRANSFERWHAREHOUSE, ATHSALESTYPE,
            NUMBERSEQUENCEGROUP, SALESSTATUS, SALESTYPE, ATHDNSTATUS, INVOICEACCOUNT,
            CURRENCYCODE, PURCHORDERFORMNUM, CUSTOMERREF, CUSTACCOUNT, TAXGROUP, 
            SHIPPINGDATEREQUESTED, SHIPPINGDATECONFIRMED, CREATEDDATETIME
        FROM SALESTABLE 
        WHERE CREATEDDATETIME > '2020-01-01'
    """
    
    # The keys must match the SQL column names exactly
    JSON_KEYS = [
        "SALESID", "SALESNAME", "TRANSFERWHAREHOUSE", "ATHSALESTYPE",
        "NUMBERSEQUENCEGROUP", "SALESSTATUS", "SALESTYPE", "ATHDNSTATUS", "INVOICEACCOUNT",
        "CURRENCYCODE", "PURCHORDERFORMNUM", "CUSTOMERREF", "CUSTACCOUNT", "TAXGROUP", 
        "SHIPPINGDATEREQUESTED", "SHIPPINGDATECONFIRMED", "CREATEDDATETIME"
    ]
    
    try:
        with DynamicsDB() as cursor:
            print("Fetching Top 10 Sales Records...")
            cursor.execute(sql_query)
            rows = cursor.fetchall()
            
            # Serialize rows into list of dicts
            data_list = [serialize_row(r, JSON_KEYS) for r in rows]
            print(f"Read {len(data_list)} rows.")

        if data_list:
            print(f"Syncing to {ENDPOINT}...")
            response = requests.post(
                ENDPOINT, 
                json=data_list, 
                headers=APIConfig.get_headers(), 
                timeout=60
            )
            
            response.raise_for_status()
            result = response.json()
            
            if result.get("success"):
                print(f"Success! Records inserted: {result.get('inserted')}")
            else:
                print(f"API Error: {result.get('error')}")
        else:
            print("No new records found to sync.")

    except Exception as e:
        print(f"Sync Failed: {e}")

if __name__ == "__main__":
    run_sales_sync()