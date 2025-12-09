import cv2
import numpy as np
import base64
from fastapi import FastAPI, UploadFile, File, HTTPException, Form
from pydantic import BaseModel
from typing import List, Optional
import insightface
from insightface.app import FaceAnalysis
from ultralytics import YOLO

app = FastAPI(title="FaceAccess AI Service")

# --- Initialize Models ---
# YOLOv8 for Person/Face Detection (or Liveness)
# Using standard YOLOv8n check first if human is present to save resources, 
# or use it for specialized liveness if custom trained. 
# Here we use InsightFace for the heavy lifting of face detection + embedding.
app_face = FaceAnalysis(name='buffalo_l', providers=['CPUExecutionProvider'])
app_face.prepare(ctx_id=0, det_size=(640, 640))

# Pre-load comparison utility
def compute_sim(feat1, feat2):
    return np.dot(feat1, feat2) / (np.linalg.norm(feat1) * np.linalg.norm(feat2))

class RecognizeRequest(BaseModel):
    image: str # Base64 encoded image
    known_embeddings: List[dict] # List of {"user_id": 1, "embedding": [float...]}
    threshold: float = 0.45

@app.get("/health")
def health():
    return {"status": "ok", "service": "FaceAccess AI"}

@app.post("/extract-embedding")
async def extract_embedding(file: UploadFile = File(...)):
    """
    Extracts face embedding from an uploaded image (registration phase).
    """
    contents = await file.read()
    nparr = np.frombuffer(contents, np.uint8)
    img = cv2.imdecode(nparr, cv2.IMREAD_COLOR)
    
    faces = app_face.get(img)
    if len(faces) == 0:
        raise HTTPException(status_code=400, detail="No face detected")
    
    # Sort by size to get the main face
    faces = sorted(faces, key=lambda x: (x.bbox[2]-x.bbox[0]) * (x.bbox[3]-x.bbox[1]), reverse=True)
    main_face = faces[0]
    
    return {
        "embedding": main_face.embedding.tolist(),
        "bbox": main_face.bbox.tolist(),
        "kps": main_face.kps.tolist(),
        "gender": int(main_face.gender), # 1=M, 0=F
        "age": int(main_face.age)
    }

@app.post("/recognize")
async def recognize(
    file: UploadFile = File(...),
    known_embeddings: str = Form(...), # JSON string of known embeddings
    threshold: float = Form(0.5)
):
    """
    Detects faces in frame and compares with known embeddings.
    """
    import json
    try:
        known_data = json.loads(known_embeddings)
    except:
        return {"error": "Invalid JSON for known_embeddings"}

    contents = await file.read()
    nparr = np.frombuffer(contents, np.uint8)
    img = cv2.imdecode(nparr, cv2.IMREAD_COLOR)

    faces = app_face.get(img)
    results = []

    for face in faces:
        best_match = None
        max_score = -1.0
        
        # Compare with all known faces
        for person in known_data:
            # known_emb should be a list of floats
            known_emb = np.array(person['embedding'], dtype=np.float32)
            score = compute_sim(face.embedding, known_emb)
            
            if score > max_score:
                max_score = score
                best_match = person

        is_match = max_score >= threshold
        
        results.append({
            "bbox": face.bbox.tolist(),
            "score": float(max_score),
            "match": best_match['user_id'] if is_match and best_match else None,
            "match_name": best_match['name'] if is_match and best_match else "Unknown",
            "is_suspect": not is_match,
            "confidence": float(max_score)
        })

    return {"faces": results, "count": len(results)}
