import os
import sys
import time
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
if str(ROOT) not in sys.path:
    sys.path.insert(0, str(ROOT))

from sqlalchemy import inspect, text
from sqlalchemy.exc import OperationalError

from app import create_app
from app.extensions import db
from app.models import User

app = create_app()


def migrate_attendances_if_needed() -> None:
    inspector = inspect(db.engine)
    if "attendances" not in inspector.get_table_names():
        return

    columns = {col["name"] for col in inspector.get_columns("attendances")}
    additions = {
        "contact_name": "ALTER TABLE attendances ADD COLUMN contact_name VARCHAR(160) NOT NULL DEFAULT ''",
        "contact_phone": "ALTER TABLE attendances ADD COLUMN contact_phone VARCHAR(30)",
        "platform": "ALTER TABLE attendances ADD COLUMN platform VARCHAR(80)",
        "issue_type": "ALTER TABLE attendances ADD COLUMN issue_type VARCHAR(80)",
        "device_type": "ALTER TABLE attendances ADD COLUMN device_type VARCHAR(80)",
        "service_status": "ALTER TABLE attendances ADD COLUMN service_status VARCHAR(20) NOT NULL DEFAULT 'aberto'",
        "priority": "ALTER TABLE attendances ADD COLUMN priority VARCHAR(20) NOT NULL DEFAULT 'normal'",
    }

    with db.engine.begin() as conn:
        for col, ddl in additions.items():
            if col not in columns:
                conn.execute(text(ddl))
                print(f"[OK] coluna adicionada em attendances: {col}")


def bootstrap() -> None:
    admin_name = os.getenv("ADMIN_NAME", "Administrador")
    admin_email = os.getenv("ADMIN_EMAIL", "admin@empresa.com").strip().lower()
    admin_password = os.getenv("ADMIN_PASSWORD", "123456")

    for attempt in range(1, 31):
        try:
            with app.app_context():
                db.create_all()
                migrate_attendances_if_needed()
                admin = User.query.filter_by(email=admin_email).first()
                if not admin:
                    admin = User(name=admin_name, email=admin_email, role="admin", active=True)
                    admin.set_password(admin_password)
                    db.session.add(admin)
                    db.session.commit()
                    print(f"[OK] admin criado: {admin_email}")
                else:
                    print(f"[OK] admin ja existe: {admin_email}")
            return
        except OperationalError as exc:
            if attempt == 30:
                raise
            print(f"[wait] banco indisponivel ({attempt}/30): {exc}")
            time.sleep(2)


if __name__ == "__main__":
    bootstrap()
    sys.exit(0)
