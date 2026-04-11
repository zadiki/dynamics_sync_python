# PHP API Endpoints Documentation

This document describes all available endpoints in the `destination_php` directory. All endpoints return JSON and require an `X-API-KEY` header for authentication.

---

## Common Features
- **Authentication:** All endpoints require a valid `X-API-KEY` header.
- **CORS:** All endpoints allow cross-origin requests.
- **Methods:** All endpoints support `GET` and `POST` unless otherwise noted.
- **Content-Type:** All endpoints return `application/json`.
- **Base-API** https://ycl.co.ke/destination-data/

---

## Endpoints

### 1. `/athcountingjobline.php`
- **GET:** Retrieve job line records from `ATHCOUNTINGJOBLINE`. Supports filtering by any column via query parameters.
- **POST:** Insert a new job line record (fields depend on table schema).

### 2. `/athcountingjobtable.php`
- **GET:** Retrieve job table records from `ATHCOUNTINGJOBTABLE`. Supports filtering by any column via query parameters.
- **POST:** Insert a new job table record (fields depend on table schema).

### 3. `/dates.php`
- **GET:** Retrieve report date ranges from `Athsalesordersummaryreport`. Columns: `DESCRIPTION`, `FROMDATE`, `TODATE`. Supports filtering by these columns.
- **POST:** Insert a new report date range.

### 4. `/dynamics-shipment-status.php`
- **GET:** Retrieve shipment status from `SevkiyatPlanlama_App_01`. Supports filtering by any column via query parameters.
- **POST:** Insert a new shipment status record.

### 5. `/item_group_prices.php`
- **GET:** Retrieve item group prices. Columns include `ITEMID`, `ITEMNAME`, `NAMEALIAS`, `ATHITEMGROUPIDA`-`G`, `PRIMARYVENDORID`, `VENDNAME`, `LISTPRICE`, `RETAILPRICE`, `SuperMarketPRICE`, `PurchPRICE`, `TaxItemGroupId`, `LOWESTQTY`, `MULTIPLEQTY`, `NETWEIGHT`, `UNITVOLUME`, `vatMult`, `RRP`, `ITEMBUYERGROUPID`, `MarginPct`.
- **POST:** Insert a new item group price record.

### 6. `/no_of_order_ps.php`
- **GET:** Retrieve order counts from `ATHSALESORDERCOUNTS01`. Columns: `DESCRIPTION`, `FROMDATE`, `TODATE`, `NOOFORDER`, `NOOFCPS`, `NOOFCIJ`.
- **POST:** Insert a new order count record.

### 7. `/order_status.php`
- **GET:** Retrieve order status from `ATHSALESORDERCOUNTS02`. Columns: `DESCRIPTION`, `FROMDATE`, `TODATE`, `OpenOrder`, `Delivered`, `invoiced`, `cancelled`.
- **POST:** Insert a new order status record.

### 8. `/pending_delivery_amount.php`
- **GET:** Retrieve pending delivery amounts from `ATHSALESORDERSUMMARY01C`. Columns: `Description`, `PendingDeliveryAmount`, `SUMOFSALESAMOUNT`, `AVGOFPERCENT`.
- **POST:** Insert a new pending delivery amount record.

### 9. `/pending_invoice_amount.php`
- **GET:** Retrieve pending invoice amounts from `ATHSALESORDERSUMMARY01B`. Columns: `Description`, `PendingInvoiceAmount`, `SUMOFSALESAMOUNT`, `AVGOFPERCENTUNFULFILLED`.
- **POST:** Insert a new pending invoice amount record.

### 10. `/sales_table_data.php`
- **GET:** Retrieve sales table data. Columns: `SALESID`, `SALESNAME`, `TRANSFERWHAREHOUSE`, `ATHSALESTYPE`, `NUMBERSEQUENCEGROUP`, `SALESSTATUS`, `SALESTYPE`, `ATHDNSTATUS`, `INVOICEACCOUNT`, `CURRENCYCODE`, `PURCHORDERFORMNUM`, `CUSTOMERREF`, `CUSTACCOUNT`, `TAXGROUP`, `SHIPPINGDATEREQUESTED`, `SHIPPINGDATECONFIRMED`, `CREATEDDATETIME`.
- **POST:** Insert a new sales table record.

### 11. `/sales_table_detail_data.php`
- **GET:** Retrieve sales table detail data from `SALESTABLE_SALESDETAIL`. Columns: `SALESID`, `ITEMID`, `ITEMNAME`, `SALESQTY`, `INVENTLOCATIONID`, `INVENTSITEID`, `SALESUNIT`, `SALESPRICE`, `FREEOFCHARGE`, `LINEDISC`, `LINEPERCENT`, `LINEAMOUNT`, `TAXITEMGROUP`, `ATHITEMGROUPIDA`, `ATHITEMGROUPIDB`, `ATHITEMGROUPIDC`, `ATHNONFULFILLMENTREASONID`.
- **POST:** Insert a new sales table detail record.

### 12. `/unfullfilled_last_90_days.php`
- **GET:** Retrieve unfulfilled sales data from `ATHSALESORDERSUMMARY01G`. Columns: `ITEMNAME`, `LatInvoiceDate`, `LastOrderDate`, `MOSTRECENTPRODDATE`, `UNFULFILLEDAMOUNT90DAYS`, `DAYLASTSALES`.
- **POST:** Insert a new unfulfilled sales record.

### 13. `/unfullfillment_amount.php`
- **GET:** Retrieve unfulfilled amount data from `ATHSALESORDERSUMMARY01A`. Columns: `Description`, `UnfulfilledAmount`, `OrderAmount`, `FulfillmentPercent`.
- **POST:** Insert a new unfulfilled amount record.

### 14. `/unfullfillment_reasons.php`
- **GET:** Retrieve unfulfillment reasons from `ATHSALESORDERSUMMARY01E`. Columns: `Description`, `Unfullfillmentamount`, `FullfillmentPercent`.
- **POST:** Insert a new unfulfillment reason record.

---

## Usage Example

**Request:**
```
GET /destination_php/order_status.php?DESCRIPTION=SomeDesc
X-API-KEY: your_api_key
```

**Response:**
```json
[
  {
    "DESCRIPTION": "SomeDesc",
    "FROMDATE": "2024-01-01",
    ...
  }
]
```

---

## Notes
- For all endpoints, POST request bodies should be JSON and match the table columns.
- Filtering is supported via query parameters for GET requests, limited to allowed columns.
- All endpoints return HTTP 405 for unsupported methods.

---

For more details, refer to the code in each PHP file.
