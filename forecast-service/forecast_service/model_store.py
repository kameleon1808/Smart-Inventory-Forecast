from __future__ import annotations

import json
from pathlib import Path
from typing import Dict, Any


class ModelStore:
    def __init__(self, base_dir: Path | None = None) -> None:
        self.base_dir = base_dir or Path(__file__).resolve().parent / "models"

    def path_for(self, org_id: int, location_id: int) -> Path:
        return self.base_dir / str(org_id) / str(location_id) / "baseline.json"

    def save(self, org_id: int, location_id: int, payload: Dict[str, Any]) -> None:
        path = self.path_for(org_id, location_id)
        path.parent.mkdir(parents=True, exist_ok=True)
        path.write_text(json.dumps(payload, indent=2))

    def load(self, org_id: int, location_id: int) -> Dict[str, Any]:
        path = self.path_for(org_id, location_id)
        if not path.exists():
            raise FileNotFoundError(f"Model not found for org {org_id}, location {location_id}")
        return json.loads(path.read_text())
