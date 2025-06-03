#Importing Required Libraries

import cv2               # OpenCV for camera capture and image processing
import face_recognition  # High-level face recognition (uses dlib)
import os                # For file/folder operations
import csv               # For handling CSV attendance logs
from datetime import datetime  # To record timestamps
import numpy as np

#Loading Known Faces

known_faces_dir = "known_faces" # The folder where training image dataset will be stored
known_face_images = []  # Stores facial encodings 
known_face_names = []      # Stores corresponding names

#Loop Through Known Faces
#When can add here something that that assure that is a image of valide face

"""
# This was my first try
# Try to to use fct to debug

for filename in os.listdir(known_faces_dir):
    image = face_recognition.load_image_file(f"{known_faces_dir}/{filename}")
    image = cv2.cvtColor(image,cv2.COLOR_BGR2RGB)
    encoding = face_recognition.face_encodings(image)[0]  # Get the first face's encoding
    known_face_encodings.append(encoding)
    known_face_names.append(filename.split(".")[0])  # Use filename as name ("elon.jpg" → "elon")    
"""

# Traverse all image files in the directory, read images, and append the image array to the image list 'known_face_images' and file name to 'known_face_neme'

for file_name in os.listdir(known_faces_dir):
    known_face_images.append(cv2.imread(f'{known_faces_dir}/{file_name}'))
    known_face_names.append(os.path.splitext(file_name)[0])

# Function to encode all the train images and store them in a variable 'known_face_encoded'

def encode_known_faces(known_face_images):
    known_face_encoded = []
    for image in known_face_images:
        image = cv2.cvtColor(image, cv2.COLOR_BGR2RGB)
        known_face_encoded.append(face_recognition.face_encodings(image)[0])
    return known_face_encoded

known_face_encoded = encode_known_faces(known_face_images)

"""
# Use a function that will create a attendance file

attendance_file = "attendance.csv"
if not os.path.exists(attendance_file):
    with open(attendance_file, mode='w', newline='') as f:
        writer = csv.writer(f)
        writer.writerow(["Name", "Time"])  # Creates attendance.csv with headers if it doesn’t exist.
"""   

# Define a function that records attendance for a given name in csv file
#  Note: here you need to create Attendance.csv file manually and give the path in the function

def markAttendance(name): # 'name' name of a person whose attendance is to be marked
    with open('Attendance.csv','r+') as f:
        myDataList = f.readlines()
        nameList = []
        for line in myDataList:
            entry = line.split(',')
            nameList.append(entry[0])
        if name not in nameList: # To not write the same name again
            now = datetime.now()
            time = now.strftime('%I:%M:%S:%p')
            date = now.strftime('%d-%B-%Y')
            f.writelines(f'{name}, {time}, {date}\n')
        
#Real-Time Face Detection

# take pictures from webcam 
cap  = cv2.VideoCapture(0)

while True:
    success, img = cap.read()
    imgS = cv2.resize(img, (0,0), None, 0.25,0.25)
    imgS = cv2.cvtColor(imgS, cv2.COLOR_BGR2RGB)
    
    faces_in_frame = face_recognition.face_locations(imgS)
    encoded_faces = face_recognition.face_encodings(imgS, faces_in_frame)
    
    for encode_face, faceloc in zip(encoded_faces,faces_in_frame):
        matches = face_recognition.compare_faces(known_face_encoded, encode_face)
        faceDist = face_recognition.face_distance(known_face_encoded, encode_face)
        
        matchIndex = np.argmin(faceDist)
        print(matchIndex)
        
        if matches[matchIndex]:
            name = known_face_names[matchIndex].upper().lower()
            y1,x2,y2,x1 = faceloc
            
            # since we scaled down by 4 times
            y1, x2,y2,x1 = y1*4,x2*4,y2*4,x1*4
            cv2.rectangle(img,(x1,y1),(x2,y2),(0,255,0),2)
            cv2.rectangle(img, (x1,y2-35),(x2,y2), (0,255,0), cv2.FILLED)
            cv2.putText(img,name, (x1+6,y2-5), cv2.FONT_HERSHEY_COMPLEX,1,(255,255,255),2)
            markAttendance(name)
            
    cv2.imshow('webcam', img)
    if cv2.waitKey(1) & 0xFF == ord('q'):
        break
