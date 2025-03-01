import whisper
import mysql.connector
import time
import json
import regex

def clean_string(s: str) -> str:
    s = regex.sub(r'[^\p{Letter}\s\d]+', '', s)
    s = s.strip().lower()
    s = regex.sub(r'\s+', ' ', s)
    return s

with open("../etc/mysql_config.json", "r") as f:
    config_data = json.load(f)

conn = mysql.connector.connect(
    host=config_data["mysql"]["host"],
    user=config_data["mysql"]["user"],
    password=config_data["mysql"]["password"],
    database=config_data["mysql"]["database"],
)
cursor = conn.cursor()

cursor.execute("SELECT a.id, s.textline, a.clip_path FROM audio_clip as a, statements as s WHERE status = 0 AND model = 'cleaned' AND a.statement_id = s.id")
rows = cursor.fetchall()

model = whisper.load_model("medium")
repo_dir = config_data["repo_dir"]

for id, textline, clip_path in rows:
    print(f"Transcribing file {clip_path} with {textline} (row id: {id})...")
    result = model.transcribe(repo_dir + clip_path, language="Swedish")

    cleanTextLine = clean_string(textline)
    cleanTranscription = clean_string(result["text"])

    if (cleanTextLine == cleanTranscription):
        print(f"Approved : {cleanTranscription}");
        cursor.execute(f"UPDATE audio_clip SET status = 1 WHERE id = {id}")
        conn.commit()
    else:
        print(f"User required : {cleanTranscription}");
        cursor.execute(f"UPDATE audio_clip SET status = 2 WHERE id = {id}")
        conn.commit()

cursor.close()
conn.close()
