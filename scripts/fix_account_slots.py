import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
if str(ROOT) not in sys.path:
    sys.path.insert(0, str(ROOT))

from app import create_app
from app.extensions import db
from app.models import AccountSlot, PlatformAccount


def main() -> int:
    app = create_app()

    with app.app_context():
        total_created = 0
        accounts = PlatformAccount.query.order_by(PlatformAccount.id.asc()).all()

        for account in accounts:
            existing_numbers = {slot.slot_number for slot in account.slots}
            created_for_account = 0

            for slot_number in range(1, account.max_slots + 1):
                if slot_number in existing_numbers:
                    continue

                db.session.add(
                    AccountSlot(
                        account_id=account.id,
                        slot_number=slot_number,
                    )
                )
                created_for_account += 1
                total_created += 1

            if created_for_account:
                print(
                    f"[OK] conta {account.id} ({account.login}): "
                    f"{created_for_account} slot(s) criado(s)"
                )

        db.session.commit()
        print(f"[OK] finalizado: {total_created} slot(s) criado(s)")

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
