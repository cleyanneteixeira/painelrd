from __future__ import annotations

from datetime import datetime, timedelta

from flask import Blueprint, current_app, flash, jsonify, redirect, render_template, request, url_for
from flask_login import current_user, login_required
from sqlalchemy import case, func as sql_func, or_

from app.extensions import db
from app.models import Attendance, Charge, Client, DirectMessage, TeamMessage, TimeEntry, User
from app.services.mercadopago import (
    MercadoPagoError,
    configured,
    create_checkout_preference,
    get_payment,
    validate_webhook_signature,
)
from app.utils import (
    ENTRY_LABELS,
    STATUS_BADGES,
    add_one_month,
    client_due_state,
    due_badge,
    due_label,
    local_day_bounds,
    local_now,
    local_today,
    next_entry_type,
    normalize_phone,
    parse_date,
    parse_datetime_local,
    parse_decimal,
    renewal_message,
    roles_required,
    whatsapp_link,
)

bp = Blueprint("main", __name__)


SUPPORT_PLATFORMS = [
    "Netflix",
    "Prime Video",
    "Disney+",
    "Max",
    "Globoplay",
    "Paramount+",
    "Apple TV+",
    "YouTube Premium",
    "Outro",
]
SUPPORT_ISSUES = [
    "nao entra",
    "senha incorreta",
    "tela caiu",
    "perfil ocupado",
    "codigo de verificacao",
    "pagamento pendente",
    "dispositivo nao compativel",
    "travando",
    "conta deslogada",
    "troca de tela",
    "outro",
]
SUPPORT_DEVICES = [
    "tv",
    "celular",
    "notebook",
    "tv box",
    "fire stick",
    "chromecast",
    "computador",
    "outro",
]
SUPPORT_STATUSES = ["aberto", "em andamento", "resolvido", "aguardando cliente"]
SUPPORT_PRIORITIES = ["normal", "urgente"]


@bp.route("/")
def index():
    if current_user.is_authenticated:
        return redirect(url_for("main.dashboard"))
    return redirect(url_for("auth.login"))


@bp.route("/dashboard")
@login_required
def dashboard():
    today = local_today()
    alert_window = current_app.config.get("ALERT_WINDOW_DAYS", 3)
    alert_limit = today + timedelta(days=alert_window)
    start_today, end_today = local_day_bounds(today)
    month_start = today.replace(day=1)
    next_month = add_one_month(month_start)

    #Contagens de clientes e cobranças em queries únicas
    client_counts = db.session.query(
        sql_func.count().label("total"),
        sql_func.count(case((db.and_(Client.status != "cancelado", Client.status != "inativo", Client.due_date < today), 1))).label("overdue"),
        sql_func.count(case((db.and_(Client.status != "cancelado", Client.status != "inativo", Client.due_date == today), 1))).label("due_today"),
        sql_func.count(case((db.and_(Client.status != "cancelado", Client.status != "inativo", Client.due_date > today, Client.due_date <= alert_limit), 1))).label("due_soon"),
    ).one()
    total_clients = client_counts.total
    overdue_clients = client_counts.overdue
    due_today_clients = client_counts.due_today
    due_soon_clients = client_counts.due_soon

    charge_counts = db.session.query(
        sql_func.count(case((Charge.status == "pendente", 1))).label("pending"),
        sql_func.count(case((db.and_(Charge.status == "pago", Charge.paid_at >= datetime.combine(month_start, datetime.min.time()), Charge.paid_at < datetime.combine(next_month, datetime.min.time())), 1))).label("paid_month"),
    ).one()
    pending_charges = charge_counts.pending
    paid_this_month = charge_counts.paid_month

    active_clients = Client.query.filter(Client.status != "cancelado", Client.status != "inativo")

    points_today = (
        TimeEntry.query.filter(TimeEntry.created_at >= start_today, TimeEntry.created_at < end_today)
        .order_by(TimeEntry.created_at.desc())
        .limit(8)
        .all()
    )
    recent_support = Attendance.query.order_by(Attendance.attended_at.desc()).limit(8).all()
    alert_clients = (
        active_clients.filter(Client.due_date <= alert_limit)
        .order_by(Client.due_date.asc(), Client.name.asc())
        .limit(8)
        .all()
    )
    latest_pending_charges = (
        Charge.query.filter_by(status="pendente")
        .order_by(Charge.due_date.asc(), Charge.created_at.desc())
        .limit(8)
        .all()
    )

    return render_template(
        "dashboard.html",
        total_clients=total_clients,
        overdue_clients=overdue_clients,
        due_today_clients=due_today_clients,
        due_soon_clients=due_soon_clients,
        pending_charges=pending_charges,
        paid_this_month=paid_this_month,
        points_today=points_today,
        recent_support=recent_support,
        alert_clients=alert_clients,
        latest_pending_charges=latest_pending_charges,
        alert_window=alert_window,
    )


@bp.route("/clientes")
@login_required
def clients_list():
    status = request.args.get("status", "").strip()
    search = request.args.get("q", "").strip()
    page = request.args.get("page", 1, type=int)
    today = local_today()
    alert_limit = today + timedelta(days=current_app.config.get("ALERT_WINDOW_DAYS", 3))

    query = Client.query
    if search:
        pattern = f"%{search}%"
        query = query.filter(
            or_(
                Client.name.ilike(pattern),
                Client.whatsapp.ilike(pattern),
                Client.service_name.ilike(pattern),
                Client.email.ilike(pattern),
            )
        )

    if status:
        if status == "atrasado":
            query = query.filter(Client.due_date < today, Client.status != "cancelado", Client.status != "inativo")
        elif status == "vence_hoje":
            query = query.filter(Client.due_date == today, Client.status != "cancelado", Client.status != "inativo")
        elif status == "vence_em_breve":
            query = query.filter(
                Client.due_date > today,
                Client.due_date <= alert_limit,
                Client.status != "cancelado",
                Client.status != "inativo",
            )
        else:
            query = query.filter_by(status=status)

    pagination = query.order_by(Client.due_date.asc(), Client.name.asc()).paginate(page=page, per_page=12, error_out=False)
    #Sumário em uma única query
    _summary = db.session.query(
        sql_func.count().label("total"),
        sql_func.count(case((db.and_(Client.status != "cancelado", Client.status != "inativo", Client.due_date < today), 1))).label("atrasados"),
        sql_func.count(case((db.and_(Client.status != "cancelado", Client.status != "inativo", Client.due_date == today), 1))).label("vence_hoje"),
        sql_func.count(case((db.and_(Client.status != "cancelado", Client.status != "inativo", Client.due_date > today, Client.due_date <= alert_limit), 1))).label("vence_em_breve"),
    ).one()
    summary = {
        "total": _summary.total,
        "atrasados": _summary.atrasados,
        "vence_hoje": _summary.vence_hoje,
        "vence_em_breve": _summary.vence_em_breve,
    }
    return render_template(
        "clients/list.html",
        clients=pagination.items,
        pagination=pagination,
        filter_status=status,
        search=search,
        summary=summary,
    )


@bp.route("/clientes/novo", methods=["GET", "POST"])
@roles_required("admin")
def clients_create():
    if request.method == "POST":
        name = request.form.get("name", "").strip()
        whatsapp = normalize_phone(request.form.get("whatsapp", ""))
        email = request.form.get("email", "").strip() or None
        service_name = request.form.get("service_name", "").strip()
        monthly_fee = parse_decimal(request.form.get("monthly_fee", "0"))
        due_date = parse_date(request.form.get("due_date", ""))
        status = request.form.get("status", "ativo")
        notes = request.form.get("notes", "").strip() or None

        if not all([name, whatsapp, service_name, due_date]):
            flash("Preencha nome, WhatsApp, servico e vencimento.", "danger")
        else:
            client = Client(
                name=name,
                whatsapp=whatsapp,
                email=email,
                service_name=service_name,
                monthly_fee=monthly_fee,
                due_date=due_date,
                status=status,
                notes=notes,
            )
            db.session.add(client)
            db.session.commit()
            flash("Cliente cadastrado com sucesso.", "success")
            return redirect(url_for("main.client_detail", client_id=client.id))

    return render_template("clients/form.html", client=None)


@bp.route("/clientes/<int:client_id>")
@login_required
def client_detail(client_id: int):
    client = Client.query.get_or_404(client_id)
    support_entries = (
        Attendance.query.filter_by(client_id=client.id)
        .order_by(Attendance.attended_at.desc())
        .limit(8)
        .all()
    )
    charges = (
        Charge.query.filter_by(client_id=client.id)
        .order_by(Charge.due_date.desc(), Charge.created_at.desc())
        .limit(8)
        .all()
    )
    #Contagens em uma única query
    _totals = db.session.query(
        sql_func.count(Attendance.id).label("support_total"),
    ).filter(Attendance.client_id == client.id).one()
    support_total = _totals.support_total
    charges_total = Charge.query.filter_by(client_id=client.id).count()
    pending_charge = (
        Charge.query.filter_by(client_id=client.id, status="pendente")
        .order_by(Charge.due_date.asc(), Charge.created_at.desc())
        .first()
    )
    preview_message = renewal_message(client, pending_charge)
    return render_template(
        "clients/detail.html",
        client=client,
        support_entries=support_entries,
        support_total=support_total,
        charges=charges,
        charges_total=charges_total,
        pending_charge=pending_charge,
        renewal_message=preview_message,
        due_label=due_label(client.due_date),
    )


@bp.route("/clientes/<int:client_id>/editar", methods=["GET", "POST"])
@roles_required("admin")
def client_edit(client_id: int):
    client = Client.query.get_or_404(client_id)
    if request.method == "POST":
        client.name = request.form.get("name", "").strip()
        client.whatsapp = normalize_phone(request.form.get("whatsapp", ""))
        client.email = request.form.get("email", "").strip() or None
        client.service_name = request.form.get("service_name", "").strip()
        client.monthly_fee = parse_decimal(request.form.get("monthly_fee", "0"))
        client.due_date = parse_date(request.form.get("due_date", ""))
        client.status = request.form.get("status", "ativo")
        client.notes = request.form.get("notes", "").strip() or None
        if not all([client.name, client.whatsapp, client.service_name, client.due_date]):
            flash("Preencha os campos obrigatorios.", "danger")
        else:
            db.session.commit()
            flash("Cliente atualizado com sucesso.", "success")
            return redirect(url_for("main.client_detail", client_id=client.id))
    return render_template("clients/form.html", client=client)


@bp.route("/clientes/<int:client_id>/remover", methods=["POST"])
@roles_required("admin")
def client_delete(client_id: int):
    client = Client.query.get_or_404(client_id)
    client_name = client.name
    db.session.delete(client)
    db.session.commit()
    flash(f"Cliente removido com sucesso: {client_name}.", "warning")
    return redirect(url_for("main.clients_list"))


@bp.route("/clientes/<int:client_id>/suporte")
@login_required
def client_support_redirect(client_id: int):
    Client.query.get_or_404(client_id)
    return redirect(url_for("main.support_center", client=client_id))


@bp.route("/clientes/<int:client_id>/atendimentos/novo", methods=["POST"])
@login_required
def attendance_create(client_id: int):
    client = Client.query.get_or_404(client_id)
    create_support_entry(client)
    return redirect(url_for("main.support_center"))


@bp.route("/suporte/<int:attendance_id>/editar", methods=["GET", "POST"])
@login_required
def support_edit(attendance_id: int):
    attendance = Attendance.query.get_or_404(attendance_id)
    if not current_user.is_admin and attendance.user_id != current_user.id:
        flash("Voce nao pode editar este atendimento.", "danger")
        return redirect(url_for("main.support_center"))

    if request.method == "POST":
        if update_support_entry(attendance):
            flash("Atendimento atualizado com sucesso.", "success")
            return redirect(url_for("main.support_center"))

    return render_template(
        "support/form.html",
        attendance=attendance,
        support_platforms=SUPPORT_PLATFORMS,
        support_issues=SUPPORT_ISSUES,
        support_devices=SUPPORT_DEVICES,
        support_statuses=SUPPORT_STATUSES,
        support_priorities=SUPPORT_PRIORITIES,
    )


@bp.route("/suporte/<int:attendance_id>/status", methods=["POST"])
@login_required
def support_update_status(attendance_id: int):
    attendance = Attendance.query.get_or_404(attendance_id)
    if not current_user.is_admin and attendance.user_id != current_user.id:
        flash("Voce nao pode alterar este atendimento.", "danger")
        return redirect(url_for("main.support_center"))

    new_status = (request.form.get("service_status", "").strip().lower() or "aberto")
    if new_status not in SUPPORT_STATUSES:
        flash("Status invalido.", "danger")
    else:
        attendance.service_status = new_status
        db.session.commit()
        flash("Status do atendimento atualizado.", "success")
    return redirect(request.referrer or url_for("main.support_center"))


@bp.route("/suporte/<int:attendance_id>/excluir", methods=["POST"])
@login_required
def support_delete(attendance_id: int):
    attendance = Attendance.query.get_or_404(attendance_id)
    if not current_user.is_admin and attendance.user_id != current_user.id:
        flash("Voce nao pode excluir este atendimento.", "danger")
        return redirect(url_for("main.support_center"))

    title = attendance.title
    owner = attendance.display_name
    db.session.delete(attendance)
    db.session.commit()
    flash(f"Atendimento removido com sucesso: {title} · {owner}.", "warning")
    return redirect(request.referrer or url_for("main.support_center"))


@bp.route("/suporte", methods=["GET", "POST"])
@login_required
def support_center():
    page = request.args.get("page", 1, type=int)
    search = request.args.get("q", "").strip()
    status_filter = request.args.get("status", "").strip().lower()

    if request.method == "POST":
        create_support_entry()
        return redirect(url_for("main.support_center"))

    query = Attendance.query

    if search:
        pattern = f"%{search}%"
        query = query.filter(
            or_(
                Attendance.contact_name.ilike(pattern),
                Attendance.contact_phone.ilike(pattern),
                Attendance.title.ilike(pattern),
                Attendance.description.ilike(pattern),
                Attendance.platform.ilike(pattern),
                Attendance.issue_type.ilike(pattern),
                Attendance.device_type.ilike(pattern),
            )
        )

    if status_filter:
        query = query.filter(Attendance.service_status == status_filter)

    pagination = query.order_by(Attendance.attended_at.desc()).paginate(page=page, per_page=12, error_out=False)

    employees = User.query.filter_by(active=True).order_by(User.name.asc()).all()

    return render_template(
        "support/index.html",
        support_entries=pagination.items,
        pagination=pagination,
        search=search,
        status_filter=status_filter,
        support_platforms=SUPPORT_PLATFORMS,
        support_issues=SUPPORT_ISSUES,
        support_devices=SUPPORT_DEVICES,
        support_statuses=SUPPORT_STATUSES,
        support_priorities=SUPPORT_PRIORITIES,
        endpoint="main.support_center",
        employees=employees,
    )


@bp.route("/cobrancas")
@login_required
def charges_list():
    status = request.args.get("status", "").strip()
    search = request.args.get("q", "").strip()
    page = request.args.get("page", 1, type=int)

    query = Charge.query.join(Client)
    if status:
        query = query.filter(Charge.status == status)
    if search:
        pattern = f"%{search}%"
        query = query.filter(
            or_(
                Charge.description.ilike(pattern),
                Client.name.ilike(pattern),
                Client.service_name.ilike(pattern),
            )
        )

    pagination = query.order_by(Charge.due_date.asc(), Charge.created_at.desc()).paginate(page=page, per_page=12, error_out=False)
    #Apenas id e nome para o select, sem carregar todos os campos
    clients = db.session.query(Client.id, Client.name).order_by(Client.name.asc()).all()
    summary = {
        "pendente": Charge.query.filter_by(status="pendente").count(),
        "pago": Charge.query.filter_by(status="pago").count(),
        "cancelado": Charge.query.filter_by(status="cancelado").count(),
    }
    return render_template(
        "charges/list.html",
        charges=pagination.items,
        pagination=pagination,
        clients=clients,
        filter_status=status,
        search=search,
        summary=summary,
        mp_enabled=configured(),
    )


@bp.route("/cobrancas/nova", methods=["POST"])
@roles_required("admin")
def charges_create():
    client_id = request.form.get("client_id", type=int)
    client = Client.query.get_or_404(client_id)
    amount = parse_decimal(request.form.get("amount", "0"), default=str(client.monthly_fee)) or client.monthly_fee
    description = request.form.get("description", "").strip() or f"Mensalidade - {client.service_name}"
    due_date = parse_date(request.form.get("due_date", "")) or client.due_date

    existing = Charge.query.filter_by(client_id=client.id, due_date=due_date, status="pendente").first()
    if existing:
        flash("Ja existe uma cobranca pendente para este cliente nesta data.", "warning")
        return redirect(request.referrer or url_for("main.client_detail", client_id=client.id))

    charge = Charge(
        client_id=client.id,
        created_by_id=current_user.id,
        amount=amount,
        description=description,
        due_date=due_date,
        status="pendente",
    )
    db.session.add(charge)
    db.session.commit()

    if configured():
        try:
            response = create_checkout_preference(charge)
            charge.mercado_pago_preference_id = response.get("id")
            charge.mercado_pago_init_point = response.get("init_point")
            charge.mercado_pago_sandbox_init_point = response.get("sandbox_init_point")
            db.session.commit()
            flash("Cobranca criada e link de pagamento gerado.", "success")
        except MercadoPagoError as exc:
            flash(f"Cobranca criada, mas sem link do Mercado Pago: {exc}", "warning")
        except Exception as exc:  # pragma: no cover
            flash(f"Cobranca criada, mas houve falha ao gerar link: {exc}", "warning")
    else:
        flash("Cobranca criada com sucesso.", "success")

    return redirect(request.referrer or url_for("main.client_detail", client_id=client.id))


@bp.route("/cobrancas/<int:charge_id>/gerar-link", methods=["POST"])
@roles_required("admin")
def charges_generate_link(charge_id: int):
    charge = Charge.query.get_or_404(charge_id)
    try:
        response = create_checkout_preference(charge)
        charge.mercado_pago_preference_id = response.get("id")
        charge.mercado_pago_init_point = response.get("init_point")
        charge.mercado_pago_sandbox_init_point = response.get("sandbox_init_point")
        db.session.commit()
        flash("Link do Mercado Pago gerado.", "success")
    except MercadoPagoError as exc:
        flash(str(exc), "danger")
    except Exception as exc:  # pragma: no cover
        flash(f"Falha ao gerar link: {exc}", "danger")
    return redirect(request.referrer or url_for("main.charges_list"))


@bp.route("/cobrancas/<int:charge_id>/pagar", methods=["POST"])
@roles_required("admin")
def charges_mark_paid(charge_id: int):
    charge = Charge.query.get_or_404(charge_id)
    mark_charge_paid(charge, source="manual")
    flash("Cobranca marcada como paga.", "success")
    return redirect(request.referrer or url_for("main.charges_list"))


@bp.route("/cobrancas/<int:charge_id>/cancelar", methods=["POST"])
@roles_required("admin")
def charges_cancel(charge_id: int):
    charge = Charge.query.get_or_404(charge_id)
    charge.status = "cancelado"
    charge.mp_status = "cancelled"
    db.session.commit()
    flash("Cobranca cancelada.", "warning")
    return redirect(request.referrer or url_for("main.charges_list"))


@bp.route("/cobrancas/<int:charge_id>/retorno")
@login_required
def charge_return(charge_id: int):
    charge = Charge.query.get_or_404(charge_id)
    status = request.args.get("status", "pendente")
    if status == "success":
        flash("O comprador voltou do checkout. Aguarde a confirmacao final pelo webhook do Mercado Pago.", "info")
    elif status == "failure":
        flash("Pagamento nao concluido no checkout.", "warning")
    else:
        flash("Pagamento ainda pendente de confirmacao.", "info")
    return redirect(url_for("main.client_detail", client_id=charge.client_id))


@bp.route("/integracoes/mercadopago/webhook", methods=["POST"])
def mercadopago_webhook():
    payload = request.get_json(silent=True) or {}
    data = payload.get("data", {}) if isinstance(payload, dict) else {}
    event_type = request.args.get("type") or payload.get("type") or payload.get("topic")
    data_id = request.args.get("data.id") or data.get("id") or request.args.get("id")
    signature_header = request.headers.get("x-signature")
    request_id = request.headers.get("x-request-id")

    if current_app.config.get("MP_WEBHOOK_SECRET"):
        is_valid = validate_webhook_signature(signature_header, request_id, str(data_id) if data_id else None)
        if not is_valid:
            return jsonify({"ok": False, "error": "invalid signature"}), 401

    if event_type in {"payment", "payments"} and data_id:
        try:
            payment = get_payment(data_id)
            external_reference = payment.get("external_reference")
            charge = Charge.query.filter_by(external_reference=external_reference).first()
            if charge:
                charge.mercado_pago_payment_id = str(payment.get("id"))
                charge.mp_status = payment.get("status")
                charge.last_notification_at = local_now()
                if payment.get("status") == "approved":
                    mark_charge_paid(charge, source="mercado_pago")
                elif payment.get("status") in {"cancelled", "rejected"}:
                    charge.status = "cancelado"
                    db.session.commit()
                else:
                    db.session.commit()
        except MercadoPagoError:
            pass

    return jsonify({"ok": True})


@bp.route("/ponto", methods=["GET", "POST"])
@login_required
def time_clock():
    today = local_today()
    start_today, end_today = local_day_bounds(today)

    today_entries = (
        TimeEntry.query.filter(
            TimeEntry.user_id == current_user.id,
            TimeEntry.created_at >= start_today,
            TimeEntry.created_at < end_today,
        )
        .order_by(TimeEntry.created_at.asc())
        .all()
    )
    entry_types = [entry.entry_type for entry in today_entries]
    next_type = next_entry_type(entry_types)

    if request.method == "POST":
        requested_type = request.form.get("entry_type", next_type)
        note = request.form.get("note", "").strip() or None
        entry = TimeEntry(user_id=current_user.id, entry_type=requested_type, note=note, created_at=local_now())
        db.session.add(entry)
        db.session.commit()
        flash(f"Ponto registrado: {ENTRY_LABELS.get(requested_type, requested_type)}.", "success")
        return redirect(url_for("main.time_clock"))

    recent_all = []
    if current_user.is_admin:
        recent_all = TimeEntry.query.order_by(TimeEntry.created_at.desc()).limit(40).all()

    return render_template(
        "time_clock/index.html",
        today_entries=today_entries,
        next_type=next_type,
        recent_all=recent_all,
        timezone_name=current_app.config.get("APP_TIMEZONE"),
    )


@bp.route("/ponto/<int:entry_id>/excluir", methods=["POST"])
@login_required
def time_clock_delete(entry_id: int):
    entry = TimeEntry.query.get_or_404(entry_id)
    if not current_user.is_admin and entry.user_id != current_user.id:
        flash("Voce nao pode excluir este ponto.", "danger")
        return redirect(url_for("main.time_clock"))

    entry_owner = entry.user.name
    entry_label_text = ENTRY_LABELS.get(entry.entry_type, entry.entry_type)
    db.session.delete(entry)
    db.session.commit()
    flash(f"Ponto removido: {entry_label_text} de {entry_owner}.", "warning")
    return redirect(request.referrer or url_for("main.time_clock"))


@bp.route("/usuarios", methods=["GET", "POST"])
@roles_required("admin")
def users_list():
    if request.method == "POST":
        name = request.form.get("name", "").strip()
        email = request.form.get("email", "").strip().lower()
        password = request.form.get("password", "")
        role = request.form.get("role", "employee")
        active = request.form.get("active") == "on"

        existing = User.query.filter(User.email == email).first()
        if existing:
            flash("Ja existe um usuario com este e-mail.", "danger")
        elif not all([name, email, password]):
            flash("Preencha nome, e-mail e senha.", "danger")
        else:
            user = User(name=name, email=email, role=role, active=active)
            user.set_password(password)
            db.session.add(user)
            db.session.commit()
            flash("Usuario criado com sucesso.", "success")
            return redirect(url_for("main.users_list"))

    users = User.query.order_by(User.role.asc(), User.name.asc()).all()
    return render_template("users/list.html", users=users)


@bp.app_template_filter("money")
def money_filter(value):
    from app.utils import currency_br

    return currency_br(value)


@bp.app_template_filter("date_br")
def date_filter(value):
    from app.utils import date_br

    return date_br(value)


@bp.app_template_filter("datetime_br")
def datetime_filter(value):
    from app.utils import datetime_br

    return datetime_br(value)


@bp.app_template_global()
def badge_for(status: str) -> str:
    return STATUS_BADGES.get(status, "secondary")


@bp.app_template_global()
def whatsapp_url(phone: str, message: str = "") -> str:
    return whatsapp_link(phone, message)


@bp.app_template_global()
def display_client_status(client: Client) -> str:
    if client.status == "cancelado":
        return "cancelado"
    if client.status == "inativo":
        return "inativo"
    state = client_due_state(client.due_date)
    return "atrasado" if state == "atrasado" else client.status


@bp.app_template_global()
def renewal_message_for(client: Client, charge: Charge | None = None) -> str:
    return renewal_message(client, charge)


@bp.app_template_global()
def due_badge_for(due_date):
    return due_badge(due_date)


@bp.app_template_global()
def due_label_for(due_date):
    return due_label(due_date)


@bp.app_template_global()
def entry_label(entry_type: str) -> str:
    return ENTRY_LABELS.get(entry_type, entry_type.replace("_", " ").title())


@bp.app_template_global()
def endpoint_args_without_page(**extra):
    args = request.args.to_dict(flat=True)
    args.pop("page", None)
    args.update({key: value for key, value in extra.items() if value not in (None, "")})
    return args



def update_support_entry(attendance: Attendance) -> bool:
    contact_name = request.form.get("contact_name", "").strip()
    contact_phone = normalize_phone(request.form.get("contact_phone", ""))
    platform = request.form.get("platform", "").strip() or None
    issue_type = request.form.get("issue_type", "").strip() or None
    device_type = request.form.get("device_type", "").strip() or None
    service_status = request.form.get("service_status", "aberto").strip().lower() or "aberto"
    priority = request.form.get("priority", "normal").strip().lower() or "normal"
    title = request.form.get("title", "").strip()
    description = request.form.get("description", "").strip()
    attended_at = request.form.get("attended_at", "")
    follow_up = request.form.get("next_follow_up", "")
    attendance_dt = parse_datetime_local(attended_at) or attendance.attended_at or local_now()

    if not contact_name or not title or not description:
        flash("Informe nome, titulo e descricao do atendimento.", "danger")
        return False

    attendance.contact_name = contact_name
    attendance.contact_phone = contact_phone or None
    attendance.platform = platform if platform in SUPPORT_PLATFORMS else (platform or None)
    attendance.issue_type = issue_type if issue_type in SUPPORT_ISSUES else (issue_type or None)
    attendance.device_type = device_type if device_type in SUPPORT_DEVICES else (device_type or None)
    attendance.service_status = service_status if service_status in SUPPORT_STATUSES else "aberto"
    attendance.priority = priority if priority in SUPPORT_PRIORITIES else "normal"
    attendance.title = title
    attendance.description = description
    attendance.attended_at = attendance_dt
    attendance.next_follow_up = parse_date(follow_up)
    db.session.commit()
    return True



def create_support_entry(client: Client | None = None) -> None:
    attendance = Attendance(
        client_id=client.id if client else None,
        user_id=current_user.id,
        contact_name="-",
        title="-",
        description="-",
        attended_at=local_now(),
    )
    db.session.add(attendance)
    db.session.flush()
    if not update_support_entry(attendance):
        db.session.rollback()
        return
    flash("Atendimento de suporte salvo com sucesso.", "success")



def mark_charge_paid(charge: Charge, source: str = "manual") -> None:
    charge.status = "pago"
    charge.mp_status = "approved" if source == "mercado_pago" else charge.mp_status or "manual"
    charge.paid_at = local_now()
    if charge.client.due_date <= charge.due_date:
        charge.client.due_date = add_one_month(charge.due_date)
    db.session.commit()


# ─── Chat interno ────────────────────────────────────────────────────────────

@bp.route("/chat")
@login_required
def chat_hub():
    tab = request.args.get("aba", "geral").strip() or "geral"
    private_id = request.args.get("com", "").strip()

    employees = User.query.filter(User.id != current_user.id).order_by(User.name.asc()).all()

    general_messages = (
        TeamMessage.query.filter(TeamMessage.support_id.is_(None))
        .order_by(TeamMessage.created_at.asc())
        .limit(160)
        .all()
    )

    selected_employee = None
    if private_id.isdigit():
        selected_employee = db.session.get(User, int(private_id))
        if selected_employee and selected_employee.id == current_user.id:
            selected_employee = None
    if selected_employee is None and employees:
        selected_employee = employees[0]

    private_messages = []
    if selected_employee:
        from sqlalchemy import and_
        private_messages = DirectMessage.query.filter(
            db.or_(
                and_(
                    DirectMessage.sender_user_id == current_user.id,
                    DirectMessage.recipient_user_id == selected_employee.id,
                ),
                and_(
                    DirectMessage.sender_user_id == selected_employee.id,
                    DirectMessage.recipient_user_id == current_user.id,
                ),
            )
        ).order_by(DirectMessage.created_at.asc()).all()

    private_threads = _build_private_threads()

    return render_template(
        "chat/index.html",
        tab=tab,
        general_messages=general_messages,
        employees=employees,
        selected_employee=selected_employee,
        private_messages=private_messages,
        private_threads=private_threads,
    )


@bp.route("/chat/enviar", methods=["POST"])
@login_required
def send_team_message():
    body = request.form.get("body", "").strip()
    if not body:
        flash("Digite uma mensagem.", "warning")
        return redirect(url_for("main.chat_hub"))

    message = TeamMessage(
        author_user_id=current_user.id,
        support_id=None,
        body=body,
    )
    db.session.add(message)
    db.session.commit()
    flash("Mensagem enviada.", "success")
    return redirect(url_for("main.chat_hub"))


@bp.route("/chat/mensagem/<int:message_id>/remover", methods=["POST"])
@login_required
def delete_team_message(message_id: int):
    message = TeamMessage.query.get_or_404(message_id)
    if not current_user.is_admin and message.author_user_id != current_user.id:
        flash("Voce nao pode remover esta mensagem.", "warning")
        return redirect(url_for("main.chat_hub"))
    db.session.delete(message)
    db.session.commit()
    flash("Mensagem removida.", "success")
    return redirect(url_for("main.chat_hub"))


@bp.route("/chat/privado/enviar", methods=["POST"])
@login_required
def send_direct_message():
    recipient_id = request.form.get("recipient_id", "").strip()
    body = request.form.get("body", "").strip()

    if not recipient_id.isdigit():
        flash("Selecione um funcionario.", "warning")
        return redirect(url_for("main.chat_hub", aba="privado"))

    recipient = db.session.get(User, int(recipient_id))
    if recipient is None or recipient.id == current_user.id:
        flash("Funcionario invalido.", "warning")
        return redirect(url_for("main.chat_hub", aba="privado"))

    if not body:
        flash("Digite uma mensagem.", "warning")
        return redirect(url_for("main.chat_hub", aba="privado", com=recipient.id))

    message = DirectMessage(
        sender_user_id=current_user.id,
        recipient_user_id=recipient.id,
        body=body,
    )
    db.session.add(message)
    db.session.commit()
    flash("Mensagem privada enviada.", "success")
    return redirect(url_for("main.chat_hub", aba="privado", com=recipient.id))


@bp.route("/chat/privado/<int:message_id>/remover", methods=["POST"])
@login_required
def delete_direct_message(message_id: int):
    message = DirectMessage.query.get_or_404(message_id)
    if not current_user.is_admin and current_user.id not in {message.sender_user_id, message.recipient_user_id}:
        flash("Voce nao pode remover esta mensagem.", "warning")
        return redirect(url_for("main.chat_hub", aba="privado"))
    other_id = message.recipient_user_id if message.sender_user_id == current_user.id else message.sender_user_id
    db.session.delete(message)
    db.session.commit()
    flash("Mensagem privada removida.", "success")
    return redirect(url_for("main.chat_hub", aba="privado", com=other_id))


# ─── Perfil ──────────────────────────────────────────────────────────────────

@bp.route("/perfil", methods=["GET", "POST"])
@login_required
def profile():
    if request.method == "POST":
        name = request.form.get("name", "").strip()
        password = request.form.get("password", "").strip()
        password_confirm = request.form.get("password_confirm", "").strip()

        if name:
            current_user.name = name

        if password:
            if len(password) < 6:
                flash("A senha precisa ter pelo menos 6 caracteres.", "warning")
                return render_template("profile.html")
            if password != password_confirm:
                flash("As senhas nao conferem.", "warning")
                return render_template("profile.html")
            current_user.set_password(password)

        db.session.commit()
        flash("Perfil atualizado.", "success")
        return redirect(url_for("main.profile"))

    return render_template("profile.html")


# ─── Handlers de erro ────────────────────────────────────────────────────────

@bp.app_errorhandler(404)
def not_found(_error):
    return render_template("404.html"), 404


@bp.app_errorhandler(500)
def internal_error(_error):
    db.session.rollback()
    return render_template("500.html"), 500


# ─── Helper privado para chat ────────────────────────────────────────────────

def _build_private_threads():
    from sqlalchemy import and_
    employees = User.query.filter(User.id != current_user.id).order_by(User.name.asc()).all()
    if not employees:
        return []
    #Uma query para todas as mensagens diretas do usuario atual
    all_messages = (
        DirectMessage.query.filter(
            db.or_(
                DirectMessage.sender_user_id == current_user.id,
                DirectMessage.recipient_user_id == current_user.id,
            )
        )
        .order_by(DirectMessage.created_at.desc())
        .all()
    )
    #Agrupar por parceiro de conversa
    threads_map: dict[int, dict] = {}
    for msg in all_messages:
        partner_id = msg.recipient_user_id if msg.sender_user_id == current_user.id else msg.sender_user_id
        if partner_id not in threads_map:
            threads_map[partner_id] = {"last_message": msg, "count": 0}
        threads_map[partner_id]["count"] += 1

    threads = []
    for user in employees:
        info = threads_map.get(user.id, {"last_message": None, "count": 0})
        threads.append({"user": user, "last_message": info["last_message"], "count": info["count"]})
    threads.sort(
        key=lambda item: (
            -(item["last_message"].created_at.timestamp()) if item["last_message"] else float("-inf"),
        )
    )
    return threads


# ─── Ações de andamento do suporte ──────────────────────────────────────────

@bp.route("/suporte/<int:attendance_id>/acao", methods=["POST"])
@login_required
def support_action(attendance_id: int):
    attendance = Attendance.query.get_or_404(attendance_id)
    if not current_user.is_admin and attendance.user_id != current_user.id:
        flash("Voce nao pode alterar este atendimento.", "danger")
        return redirect(url_for("main.support_center"))

    action = request.form.get("action", "").strip()
    assigned_to_id = request.form.get("assigned_to_id", "").strip()
    target = request.form.get("forwarded_to", "").strip()

    if assigned_to_id.isdigit():
        attendance.assigned_to_id = int(assigned_to_id)

    if action == "iniciado":
        attendance.service_status = "em andamento"
        if not attendance.started_at:
            attendance.started_at = local_now()
        flash("Atendimento marcado como iniciado.", "success")
    elif action == "finalizado":
        attendance.service_status = "resolvido"
        if not attendance.started_at:
            attendance.started_at = local_now()
        attendance.finished_at = local_now()
        flash("Atendimento finalizado.", "success")
    elif action == "encaminhado":
        if not target:
            flash("Informe o destino do encaminhamento.", "warning")
            return redirect(request.referrer or url_for("main.support_center"))
        attendance.forwarded_to = target
        attendance.service_status = "em andamento"
        flash(f"Atendimento encaminhado para {target}.", "success")
    else:
        flash("Acao invalida.", "danger")
        return redirect(request.referrer or url_for("main.support_center"))

    db.session.commit()
    return redirect(request.referrer or url_for("main.support_center"))


# ─── Chat interno por suporte ────────────────────────────────────────────────

@bp.route("/suporte/<int:attendance_id>/mensagem", methods=["POST"])
@login_required
def support_send_message(attendance_id: int):
    attendance = Attendance.query.get_or_404(attendance_id)
    body = request.form.get("body", "").strip()
    if not body:
        flash("Digite uma mensagem.", "warning")
        return redirect(request.referrer or url_for("main.support_center"))
    message = TeamMessage(
        author_user_id=current_user.id,
        support_id=attendance.id,
        body=body,
    )
    db.session.add(message)
    db.session.commit()
    flash("Mensagem registrada.", "success")
    return redirect(request.referrer or url_for("main.support_center"))


# ─── Registrar aviso ao cliente ──────────────────────────────────────────────

@bp.route("/clientes/<int:client_id>/aviso", methods=["POST"])
@login_required
def register_client_notice(client_id: int):
    client = Client.query.get_or_404(client_id)
    client.last_notice_at = local_now()
    db.session.commit()
    flash(f"Aviso registrado para {client.name}.", "success")
    return redirect(request.referrer or url_for("main.client_detail", client_id=client.id))


# ─── Sincronizar cobrança com Mercado Pago ───────────────────────────────────

@bp.route("/cobrancas/<int:charge_id>/sincronizar", methods=["POST"])
@login_required
def charges_sync(charge_id: int):
    from app.services.mercadopago import MercadoPagoError, get_payment
    charge = Charge.query.get_or_404(charge_id)
    if not charge.mercado_pago_payment_id:
        flash("Esta cobranca ainda nao tem pagamento registrado no Mercado Pago.", "warning")
        return redirect(request.referrer or url_for("main.charges_list"))
    try:
        payment = get_payment(charge.mercado_pago_payment_id)
        charge.mp_status = payment.get("status")
        charge.last_notification_at = local_now()
        if payment.get("status") == "approved":
            mark_charge_paid(charge, source="mercado_pago")
            flash("Cobranca sincronizada — pagamento aprovado.", "success")
        else:
            db.session.commit()
            flash(f"Cobranca sincronizada — status: {payment.get('status')}.", "info")
    except MercadoPagoError as exc:
        flash(str(exc), "danger")
    return redirect(request.referrer or url_for("main.charges_list"))


# ─── Marcar cobrança como enviada ────────────────────────────────────────────

@bp.route("/cobrancas/<int:charge_id>/enviado", methods=["POST"])
@login_required
def charges_mark_sent(charge_id: int):
    charge = Charge.query.get_or_404(charge_id)
    charge.status = "enviado"
    charge.sent_at = local_now()
    db.session.commit()
    flash("Cobranca marcada como enviada.", "success")
    return redirect(request.referrer or url_for("main.charges_list"))
