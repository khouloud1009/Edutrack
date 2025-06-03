import face_recognition
import cv2
import sys
import os
import csv
from datetime import datetime

# Chargement des visages connus
known_dir = "known_faces"
known_encodings = []
known_names = []

for filename in os.listdir(known_dir):
    path = os.path.join(known_dir, filename)
    image = face_recognition.load_image_file(path)
    encodings = face_recognition.face_encodings(image)
    if encodings:
        known_encodings.append(encodings[0])
        known_names.append(os.path.splitext(filename)[0])

# Lecture de l'image capturée
if len(sys.argv) < 2:
    sys.exit("Image file required")
img_path = sys.argv[1]
img = face_recognition.load_image_file(img_path)

face_locations = face_recognition.face_locations(img)
face_encodings = face_recognition.face_encodings(img, face_locations)

for encoding in face_encodings:
    matches = face_recognition.compare_faces(known_encodings, encoding)
    face_distances = face_recognition.face_distance(known_encodings, encoding)
    best_match_index = face_distances.argmin()

    if matches[best_match_index]:
        name = known_names[best_match_index]
        # Marquer présence
        with open('Attendance.csv', 'a+') as f:
            f.seek(0)
            if name not in f.read():
                now = datetime.now()
                time = now.strftime('%I:%M:%S:%p')
                date = now.strftime('%d-%B-%Y')
                f.write(f'{name},{time},{date}\n')
