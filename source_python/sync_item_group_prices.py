import requests
from db_config import DynamicsDB, serialize_row, APIConfig

# Target endpoint
ENDPOINT = f"{APIConfig.BASE_URL}/item_group_prices.php"

# Configuration for zzz_vw_InventTableForExcel
TABLE_NAME = 'zzz_vw_InventTableForExcel'
COLUMNS = [
    "ITEMID", "ITEMNAME", "NAMEALIAS", "ATHITEMGROUPIDA", "ATHITEMGROUPIDB",
    "ATHITEMGROUPIDC", "ATHITEMGROUPIDD", "ATHITEMGROUPIDE", "ATHITEMGROUPIDG",
    "PRIMARYVENDORID", "VENDNAME", "LISTPRICE", "RETAILPRICE", "SuperMarketPRICE",
    "PurchPRICE", "TaxItemGroupId", "LOWESTQTY", "MULTIPLEQTY", "NETWEIGHT",
    "UNITVOLUME", "vatMult", "RRP", "ITEMBUYERGROUPID", "MarginPct"
]

def run_sync_inventory():
    # Fetching all records from the view
    sql_query = f"SELECT {', '.join(COLUMNS)} FROM {TABLE_NAME}"
    
    data_list = []

    try:
        with DynamicsDB() as cursor:
            print(f"Fetching inventory data from {TABLE_NAME}...")
            cursor.execute(sql_query)
            rows = cursor.fetchall()
            
            data_list = [serialize_row(r, COLUMNS) for r in rows]
            print(f"Read {len(data_list)} items from Dynamics.")

        if data_list:
            print(f"Posting data to {ENDPOINT}...")
            # Inventory data can be heavy, using a 180s timeout
            response = requests.post(
                ENDPOINT, 
                json=data_list, 
                headers=APIConfig.get_headers(), 
                timeout=180
            )
            
            response.raise_for_status()
            result = response.json()
            
            if result.get("success"):
                print(f"Inventory sync successful! Updated {result.get('inserted')} rows.")
            else:
                print(f"Server Error: {result.get('error')}")
        else:
            print("No inventory data found.")

    except Exception as e:
        print(f"Error during inventory sync: {e}")

if __name__ == "__main__":
    run_sync_inventory()