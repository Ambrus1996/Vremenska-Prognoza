import mysql.connector
import os
import requests
import time
from datetime import datetime

# 1. CONFIGURATION
db_config = {
    'user': os.environ.get('DB_USER'),
    'password': os.environ.get('DB_PASSWORD'),
    'host': os.environ.get('DB_HOST'),
    'database': os.environ.get('DB_NAME')
}

REFRESH_INTERVAL = 60  # Seconds to wait between reports

# --- GEO-LOCATION FUNCTION ---
def get_location(ip_address):
    """
    Takes an IP address (e.g., '8.8.8.8') and returns location (e.g., 'USA, Virginia').
    """
    # Check: Is this a local/docker IP?
    if ip_address.startswith('127.') or ip_address.startswith('172.') or ip_address == '::1':
        return "🏠 Local / Docker Internal"

    try:
        # MAGIC HAPPENS HERE: We ask the API "Who is this?"
        url = f"http://ip-api.com/json/{ip_address}"
        response = requests.get(url, timeout=5)
        data = response.json()

        if data['status'] == 'success':
            return f"{data['country']}, {data['city']}"
        else:
            return "Unknown Location"
    except Exception as e:
        return "API Error"

# --- MAIN LOOP (DAEMON MODE) ---
print("🚀 STARTING ANALYTICS DAEMON...")

while True:
    timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    print(f"\n\n--- 🕒 REPORT GENERATED AT: {timestamp} ---")

    conn = None
    cursor = None

    try:
        conn = mysql.connector.connect(**db_config)
        cursor = conn.cursor()
        
        # --- PART 1: TOP CITIES ---
        print("\n📊 TOP CITIES (WHAT PEOPLE SEARCH):")
        print("-" * 50)
        cursor.execute("""
            SELECT city_name, COUNT(*) as cnt, AVG(temperature) as temp 
            FROM search_history GROUP BY city_name ORDER BY cnt DESC LIMIT 3
        """)
        results = cursor.fetchall()
        
        if not results:
            print("   (No searches yet)")
        
        for row in results:
            print(f"   📍 {row[0]:<15} | {row[1]} searches | {round(row[2],1)}°C")

        # --- PART 2: USER ANALYSIS (Sherlock Mode) ---
        print("\n🌍 TOP USERS (WHO IS SEARCHING):")
        print("-" * 65)
        print(f"{'IP ADDRESS':<18} | {'CNT':<5} | {'LOCATION (GEO-IP)':<20}")
        print("-" * 65)

        cursor.execute("""
            SELECT client_ip, COUNT(*) as cnt 
            FROM search_history GROUP BY client_ip ORDER BY cnt DESC LIMIT 5
        """)
        
        users = cursor.fetchall()

        if not users:
            print("⚠️  No user data available.")
        else:
            for user in users:
                ip = user[0]
                count = user[1]
                location = get_location(ip)
                print(f"{ip:<18} | {count:<5} | {location:<20}")

        print("-" * 65)

    except mysql.connector.Error as err:
        print(f"❌ DATABASE ERROR: {err}")
    except Exception as e:
        print(f"❌ GENERAL ERROR: {e}")

    finally:
        if conn and conn.is_connected():
            cursor.close()
            conn.close()
            # We don't print "Done" anymore, because we go to sleep now

    print(f"💤 Sleeping for {REFRESH_INTERVAL} seconds...")
    time.sleep(REFRESH_INTERVAL)