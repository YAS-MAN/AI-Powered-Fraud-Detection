import sys
import json
from database import Database
from fraud_engine import FraudEngine

# Class dummy untuk menampung data dari PHP
class ClaimObj:
    pass

def main():
    # 1. Terima argumen dari PHP (Urutan argumen sangat penting!)
    # sys.argv[0] adalah nama file script
    try:
        claim = ClaimObj()
        claim.customer_name = sys.argv[1]
        claim.diagnosis = sys.argv[2]
        claim.amount = float(sys.argv[3])
        claim.hospital_name = sys.argv[4]
        claim.policy_age = int(sys.argv[5])
        claim.frequency = int(sys.argv[6])
        claim.treatment_days = int(sys.argv[7])
        
        # 2. Inisialisasi Engine & Database
        db = Database()
        engine = FraudEngine(db)
        
        # 3. Hitung Score
        final_score, details = engine.final_fraud_score(claim)
        
        # 4. Return Output sebagai JSON Murni agar bisa dibaca PHP
        result = {
            "score": final_score,
            "details": details
        }
        
        # Print JSON ke stdout
        print(json.dumps(result))

    except Exception as e:
        # Jika error, print error dalam format JSON juga
        print(json.dumps({"error": str(e)}))

if __name__ == "__main__":
    main()  