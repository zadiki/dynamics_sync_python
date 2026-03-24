import requests
from db_config import DynamicsDB, serialize_row, APIConfig

# Pointing to the new joined handler
ENDPOINT = f"{APIConfig.BASE_URL}/sales_joined_sync.php"

def run_joined_sync():
    # The complex multi-table JOIN query
    sql_query = """
        SELECT 
            st.SALESID, sl.ITEMID, inv.ITEMNAME, sl.SALESQTY,
            idm.INVENTLOCATIONID, idm.INVENTSITEID, sl.SALESUNIT, 
            sl.SALESPRICE, sl.FREEOFCHARGE, sl.LINEDISC, sl.LINEPERCENT, 
            sl.LINEAMOUNT, sl.TAXITEMGROUP, sl.ATHITEMGROUPIDA, 
            sl.ATHITEMGROUPIDB, sl.ATHITEMGROUPIDC, sl.ATHNONFULFILLMENTREASONID
        FROM SALESTABLE AS st 
        INNER JOIN SALESLINE sl ON st.SALESID = sl.SALESID
        INNER JOIN INVENTTABLE inv ON inv.ITEMID = sl.ITEMID
        LEFT JOIN INVENTDIM idm ON idm.INVENTDIMID = sl.INVENTDIMID
        WHERE st.CREATEDDATETIME > '2026-03-03'
    """
    
    # Keys must match the SELECT column names exactly (order matters!)
    JSON_KEYS = [
        "SALESID", "ITEMID", "ITEMNAME", "SALESQTY", "INVENTLOCATIONID", 
        "INVENTSITEID", "SALESUNIT", "SALESPRICE", "FREEOFCHARGE", "LINEDISC", 
        "LINEPERCENT", "LINEAMOUNT", "TAXITEMGROUP", "ATHITEMGROUPIDA", 
        "ATHITEMGROUPIDB", "ATHITEMGROUPIDC", "ATHNONFULFILLMENTREASONID"
    ]
    
    try:
        with DynamicsDB() as cursor:
            print("Executing Joined Query (Top 100)...")
            cursor.execute(sql_query)
            rows = cursor.fetchall()
            
            data_list = [serialize_row(r, JSON_KEYS) for r in rows]
            print(f"Captured {len(data_list)} joined records.")

        if data_list:
            print(f"Syncing data to {ENDPOINT}...")
            response = requests.post(
                ENDPOINT, 
                json=data_list, 
                headers=APIConfig.get_headers(), 
                timeout=90
            )
            
            response.raise_for_status()
            result = response.json()
            
            if result.get("success"):
                print(f"Sync Successful! Rows updated: {result.get('inserted')}")
            else:
                print(f"Server Error: {result.get('error')}")
        else:
            print("No matching records found for the specified date.")

    except Exception as e:
        print(f"Critical Sync Failure: {e}")

if __name__ == "__main__":
    run_joined_sync()