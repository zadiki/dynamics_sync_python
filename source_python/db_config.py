import pyodbc
from decimal import Decimal

class DynamicsDB:
    def __init__(self):
        self.server = '.'
        self.database = 'DynamicsVer2022'
        self.conn_str = (
            f'DRIVER={{ODBC Driver 18 for SQL Server}};'
            f'SERVER={self.server};'
            f'DATABASE={self.database};'
            'Trusted_Connection=yes;'
            'Encrypt=no;'
        )
        self.conn = None

    def __enter__(self):
        """Allows use of 'with DynamicsDB() as cursor:'"""
        try:
            self.conn = pyodbc.connect(self.conn_str)
            return self.conn.cursor()
        except Exception as e:
            print(f"Database connection error: {e}")
            raise

    def __exit__(self, exc_type, exc_val, exc_tb):
        if self.conn:
            self.conn.close()

def serialize_row(row, columns):
    """Utility to clean SQL types for JSON/API use."""
    row_dict = {}
    for i, col in enumerate(columns):
        val = row[i]
        if hasattr(val, 'isoformat'):
            val = val.isoformat()
        elif isinstance(val, Decimal):
            val = float(val)
        row_dict[col] = val
    return row_dict

# db_config.py (Add this section)

class APIConfig:
    """Centralized configuration for API communication"""
    BASE_URL = 'https://ycl.co.ke'
    API_KEY = '4f9a2b8c5e1d7f3a9b0c2e4d6f8a1b3c5e7d9f0a2b4c6d8e0f1a3b5c7d9e1f'  # Replace with the key from your PHP config.php
    
    @classmethod
    def get_headers(cls):
        return {
            'X-API-KEY': cls.API_KEY,
            'Content-Type': 'application/json'
        }