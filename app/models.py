from __future__ import annotations

from decimal import Decimal
from uuid import uuid4

from flask_login import UserMixin
from sqlalchemy import event
from werkzeug.security import check_password_hash, generate_password_hash

from .extensions import db
from .utils import local_now, local_today


class TimestampMixin:
    created_at = db.Column(db.DateTime, default=local_now, nullable=False)
    updated_at = db.Column(db.DateTime, default=local_now, onupdate=local_now, nullable=False)


class User(UserMixin, TimestampMixin, db.Model):
    __tablename__ = "users"

    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(120), nullable=False)
    email = db.Column(db.String(255), unique=True, nullable=False, index=True)
    password_hash = db.Column(db.String(255), nullable=False)
    role = db.Column(db.String(20), nullable=False, default="employee")
    active = db.Column(db.Boolean, default=True, nullable=False)

    attendances = db.relationship("Attendance", back_populates="user", lazy=True, foreign_keys="Attendance.user_id")
    assigned_attendances = db.relationship("Attendance", back_populates="assigned_to", lazy=True, foreign_keys="Attendance.assigned_to_id")
    charges_created = db.relationship("Charge", back_populates="created_by", lazy=True)
    time_entries = db.relationship("TimeEntry", back_populates="user", lazy=True)

    def set_password(self, password: str) -> None:
        self.password_hash = generate_password_hash(password)

    def check_password(self, password: str) -> bool:
        return check_password_hash(self.password_hash, password)

    @property
    def is_admin(self) -> bool:
        return self.role == "admin"

    def get_id(self) -> str:
        return str(self.id)


class Client(TimestampMixin, db.Model):
    __tablename__ = "clients"

    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(160), nullable=False)
    whatsapp = db.Column(db.String(30), nullable=False)
    email = db.Column(db.String(255), nullable=True)
    service_name = db.Column(db.String(160), nullable=False)
    monthly_fee = db.Column(db.Numeric(10, 2), nullable=False, default=Decimal("0.00"))
    due_date = db.Column(db.Date, nullable=False)
    status = db.Column(db.String(20), nullable=False, default="ativo")
    notes = db.Column(db.Text, nullable=True)
    last_notice_at = db.Column(db.DateTime, nullable=True)

    attendances = db.relationship("Attendance", back_populates="client", cascade="all, delete-orphan", lazy=True)
    charges = db.relationship("Charge", back_populates="client", cascade="all, delete-orphan", lazy=True)


class Attendance(TimestampMixin, db.Model):
    __tablename__ = "attendances"

    id = db.Column(db.Integer, primary_key=True)
    client_id = db.Column(db.Integer, db.ForeignKey("clients.id"), nullable=True, index=True)
    user_id = db.Column(db.Integer, db.ForeignKey("users.id"), nullable=False)
    assigned_to_id = db.Column(db.Integer, db.ForeignKey("users.id"), nullable=True, index=True)
    contact_name = db.Column(db.String(160), nullable=False)
    contact_phone = db.Column(db.String(30), nullable=True)
    platform = db.Column(db.String(80), nullable=True)
    issue_type = db.Column(db.String(80), nullable=True)
    device_type = db.Column(db.String(80), nullable=True)
    service_status = db.Column(db.String(20), nullable=False, default="aberto")
    priority = db.Column(db.String(20), nullable=False, default="normal")
    title = db.Column(db.String(180), nullable=False)
    description = db.Column(db.Text, nullable=False)
    attended_at = db.Column(db.DateTime, default=local_now, nullable=False)
    next_follow_up = db.Column(db.Date, nullable=True)
    forwarded_to = db.Column(db.String(160), nullable=True)
    started_at = db.Column(db.DateTime, nullable=True)
    finished_at = db.Column(db.DateTime, nullable=True)
    due_date_info = db.Column(db.String(100), nullable=True)

    client = db.relationship("Client", back_populates="attendances")
    user = db.relationship("User", back_populates="attendances", foreign_keys=[user_id])
    assigned_to = db.relationship("User", back_populates="assigned_attendances", foreign_keys=[assigned_to_id])
    messages = db.relationship("TeamMessage", back_populates="support", lazy=True, cascade="all, delete-orphan")

    @property
    def display_name(self) -> str:
        return self.contact_name or (self.client.name if self.client else "-")

    @property
    def display_phone(self) -> str:
        return self.contact_phone or (self.client.whatsapp if self.client else "")

    @property
    def progress_percent(self) -> int:
        mapping = {
            "aberto": 10,
            "em andamento": 50,
            "aguardando cliente": 70,
            "resolvido": 100,
        }
        return mapping.get(self.service_status, 10)

    @property
    def status_badge(self) -> str:
        mapping = {
            "aberto": "secondary",
            "em andamento": "primary",
            "aguardando cliente": "warning",
            "resolvido": "success",
        }
        return mapping.get(self.service_status, "secondary")


class Charge(TimestampMixin, db.Model):
    __tablename__ = "charges"

    id = db.Column(db.Integer, primary_key=True)
    client_id = db.Column(db.Integer, db.ForeignKey("clients.id"), nullable=False, index=True)
    created_by_id = db.Column(db.Integer, db.ForeignKey("users.id"), nullable=False)
    amount = db.Column(db.Numeric(10, 2), nullable=False, default=Decimal("0.00"))
    description = db.Column(db.String(180), nullable=False)
    due_date = db.Column(db.Date, nullable=False)
    status = db.Column(db.String(20), nullable=False, default="pendente")
    external_reference = db.Column(db.String(64), unique=True, nullable=False, default=lambda: uuid4().hex)
    mercado_pago_preference_id = db.Column(db.String(120), nullable=True)
    mercado_pago_init_point = db.Column(db.Text, nullable=True)
    mercado_pago_sandbox_init_point = db.Column(db.Text, nullable=True)
    mercado_pago_payment_id = db.Column(db.String(64), nullable=True)
    mp_status = db.Column(db.String(40), nullable=True)
    paid_at = db.Column(db.DateTime, nullable=True)
    sent_at = db.Column(db.DateTime, nullable=True)
    last_notification_at = db.Column(db.DateTime, nullable=True)

    client = db.relationship("Client", back_populates="charges")
    created_by = db.relationship("User", back_populates="charges_created")

    @property
    def is_overdue(self) -> bool:
        if not self.due_date:
            return False
        return self.due_date < local_today() and self.status not in {"pago", "cancelado"}


class TimeEntry(db.Model):
    __tablename__ = "time_entries"

    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey("users.id"), nullable=False, index=True)
    entry_type = db.Column(db.String(20), nullable=False)
    note = db.Column(db.String(255), nullable=True)
    created_at = db.Column(db.DateTime, default=local_now, nullable=False)

    user = db.relationship("User", back_populates="time_entries")


class TeamMessage(db.Model):
    __tablename__ = "team_messages"

    id = db.Column(db.Integer, primary_key=True)
    author_user_id = db.Column(db.Integer, db.ForeignKey("users.id"), nullable=False, index=True)
    support_id = db.Column(db.Integer, db.ForeignKey("attendances.id"), nullable=True, index=True)
    body = db.Column(db.Text, nullable=False)
    created_at = db.Column(db.DateTime, default=local_now, nullable=False, index=True)

    author = db.relationship("User", foreign_keys=[author_user_id])
    support = db.relationship("Attendance", back_populates="messages", foreign_keys=[support_id])

    @property
    def is_admin_message(self) -> bool:
        return getattr(self.author, "role", "") == "admin"


class DirectMessage(db.Model):
    __tablename__ = "direct_messages"

    id = db.Column(db.Integer, primary_key=True)
    sender_user_id = db.Column(db.Integer, db.ForeignKey("users.id"), nullable=False, index=True)
    recipient_user_id = db.Column(db.Integer, db.ForeignKey("users.id"), nullable=False, index=True)
    body = db.Column(db.Text, nullable=False)
    created_at = db.Column(db.DateTime, default=local_now, nullable=False, index=True)

    sender = db.relationship("User", foreign_keys=[sender_user_id])
    recipient = db.relationship("User", foreign_keys=[recipient_user_id])


class Platform(TimestampMixin, db.Model):
    __tablename__ = "platforms"

    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(100), nullable=False, unique=True)
    url = db.Column(db.String(255), nullable=True)
    notes = db.Column(db.Text, nullable=True)

    accounts = db.relationship("PlatformAccount", back_populates="platform", cascade="all, delete-orphan", lazy=True)

    @property
    def total_slots(self) -> int:
        return sum(a.max_slots for a in self.accounts)

    @property
    def used_slots(self) -> int:
        return sum(
            s for a in self.accounts
            for s in [len([sl for sl in a.slots if sl.client_id is not None])]
        )

    @property
    def free_slots(self) -> int:
        return self.total_slots - self.used_slots

class PlatformAccount(TimestampMixin, db.Model):
    __tablename__ = "platform_accounts"

    id = db.Column(db.Integer, primary_key=True)
    platform_id = db.Column(db.Integer, db.ForeignKey("platforms.id"), nullable=False, index=True)
    login = db.Column(db.String(255), nullable=False)
    password = db.Column(db.String(255), nullable=False)
    max_slots = db.Column(db.Integer, nullable=False, default=1)
    notes = db.Column(db.Text, nullable=True)

    platform = db.relationship("Platform", back_populates="accounts")
    slots = db.relationship("AccountSlot", back_populates="account", cascade="all, delete-orphan", lazy=True)

    @property
    def used_slots(self) -> int:
        return len([s for s in self.slots if s.client_id is not None])

    @property
    def free_slots(self) -> int:
        return self.max_slots - self.used_slots

    @property
    def is_full(self) -> bool:
        return self.free_slots <= 0


class AccountSlot(TimestampMixin, db.Model):
    __tablename__ = "account_slots"

    id = db.Column(db.Integer, primary_key=True)
    account_id = db.Column(db.Integer, db.ForeignKey("platform_accounts.id"), nullable=False, index=True)
    client_id = db.Column(db.Integer, db.ForeignKey("clients.id"), nullable=True, index=True)
    slot_number = db.Column(db.Integer, nullable=False, default=1)
    notes = db.Column(db.Text, nullable=True)

    account = db.relationship("PlatformAccount", back_populates="slots")
    client = db.relationship("Client", backref=db.backref("slots", lazy=True))


@event.listens_for(PlatformAccount, "after_insert")
def create_slots_after_insert(_mapper, connection, target) -> None:
    if not target.max_slots:
        return

    connection.execute(
        AccountSlot.__table__.insert(),
        [
            {
                "account_id": target.id,
                "slot_number": slot_number,
            }
            for slot_number in range(1, target.max_slots + 1)
        ],
    )
