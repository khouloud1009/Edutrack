from flask import Flask, render_template, Response, jsonify
from flask_cors import CORS
import cv2
import face_recognition
import os
import numpy as np
from datetime import datetime
import csv
import threading
import time
import logging

# Set up logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

app = Flask(__name__)
# Enable CORS for all routes
CORS(app, origins=['http://localhost', 'http://127.0.0.1', 'http://192.168.100.43'])

# Global variables
video_running = False
cap = None
attendance_file = 'admin/Attendance.csv'
frame_thread = None
current_frame = None
frame_lock = threading.Lock()

# Load known faces
known_faces_dir = "known_faces"
known_face_images = []
known_face_names = []
known_face_encoded = []

def load_known_faces():
    """Load and encode known faces from the known_faces directory"""
    global known_face_images, known_face_names, known_face_encoded
    
    known_face_images = []
    known_face_names = []
    known_face_encoded = []
    
    if not os.path.exists(known_faces_dir):
        os.makedirs(known_faces_dir)
        logger.info(f"Created directory: {known_faces_dir}")
        return False
    
    # Load image files
    image_files = [f for f in os.listdir(known_faces_dir) 
                  if f.lower().endswith(('.png', '.jpg', '.jpeg'))]
    
    if not image_files:
        logger.warning(f"No image files found in {known_faces_dir}")
        return False
    
    for file_name in image_files:
        image_path = os.path.join(known_faces_dir, file_name)
        try:
            image = cv2.imread(image_path)
            if image is not None:
                known_face_images.append(image)
                # Remove file extension from name
                name = os.path.splitext(file_name)[0]
                known_face_names.append(name)
                logger.info(f"Loaded image for: {name}")
            else:
                logger.warning(f"Could not load image: {image_path}")
        except Exception as e:
            logger.error(f"Error loading image {image_path}: {e}")
    
    # Encode faces
    known_face_encoded = encode_known_faces(known_face_images)
    logger.info(f"Successfully loaded {len(known_face_names)} known faces: {known_face_names}")
    return len(known_face_encoded) > 0

def encode_known_faces(face_images):
    """Encode face images for recognition"""
    encoded_faces = []
    for i, image in enumerate(face_images):
        try:
            rgb_image = cv2.cvtColor(image, cv2.COLOR_BGR2RGB)
            encodings = face_recognition.face_encodings(rgb_image)
            if encodings:
                encoded_faces.append(encodings[0])
                logger.info(f"Successfully encoded face for: {known_face_names[i]}")
            else:
                logger.warning(f"No face found in image for: {known_face_names[i]}")
        except Exception as e:
            logger.error(f"Error encoding face {known_face_names[i]}: {e}")
    return encoded_faces

def mark_attendance(name):
    """Mark attendance in CSV file"""
    try:
        # Ensure directory exists
        os.makedirs(os.path.dirname(attendance_file), exist_ok=True)
        
        # Check if file exists and create header if needed
        file_exists = os.path.exists(attendance_file)
        if not file_exists:
            with open(attendance_file, 'w', newline='', encoding='utf-8') as f:
                writer = csv.writer(f)
                writer.writerow(['Name', 'Time', 'Date'])
            logger.info(f"Created attendance file: {attendance_file}")
        
        # Read existing attendance for today
        current_date = datetime.now().strftime('%d-%B-%Y')
        existing_today = []
        
        try:
            with open(attendance_file, 'r', encoding='utf-8') as f:
                reader = csv.reader(f)
                next(reader, None)  # Skip header
                for row in reader:
                    if len(row) >= 3 and row[2] == current_date:
                        existing_today.append(row[0])
        except Exception as e:
            logger.warning(f"Could not read existing attendance: {e}")
        
        # Add attendance if not already marked today
        if name not in existing_today:
            with open(attendance_file, 'a', newline='', encoding='utf-8') as f:
                writer = csv.writer(f)
                current_time = datetime.now().strftime('%I:%M:%S %p')
                writer.writerow([name, current_time, current_date])
                logger.info(f"Marked attendance for: {name} at {current_time}")
        else:
            logger.info(f"Attendance already marked today for: {name}")
        
    except Exception as e:
        logger.error(f"Error marking attendance for {name}: {e}")

def capture_frames():
    """Capture frames in a separate thread"""
    global video_running, cap, current_frame, frame_lock
    
    last_recognition_time = {}
    recognition_cooldown = 5  # seconds
    
    while video_running and cap and cap.isOpened():
        try:
            success, frame = cap.read()
            if not success:
                logger.warning("Failed to read frame from camera")
                time.sleep(0.1)
                continue
            
            # Process frame for face recognition
            if len(known_face_encoded) > 0:
                # Resize frame for faster processing
                small_frame = cv2.resize(frame, (0, 0), fx=0.25, fy=0.25)
                rgb_small_frame = cv2.cvtColor(small_frame, cv2.COLOR_BGR2RGB)
                
                # Find faces in the current frame
                face_locations = face_recognition.face_locations(rgb_small_frame)
                face_encodings = face_recognition.face_encodings(rgb_small_frame, face_locations)
                
                current_time = time.time()
                
                for face_encoding, face_location in zip(face_encodings, face_locations):
                    # Compare with known faces
                    matches = face_recognition.compare_faces(known_face_encoded, face_encoding, tolerance=0.6)
                    face_distances = face_recognition.face_distance(known_face_encoded, face_encoding)
                    
                    name = "Unknown"
                    color = (0, 0, 255)  # Red for unknown
                    
                    if True in matches:
                        best_match_index = np.argmin(face_distances)
                        if matches[best_match_index] and face_distances[best_match_index] < 0.6:
                            name = known_face_names[best_match_index]
                            color = (0, 255, 0)  # Green for known
                            
                            # Mark attendance with cooldown
                            if name not in last_recognition_time or \
                               current_time - last_recognition_time[name] > recognition_cooldown:
                                mark_attendance(name)
                                last_recognition_time[name] = current_time
                    
                    # Scale back up face locations
                    top, right, bottom, left = face_location
                    top *= 4
                    right *= 4
                    bottom *= 4
                    left *= 4
                    
                    # Draw rectangle around face
                    cv2.rectangle(frame, (left, top), (right, bottom), color, 2)
                    
                    # Draw label with background
                    cv2.rectangle(frame, (left, bottom - 35), (right, bottom), color, cv2.FILLED)
                    font = cv2.FONT_HERSHEY_DUPLEX
                    cv2.putText(frame, name, (left + 6, bottom - 6), font, 0.6, (255, 255, 255), 1)
            
            # Add timestamp and status to frame
            timestamp = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
            cv2.putText(frame, timestamp, (10, 30), cv2.FONT_HERSHEY_SIMPLEX, 0.7, (0, 255, 0), 2)
            
            status_text = f"Known Faces: {len(known_face_names)} | Recognition: {'ON' if len(known_face_encoded) > 0 else 'OFF'}"
            cv2.putText(frame, status_text, (10, 60), cv2.FONT_HERSHEY_SIMPLEX, 0.5, (255, 255, 255), 1)
            
            # Update current frame thread-safely
            with frame_lock:
                current_frame = frame.copy()
            
            time.sleep(0.033)  # ~30 FPS
            
        except Exception as e:
            logger.error(f"Error in frame capture: {e}")
            time.sleep(0.1)
    
    logger.info("Frame capture thread ended")

def generate_frames():
    """Generate video frames for streaming"""
    global current_frame, frame_lock
    
    while video_running:
        with frame_lock:
            if current_frame is not None:
                frame = current_frame.copy()
            else:
                # Create a black frame with text if no camera frame available
                frame = np.zeros((480, 640, 3), dtype=np.uint8)
                cv2.putText(frame, "Camera Not Available", (150, 240), 
                           cv2.FONT_HERSHEY_SIMPLEX, 1, (0, 0, 255), 2)
        
        if frame is not None:
            # Encode frame as JPEG
            ret, buffer = cv2.imencode('.jpg', frame)
            if ret:
                frame_bytes = buffer.tobytes()
                yield (b'--frame\r\n'
                       b'Content-Type: image/jpeg\r\n\r\n' + frame_bytes + b'\r\n')
        
        time.sleep(0.033)  # ~30 FPS

@app.route('/')
def index():
    """Main route"""
    return jsonify({
        "status": "running",
        "message": "Face Recognition Service is running",
        "video_running": video_running,
        "known_faces": len(known_face_names)
    })

@app.route('/start_video', methods=['POST', 'GET', 'OPTIONS'])
def start_video():
    """Start the video feed and face recognition"""
    global video_running, cap, frame_thread
    
    if video_running:
        return jsonify({"status": "already_running", "message": "Video feed is already running"}), 200
    
    try:
        # Load known faces first
        if not load_known_faces():
            return jsonify({"status": "error", "message": "No known faces loaded. Please add face images to the known_faces directory."}), 400
        
        # Initialize camera
        cap = cv2.VideoCapture(0)
        if not cap.isOpened():
            logger.error("Could not open camera")
            return jsonify({"status": "error", "message": "Could not open camera"}), 500
        
        # Set camera properties
        cap.set(cv2.CAP_PROP_FRAME_WIDTH, 640)
        cap.set(cv2.CAP_PROP_FRAME_HEIGHT, 480)
        cap.set(cv2.CAP_PROP_FPS, 30)
        
        video_running = True
        
        # Start frame capture thread
        frame_thread = threading.Thread(target=capture_frames, daemon=True)
        frame_thread.start()
        
        logger.info("Video feed started successfully")
        return jsonify({
            "status": "started", 
            "message": "Video feed started successfully",
            "known_faces_count": len(known_face_names),
            "known_faces": known_face_names
        }), 200
        
    except Exception as e:
        logger.error(f"Error starting video: {e}")
        video_running = False
        if cap:
            cap.release()
            cap = None
        return jsonify({"status": "error", "message": f"Error starting video: {str(e)}"}), 500

'''
@app.route('/start_video', methods=['POST', 'GET', 'OPTIONS'])

def start_video():
    Start the video feed and face recognition
    global video_running, cap, frame_thread
    
    if video_running:
        return jsonify({"status": "already_running", "message": "Video feed is already running"}), 200
    
    try:
        # Load known faces first
        if not load_known_faces():
            return jsonify({"status": "error", "message": "No known faces loaded. Please add face images to the known_faces directory."}), 400
        
        # Initialize camera
        cap = cv2.VideoCapture(0)
        if not cap.isOpened():
            logger.error("Could not open camera")
            return jsonify({"status": "error", "message": "Could not open camera"}), 500
        
        # Set camera properties
        cap.set(cv2.CAP_PROP_FRAME_WIDTH, 640)
        cap.set(cv2.CAP_PROP_FRAME_HEIGHT, 480)
        cap.set(cv2.CAP_PROP_FPS, 30)
        
        video_running = True
        
        # Start frame capture thread
        frame_thread = threading.Thread(target=capture_frames, daemon=True)
        frame_thread.start()
        
        logger.info("Video feed started successfully")
        return jsonify({
            "status": "started", 
            "message": "Video feed started successfully",
            "known_faces_count": len(known_face_names),
            "known_faces": known_face_names
        }), 200
        
    except Exception as e:
        logger.error(f"Error starting video: {e}")
        video_running = False
        if cap:
            cap.release()
            cap = None
        return jsonify({"status": "error", "message": f"Error starting video: {str(e)}"}), 500
'''

@app.route('/stop_video', methods=['POST', 'GET', 'OPTIONS'])
def stop_video():
    """Stop the video feed"""
    global video_running, cap, frame_thread, current_frame
    
    try:
        video_running = False
        
        # Wait for frame thread to finish
        if frame_thread and frame_thread.is_alive():
            frame_thread.join(timeout=2)
        
        # Release camera
        if cap:
            cap.release()
            cap = None
        
        # Clear current frame
        with frame_lock:
            current_frame = None
        
        logger.info("Video feed stopped successfully")
        return jsonify({"status": "stopped", "message": "Video feed stopped successfully"}), 200
        
    except Exception as e:
        logger.error(f"Error stopping video: {e}")
        return jsonify({"status": "error", "message": f"Error stopping video: {str(e)}"}), 500

@app.route('/video_feed')
def video_feed():
    """Video streaming route"""
    if not video_running:
        return jsonify({"error": "Video feed not started"}), 404
    
    return Response(generate_frames(),
                   mimetype='multipart/x-mixed-replace; boundary=frame')

@app.route('/status')
def get_status():
    """Get current status of the video feed"""
    camera_available = False
    if cap:
        camera_available = cap.isOpened()
    
    return jsonify({
        "video_running": video_running,
        "camera_available": camera_available,
        "known_faces_count": len(known_face_names),
        "known_faces": known_face_names,
        "attendance_file": attendance_file,
        "attendance_file_exists": os.path.exists(attendance_file)
    })

@app.route('/reload_faces', methods=['POST'])
def reload_faces():
    """Reload known faces"""
    success = load_known_faces()
    if success:
        return jsonify({
            "status": "success", 
            "message": "Known faces reloaded successfully",
            "known_faces_count": len(known_face_names),
            "known_faces": known_face_names
        })
    else:
        return jsonify({
            "status": "error", 
            "message": "Failed to load known faces"
        }), 400

if __name__ == '__main__':
    logger.info("Starting Face Recognition Service...")
    logger.info("Make sure you have images in the 'known_faces' directory")
    logger.info("CORS enabled for local development")
    
    # Pre-load known faces
    load_known_faces()
    
    app.run(debug=True, host='0.0.0.0', port=5000, threaded=True)