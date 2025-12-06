import math

class FraudEngine:
    def __init__(self, db):
        self.db = db

    # ----------------------------------------------------
    # 1. Bayesian Probability
    # ----------------------------------------------------

    def bayesian_probability(self, claim):
        total_claims = self.db.get_total_claims()
        fraud_cases = self.db.get_fraud_cases_count()

        # Prior Probability (Peluang Awal)
        P_fraud = fraud_cases / total_claims if total_claims > 0 else 0.1
        P_fraud = max(P_fraud, 0.1) # Jaga agar tidak nol

        # A. Ambil Data DINAMIS (Dari tabel claims - history user)
        diagnosis_claims = self.db.get_claims_by_diagnosis(claim.diagnosis)
        dynamic_avg = 0
        if len(diagnosis_claims) > 0:
            dynamic_avg = sum(c['amount'] for c in diagnosis_claims) / len(diagnosis_claims)

        # B. Ambil Data STATIS (Dari tabel stats - standar baku)
        stats = self.db.get_diagnosis_stats(claim.diagnosis)
        static_avg = float(stats['avg_cost']) if stats else 0

        # C. LOGIKA HYBRID (GABUNGAN)
        if static_avg > 0 and dynamic_avg > 0:
            # UBAH DISINI JADI 0.5 dan 0.5
            reference_price = (0.7 * static_avg) + (0.3 * dynamic_avg)
            
        elif static_avg > 0:
            reference_price = static_avg 
        else:
            reference_price = dynamic_avg

        # Hitung Rasio terhadap Harga Referensi Gabungan
        if reference_price > 0:
            ratio = claim.amount / reference_price
        else:
            ratio = 1.0 # Tidak bisa menilai

        # Hitung Likelihood
        if ratio > 1.2: 
            # Jika harga > 20% dari referensi gabungan -> Curiga
            # Gunakan pangkat agar skor naik eksponensial
            likelihood = min((ratio ** 1.5) * 0.4, 0.99)
        elif ratio < 0.5:
             # Terlalu murah
             likelihood = 0.2
        else:
            # Harga wajar
            likelihood = 0.05

        # Rumus Bayes
        numerator = P_fraud * likelihood
        prob_legit = 1 - likelihood
        denominator = numerator + ((1 - P_fraud) * prob_legit)
        
        posterior = numerator / denominator if denominator > 0 else 0
        
        return posterior * 100
    # ----------------------------------------------------
    # 2. Anomaly Detection (Costs)
    # ----------------------------------------------------
    def anomaly_score(self, claim):
        stats = self.db.get_diagnosis_stats(claim.diagnosis)
        if not stats:
            return 20
        
        expected = stats["avg_cost"]
        deviation = max(0, (claim.amount - expected) / expected)
        return min(deviation * 50, 100)

    # ----------------------------------------------------
    # 3. Hospital Fraud Index
    # ----------------------------------------------------
    def hospital_risk(self, hospital):
        data = self.db.get_hospital_risk(hospital)
        if not data:
            return 10
        return min((data["fraud_cases"] / data["total_cases"]) * 120, 100)

    # ----------------------------------------------------
    # 4. Diagnosis Severity
    # ----------------------------------------------------
    def diagnosis_severity(self, diagnosis):
        stats = self.db.get_diagnosis_stats(diagnosis)
        return stats["severity_index"] * 10 if stats else 10

    # ----------------------------------------------------
    # 5. Rule Engine (Enhanced)
    # ----------------------------------------------------
    def rule_engine(self, claim):
        flags = []
        stats = self.db.get_diagnosis_stats(claim.diagnosis)

        # Early policy
        if claim.policy_age < 30:
            flags.append("Early policy")

        # High frequency
        if claim.frequency >= 3:
            flags.append("High claim frequency")

        # Big claim amount
        if claim.amount > 15000000:
            flags.append("Large claim amount")

        # Long treatment
        if claim.treatment_days > 7:
            flags.append("Long treatment duration")

        # Treatment anomaly vs expected
        if stats:
            if claim.treatment_days < stats["expected_min_days"] or claim.treatment_days > stats["expected_max_days"]:
                flags.append("Unusual treatment duration")

        # 2x cost anomaly
        if stats and claim.amount > stats["avg_cost"] * 2:
            flags.append("Cost anomaly >2x normal")

        return len(flags), flags

    # ----------------------------------------------------
    # 6. CBR Similarity (TOP 3 cases)
    # ----------------------------------------------------
    def cbr_top3(self, claim):
        cases = self.db.get_fraud_cases()
        if len(cases) == 0:
            return [], 0
        
        scored_cases = []
        for c in cases:
            sim_diag = 1 if c["diagnosis"] == claim.diagnosis else 0.5
            sim_amount = 1 - abs(c["amount"] - claim.amount) / max(c["amount"], claim.amount)
            sim_freq = 1 - abs(c["frequency"] - claim.frequency) / max(c["frequency"], claim.frequency, 1)
            sim_age = 1 - abs(c["policy_age"] - claim.policy_age) / max(c["policy_age"], claim.policy_age, 1)

            sim = sim_diag * 0.35 + sim_amount * 0.25 + sim_freq * 0.2 + sim_age * 0.2
            scored_cases.append((sim, c))

        # Sort best first
        scored_cases.sort(key=lambda x: x[0], reverse=True)

        top3 = scored_cases[:3]
        best_similarity = top3[0][0] * 100

        return top3, best_similarity

    # ----------------------------------------------------
    # Explanation
    # ----------------------------------------------------
    def generate_explanation(self, bayes, anomaly, hospital, severity, flags, cbr):
        reasons = []

        if anomaly > 40:
            reasons.append("Claim amount far above normal medical range.")

        if hospital > 40:
            reasons.append("Hospital has high historical fraud rate.")

        if severity > 12:
            reasons.append("Diagnosis severity increases claim risk.")

        if flags:
            reasons.append("Triggered rules: " + ", ".join(flags))

        if cbr > 40:
            reasons.append("Highly similar to fraudulent cases in the past.")

        if len(reasons) == 0:
            reasons.append("Claim falls within normal risk parameters.")

        return reasons

    # ----------------------------------------------------
    # Final Fraud Score
    # ----------------------------------------------------
    def final_fraud_score(self, claim):
        bayes = self.bayesian_probability(claim)
        anomaly = self.anomaly_score(claim)
        hospital = self.hospital_risk(claim.hospital_name)
        severity = self.diagnosis_severity(claim.diagnosis)
        flag_count, flags = self.rule_engine(claim)
        top3, cbr = self.cbr_top3(claim)

        flags_score = flag_count * 30
        cbr_score = cbr

        final = (
            0.20 * bayes +
            0.25 * anomaly +
            0.15 * hospital +
            0.15 * severity +
            0.20 * flags_score +
            0.15 * cbr_score
        )

        final = min(final, 100)

        return final, {
            "bayesian_probability": bayes,
            "anomaly_score": anomaly,
            "hospital_risk": hospital,
            "diagnosis_severity": severity,
            "rule_flags": flags,
            "cbr_similarity": cbr,
            "top3_cases": top3,
            "explanation": self.generate_explanation(bayes, anomaly, hospital, severity, flags, cbr)
        }
