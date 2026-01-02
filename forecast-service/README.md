# Forecast Service

Lightweight FastAPI service that trains and serves baseline demand forecasts for inventory items.

## Prerequisites
- Python 3.11+
- pip (or Poetry if you prefer to convert)

## Setup
```bash
cd forecast-service
python -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
```

## Run
```bash
uvicorn forecast_service.main:app --reload --port 9000
```

Endpoints:
- `GET /health` — liveness check
- `POST /train` — body: `{org_id, location_id, item_ids, start_date, end_date, history:[{item_id,date,quantity}]}`; trains baseline model and stores under `./forecast_service/models/{org}/{location}/baseline.json`
- `POST /predict` — body: `{org_id, location_id, item_ids, horizon_days, signals:{dow_multipliers?, event_multiplier?}, method:"baseline"}`; returns per-day forecasts with simple confidence interval (avg ± std dev)

## Tests
```bash
cd forecast-service
source .venv/bin/activate
pytest
```
