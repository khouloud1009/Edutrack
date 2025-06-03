import csv
import mysql.connector
import pandas as pd


# Database connection
db = mysql.connector.connect(
    host="localhost",
    user="root",
    password="Khouloud123@",
    database="pfe_db"
)

cursor = db.cursor()

file_path = 'Attendance.csv'
data= pd.read_csv(file_path, header=None, names=['name'])
print(data)
insert_query = "INSERT INTO test_att (name) VALUES (%s)"
for index, row in data.iterrows():
    cursor.execute(insert_query, (row['name'],))




db.commit()
cursor.close()
db.close()
print("Data transfer completed successfully.")