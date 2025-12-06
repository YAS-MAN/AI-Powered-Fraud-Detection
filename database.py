import mysql.connector

class Database:
    def __init__(self):
        self.conn = mysql.connector.connect(
            host="localhost",
            user="root",
            password="",
            database="fraud_detection"
        )
        self.cursor = self.conn.cursor(dictionary=True)

    def insert_claim(self, d):
        sql = """
        INSERT INTO claims 
        (customer_name, diagnosis, amount, policy_age, frequency, hospital_name, treatment_days, doctor_name)
        VALUES (%s,%s,%s,%s,%s,%s,%s,%s)
        """
        self.cursor.execute(sql, d)
        self.conn.commit()
        return self.cursor.lastrowid

    def get_diagnosis_stats(self, d):
        self.cursor.execute("SELECT * FROM diagnosis_stats WHERE diagnosis=%s", (d,))
        return self.cursor.fetchone()

    def get_hospital_risk(self, h):
        self.cursor.execute("SELECT * FROM hospital_risk WHERE hospital_name=%s", (h,))
        return self.cursor.fetchone()

    def get_total_claims(self):
        self.cursor.execute("SELECT COUNT(*) AS total FROM claims")
        return self.cursor.fetchone()["total"]

    def get_fraud_cases_count(self):
        self.cursor.execute("SELECT COUNT(*) AS fraud FROM fraud_cases")
        return self.cursor.fetchone()["fraud"]

    def get_claims_by_diagnosis(self, diag):
        self.cursor.execute("SELECT * FROM claims WHERE diagnosis=%s", (diag,))
        return self.cursor.fetchall()

    def get_fraud_cases(self):
        self.cursor.execute("SELECT * FROM fraud_cases")
        return self.cursor.fetchall()

    def insert_fraud_score(self, cid, score, category, js):
        sql = """INSERT INTO results (claim_id, final_score, category, details_json)
                 VALUES (%s,%s,%s,%s)"""
        self.cursor.execute(sql, (cid, score, category, js))
        self.conn.commit()

    def get_all_results(self):
        self.cursor.execute("""
        SELECT r.*, c.customer_name, c.diagnosis, c.amount, r.created_at
        FROM results r
        JOIN claims c ON r.claim_id=c.id
        ORDER BY r.id DESC
        """)
        return self.cursor.fetchall()
