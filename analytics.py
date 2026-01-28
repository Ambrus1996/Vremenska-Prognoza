import mysql.connector
import os
import requests  # <--- OVO JE NOVO: Biblioteka koja "zove" internet

# 1. KONFIGURACIJA
db_config = {
    'user': os.environ.get('DB_USER'),
    'password': os.environ.get('DB_PASSWORD'),
    'host': os.environ.get('DB_HOST'),
    'database': os.environ.get('DB_NAME')
}

# --- NOVA FUNKCIJA ZA GEOLOCIRANJE ---
def get_location(ip_address):
    """
    Prima IP adresu (npr. '8.8.8.8') i vraća lokaciju (npr. 'USA, Virginia').
    """
    # Provera: Da li je ovo lokalna/docker IP adresa?
    # Privatne IP adrese (127..., 172..., 192...) nemaju lokaciju na internetu.
    if ip_address.startswith('127.') or ip_address.startswith('172.') or ip_address == '::1':
        return "🏠 Local / Docker Internal"

    try:
        # OVDE SE DEŠAVA MAGIJA: Pitamo besplatan API "Ko je ovo?"
        url = f"http://ip-api.com/json/{ip_address}"
        response = requests.get(url, timeout=5) # Cekamo max 5 sekundi
        data = response.json() # Pretvaramo odgovor u Python rečnik (dict)

        if data['status'] == 'success':
            return f"{data['country']}, {data['city']}"
        else:
            return "Nepoznata lokacija"
    except Exception as e:
        return "Greska u API pozivu"

print("\n🕵️‍♂️ --- POKRETANJE NAPREDNE ANALITIKE ---")

try:
    conn = mysql.connector.connect(**db_config)
    cursor = conn.cursor()
    print("✅ Baza povezana.")

    # --- DEO 1: TOP GRADOVI (Ono staro) ---
    print("\n📊 TOP GRADOVI (ŠTA SE TRAŽI):")
    print("-" * 50)
    cursor.execute("""
        SELECT city_name, COUNT(*) as br, AVG(temperature) as temp 
        FROM search_history GROUP BY city_name ORDER BY br DESC LIMIT 3
    """)
    for row in cursor.fetchall():
        print(f"   📍 {row[0]:<15} | {row[1]} pretraga | {round(row[2],1)}°C")

    # --- DEO 2: ANALIZA KORISNIKA (Ono novo - Sherlock) ---
    print("\n🌍 TOP KORISNICI (KO PRETRAŽUJE):")
    print("-" * 60)
    print(f"{'IP ADRESA':<18} | {'BR.':<5} | {'LOKACIJA (GEO-IP)':<20}")
    print("-" * 60)

    # Vadimo IP adrese koje su najvise puta pretrazivale
    cursor.execute("""
        SELECT client_ip, COUNT(*) as broj 
        FROM search_history GROUP BY client_ip ORDER BY broj DESC LIMIT 5
    """)
    
    users = cursor.fetchall()

    if not users:
        print("⚠️  Nema podataka o korisnicima.")
    else:
        for user in users:
            ip = user[0]    # Uzimamo IP iz baze
            count = user[1] # Uzimamo broj pretraga
            
            # Pozivamo nasu funkciju da otkrije zemlju
            lokacija = get_location(ip)
            
            print(f"{ip:<18} | {count:<5} | {lokacija:<20}")

    print("-" * 60)

except mysql.connector.Error as err:
    print(f"❌ GREŠKA BAZA: {err}")

finally:
    if 'conn' in locals() and conn.is_connected():
        cursor.close()
        conn.close()
        print("\n🔒 Gotovo.")