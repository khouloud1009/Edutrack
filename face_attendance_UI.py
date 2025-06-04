from flask import Flask, render_template, Response # flask web app , render html temlates , return http responses
import cv2
import face_recognition
import os
import numpy as np
from datetime import datetime

app = Flask(__name__) # create an instance of class flask # main app object

# Load known faces
known_faces_dir = "known_faces"
known_face_images = []
known_face_names = []

for file_name in os.listdir(known_faces_dir):
    known_face_images.append(cv2.imread(f'{known_faces_dir}/{file_name}'))
    known_face_names.append(os.path.splitext(file_name)[0])

def encode_known_faces(known_face_images):
    known_face_encoded = []
    for image in known_face_images:
        image = cv2.cvtColor(image, cv2.COLOR_BGR2RGB)
        known_face_encoded.append(face_recognition.face_encodings(image)[0])
    return known_face_encoded

known_face_encoded = encode_known_faces(known_face_images)

def markAttendance(name):
    with open('Attendance.csv', 'r+') as f:
        myDataList = f.readlines()
        nameList = []
        for line in myDataList:
            entry = line.split(',')
            nameList.append(entry[0])
        if name not in nameList:
            now = datetime.now()
            time = now.strftime('%I:%M:%S:%p')
            date = now.strftime('%d-%B-%Y')
            f.writelines(f'{name}, {time}, {date}\n')

# Video capture generator
def generate_frames():

    global video_running
    video_running = True  # Set the flag to indicate the video is running
    
    cap = cv2.VideoCapture(0)
    while True:
        success, img = cap.read()
        if not success:
            break
        imgS = cv2.resize(img, (0, 0), None, 0.25, 0.25)
        imgS = cv2.cvtColor(imgS, cv2.COLOR_BGR2RGB)

        faces_in_frame = face_recognition.face_locations(imgS)
        encoded_faces = face_recognition.face_encodings(imgS, faces_in_frame)

        for encode_face, faceloc in zip(encoded_faces, faces_in_frame):
            matches = face_recognition.compare_faces(known_face_encoded, encode_face)
            faceDist = face_recognition.face_distance(known_face_encoded, encode_face)

            matchIndex = np.argmin(faceDist)

            if matches[matchIndex]:
                name = known_face_names[matchIndex].upper().lower()
                y1, x2, y2, x1 = faceloc
                y1, x2, y2, x1 = y1 * 4, x2 * 4, y2 * 4, x1 * 4
                cv2.rectangle(img, (x1, y1), (x2, y2), (0, 255, 0), 2)
                cv2.rectangle(img, (x1, y2 - 35), (x2, y2), (0, 255, 0), cv2.FILLED)
                cv2.putText(img, name, (x1 + 6, y2 - 5), cv2.FONT_HERSHEY_COMPLEX, 1, (255, 255, 255), 2)
                markAttendance(name)

        # Encode the image as JPEG / important - cause http responses must send in data as bytes
        ret, buffer = cv2.imencode('.jpg', img)
        frame = buffer.tobytes()
        # this will generates a multipart HTTP response chunk (chunk : is a one jpeg frame of the video stream)
        yield (b'--frame\r\n'
               b'Content-Type: image/jpeg\r\n\r\n' + frame + b'\r\n')

# route serves your main HTML page
# Important to load the UI 
@app.route('/')
def index():
    return render_template('index.html')

# To response to the frontend Button click event
@app.route('/start_video')
def start_video():
    return "Video feed started", 200

# Variable to control the video feed
video_running = False

@app.route('/stop_video')
def stop_video():
    global video_running # May be don't need
    video_running = False  # Set the flag to stop the video feed
    return "Video feed stopped", 200

@app.route('/video_feed')
def video_feed():
    return Response(generate_frames(), mimetype='multipart/x-mixed-replace; boundary=frame')

if __name__ == '__main__':
    app.run(debug=True)
