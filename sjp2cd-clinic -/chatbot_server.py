from flask import Flask, request, jsonify
import random
import re
import mysql.connector
from mysql.connector import Error

app = Flask(__name__)

SYSTEM_NAME = 'SJPIICD Clinic System'

# Database configuration
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'clinic_db'
}

CLINIC_RESPONSE = 'The clinic is on the 2nd floor of Building C, in the middle section, right next to the library.'

FAQ_RESPONSES = {
    'system': 'The system handles clinic users, patient records, medicines, reports, and student access.',
    'login': 'Use admin/login.php for admins and student/login.php for students.',
    'admin': 'Admins sign in at admin/login.php to manage dashboard, users, patients, medicines, and reports.',
    'student': 'Students sign in at student/login.php to view their dashboard and records.',
    'patient': 'Patient data is stored in the admin dashboard under the patients section.',
    'medicine': 'Medicine details are managed in the admin medicines section.',
    'reports': 'Reports show clinic visits, medicines, and activity summaries.',
    'database': 'clinic_db stores users, patients, medical_records, medicines, and report data.',
    'location': 'The clinic is on the 2nd floor of Building C, in the middle section, right next to the library.',
}

RESPONSE_PATTERNS = [
    (['how do i login', 'how to login', 'cannot login', 'login failed', 'login page'], [
        'Go to admin/login.php if you are an admin, or student/login.php if you are a student.',
        'Use the correct login page for your role and enter the right password.',
        'The login must match your account type: admin or student on the proper page.',
    ]),
    (['admin login', 'admin access', 'admin portal', 'admin page', 'administrator'], [
        'Admins sign in at admin/login.php to manage dashboard, users, patient records, medicines, and reports.',
        'Use admin/login.php for admin actions like patients, medicines, reports, and user management.',
        'Admin login is separate from student login; use admin/login.php for admin access.',
    ]),
    (['student access', 'student login', 'student portal', 'student dashboard', 'student', 'freshman', 'freshmen'], [
        'If you are a student or freshman, sign in at student/login.php with your student number and password to access your clinic records.',
        'Freshmen and students should use the student portal at student/login.php to view their dashboard and records.',
        'Use student/login.php for student access. Admin login is separate from the student portal.',
    ]),
    (['available', 'hours', 'open', 'close', 'closing', 'opening', 'breaktime', 'break', 'when open', 'when available', 'what time', 'time', 'weekdays', 'saturday'], [
        'Clinic hours are Monday to Friday, 8:00 AM to 5:00 PM with a lunch break from 12:00 PM to 1:00 PM. On Saturdays, the clinic is open 8:00 AM to 5:00 PM with break time from 11:30 AM to 1:00 PM.',
        'We are open weekdays from 8:00 AM until 5:00 PM, break from 12:00 PM to 1:00 PM. Saturday also runs 8:00 AM to 5:00 PM with a break from 11:30 AM to 1:00 PM.',
        'The clinic schedule is: weekdays 8am–5pm (lunch break 12:00pm–1:00pm) and Saturday 8am–5pm (break 11:30am–12:00pm).',
    ]),
    (['patient records', 'medical records', 'patient data', 'patient history', 'view patient'], [
        'Patient records are managed in the admin patients section.',
        'Admins use the patients area to view and update patient data.',
        'Patient history lives in the admin dashboard under patients.',
    ]),
    (['medicine stock', 'medicines', 'medicine inventory', 'add medicine', 'edit medicine', 'medicine list'], [
        'Medicine details are managed in the admin medicines section.',
        'Use the medicines page to add, edit, and track inventory.',
        'Medicine inventory is handled by admins in the medicines area.',
    ]),
    (['reports', 'report page', 'dashboard reports', 'clinic activity', 'visit summary'], [
        'Reports are shown in the admin reports page.',
        'Open reports to view clinic visit and medicine usage summaries.',
        'The reports page summarizes activity, visits, and inventory use.',
    ]),
    (['database', 'db', 'clinic_db', 'table', 'schema', 'mysql'], [
        'This app stores data in clinic_db, including users, patients, medicines, and records.',
        'clinic_db contains users, patients, medical_records, medicines, and report tables.',
        'The system uses MySQL clinic_db for users, patients, medicines, and medical records.',
    ]),
    (['where login', 'where is login', 'which login page'], [
        'Admin login is admin/login.php. Student login is student/login.php.',
        'Use admin/login.php for admins and student/login.php for students.',
    ]),
    (['forgot password', 'reset password', 'password reset'], [
        'Contact the clinic administrator to reset your password.',
        'Password reset is handled by the admin, not through this page.',
    ]),
    (['help', 'support', 'issue', 'problem', 'error'], [
        'Contact the clinic administrator for support.',
        'If there is a problem, tell the admin exactly what you see.',
    ]),
    (['location'], [
        'The clinic is on the 2nd floor of Building C, in the middle section, next to the library.',
        'Find the clinic on 2nd floor Building C, middle section, beside the library.',
    ]),
]

UNKNOWN_RESPONSE = [
    'Ask a specific question about login, student access, patients, medicines, reports, or location.',
    'Try logging in, asking about the clinic location, patient records, medicines, or reports.',
    'I can answer login, student access, clinic location, patient records, medicines, and reports questions.',
]

def get_db_connection():
    try:
        return mysql.connector.connect(**DB_CONFIG)
    except Error as e:
        print(f"Database connection error: {e}")
        return None

def query_count(sql_query: str):
    conn = get_db_connection()
    if not conn:
        return None
    try:
        cursor = conn.cursor()
        cursor.execute(sql_query)
        result = cursor.fetchone()
        return result[0] if result else None
    except Error as e:
        print(f"Database query error: {e}")
        return None
    finally:
        if conn:
            conn.close()

def get_patient_count():
    count = query_count("SELECT COUNT(*) FROM patients WHERE status = 'active'")
    if count is None:
        return 'Sorry, I cannot read patient count right now.'
    return f'There are {count} active patients.'

def get_medicine_count():
    count = query_count("SELECT COUNT(*) FROM medicines WHERE status = 'available'")
    if count is None:
        return 'Sorry, I cannot read medicine stock right now.'
    return f'There are {count} available medicines.'

def get_recent_visits():
    count = query_count(
        "SELECT COUNT(*) FROM medical_records WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"
    )
    if count is None:
        return 'Sorry, I cannot read recent visits right now.'
    return f'There have been {count} visits in the last 7 days.'

def matches(keywords, text):
    for keyword in keywords:
        if keyword in text:
            return True
    return False

def find_answer(message: str) -> str:
    # 1. Clean incoming input text completely
    normalized = message.strip().lower()
    normalized_clean = re.sub(r'[^\w\s]', '', normalized)
    
    if not normalized:
        return 'Please ask a specific question about login, students, patients, medicines, or reports.'

    # 2. PRIORITY OVERRIDE CHECK: Direct string matching for location-based words
    clinic_keywords = ['clinic', 'building', 'location', 'located', 'room', 'where is', 'where is located', 'where is the clinic', 'which building', 'library', 'middle', 'next to the library']
    if any(kw in normalized_clean for kw in clinic_keywords):
        return CLINIC_RESPONSE

    # 3. Handle data metrics queries
    if 'how many patients' in normalized_clean or 'patient count' in normalized_clean or 'active patients' in normalized_clean:
        return get_patient_count()

    if 'how many medicines' in normalized_clean or 'medicine count' in normalized_clean or 'medicines in stock' in normalized_clean:
        return get_medicine_count()

    if 'recent visits' in normalized_clean or 'visits last week' in normalized_clean or 'patient visits' in normalized_clean:
        return get_recent_visits()

    for keywords, responses in RESPONSE_PATTERNS:
        if matches(keywords, normalized_clean):
            return random.choice(responses)

    return UNKNOWN_RESPONSE[0]

@app.route('/chat', methods=['POST', 'OPTIONS'])
def chat():
    if request.method == 'OPTIONS':
        response = jsonify({'status': 'ok'})
        response.headers['Access-Control-Allow-Origin'] = '*'
        response.headers['Access-Control-Allow-Headers'] = 'Content-Type'
        response.headers['Access-Control-Allow-Methods'] = 'POST, OPTIONS'
        return response

    data = request.get_json(silent=True) or {}
    message = data.get('message', '')
    reply = find_answer(message)
    response = jsonify({'reply': reply})
    response.headers['Access-Control-Allow-Origin'] = '*'
    response.headers['Access-Control-Allow-Headers'] = 'Content-Type'
    return response

if __name__ == '__main__':
    app.run(host='127.0.0.1', port=5000, debug=True)