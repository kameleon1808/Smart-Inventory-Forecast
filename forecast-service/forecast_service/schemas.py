from __future__ import annotations

from datetime import date
from typing import Dict, List, Optional

from pydantic import BaseModel, Field, field_validator


class HistoryPoint(BaseModel):
    item_id: int
    date: date
    quantity: float = Field(..., ge=0)


class TrainRequest(BaseModel):
    org_id: int
    location_id: int
    item_ids: List[int]
    start_date: date
    end_date: date
    history: List[HistoryPoint]

    @field_validator("history")
    @classmethod
    def require_history(cls, v: List[HistoryPoint]) -> List[HistoryPoint]:
        if not v:
            raise ValueError("history is required")
        return v


class PredictSignals(BaseModel):
    dow_multipliers: Optional[Dict[int, float]] = None  # 0=Mon
    event_multiplier: float = 1.0


class PredictRequest(BaseModel):
    org_id: int
    location_id: int
    item_ids: List[int]
    horizon_days: int = Field(..., gt=0, le=90)
    method: str = Field("baseline", pattern="^baseline$")
    signals: PredictSignals = Field(default_factory=PredictSignals)


class Prediction(BaseModel):
    item_id: int
    date: date
    prediction: float
    ci_lower: float
    ci_upper: float


class PredictResponse(BaseModel):
    predictions: List[Prediction]
