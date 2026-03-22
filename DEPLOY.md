# Deploy no servidor Linux + MariaDB + Passenger

## 1. Dependências do sistema (rodar como root ou com sudo)

```bash
pip3 install -r requirements.txt --break-system-packages
```

## 2. Banco de dados

Crie o banco e o usuario no MariaDB:

```sql
CREATE DATABASE painel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'painel_user'@'localhost' IDENTIFIED BY 'senha_forte';
GRANT ALL PRIVILEGES ON painel.* TO 'painel_user'@'localhost';
FLUSH PRIVILEGES;
```

## 3. Configurar o .env

```bash
cp .env.example .env
nano .env
```

Preencha no minimo:
- SECRET_KEY  (gere com: python3 -c "import secrets; print(secrets.token_hex(32))")
- DATABASE_URL
- APP_BASE_URL
- COMPANY_NAME
- COMPANY_WHATSAPP
- ADMIN_EMAIL
- ADMIN_PASSWORD

## 4. Criar tabelas e admin

```bash
python3 scripts/init_db.py
```

## 5. Passenger (cPanel/WHM)

O arquivo passenger_wsgi.py ja esta configurado.
Aponte o Document Root da aplicacao para esta pasta.
O Passenger detecta automaticamente o passenger_wsgi.py.

## 6. Login inicial

Use o email e senha definidos em ADMIN_EMAIL e ADMIN_PASSWORD no .env.
