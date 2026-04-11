# Python Scripts Documentation

This README describes the purpose and usage of each script in the `source_python` directory. These scripts are designed to synchronize data between a Dynamics database and the PHP API endpoints.

---

## Common Features
- **Database Access:** All scripts use the `DynamicsDB` class in `db_config.py` for database connections.
- **API Communication:** Data is sent to PHP endpoints using HTTP POST requests with the `requests` library.
- **Configuration:** API base URL and headers are managed via `APIConfig` (assumed to be in `db_config.py`).
- **Serialization:** SQL rows are converted to JSON using the `serialize_row` utility.
- **Base Api:** https://ycl.co.ke/destination-data/


---

## Scripts Overview

### 1. `dates.py`
- Syncs report date ranges from `Athsalesordersummaryreport` to `/dates.php`.
- Columns: `DESCRIPTION`, `FROMDATE`, `TODATE`.

### 2. `db_config.py`
- Contains the `DynamicsDB` context manager for SQL Server access.
- Provides `serialize_row` for converting SQL rows to JSON.
- Defines `APIConfig` for API settings (base URL, headers).

### 3. `dynamic_shipment_status.py`
- Syncs shipment status data from `ZZZ_vw_SevkiyatPlanlama_App_01` to `/dynamics-shipment-status.php`.
- Columns: `SALESID`, `REMAININGQTY`, `SALESNAME`, `REMAINSALESPHYSICAL`, `SHIPPINGDATEREQUESTED`, `SHIPPINGDATECONFIRMED`, `DELIVERYCITY`, `KUTU_SAYISI`.

### 4. `item_analysis_last_90_days.py`
- Syncs unfulfilled sales data from `ATHSALESORDERSUMMARY01` to `/unfullfilled_last_90_days.php`.
- Columns: `ITEMNAME`, `LatInvoiceDate`, `LastOrderDate`, `MOSTRECENTPRODDATE`, `UNFULFILLEDAMOUNT90DAYS`, `DAYLASTSALES`.

### 5. `order_status.py`
- Syncs order status counts from `ATHSALESORDERCOUNTS02` to `/order_status.php`.
- Columns: `DESCRIPTION`, `FROMDATE`, `TODATE`, `OpenOrder`, `Delivered`, `invoiced`, `cancelled`.

### 6. `pending_delivery_amount.py`
- Syncs pending delivery amounts from `ATHSALESORDERSUMMARY01C` to `/pending_delivery_amount.php`.
- Columns: `Description`, `PendingDeliveryAmount`, `SUMOFSALESAMOUNT`, `AVGOFPERCENT`.

### 7. `pending_invoice_amount.py`
- Syncs pending invoice amounts from `ATHSALESORDERSUMMARY01B` to `/pending_invoice_amount.php`.
- Columns: `Description`, `PendingInvoiceAmount`, `SUMOFSALESAMOUNT`, `AVGOFPERCENTUNFULFILLED`.

### 8. `sales_details_sync.py`
- Syncs detailed sales data (joined from multiple tables) to `/sales_table_detail_data.php`.
- Columns: `SALESID`, `ITEMID`, `ITEMNAME`, `SALESQTY`, `INVENTLOCATIONID`, `INVENTSITEID`, `SALESUNIT`, `SALESPRICE`, `FREEOFCHARGE`, `LINEDISC`, `LINEPERCENT`, `LINEAMOUNT`, `TAXITEMGROUP`, `ATHITEMGROUPIDA`, `ATHITEMGROUPIDB`, `ATHITEMGROUPIDC`, `ATHNONFULFILLMENTREASONID`.

### 9. `sales_order_count.py`
- Syncs sales order counts from `ATHSALESORDERCOUNTS01` to `/no_of_order_ps.php`.
- Columns: `DESCRIPTION`, `FROMDATE`, `TODATE`, `NOOFORDER`, `NOOFCPS`, `NOOFCIJ`.

### 10. `sales_sync.py`
- Syncs sales table data from `SALESTABLE` to `/sales_table_data.php`.
- Columns: `SALESID`, `SALESNAME`, `TRANSFERWHAREHOUSE`, `ATHSALESTYPE`, `NUMBERSEQUENCEGROUP`, `SALESSTATUS`, `SALESTYPE`, `ATHDNSTATUS`, `INVOICEACCOUNT`, `CURRENCYCODE`, `PURCHORDERFORMNUM`, `CUSTOMERREF`, `CUSTACCOUNT`, `TAXGROUP`, `SHIPPINGDATEREQUESTED`, `SHIPPINGDATECONFIRMED`, `CREATEDDATETIME`.

### 11. `sync_counting_job_line.py`
- Syncs job line data from `ATHCOUNTINGJOBLINE` (filtered by header table) to `/athcountingjobline.php`.
- Columns: `ATHCOUNTINGJOBID`, `SALESID`, `ITEMID`, `INVENTTRANSID`, `QTY`, `SENTQTY`, `NAME`, `SALESUNIT`, `ORDERED`, `REMAIN`, `QTYAX`, `QTYDIFFERENCE`, `MODIFIEDDATETIME`, `MODIFIEDBY`, `MODIFIEDTRANSACTIONID`, `CREATEDBY`, `CREATEDTRANSACTIONID`, `DATAAREAID`, `RECVERSION`, `RECID`.

### 12. `sync_counting_job_table.py`
- Syncs job table data from `ATHCOUNTINGJOBTABLE` to `/athcountingjobtable.php`.
- Columns: `ATHCOUNTINGJOBID`, `ATHCOUNTINGJOBSTATUS`, `COUNTEDINVENTLOCATIONID`, `INVENTLOCATIONIDFILLEDFROM`, `TRANSFERITEM`, `PACKINGSLIPID`, `SYSTEMENTERDATE`, `QTY`, `MODIFIEDDATETIME`, `MODIFIEDBY`, `MODIFIEDTRANSACTIONID`, `CREATEDBY`, `CREATEDTRANSACTIONID`, `DATAAREAID`, `RECVERSION`, `RECID`, `SALESID`, `COUNTINGDATE`, `REALTRANSDATE`.

### 13. `sync_item_group_prices.py`
- Syncs item group prices from `zzz_vw_InventTableForExcel` to `/item_group_prices.php`.
- Columns: `ITEMID`, `ITEMNAME`, `NAMEALIAS`, `ATHITEMGROUPIDA`, `ATHITEMGROUPIDB`, `ATHITEMGROUPIDC`, `ATHITEMGROUPIDD`, `ATHITEMGROUPIDE`, `ATHITEMGROUPIDG`, `PRIMARYVENDORID`, `VENDNAME`, `LISTPRICE`, `RETAILPRICE`, `SuperMarketPRICE`, `PurchPRICE`, `TaxItemGroupId`, `LOWESTQTY`, `MULTIPLEQTY`, `NETWEIGHT`, `UNITVOLUME`, `vatMult`, `RRP`, `ITEMBUYERGROUPID`, `MarginPct`.

### 14. `unfullfillement_reason.py`
- Syncs unfulfillment reasons from `ATHSALESORDERSUMMARY01E` to `/unfullfillment_reasons.php`.
- Columns: `Description`, `Unfullfillmentamount`, `FullfillmentPercent`.

### 15. `unfullfillment_amount.py`
- Syncs unfulfilled amount data from `ATHSALESORDERSUMMARY01A` to `/unfullfillment_amount.php`.
- Columns: `Description`, `UnfulfilledAmount`, `OrderAmount`, `FulfillmentPercent`.

---

## Usage
- Each script can be run as a standalone module: `python script_name.py`
- Ensure `requirements.txt` dependencies are installed (notably `pyodbc` and `requests`).
- Database and API configuration should be set in `db_config.py`.

---

## Requirements
- Python 3.7+
- See `requirements.txt` for dependencies.
- ODBC Driver 18 for SQL Server (or as configured in `db_config.py`).

---

For more details, refer to the code and docstrings in each script.
