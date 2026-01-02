from __future__ import annotations

import json
import shutil
from datetime import date, timedelta
from pathlib import Path

import pytest
from fastapi.testclient import TestClient

from forecast_service.baseline import BaselineForecaster
from forecast_service.main import app, forecaster
from forecast_service.model_store import ModelStore
from forecast_service.schemas import PredictRequest, PredictSignals, TrainRequest, HistoryPoint


MODELS_DIR = Path(__file__).resolve().parent.parent / "forecast_service" / "models"


def teardown_module(module=None):
    if MODELS_DIR.exists():
        shutil.rmtree(MODELS_DIR)


def test_train_and_predict_api_roundtrip():
    client = TestClient(app)

    today = date.today()
    history = [
        {"item_id": 1, "date": (today - timedelta(days=i)).isoformat(), "quantity": 10.0}
        for i in range(7)
    ]

    train_payload = {
        "org_id": 1,
        "location_id": 1,
        "item_ids": [1],
        "start_date": (today - timedelta(days=7)).isoformat(),
        "end_date": today.isoformat(),
        "history": history,
    }

    res = client.post("/train", json=train_payload)
    assert res.status_code == 200, res.text

    predict_payload = {
        "org_id": 1,
        "location_id": 1,
        "item_ids": [1],
        "horizon_days": 3,
        "method": "baseline",
    }

    res = client.post("/predict", json=predict_payload)
    assert res.status_code == 200, res.text
    body = res.json()
    assert "predictions" in body
    assert len(body["predictions"]) == 3
    assert all(abs(p["prediction"] - 10) < 0.001 for p in body["predictions"])


def test_signal_overrides_and_ci():
    tmp_store = ModelStore(base_dir=MODELS_DIR)
    local_forecaster = BaselineForecaster(store=tmp_store)

    payload = TrainRequest(
        org_id=2,
        location_id=3,
        item_ids=[5],
        start_date=date.today() - timedelta(days=2),
        end_date=date.today(),
        history=[
            HistoryPoint(item_id=5, date=date.today() - timedelta(days=1), quantity=8),
            HistoryPoint(item_id=5, date=date.today() - timedelta(days=2), quantity=12),
        ],
    )

    local_forecaster.train(payload)

    target_dow = (date.today() + timedelta(days=1)).weekday()

    predict_payload = PredictRequest(
        org_id=2,
        location_id=3,
        item_ids=[5],
        horizon_days=1,
        signals=PredictSignals(dow_multipliers={target_dow: 2.0}, event_multiplier=1.0),
    )

    response = local_forecaster.predict(predict_payload)
    pred = response.predictions[0]
    # avg is 10, multiplier 2 => 20; std is 2 => CI [18, 22]
    assert abs(pred.prediction - 20) < 0.01
    assert abs(pred.ci_lower - 18) < 0.01
    assert abs(pred.ci_upper - 22) < 0.01
