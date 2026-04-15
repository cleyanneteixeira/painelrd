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


def _create_chat_table(conn, table_name: str) -> None:
    dialect = conn.engine.dialect.name

    if dialect == "sqlite":
        if table_name == "team_messages":
            conn.execute(text("""
                CREATE TABLE team_messages (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    author_user_id INTEGER NOT NULL,
                    support_id INTEGER,
                    body TEXT NOT NULL,
                    created_at DATETIME NOT NULL
                )
            """))
            conn.execute(text("CREATE INDEX ix_tm_author ON team_messages (author_user_id)"))
            conn.execute(text("CREATE INDEX ix_tm_support ON team_messages (support_id)"))
            conn.execute(text("CREATE INDEX ix_tm_created ON team_messages (created_at)"))
        else:
            conn.execute(text("""
                CREATE TABLE direct_messages (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    sender_user_id INTEGER NOT NULL,
                    recipient_user_id INTEGER NOT NULL,
                    body TEXT NOT NULL,
                    created_at DATETIME NOT NULL
                )
            """))
            conn.execute(text("CREATE INDEX ix_dm_sender ON direct_messages (sender_user_id)"))
            conn.execute(text("CREATE INDEX ix_dm_recipient ON direct_messages (recipient_user_id)"))
            conn.execute(text("CREATE INDEX ix_dm_created ON direct_messages (created_at)"))
        return

    if table_name == "team_messages":
        conn.execute(text("""
            CREATE TABLE team_messages (
                id INTEGER AUTO_INCREMENT PRIMARY KEY,
                author_user_id INTEGER NOT NULL,
                support_id INTEGER,
                body TEXT NOT NULL,
                created_at DATETIME NOT NULL,
                INDEX ix_tm_author (author_user_id),
                INDEX ix_tm_support (support_id),
                INDEX ix_tm_created (created_at)
            )
        """))
    else:
        conn.execute(text("""
            CREATE TABLE direct_messages (
                id INTEGER AUTO_INCREMENT PRIMARY KEY,
                sender_user_id INTEGER NOT NULL,
                recipient_user_id INTEGER NOT NULL,
                body TEXT NOT NULL,
                created_at DATETIME NOT NULL,
                INDEX ix_dm_sender (sender_user_id),
                INDEX ix_dm_recipient (recipient_user_id),
                INDEX ix_dm_created (created_at)
            )
        """))


def migrate_chat_tables_if_needed() -> None:
    """Cria as tabelas de chat se ainda nao existirem."""
    inspector = inspect(db.engine)
    existing = inspector.get_table_names()
    if "team_messages" not in existing:
        with db.engine.begin() as conn:
            _create_chat_table(conn, "team_messages")
        print("[OK] tabela team_messages criada")
    if "direct_messages" not in existing:
        with db.engine.begin() as conn:
            _create_chat_table(conn, "direct_messages")
        print("[OK] tabela direct_messages criada")


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
                migrate_chat_tables_if_needed()
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
