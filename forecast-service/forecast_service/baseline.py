from __future__ import annotations

import statistics
from collections import defaultdict
from datetime import date, timedelta
from typing import Dict, Iterable, List

from .schemas import HistoryPoint, PredictRequest, Prediction, PredictResponse, TrainRequest
from .model_store import ModelStore


class BaselineForecaster:
    def __init__(self, store: ModelStore | None = None) -> None:
        self.store = store or ModelStore()

    def train(self, payload: TrainRequest) -> Dict:
        history_by_item: Dict[int, List[HistoryPoint]] = defaultdict(list)
        for point in payload.history:
            if point.item_id in payload.item_ids and payload.start_date <= point.date <= payload.end_date:
                history_by_item[point.item_id].append(point)

        if not history_by_item:
            raise ValueError("No history found for requested items/date range")

        artifact = {}
        for item_id, points in history_by_item.items():
            daily_totals: Dict[date, float] = defaultdict(float)
            for p in points:
                daily_totals[p.date] += p.quantity

            daily_values = list(daily_totals.values())
            avg = statistics.fmean(daily_values)
            std = statistics.pstdev(daily_values) if len(daily_values) > 1 else 0.0

            dow_totals: Dict[int, List[float]] = defaultdict(list)
            for d, qty in daily_totals.items():
                dow_totals[d.weekday()].append(qty)

            dow_multipliers: Dict[int, float] = {}
            for dow, values in dow_totals.items():
                dow_avg = statistics.fmean(values)
                dow_multipliers[dow] = dow_avg / avg if avg > 0 else 1.0

            artifact[item_id] = {
                "avg_daily": avg,
                "std_daily": std,
                "dow_multipliers": dow_multipliers,
            }

        self.store.save(payload.org_id, payload.location_id, artifact)
        return artifact

    def predict(self, payload: PredictRequest) -> PredictResponse:
        artifact = self.store.load(payload.org_id, payload.location_id)
        predictions: List[Prediction] = []

        for offset in range(payload.horizon_days):
            day = date.today() + timedelta(days=offset + 1)
            dow = day.weekday()
            signal_dow = payload.signals.dow_multipliers or {}
            event_multiplier = payload.signals.event_multiplier or 1.0

            for item_id in payload.item_ids:
                if str(item_id) in artifact:
                    item_artifact = artifact[str(item_id)]
                elif item_id in artifact:
                    item_artifact = artifact[item_id]
                else:
                    raise ValueError(f"Item {item_id} not trained")

                base_avg = float(item_artifact["avg_daily"])
                std = float(item_artifact.get("std_daily", 0.0))
                dow_m = item_artifact.get("dow_multipliers", {})
                dow_multiplier = dow_m.get(dow, 1.0)
                dow_multiplier = signal_dow.get(dow, dow_multiplier)

                pred = base_avg * dow_multiplier * event_multiplier
                lower = max(0.0, pred - std)
                upper = pred + std

                predictions.append(
                    Prediction(
                        item_id=int(item_id),
                        date=day,
                        prediction=round(pred, 4),
                        ci_lower=round(lower, 4),
                        ci_upper=round(upper, 4),
                    )
                )

        return PredictResponse(predictions=predictions)
