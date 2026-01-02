from __future__ import annotations

from fastapi import FastAPI, HTTPException

from .baseline import BaselineForecaster
from .schemas import PredictRequest, TrainRequest

app = FastAPI(title="Forecast Service", version="0.1.0")
forecaster = BaselineForecaster()


@app.get("/health")
def health() -> dict:
    return {"status": "ok"}


@app.post("/train")
def train(request: TrainRequest) -> dict:
    try:
        artifact = forecaster.train(request)
    except ValueError as exc:
        raise HTTPException(status_code=400, detail=str(exc))

    return {"status": "trained", "items": list(artifact.keys())}


@app.post("/predict")
def predict(request: PredictRequest):
    if request.method != "baseline":
        raise HTTPException(status_code=400, detail="Unsupported method")

    try:
        response = forecaster.predict(request)
    except FileNotFoundError as exc:
        raise HTTPException(status_code=404, detail=str(exc))
    except ValueError as exc:
        raise HTTPException(status_code=400, detail=str(exc))

    return response
